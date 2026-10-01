<?php

namespace App\Support;

use Illuminate\Http\Response;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class PdfRenderer
{
    /**
     * يولّد ملف PDF من واجهة Blade ويرجعه كتنزيل.
     * نستخدم mPDF لأنه يدعم ربط الحروف العربية والاتجاه من اليمين لليسار (على عكس DomPDF).
     */
    public static function download(string $view, array $data, string $filename, string $title = ''): Response
    {
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

        if ($title !== '') {
            $mpdf->SetTitle($title);
        }

        $mpdf->WriteHTML(view($view, $data)->render());

        return response($mpdf->Output('', Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
