<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Maintenance;
use App\Models\Setting;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ItemsExport;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class ItemController extends Controller
{
    private function getFilteredData($type)
    {
        $query = Item::query();
        $pageTitle = "جميع المواد";

        if ($type == 'low_stock') {
            // منخفضة وليست نافدة (النافدة تظهر في "المواد الناقصة")
            $query->where('current_stock', '>', 0)->where('current_stock', '<=', Setting::lowStockThreshold());
            $pageTitle = "المواد منخفضة المخزون";
        } elseif ($type == 'damaged') {
            // المواد التي لديها سجل صيانة غير مكتمل (قيد الانتظار أو الإصلاح أو الإتلاف)
            $query->whereIn('id', Maintenance::where('status', '!=', 'fixed')->select('item_id'));
            $pageTitle = "المواد التالفة";
        } elseif ($type == 'missing') {
            $query->where('current_stock', 0); // المواد الناقصة هي التي رصيدها صفر
            $pageTitle = "المواد الناقصة";
        } elseif ($type == 'most_used') {
            // الترتيب من الأكثر استخداماً للأقل (الفرق بين الرصيد الابتدائي والحالي)
            $query->orderByRaw('(initial_balance - current_stock) DESC');
            $pageTitle = "أكثر المواد استخداماً";
        }

        return ['items' => $query->get(), 'pageTitle' => $pageTitle];
    }

    public function index(Request $request)
    {
        $currentType = $request->type ?? 'all';
        $data = $this->getFilteredData($currentType);
        
        $items = $data['items'];
        $pageTitle = $data['pageTitle'];

        return view('items.index', compact('items', 'pageTitle', 'currentType'));
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getFilteredData($request->type);
        return Excel::download(new ItemsExport($data['items']), 'inventory_report.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $data = $this->getFilteredData($request->type);
        $items = $data['items'];
        $pageTitle = $data['pageTitle'];

        $html = view('items.pdf', compact('items', 'pageTitle'))->render();

        // mPDF يدعم ربط الحروف العربية والاتجاه من اليمين لليسار (على عكس DomPDF)
        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetTitle($pageTitle);
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="inventory_report.pdf"',
        ]);
    }
}