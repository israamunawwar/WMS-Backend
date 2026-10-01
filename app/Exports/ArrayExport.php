<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** تصدير عام لأي جدول (عناوين + صفوف) إلى Excel. */
class ArrayExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(private array $headings, private array $rows)
    {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
