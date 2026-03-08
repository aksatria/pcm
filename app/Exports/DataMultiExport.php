<?php
// app/Exports/DataMultiExport.php

namespace App\Exports;

use App\Models\Data;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class DataMultiExport implements WithMultipleSheets
{
    use Exportable;
    
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $sheets = [];
        
        foreach (Data::KATEGORI as $kode => $nama) {
            $filters = $this->filters;
            $filters['kategori'] = $kode;
            
            $sheets[] = new DataSheetExport($nama, $filters);
        }

        return $sheets;
    }
}