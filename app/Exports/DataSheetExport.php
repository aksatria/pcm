<?php
// app/Exports/DataSheetExport.php

namespace App\Exports;

use App\Models\Data;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;

class DataSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    use Exportable;
    
    protected $sheetName;
    protected $filters;

    public function __construct($sheetName, $filters = [])
    {
        $this->sheetName = $sheetName;
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Data::query();

        // Apply filters
        if (isset($this->filters['status']) && $this->filters['status'] !== '') {
            $statusValue = $this->filters['status'] == '1' ? true : false;
            $query->where('status', $statusValue);
        }

        if (isset($this->filters['search']) && !empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('uraian', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%")
                  ->orWhere('spesifikasi', 'like', "%{$search}%");
            });
        }

        if (isset($this->filters['kategori']) && !empty($this->filters['kategori'])) {
            $query->where('kode_kategori', $this->filters['kategori']);
        }

        return $query->orderBy('kode')->get();
    }

    public function headings(): array
    {
        return [
            'Kode',
            'Kategori',
            'Uraian',
            'Spesifikasi',
            'Satuan',
            'Harga',
            'Status',
            'Created By',
            'Updated By',
            'Created At',
            'Updated At'
        ];
    }

    public function map($data): array
    {
        return [
            $data->kode,
            $data->kategori,
            $data->uraian,
            $data->spesifikasi ?? '',
            $data->satuan,
            $data->harga,
            $data->status ? 'Aktif' : 'Nonaktif',
            $data->created_by ?? '',
            $data->updated_by ?? '',
            $data->created_at ? $data->created_at->format('Y-m-d H:i:s') : '',
            $data->updated_at ? $data->updated_at->format('Y-m-d H:i:s') : ''
        ];
    }

    public function title(): string
    {
        // Clean sheet name untuk menghindari karakter invalid
        return mb_substr(preg_replace('/[^\w\s\-]/', '', $this->sheetName), 0, 31);
    }

    public function styles(Worksheet $sheet)
    {
        $count = $this->collection()->count();
        
        return [
            // Style the first row as bold text
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2D3748']]
            ],
            // Set borders for all cells
            'A1:K' . ($count + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => 'thin',
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Kode
            'B' => 15, // Kategori
            'C' => 50, // Uraian
            'D' => 40, // Spesifikasi
            'E' => 12, // Satuan
            'F' => 15, // Harga
            'G' => 12, // Status
            'H' => 20, // Created By
            'I' => 20, // Updated By
            'J' => 20, // Created At
            'K' => 20, // Updated At
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $count = $this->collection()->count();
                if ($count > 0) {
                    $event->sheet->getStyle('F2:F' . ($count + 1))
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
            },
        ];
    }
}