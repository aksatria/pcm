<?php
namespace App\Exports;

use App\Models\Data;
use Illuminate\Support\Facades\Response;

class SimpleDataExport
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function download($filename = 'data-export.csv')
    {
        $data = $this->getData();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            // Header
            fputcsv($file, [
                'Kode', 'Kategori', 'Uraian', 'Spesifikasi', 
                'Satuan', 'Harga', 'Status', 'Created At'
            ]);

            // Data
            foreach ($data as $item) {
                fputcsv($file, [
                    $item->kode,
                    $item->kategori,
                    $item->uraian,
                    $item->spesifikasi ?? '',
                    $item->satuan,
                    number_format($item->harga, 2, '.', ''),
                    $item->status ? 'Aktif' : 'Nonaktif',
                    $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : ''
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function downloadAll($filename = 'data-all-categories.csv')
    {
        return $this->download($filename);
    }

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

        return $query->orderBy('kode_kategori')->orderBy('kode')->get();
    }
}