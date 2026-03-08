<?php
// app/Imports/DataImport.php

namespace App\Imports;

use App\Models\Data;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DataImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    private $importedCount = 0;
    private $skippedCount = 0;
    private $errors = [];
    private $tableColumns = [];
    private $kodeCache = [];
    private $lastNumbers = []; // SIMPAN NOMOR TERAKHIR UNTUK SETIAP KATEGORI

    public function __construct()
    {
        // Get actual table columns
        $this->tableColumns = Schema::getColumnListing('data');
        // Initialize kode cache dan last numbers
        foreach (Data::KATEGORI as $kode => $nama) {
            $this->kodeCache[$kode] = [];
            $this->lastNumbers[$kode] = $this->getLastNumber($kode);
        }
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Skip header row atau baris kosong
            if (empty($row['kategori']) || empty($row['uraian_item']) || 
                $row['kategori'] === 'KATEGORI' || $row['uraian_item'] === 'URAIAN_ITEM' ||
                $row['kategori'] === 'CATATAN PENTING:') {
                $this->skippedCount++;
                return null;
            }

            // Validasi kode kategori
            $kodeKategori = strtoupper(trim($row['kategori']));
            if (!array_key_exists($kodeKategori, Data::KATEGORI)) {
                $this->skippedCount++;
                $this->errors[] = "Baris " . ($this->importedCount + $this->skippedCount + 1) . ": Kategori '{$kodeKategori}' tidak valid. Gunakan: MT, JS, AT, HO, SR, SB";
                return null;
            }

            // Cek duplikasi berdasarkan uraian dan kategori
            $existing = Data::where('uraian', $row['uraian_item'])
                ->where('kode_kategori', $kodeKategori)
                ->first();

            if ($existing) {
                $this->skippedCount++;
                $this->errors[] = "Baris " . ($this->importedCount + $this->skippedCount + 1) . ": Duplikat - Uraian '" . $row['uraian_item'] . "' sudah ada untuk kategori " . $kodeKategori;
                return null;
            }

            // Validasi satuan - DIPERBAIKI dengan normalisasi
            $satuan = $this->normalizeSatuan($row['satuan'] ?? '');
            if (!in_array($satuan, Data::SATUAN)) {
                $this->skippedCount++;
                $this->errors[] = "Baris " . ($this->importedCount + $this->skippedCount + 1) . ": Satuan '" . ($row['satuan'] ?? '') . "' tidak valid. Satuan valid: " . implode(', ', array_slice(Data::SATUAN, 0, 10)) . "...";
                return null;
            }

            // Validasi harga
            $harga = $this->parseHarga($row['harga_satuan']);
            if ($harga === false || $harga < 0 || $harga > 999999999999.99) {
                $this->skippedCount++;
                $this->errors[] = "Baris " . ($this->importedCount + $this->skippedCount + 1) . ": Harga '" . ($row['harga_satuan'] ?? '') . "' tidak valid";
                return null;
            }

            // Validasi status
            $status = $this->parseStatus($row['status'] ?? 'AKTIF');

            // GENERATE KODE OTOMATIS - FIXED VERSION
            $kode = $this->generateKodeForImport($kodeKategori);
            
            $kategoriNama = Data::KATEGORI[$kodeKategori];

            // Build data array based on actual table columns
            $dataArray = [
                'kode' => $kode,
                'kode_kategori' => $kodeKategori,
                'kategori' => $kategoriNama,
                'uraian' => $row['uraian_item'],
                'satuan' => $satuan,
                'harga' => $harga,
                'status' => $status,
                'created_by' => auth()->check() ? auth()->user()->name : 'import',
                'updated_by' => auth()->check() ? auth()->user()->name : 'import',
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Only add spesifikasi if column exists and not empty
            if (in_array('spesifikasi', $this->tableColumns) && isset($row['spesifikasi']) && !empty($row['spesifikasi'])) {
                $dataArray['spesifikasi'] = $row['spesifikasi'];
            }

            $data = new Data($dataArray);

            $this->importedCount++;
            Log::info('Import success - Kode: ' . $kode . ', Item: ' . $row['uraian_item']);
            return $data;

        } catch (\Exception $e) {
            $this->skippedCount++;
            $this->errors[] = "Baris " . ($this->importedCount + $this->skippedCount + 1) . ": " . $e->getMessage();
            Log::error('Import error: ' . $e->getMessage());
            Log::error('Row data: ' . json_encode($row));
            return null;
        }
    }

    /**
     * Get last number from database untuk kategori tertentu
     */
    private function getLastNumber($kodeKategori)
    {
        $maxNumber = Data::where('kode_kategori', $kodeKategori)
            ->selectRaw('MAX(CAST(SUBSTR(kode, 4) AS INTEGER)) as max_num')
            ->value('max_num') ?? 0;
        
        return $maxNumber;
    }

    /**
     * Generate kode untuk import dengan increment yang benar - FIXED VERSION
     */
    private function generateKodeForImport($kodeKategori)
    {
        // Increment nomor untuk kategori ini
        $this->lastNumbers[$kodeKategori]++;
        $nextNumber = $this->lastNumbers[$kodeKategori];
        
        // Generate kode
        $kode = $kodeKategori . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Double check kode belum ada (safety measure)
        $existing = Data::where('kode', $kode)->exists();
        if ($existing) {
            // Jika kode sudah ada, cari nomor berikutnya yang available
            $maxNumber = $this->getLastNumber($kodeKategori);
            $this->lastNumbers[$kodeKategori] = $maxNumber + 1;
            $nextNumber = $this->lastNumbers[$kodeKategori];
            $kode = $kodeKategori . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        return $kode;
    }

    /**
     * Normalize satuan untuk case sensitivity
     */
    private function normalizeSatuan($satuan)
    {
        $satuan = trim($satuan);
        $lowerSatuan = strtolower($satuan);
        
        // Mapping untuk satuan yang umum
        $mapping = [
            'set/bulan' => 'Set/bulan',
            'm\'' => 'm\'',
            'm1' => 'm1', 
            '5kg/piel' => '5kg/piel',
            '50/pack' => '50/pack',
            '6m/ljr' => '6m/ljr',
            '2.5l/pack' => '2.5l/pack',
            'oh' => 'OH',
            'unit' => 'unit',
            'jam' => 'jam',
            'm2' => 'm2',
            'm3' => 'm3',
            'kg' => 'kg',
            'ltr' => 'ltr'
        ];
        
        return $mapping[$lowerSatuan] ?? $satuan;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'kategori' => 'required|string|in:MT,JS,AT,HO,SR,SB',
            'uraian_item' => 'required|string|max:1000',
            'satuan' => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0|max:999999999999.99',
            'status' => 'nullable|string',
            'spesifikasi' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'kategori.in' => 'Kategori harus salah satu dari: MT, JS, AT, HO, SR, SB',
            'uraian_item.required' => 'Uraian item wajib diisi',
            'satuan.required' => 'Satuan wajib diisi',
            'harga_satuan.required' => 'Harga satuan wajib diisi',
            'harga_satuan.numeric' => 'Harga satuan harus berupa angka',
            'harga_satuan.min' => 'Harga satuan minimal 0',
            'harga_satuan.max' => 'Harga satuan terlalu besar'
        ];
    }

    /**
     * Batch size untuk optimasi memory
     */
    public function batchSize(): int
    {
        return 100; // Increase untuk performa lebih baik
    }

    /**
     * Chunk size untuk optimasi memory
     */
    public function chunkSize(): int
    {
        return 100; // Increase untuk performa lebih baik
    }

    /**
     * Get imported count
     */
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * Get skipped count
     */
    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * Get errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Parse harga dari berbagai format
     */
    private function parseHarga($harga)
    {
        if (is_numeric($harga)) {
            return floatval($harga);
        }

        if (is_string($harga)) {
            // Handle scientific notation
            if (strpos($harga, 'e+') !== false || strpos($harga, 'E+') !== false) {
                return floatval($harga);
            }
            
            $harga = preg_replace('/[^0-9.]/', '', $harga);
            $harga = str_replace('.', '', $harga);
            $harga = str_replace(',', '.', $harga);
        }

        return is_numeric($harga) ? floatval($harga) : false;
    }

    /**
     * Parse status dari berbagai format
     */
    private function parseStatus($status)
    {
        if (is_bool($status)) {
            return $status;
        }

        if (is_numeric($status)) {
            return (bool)$status;
        }

        if (empty($status)) {
            return true;
        }

        $status = strtolower(trim($status));
        
        $activeValues = ['aktif', 'true', '1', 'yes', 'ya', 'active', 'y'];
        $inactiveValues = ['nonaktif', 'false', '0', 'no', 'tidak', 'inactive', 'n'];
        
        if (in_array($status, $activeValues)) {
            return true;
        }
        
        if (in_array($status, $inactiveValues)) {
            return false;
        }

        return true;
    }

    /**
     * Prepare data sebelum validasi
     */
    public function prepareForValidation($data, $index)
    {
        $preparedData = [];
        foreach ($data as $key => $value) {
            $preparedData[strtolower($key)] = $value;
        }

        $mappings = [
            'kategori' => ['kategori', 'category', 'kat'],
            'uraian_item' => ['uraian_item', 'uraian', 'item', 'deskripsi', 'description'],
            'satuan' => ['satuan', 'unit', 'units'],
            'harga_satuan' => ['harga_satuan', 'harga', 'price', 'cost'],
            'status' => ['status', 'active', 'aktif'],
            'spesifikasi' => ['spesifikasi', 'spesifik', 'spec', 'detail']
        ];

        $finalData = [];
        foreach ($mappings as $standardKey => $possibleKeys) {
            foreach ($possibleKeys as $possibleKey) {
                if (isset($preparedData[$possibleKey])) {
                    $finalData[$standardKey] = $preparedData[$possibleKey];
                    break;
                }
            }
            if (!isset($finalData[$standardKey])) {
                $finalData[$standardKey] = null;
            }
        }

        return $finalData;
    }

    /**
     * Called when import is completed
     */
    public function onCompletion()
    {
        Log::info("Import completed. Success: {$this->importedCount}, Skipped: {$this->skippedCount}, Errors: " . count($this->errors));
        
        // Update counters setelah import selesai
        $this->updateCountersAfterImport();
    }

    /**
     * Update counters setelah import selesai - FIXED untuk SQLite
     */
    private function updateCountersAfterImport()
    {
        $now = now()->toDateTimeString();
        $categories = ['MT', 'JS', 'AT', 'HO', 'SR', 'SB'];
        
        foreach ($categories as $category) {
            $maxNumber = $this->lastNumbers[$category] ?? $this->getLastNumber($category);
            
            $existingCounter = DB::table('data_counters')
                ->where('kode_kategori', $category)
                ->first();

            if ($existingCounter) {
                DB::table('data_counters')
                    ->where('kode_kategori', $category)
                    ->update([
                        'terakhir' => $maxNumber,
                        'updated_at' => $now
                    ]);
            } else {
                DB::table('data_counters')->insert([
                    'kode_kategori' => $category,
                    'terakhir' => $maxNumber,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
        }
        
        Log::info("Counters updated after import");
    }
}