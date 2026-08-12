<?php

namespace App\Exports;

use App\Models\Pelanggan;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class PelangganExport extends StringValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function query()
    {
        return Pelanggan::query()->where('user_id', Auth::id());
    }

    public function headings(): array
    {
        return [
            'nik',
            'status'
        ];
    }

    public function map($pelanggan): array
    {
        return [
            $pelanggan->nik,
            $pelanggan->status ? 'aktif' : 'nonaktif',
        ];
    }
}
