<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // المدرب يرى طلباته فقط، أما رئيس القسم وأمين المستودع فيريان الكل
        $base = Order::query();
        if (! $user->hasAnyRole(['super_admin', 'admin'])) {
            $base->where('user_id', $user->id);
        }

        $pendingCount = (clone $base)->awaitingDecision()->count();
        $approvedCount = (clone $base)->where('status', Order::APPROVED)->count();

        $query = (clone $base)->with(['user', 'items.item']);
        $pageTitle = 'جميع الطلبات';

        // الفلاتر القادمة من الداشبورد
        switch ($request->query('filter')) {
            case 'pending':
                $query->awaitingDecision();
                $pageTitle = 'طلبات بانتظار الاعتماد';
                break;
            case 'today':
                $query->whereDate('created_at', today());
                $pageTitle = 'طلبات اليوم';
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                $pageTitle = 'طلبات هذا الشهر';
                break;
            case 'rejected':
                $query->where('status', Order::REJECTED);
                $pageTitle = 'الطلبات المرفوضة';
                break;
        }

        // فلتر الحالة من القائمة المنسدلة
        $status = $request->query('status');
        if ($status === Order::PENDING) {
            $query->awaitingDecision();
        } elseif (in_array($status, [Order::APPROVED, Order::REJECTED], true)) {
            $query->where('status', $status);
        }

        // البحث برقم الطلب (يقبل ORD-12 أو #12 أو 12)
        if ($request->filled('search')) {
            $number = preg_replace('/\D/', '', $request->search);
            $query->where('id', $number === '' ? 0 : (int) $number);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        // المواد المتاحة للاختيار في نموذج الطلب الجديد
        $availableItems = Item::where('current_stock', '>', 0)
            ->orderBy('name_en')
            ->get(['id', 'name_ar', 'name_en', 'current_stock']);

        return view('orders.index', compact('orders', 'pageTitle', 'pendingCount', 'approvedCount', 'availableItems'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'destination' => 'required|string|max:255',
            'priority' => 'required|in:عادي,حساس',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|distinct|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1|max:100000',
        ]);

        $order = DB::transaction(function () use ($request, $data) {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'destination' => $data['destination'],
                'priority' => $data['priority'],
                'status' => Order::NEW,
            ]);

            $order->items()->createMany(collect($data['items'])->map(fn ($row) => [
                'item_id' => $row['item_id'],
                'quantity' => $row['quantity'],
            ])->all());

            return $order;
        });

        ActivityLog::record('create_order', "أنشأ الطلب #ORD-{$order->id} إلى \"{$order->destination}\" ويحتوي ".count($data['items']).' مادة');

        return back()->with('success', "تم إرسال الطلب #ORD-{$order->id} بنجاح.");
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'action' => 'required|string|in:موافقة,رفض',
            'notes' => 'required|string|max:1000',
        ]);

        $approved = $request->action === 'موافقة';

        // القفل يمنع قرارين متزامنين على نفس الطلب أو خصماً مزدوجاً من المخزون
        $error = DB::transaction(function () use ($order, $request, $approved) {
            $order = Order::lockForUpdate()->find($order->id);

            if (! $order->isAwaitingDecision()) {
                return 'تم البت في هذا الطلب مسبقاً ولا يمكن تغيير قراره.';
            }

            if ($approved) {
                $lines = $order->items()->get()->map(fn ($line) => [
                    'item' => Item::lockForUpdate()->find($line->item_id),
                    'quantity' => $line->quantity,
                ]);

                // نتحقق من كل المواد أولاً حتى لا يُخصم بعضها ويفشل الباقي
                foreach ($lines as $line) {
                    if ($line['item']->current_stock < $line['quantity']) {
                        $name = $line['item']->name_ar ?? $line['item']->name_en;

                        return "الكمية المطلوبة من «{$name}» ({$line['quantity']}) أكبر من المتوفر في المستودع ({$line['item']->current_stock}).";
                    }
                }

                foreach ($lines as $line) {
                    $line['item']->decrement('current_stock', $line['quantity']);
                }
            }

            $order->update([
                'status' => $approved ? Order::APPROVED : Order::REJECTED,
                'notes' => $request->notes,
            ]);

            return null;
        });

        if ($error) {
            return back()->with('error', $error);
        }

        ActivityLog::record($approved ? 'approve_order' : 'reject_order', "قام بـ{$request->action} على الطلب #ORD-{$order->id} مع ملاحظة: \"{$request->notes}\"");

        return back()->with('success', "تم {$request->action} الطلب بنجاح.".($approved ? ' وتم خصم المواد من المستودع.' : ''));
    }
}
