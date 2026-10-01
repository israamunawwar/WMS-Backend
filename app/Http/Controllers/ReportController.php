<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Models\InventorySession;
use App\Models\Item;
use App\Models\Maintenance;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\PdfRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public const TYPES = [
        'inventory' => 'تقرير المخزون الشامل',
        'orders' => 'تقرير الطلبات وحركة المواد',
        'inventory_sessions' => 'تقرير الجرد السنوي',
        'damaged' => 'تقرير المواد التالفة',
        'maintenance' => 'تقرير الصيانة',
        'custody' => 'تقرير العهدة (للمدربين)',
    ];

    /** عدد الصفوف المعروضة في المعاينة (التصدير يشمل كل الصفوف). */
    private const PREVIEW_ROWS = 50;

    public function index(Request $request)
    {
        $filters = $this->validated($request, typeRequired: false);

        $report = ! empty($filters['type'])
            ? $this->build($filters['type'], $filters['from'] ?? null, $filters['to'] ?? null)
            : null;

        return view('reports.index', [
            'types' => self::TYPES,
            'filters' => $filters,
            'report' => $report,
            'previewRows' => self::PREVIEW_ROWS,
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->validated($request, typeRequired: true);
        $request->validate(['format' => 'required|in:pdf,excel']);

        $report = $this->build($filters['type'], $filters['from'] ?? null, $filters['to'] ?? null);
        $name = 'report_'.$filters['type'].'_'.now()->format('Ymd');

        if ($request->format === 'excel') {
            return Excel::download(new ArrayExport($report['headings'], $report['rows']), $name.'.xlsx');
        }

        return PdfRenderer::download('reports.pdf', ['report' => $report], $name.'.pdf', $report['title']);
    }

    private function validated(Request $request, bool $typeRequired): array
    {
        return $request->validate([
            'type' => [$typeRequired ? 'required' : 'nullable', 'in:'.implode(',', array_keys(self::TYPES))],
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);
    }

    /** @return array{title: string, period: string, headings: array, rows: array} */
    private function build(string $type, ?string $from, ?string $to): array
    {
        [$headings, $rows] = match ($type) {
            'inventory' => $this->inventory(),
            'orders' => $this->orders($from, $to),
            'inventory_sessions' => $this->inventorySessions($from, $to),
            'damaged' => $this->damaged($from, $to),
            'maintenance' => $this->maintenance($from, $to),
            'custody' => $this->custody($from, $to),
        };

        $period = match (true) {
            $type === 'inventory' => 'الأرصدة الحالية',
            $from && $to => "من {$from} إلى {$to}",
            (bool) $from => "من {$from}",
            (bool) $to => "حتى {$to}",
            default => 'كل الفترات',
        };

        return ['title' => self::TYPES[$type], 'period' => $period, 'headings' => $headings, 'rows' => $rows];
    }

    private function inRange(Builder $query, ?string $from, ?string $to, string $column = 'created_at'): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate($column, '>=', $from))
            ->when($to, fn ($q) => $q->whereDate($column, '<=', $to));
    }

    private function itemName(?Item $item): string
    {
        return $item ? ($item->name_ar ?? $item->name_en) : 'مادة محذوفة';
    }

    private function inventory(): array
    {
        $rows = Item::with('category')->orderBy('id')->get()->map(fn ($item) => [
            $item->id,
            $item->barcode ?? '-',
            $item->name_ar ?? '-',
            $item->name_en,
            $item->category?->name ?? '-',
            $item->initial_balance,
            $item->current_stock,
        ])->all();

        return [['الرقم', 'الباركود', 'الاسم (عربي)', 'الاسم (إنكليزي)', 'الصنف', 'الرصيد الأساسي', 'الرصيد الحالي'], $rows];
    }

    private function orders(?string $from, ?string $to): array
    {
        $rows = $this->inRange(Order::with(['user', 'items.item']), $from, $to)->orderBy('id')->get()->map(fn ($order) => [
            'ORD-'.$order->id,
            $order->user?->name ?? '-',
            $order->destination ?? '-',
            $order->priority ?? '-',
            $order->status,
            $order->created_at->format('Y-m-d'),
            $order->items->map(fn ($line) => $this->itemName($line->item)." × {$line->quantity}")->implode('، ') ?: '-',
            $order->notes ?? '-',
        ])->all();

        return [['رقم الطلب', 'مقدم الطلب', 'الوجهة', 'الحساسية', 'الحالة', 'التاريخ', 'المواد', 'ملاحظات القرار'], $rows];
    }

    private function inventorySessions(?string $from, ?string $to): array
    {
        $labels = [
            InventorySession::OPEN => 'جارٍ',
            InventorySession::COMPLETED => 'بانتظار الاعتماد',
            InventorySession::APPROVED => 'معتمد',
        ];

        $rows = $this->inRange(
            InventorySession::with(['creator', 'approver'])
                ->withCount('inventorySessionItems as total_count')
                ->withCount(['inventorySessionItems as discrepancies_count' => fn ($q) => $q->where('difference', '!=', 0)]),
            $from, $to,
        )->orderBy('id')->get()->map(fn ($s) => [
            $s->title,
            $labels[$s->status] ?? $s->status,
            $s->creator?->name ?? '-',
            $s->approver?->name ?? '-',
            $s->total_count,
            $s->discrepancies_count,
            $s->created_at->format('Y-m-d'),
            $s->decision ?? '-',
        ])->all();

        return [['اسم الجلسة', 'الحالة', 'المسؤول', 'المعتمد', 'عدد المواد', 'عدد الفروقات', 'التاريخ', 'قرار رئيس القسم'], $rows];
    }

    private function damaged(?string $from, ?string $to): array
    {
        $rows = $this->inRange(Maintenance::unresolved()->with('item'), $from, $to)->orderBy('id')->get()->map(fn ($m) => [
            $this->itemName($m->item),
            $m->description,
            $m->status_label,
            $m->notes ?? '-',
            $m->created_at->format('Y-m-d'),
        ])->all();

        return [['المادة', 'وصف العطل', 'الحالة', 'ملاحظات', 'تاريخ التسجيل'], $rows];
    }

    private function maintenance(?string $from, ?string $to): array
    {
        $rows = $this->inRange(Maintenance::with(['item', 'reporter']), $from, $to)->orderBy('id')->get()->map(fn ($m) => [
            $this->itemName($m->item),
            $m->description,
            $m->status_label,
            $m->notes ?? '-',
            $m->cost ?? 0,
            $m->reporter?->name ?? '-',
            $m->created_at->format('Y-m-d'),
        ])->all();

        return [['المادة', 'وصف العطل', 'الحالة', 'ملاحظات', 'التكلفة', 'المبلّغ', 'التاريخ'], $rows];
    }

    /** العهدة: المواد التي استلمها كل مدرب بموجب طلبات تمت الموافقة عليها. */
    private function custody(?string $from, ?string $to): array
    {
        $lines = OrderItem::with(['order.user', 'item'])
            ->whereHas('order', fn ($q) => $this->inRange($q->where('status', Order::APPROVED), $from, $to))
            ->get()
            ->sortBy(fn ($line) => [$line->order->user?->name ?? '', $line->order_id])
            ->values();

        $rows = $lines->map(fn ($line) => [
            $line->order->user?->name ?? '-',
            'ORD-'.$line->order_id,
            $this->itemName($line->item),
            $line->quantity,
            $line->order->created_at->format('Y-m-d'),
        ])->all();

        return [['المدرب', 'رقم الطلب', 'المادة', 'الكمية', 'التاريخ'], $rows];
    }
}
