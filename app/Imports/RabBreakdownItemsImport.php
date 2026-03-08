<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Validation\Rule;

class RabBreakdownItemsImport implements ToArray, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;
    
    protected $rabBreakdownId;
    protected $existingItems = [];
    
    public function __construct($rabBreakdownId)
    {
        $this->rabBreakdownId = $rabBreakdownId;
        $this->loadExistingItems();
    }
    
    public function array(array $array)
    {
        return $array;
    }
    
    public function rules(): array
    {
        return [
            'item_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z]\.\d+$/'
            ],
            'uraian' => 'required|string|max:500',
            'volume_rab' => 'required|numeric|min:0.01',
            'satuan' => 'required|string|max:20',
            'unit_price' => 'required|numeric|min:0',
            'master_kode' => 'nullable|string|max:50|regex:/^[A-Z]{2}-\d+$/',
            'p' => 'nullable|numeric|min:0',
            'l' => 'nullable|numeric|min:0',
            't' => 'nullable|numeric|min:0',
            'n' => 'nullable|numeric|min:0',
            'n_tul_1' => 'nullable|numeric|min:0',
            'n_tul_2' => 'nullable|numeric|min:0',
            'jarak' => 'nullable|numeric|min:0',
            'dia_1' => 'nullable|numeric|min:0',
            'dia_2' => 'nullable|numeric|min:0',
            'dia_3' => 'nullable|numeric|min:0',
            'berat_1' => 'nullable|numeric|min:0',
            'berat_2' => 'nullable|numeric|min:0',
            'm2_peng' => 'nullable|numeric|min:0',
            'qty' => 'nullable|numeric|min:0',
            'qty_beli' => 'nullable|numeric|min:0',
            'jumlah' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }
    
    public function customValidationMessages()
    {
        return [
            'item_code.required' => 'Kolom item_code harus diisi',
            'item_code.regex' => 'Format item_code tidak valid. Gunakan format: A.1, B.1, dll',
            'uraian.required' => 'Kolom uraian harus diisi',
            'volume_rab.required' => 'Kolom volume_rab harus diisi',
            'volume_rab.numeric' => 'Volume harus angka',
            'volume_rab.min' => 'Volume minimal 0.01',
            'satuan.required' => 'Kolom satuan harus diisi',
            'unit_price.required' => 'Kolom unit_price harus diisi',
            'unit_price.numeric' => 'Harga satuan harus angka',
            'unit_price.min' => 'Harga satuan minimal 0',
            'master_kode.regex' => 'Format master_kode tidak valid. Gunakan format: JS-001, MT-133, dll',
            'p.numeric' => 'Kolom p harus angka',
            'l.numeric' => 'Kolom l harus angka',
            't.numeric' => 'Kolom t harus angka',
            'n.numeric' => 'Kolom n harus angka',
            'n_tul_1.numeric' => 'Kolom n_tul_1 harus angka',
            'n_tul_2.numeric' => 'Kolom n_tul_2 harus angka',
            'jarak.numeric' => 'Kolom jarak harus angka',
            'dia_1.numeric' => 'Kolom dia_1 harus angka',
            'dia_2.numeric' => 'Kolom dia_2 harus angka',
            'dia_3.numeric' => 'Kolom dia_3 harus angka',
            'berat_1.numeric' => 'Kolom berat_1 harus angka',
            'berat_2.numeric' => 'Kolom berat_2 harus angka',
            'm2_peng.numeric' => 'Kolom m2_peng harus angka',
            'qty.numeric' => 'Kolom qty harus angka',
            'qty_beli.numeric' => 'Kolom qty_beli harus angka',
            'jumlah.numeric' => 'Kolom jumlah harus angka',
        ];
    }
    
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($validator->getData() as $key => $data) {
                // Check for duplicate item_code in import data
                $itemCodes = array_column($validator->getData(), 'item_code');
                if (count(array_keys($itemCodes, $data['item_code'])) > 1) {
                    $validator->errors()->add(
                        "row_{$key}.item_code",
                        "Duplicate item_code found: {$data['item_code']}"
                    );
                }
                
                // Check if item_code already exists in database
                if (isset($this->existingItems[$data['item_code']])) {
                    $validator->errors()->add(
                        "row_{$key}.item_code",
                        "Item_code '{$data['item_code']}' sudah ada di database"
                    );
                }
                
                // Validate master_kode exists in Master Data if provided
                if (!empty($data['master_kode'])) {
                    $masterExists = \DB::table('master_data')
                        ->where('kode', $data['master_kode'])
                        ->exists();
                        
                    if (!$masterExists) {
                        $validator->errors()->add(
                            "row_{$key}.master_kode",
                            "Master kode '{$data['master_kode']}' tidak ditemukan di Master Data"
                        );
                    }
                }
            }
        });
    }
    
    protected function loadExistingItems()
    {
        $items = \DB::table('rab_breakdown_items')
            ->where('rab_breakdown_id', $this->rabBreakdownId)
            ->pluck('id', 'item_code')
            ->toArray();
            
        $this->existingItems = $items;
    }
    
    public function headingRow(): int
    {
        return 1;
    }
    
    public function prepareForValidation($data, $index)
    {
        // Clean and format data
        $data['item_code'] = trim($data['item_code'] ?? '');
        $data['uraian'] = trim($data['uraian'] ?? '');
        $data['satuan'] = trim($data['satuan'] ?? '');
        $data['master_kode'] = trim($data['master_kode'] ?? '');
        $data['notes'] = trim($data['notes'] ?? '');
        
        // Convert numeric values
        $data['volume_rab'] = $this->convertToNumeric($data['volume_rab'] ?? 0);
        $data['unit_price'] = $this->convertToNumeric($data['unit_price'] ?? 0);
        $data['p'] = $this->convertNullableNumeric($data['p'] ?? null);
        $data['l'] = $this->convertNullableNumeric($data['l'] ?? null);
        $data['t'] = $this->convertNullableNumeric($data['t'] ?? null);
        $data['n'] = $this->convertNullableNumeric($data['n'] ?? null);
        $data['n_tul_1'] = $this->convertNullableNumeric($data['n_tul_1'] ?? null);
        $data['n_tul_2'] = $this->convertNullableNumeric($data['n_tul_2'] ?? null);
        $data['jarak'] = $this->convertNullableNumeric($data['jarak'] ?? null);
        $data['dia_1'] = $this->convertNullableNumeric($data['dia_1'] ?? null);
        $data['dia_2'] = $this->convertNullableNumeric($data['dia_2'] ?? null);
        $data['dia_3'] = $this->convertNullableNumeric($data['dia_3'] ?? null);
        $data['berat_1'] = $this->convertNullableNumeric($data['berat_1'] ?? null);
        $data['berat_2'] = $this->convertNullableNumeric($data['berat_2'] ?? null);
        $data['m2_peng'] = $this->convertNullableNumeric($data['m2_peng'] ?? ($data['m2_peng_'] ?? null));
        $data['qty'] = $this->convertNullableNumeric($data['qty'] ?? null);
        $data['qty_beli'] = $this->convertNullableNumeric($data['qty_beli'] ?? ($data['qty_beli_'] ?? null));
        $data['jumlah'] = $this->convertNullableNumeric($data['jumlah'] ?? null);
        
        return $data;
    }
    
    protected function convertToNumeric($value)
    {
        if (is_numeric($value)) {
            return floatval($value);
        }
        
        // Handle comma as decimal separator
        $value = str_replace(',', '.', $value);
        
        // Remove non-numeric characters except dots
        $value = preg_replace('/[^0-9\.]/', '', $value);
        
        return floatval($value);
    }

    protected function convertNullableNumeric($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->convertToNumeric($value);
    }
}


