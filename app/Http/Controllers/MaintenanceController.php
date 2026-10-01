<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\Maintenance;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    /** القرارات المتاحة والحالة التي تنتقل إليها، مع الحالات التي يجوز اتخاذها منها. */
    private const DECISIONS = [
        'repair' => ['to' => Maintenance::REPAIRING, 'from' => [Maintenance::PENDING], 'label' => 'إرسال للصيانة'],
        'replace' => ['to' => Maintenance::REPLACED, 'from' => [Maintenance::PENDING], 'label' => 'استبدال من المخزون'],
        'scrap' => ['to' => Maintenance::SCRAPPED, 'from' => [Maintenance::PENDING, Maintenance::REPAIRING], 'label' => 'إتلاف نهائي'],
        'fix' => ['to' => Maintenance::FIXED, 'from' => [Maintenance::REPAIRING], 'label' => 'تحديث: تم الإصلاح'],
    ];

    public function index(Request $request)
    {
        $filter = $request->query('filter') === 'pending' ? 'pending' : 'all';

        $query = Maintenance::with(['item', 'reporter'])->latest();
        if ($filter === 'pending') {
            $query->where('status', Maintenance::PENDING);
        }

        $maintenances = $query->paginate(10)->withQueryString();

        $damagedCount = Maintenance::unresolved()->distinct()->count('item_id');
        $repairingCount = Maintenance::where('status', Maintenance::REPAIRING)->count();
        $pendingCount = Maintenance::where('status', Maintenance::PENDING)->count();

        $items = Item::orderBy('name_en')->get(['id', 'name_ar', 'name_en']);

        return view('maintenance.index', compact(
            'maintenances', 'filter', 'damagedCount', 'repairingCount', 'pendingCount', 'items'
        ));
    }

    /** الإبلاغ عن عطل في مادة. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'description' => 'required|string|max:1000',
        ]);

        $maintenance = Maintenance::create([
            'item_id' => $data['item_id'],
            'reported_by' => $request->user()->id,
            'description' => $data['description'],
            'status' => Maintenance::PENDING,
        ]);

        $name = $maintenance->item->name_ar ?? $maintenance->item->name_en;
        ActivityLog::record('report_damage', "أبلغ عن عطل في المادة \"{$name}\": {$data['description']}");

        return back()->with('success', 'تم تسجيل العطل وإحالته للجنة التقنية.');
    }

    /** اتخاذ قرار بشأن مادة معطلة. */
    public function decide(Request $request, Maintenance $maintenance)
    {
        $data = $request->validate([
            'decision' => 'required|in:'.implode(',', array_keys(self::DECISIONS)),
            'notes' => 'nullable|string|max:1000|required_unless:decision,fix',
            'cost' => 'nullable|numeric|min:0|max:99999999',
        ]);

        $decision = self::DECISIONS[$data['decision']];

        if (! in_array($maintenance->status, $decision['from'], true)) {
            return back()->with('error', 'لا يمكن اتخاذ هذا القرار في الحالة الحالية للمادة.');
        }

        $maintenance->update(array_filter([
            'status' => $decision['to'],
            'notes' => $data['notes'] ?? null,
            'cost' => $data['decision'] === 'fix' ? ($data['cost'] ?? 0) : null,
        ], fn ($value) => $value !== null));

        $name = $maintenance->item->name_ar ?? $maintenance->item->name_en;
        ActivityLog::record('maintenance_'.$data['decision'], "{$decision['label']}: \"{$name}\"".(! empty($data['notes']) ? " — {$data['notes']}" : ''));

        return back()->with('success', 'تم تسجيل القرار بنجاح.');
    }
}
