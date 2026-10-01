<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\InventorySession;
use App\Models\InventorySessionItem;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $session = InventorySession::with(['creator', 'approver'])->active()->latest()->first();

        $rows = collect();
        $counted = 0;
        $discrepancies = collect();

        if ($session) {
            $rows = $session->inventorySessionItems()->with('item')->get();
            $counted = $rows->whereNotNull('actual_quantity')->count();
            $discrepancies = $rows->filter(fn ($r) => $r->actual_quantity !== null && $r->difference != 0);
        }

        $history = InventorySession::with(['creator', 'approver'])
            ->where('status', InventorySession::APPROVED)
            ->latest()
            ->paginate(10);

        return view('inventory.index', compact('session', 'rows', 'counted', 'discrepancies', 'history'));
    }

    /** بدء جلسة جرد جديدة وتجميد الأرصدة الحالية (لقطة من الأرصدة الدفترية). */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        if (InventorySession::active()->exists()) {
            return back()->with('error', 'يوجد جلسة جرد قائمة بالفعل، أغلقيها أو اعتمديها أولاً.');
        }

        if (! Item::exists()) {
            return back()->with('error', 'لا توجد مواد في المستودع لبدء الجرد.');
        }

        DB::transaction(function () use ($request) {
            $session = InventorySession::create([
                'title' => $request->name,
                'created_by' => $request->user()->id,
                'status' => InventorySession::OPEN,
            ]);

            $now = now();

            Item::query()->select('id', 'current_stock')->orderBy('id')->chunk(500, function ($items) use ($session, $now) {
                InventorySessionItem::insert($items->map(fn ($item) => [
                    'inventory_session_id' => $session->id,
                    'item_id' => $item->id,
                    'system_quantity' => $item->current_stock,
                    'actual_quantity' => null,
                    'difference' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });
        });

        ActivityLog::record('inventory_start', "بدأ جلسة الجرد \"{$request->name}\" وجمّد الأرصدة");

        return back()->with('success', 'تم إنشاء جلسة الجرد وتجميد الأرصدة بنجاح.');
    }

    /** تسجيل الكميات الفعلية المعدودة. */
    public function count(Request $request)
    {
        $request->validate([
            'counts' => 'required|array',
            'counts.*' => 'nullable|integer|min:0',
        ]);

        $session = InventorySession::where('status', InventorySession::OPEN)->latest()->first();

        if (! $session) {
            return back()->with('error', 'لا توجد جلسة جرد مفتوحة لتسجيل الكميات.');
        }

        $rows = $session->inventorySessionItems()
            ->whereIn('id', array_keys($request->counts))
            ->get();

        DB::transaction(function () use ($rows, $request) {
            foreach ($rows as $row) {
                $actual = $request->counts[$row->id] ?? null;

                $row->update([
                    'actual_quantity' => $actual,
                    'difference' => $actual === null ? 0 : $actual - $row->system_quantity,
                ]);
            }
        });

        return back()->with('success', 'تم حفظ الكميات المجرودة.');
    }

    /** تسوية الفروقات: تعديل أرصدة النظام لتطابق الجرد الفعلي. */
    public function resolve(Request $request)
    {
        $request->validate([
            'decision' => 'required|string',
        ]);

        $session = InventorySession::where('status', InventorySession::OPEN)->latest()->first();

        if (! $session) {
            return back()->with('error', 'لا توجد جلسة جرد مفتوحة للتسوية.');
        }

        if ($session->inventorySessionItems()->whereNull('actual_quantity')->exists()) {
            return back()->with('error', 'لا يمكن التسوية قبل جرد كل المواد.');
        }

        DB::transaction(function () use ($session, $request) {
            $session->inventorySessionItems()
                ->where('difference', '!=', 0)
                ->get()
                ->each(fn ($row) => Item::whereKey($row->item_id)->update(['current_stock' => $row->actual_quantity]));

            $session->update([
                'status' => InventorySession::COMPLETED,
                'decision' => $request->decision,
                'resolved_at' => now(),
            ]);
        });

        ActivityLog::record('inventory_resolve', "سوّى فروقات جلسة الجرد \"{$session->title}\" بقرار: {$request->decision}");

        return back()->with('success', 'تمت تسوية الفروقات وتعديل الأرصدة بنجاح.');
    }

    /** اعتماد النتيجة وإغلاق الجلسة وترحيل الأرصدة كرصيد افتتاحي للعام القادم. */
    public function close(Request $request)
    {
        $session = InventorySession::where('status', InventorySession::COMPLETED)->latest()->first();

        if (! $session) {
            return back()->with('error', 'يجب تسوية الفروقات أولاً قبل الاعتماد.');
        }

        DB::transaction(function () use ($session, $request) {
            Item::query()->update(['initial_balance' => DB::raw('current_stock')]);

            $session->update([
                'status' => InventorySession::APPROVED,
                'approved_by' => $request->user()->id,
            ]);
        });

        ActivityLog::record('inventory_close', "اعتمد نتيجة الجرد \"{$session->title}\" وأغلق الجلسة");

        return back()->with('success', 'تم اعتماد نتيجة الجرد وإغلاق الجلسة نهائياً بنجاح.');
    }
}
