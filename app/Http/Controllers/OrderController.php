<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Order;
use Illuminate\Http\Request;

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

        $query = (clone $base)->with('user');
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

        return view('orders.index', compact('orders', 'pageTitle', 'pendingCount', 'approvedCount'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'action' => 'required|string|in:موافقة,رفض',
            'notes' => 'required|string|max:1000',
        ]);

        if (! $order->isAwaitingDecision()) {
            return back()->with('error', 'تم البت في هذا الطلب مسبقاً ولا يمكن تغيير قراره.');
        }

        $approved = $request->action === 'موافقة';

        $order->update([
            'status' => $approved ? Order::APPROVED : Order::REJECTED,
            'notes' => $request->notes,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $approved ? 'approve_order' : 'reject_order',
            'description' => "قام بـ{$request->action} على الطلب #ORD-{$order->id} مع ملاحظة: \"{$request->notes}\"",
        ]);

        return back()->with('success', "تم {$request->action} الطلب بنجاح.");
    }
}
