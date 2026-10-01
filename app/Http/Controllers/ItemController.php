<?php

namespace App\Http\Controllers;

use App\Exports\ItemsExport;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\Setting;
use App\Support\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ItemController extends Controller
{
    /** @return array{query: \Illuminate\Database\Eloquent\Builder, pageTitle: string} */
    private function filteredQuery(Request $request): array
    {
        $type = $request->query('type');
        $query = Item::query();
        $pageTitle = 'جميع المواد';

        if ($type == 'low_stock') {
            // منخفضة وليست نافدة (النافدة تظهر في "المواد الناقصة")
            $query->where('current_stock', '>', 0)->where('current_stock', '<=', Setting::lowStockThreshold());
            $pageTitle = 'المواد منخفضة المخزون';
        } elseif ($type == 'damaged') {
            // المواد التي لديها سجل صيانة غير مكتمل (قيد الانتظار أو الإصلاح أو الإتلاف)
            $query->whereIn('id', Maintenance::where('status', '!=', 'fixed')->select('item_id'));
            $pageTitle = 'المواد التالفة';
        } elseif ($type == 'missing') {
            $query->where('current_stock', 0); // المواد الناقصة هي التي رصيدها صفر
            $pageTitle = 'المواد الناقصة';
        } elseif ($type == 'most_used') {
            // الترتيب من الأكثر استخداماً للأقل (الفرق بين الرصيد الابتدائي والحالي)
            $query->orderByRaw('(initial_balance - current_stock) DESC');
            $pageTitle = 'أكثر المواد استخداماً';
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(fn ($q) => $q
                ->where('name_en', 'like', "%{$search}%")
                ->orWhere('name_ar', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%"));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->query('category'));
        }

        return ['query' => $query, 'pageTitle' => $pageTitle];
    }

    public function index(Request $request)
    {
        ['query' => $query, 'pageTitle' => $pageTitle] = $this->filteredQuery($request);

        $items = $query->with(['category', 'location'])->orderBy('id')->paginate(20)->withQueryString();
        $currentType = $request->query('type', 'all');

        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('section')->orderBy('cabinet_number')->get();

        return view('items.index', compact('items', 'pageTitle', 'currentType', 'categories', 'locations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + [
            'initial_balance' => 'required|integer|min:0|max:10000000',
        ]);

        // الرصيد الافتتاحي هو الرصيد الحالي عند الإضافة
        $item = Item::create($data + ['current_stock' => $data['initial_balance']]);

        ActivityLog::record('item_create', "أضاف المادة \"{$this->label($item)}\" برصيد افتتاحي {$item->initial_balance}");

        return back()->with('success', 'تمت إضافة المادة بنجاح.');
    }

    /** تعديل بيانات المادة فقط. الأرصدة تتغير بالطلبات والجرد والتوريد حتى يبقى لها أثر تدقيق. */
    public function update(Request $request, Item $item)
    {
        $item->update($request->validate($this->rules($item)));

        ActivityLog::record('item_update', "عدّل بيانات المادة \"{$this->label($item)}\"");

        return back()->with('success', 'تم تحديث بيانات المادة بنجاح.');
    }

    /** توريد كمية جديدة (استلام بضاعة): يزيد الرصيد الحالي والأساسي معاً حتى لا يُحسب التوريد كاستهلاك سالب. */
    public function restock(Request $request, Item $item)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:10000000',
            'note' => 'nullable|string|max:500',
        ]);

        $item->increment('current_stock', $data['quantity']);
        $item->increment('initial_balance', $data['quantity']);

        ActivityLog::record('item_restock', "ورّد {$data['quantity']} وحدة إلى المادة \"{$this->label($item)}\"".(! empty($data['note']) ? " — {$data['note']}" : ''));

        return back()->with('success', "تم توريد {$data['quantity']} وحدة بنجاح.");
    }

    public function destroy(Item $item)
    {
        // نمنع الحذف إذا كان للمادة تاريخ (طلبات أو جرد أو صيانة) كي لا يضيع السجل
        if ($item->orderItems()->exists() || $item->inventorySessionItems()->exists() || $item->maintenances()->exists()) {
            return back()->with('error', 'لا يمكن حذف مادة لها طلبات أو جرد أو صيانة مسجلة، لأن ذلك يُضيع تاريخها.');
        }

        $item->delete();

        ActivityLog::record('item_delete', "حذف المادة \"{$this->label($item)}\"");

        return back()->with('success', 'تم حذف المادة.');
    }

    public function exportExcel(Request $request)
    {
        $items = $this->filteredQuery($request)['query']->orderBy('id')->get();

        return Excel::download(new ItemsExport($items), 'inventory_report.xlsx');
    }

    public function exportPdf(Request $request)
    {
        ['query' => $query, 'pageTitle' => $pageTitle] = $this->filteredQuery($request);
        $items = $query->orderBy('id')->get();

        return PdfRenderer::download('items.pdf', compact('items', 'pageTitle'), 'inventory_report.pdf', $pageTitle);
    }

    private function rules(?Item $item = null): array
    {
        return [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('items', 'barcode')->ignore($item?->id)],
            'category_id' => 'required|integer|exists:categories,id',
            'location_id' => 'nullable|integer|exists:locations,id',
        ];
    }

    private function label(Item $item): string
    {
        return $item->name_ar ?? $item->name_en;
    }
}
