<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TestExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['MT-001', 'Material', 'Test Material 1', 'Spesifikasi test', 'unit', 100000, 'Aktif'],
            ['MT-002', 'Material', 'Test Material 2', 'Spesifikasi test 2', 'unit', 200000, 'Aktif'],
        ];
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
}