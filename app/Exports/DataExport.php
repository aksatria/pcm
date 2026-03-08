<?php
// app/Exports/DataExport.php

namespace App\Exports;

use App\Models\Data;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Facades\Log;

class DataExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    use Exportable;
    
    protected $filters;
    protected $data;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
        // Load data sekali saja di constructor
        $this->data = $this->getData();
    }

    public function collection()
    {
        Log::info('DataExport collection called', [
            'filters' => $this->filters,
            'count' => $this->data->count()
        ]);
        
        return $this->data;
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
            'Status'
        ];
    }

    public function map($data): array
    {
        return [
            $data->kode ?? '',
            $data->kategori ?? '',
            $data->uraian ?? '',
            $data->spesifikasi ?? '',
            $data->satuan ?? '',
            $data->harga ?? 0,
            $data->status ? 'Aktif' : 'Nonaktif'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $count = $this->data->count();
        
        return [
            // Style the first row as bold text
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2D3748']]
            ],
            // Set borders for all cells
            'A1:G' . ($count + 1) => [
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
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $count = $this->data->count();
                
                if ($count > 0) {
                    $event->sheet->getStyle('F2:F' . ($count + 1))
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
            },
        ];
    }

    /**
     * Get data dengan filter
     */
    protected function getData()
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

        $results = $query->orderBy('kode_kategori')->orderBy('kode')->get();
        
        Log::info('DataExport data loaded', ['count' => $results->count()]);
        
        return $results;
    }
}