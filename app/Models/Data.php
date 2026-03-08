<?php
// app/Models/Data.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class Data extends Model
{
    use HasFactory;

    protected $table = 'data';

    protected $fillable = [
        'kode',
        'kategori',
        'kode_kategori', 
        'uraian',
        'spesifikasi',
        'satuan',
        'harga',
        'status',
        'created_by',
        'updated_by',
        'delete_status',
        'delete_requested_by',
        'delete_requested_at',
        'delete_reason',
        'delete_reviewed_by',
        'delete_reviewed_at',
        'delete_review_note'
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'status' => 'boolean',
    ];

    // Kategori yang valid
    public const KATEGORI = [
        'MT' => 'Material',
        'JS' => 'Jasa',
        'AT' => 'Alat',
        'HO' => 'Head Office', 
        'SR' => 'Sirkulasi',
        'SB' => 'SubKon'
    ];

    // Satuan yang umum - DIPERBAIKI: sekarang user bisa input bebas
    public const SATUAN = [
        'unit', 'jam', 'set', 'bulan', 'oh', 'm', 'm2', 'm3', 'kg', 'ltr', 'gln',
        'roll', 'lembar', 'btg', 'bj', 'ljr', 'titik', 'dus', 'pack', 'sak', 'rit',
        'galon', 'buah', 'ls', 'm\'', 'm1', '5kg/piel', '50/pack', '6m/ljr',
        '2.5l/pack', 'Set/bulan', 'Unit', 'OH', 'M3', 'M2', 'M1', 'M\'', 'Ls'
    ];

    // Scope helpers
    public function scopeAktif($query)
    {
        return $query->where('status', true);
    }

    public function scopeByKategori($query, $kategori)
    {
        return $query->where('kode_kategori', $kategori);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('kode', 'like', "%{$search}%")
              ->orWhere('uraian', 'like', "%{$search}%")
              ->orWhere('kategori', 'like', "%{$search}%")
              ->orWhere('spesifikasi', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getHargaFormattedAttribute()
    {
        return 'Rp ' . number_format($this->harga, 2, ',', '.');
    }

    public function getStatusLabelAttribute()
    {
        return $this->status ? 'Aktif' : 'Nonaktif';
    }

    public function getKategoriWarnaAttribute()
    {
        $warna = [
            'MT' => 'blue',
            'JS' => 'green',
            'AT' => 'purple', 
            'HO' => 'yellow',
            'SR' => 'indigo',
            'SB' => 'pink'
        ];
        
        return $warna[$this->kode_kategori] ?? 'gray';
    }

    // Untuk AJAX response
    public function toArray()
    {
        $array = parent::toArray();
        $array['harga_formatted'] = $this->harga_formatted;
        $array['status_label'] = $this->status_label;
        $array['kategori_warna'] = $this->kategori_warna;
        return $array;
    }

    // Validation Rules - DIPERBAIKI: satuan sekarang string bebas
    public static function getValidationRules($id = null)
    {
        return [
            'kode_kategori' => 'required|in:MT,JS,AT,HO,SR,SB',
            'uraian' => [
                'required',
                'string',
                'max:1000',
                Rule::unique('data')->where(function ($query) use ($id) {
                    if ($id) {
                        $query->where('id', '!=', $id);
                    }
                    return $query;
                })
            ],
            'spesifikasi' => 'nullable|string|max:1000',
            'satuan' => 'required|string|max:50', // DIPERBAIKI: tidak ada in: validation
            'harga' => 'required|numeric|min:0|max:999999999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'status' => 'boolean'
        ];
    }

    public static function getValidationMessages()
    {
        return [
            'uraian.unique' => 'Uraian sudah ada dalam database',
            'harga.regex' => 'Format harga tidak valid. Contoh: 100000 atau 100000.00',
            'harga.max' => 'Harga terlalu besar. Maksimal 999.999.999.999,99'
        ];
    }

    /**
     * Generate kode otomatis - FIXED VERSION untuk mengisi gap
     */
    public static function generateKode($kodeKategori)
    {
        if (!array_key_exists($kodeKategori, self::KATEGORI)) {
            throw new \InvalidArgumentException("Kode kategori tidak valid: {$kodeKategori}");
        }

        return DB::transaction(function () use ($kodeKategori) {
            $now = now()->toDateTimeString();

            // 1. Cari semua nomor yang sudah digunakan
            $existingNumbers = self::where('kode_kategori', $kodeKategori)
                ->selectRaw('CAST(SUBSTR(kode, 4) AS INTEGER) as number')
                ->pluck('number')
                ->sort()
                ->values();

            // 2. Cari gap/nomor yang kosong
            $availableNumber = null;
            $expectedNumber = 1;
            
            foreach ($existingNumbers as $existingNumber) {
                if ($existingNumber > $expectedNumber) {
                    // Ada gap, gunakan nomor ini
                    $availableNumber = $expectedNumber;
                    break;
                }
                $expectedNumber = $existingNumber + 1;
            }

            // 3. Jika tidak ada gap, gunakan nomor berikutnya
            if ($availableNumber === null) {
                $availableNumber = $expectedNumber;
            }

            // 4. Generate kode
            $kode = $kodeKategori . '-' . str_pad($availableNumber, 3, '0', STR_PAD_LEFT);

            // 5. Update counter untuk konsistensi
            $existingCounter = DB::table('data_counters')
                ->where('kode_kategori', $kodeKategori)
                ->first();

            if ($existingCounter) {
                DB::table('data_counters')
                    ->where('kode_kategori', $kodeKategori)
                    ->update([
                        'terakhir' => max($existingCounter->terakhir, $availableNumber),
                        'updated_at' => $now
                    ]);
            } else {
                DB::table('data_counters')->insert([
                    'kode_kategori' => $kodeKategori,
                    'terakhir' => $availableNumber,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }

            return $kode;
        });
    }

    /**
     * Generate kode otomatis - SIMPLE VERSION untuk import batch
     */
    public static function generateKodeSimple($kodeKategori)
    {
        if (!array_key_exists($kodeKategori, self::KATEGORI)) {
            throw new \InvalidArgumentException("Kode kategori tidak valid: {$kodeKategori}");
        }

        $now = now()->toDateTimeString();

        // Cari nomor tertinggi yang ada
        $maxNumber = self::where('kode_kategori', $kodeKategori)
            ->selectRaw('MAX(CAST(SUBSTR(kode, 4) AS INTEGER)) as max_num')
            ->value('max_num') ?? 0;
        
        $nextNumber = $maxNumber + 1;
        
        // Generate kode
        $kode = $kodeKategori . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Update counter untuk konsistensi
        $existingCounter = DB::table('data_counters')
            ->where('kode_kategori', $kodeKategori)
            ->first();

        if ($existingCounter) {
            DB::table('data_counters')
                ->where('kode_kategori', $kodeKategori)
                ->update([
                    'terakhir' => $nextNumber,
                    'updated_at' => $now
                ]);
        } else {
            DB::table('data_counters')->insert([
                'kode_kategori' => $kodeKategori,
                'terakhir' => $nextNumber,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }

        return $kode;
    }

    /**
     * Generate multiple kodes untuk batch import
     */
    public static function generateKodeBatch($kodeKategori, $count = 1)
    {
        if (!array_key_exists($kodeKategori, self::KATEGORI)) {
            throw new \InvalidArgumentException("Kode kategori tidak valid: {$kodeKategori}");
        }

        $now = now()->toDateTimeString();

        return DB::transaction(function () use ($kodeKategori, $count, $now) {
            // Cari semua nomor yang sudah digunakan untuk cari gap
            $existingNumbers = self::where('kode_kategori', $kodeKategori)
                ->selectRaw('CAST(SUBSTR(kode, 4) AS INTEGER) as number')
                ->pluck('number')
                ->sort()
                ->values();

            // Cari gap yang tersedia
            $availableNumbers = [];
            $expectedNumber = 1;
            $numbersFound = 0;
            
            // Cari gap terlebih dahulu
            foreach ($existingNumbers as $existingNumber) {
                while ($expectedNumber < $existingNumber && $numbersFound < $count) {
                    $availableNumbers[] = $expectedNumber;
                    $numbersFound++;
                    $expectedNumber++;
                }
                $expectedNumber = $existingNumber + 1;
            }

            // Jika masih butuh lebih banyak nomor, tambahkan dari akhir
            while ($numbersFound < $count) {
                $availableNumbers[] = $expectedNumber;
                $numbersFound++;
                $expectedNumber++;
            }
            
            $kodes = [];
            foreach ($availableNumbers as $number) {
                $kodes[] = $kodeKategori . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
            }
            
            // Update counter untuk konsistensi
            $lastNumber = end($availableNumbers);
            $existingCounter = DB::table('data_counters')
                ->where('kode_kategori', $kodeKategori)
                ->first();

            if ($existingCounter) {
                DB::table('data_counters')
                    ->where('kode_kategori', $kodeKategori)
                    ->update([
                        'terakhir' => max($existingCounter->terakhir, $lastNumber),
                        'updated_at' => $now
                    ]);
            } else {
                DB::table('data_counters')->insert([
                    'kode_kategori' => $kodeKategori,
                    'terakhir' => $lastNumber,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }

            return $count === 1 ? $kodes[0] : $kodes;
        });
    }

    // Method untuk reindex semua kode - mengisi yang kosong
    public static function reindexAllKodes()
    {
        $categories = ['MT', 'JS', 'AT', 'HO', 'SR', 'SB'];
        $now = now()->toDateTimeString();
        
        foreach ($categories as $category) {
            // Dapatkan semua data untuk kategori ini, urutkan by id atau created_at
            $dataItems = self::where('kode_kategori', $category)
                ->orderBy('id')
                ->get();

            $expectedNumber = 1;
            
            foreach ($dataItems as $item) {
                $newKode = $category . '-' . str_pad($expectedNumber, 3, '0', STR_PAD_LEFT);
                
                // Update kode jika berbeda
                if ($item->kode !== $newKode) {
                    self::where('id', $item->id)->update(['kode' => $newKode]);
                }
                
                $expectedNumber++;
            }

            // Update counter
            $maxNumber = $expectedNumber - 1;
            $existingCounter = DB::table('data_counters')
                ->where('kode_kategori', $category)
                ->first();

            if ($existingCounter) {
                DB::table('data_counters')
                    ->where('kode_kategori', $category)
                    ->update([
                        'terakhir' => $maxNumber > 0 ? $maxNumber : 0,
                        'updated_at' => $now
                    ]);
            } else {
                DB::table('data_counters')->insert([
                    'kode_kategori' => $category,
                    'terakhir' => $maxNumber > 0 ? $maxNumber : 0,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
        }
    }

    // Method untuk reset semua counters ke nilai yang benar
    public static function resetCounters()
    {
        $categories = ['MT', 'JS', 'AT', 'HO', 'SR', 'SB'];
        $now = now()->toDateTimeString();
        
        foreach ($categories as $category) {
            $maxNumber = self::where('kode_kategori', $category)
                ->selectRaw('MAX(CAST(SUBSTR(kode, 4) AS INTEGER)) as max_num')
                ->value('max_num') ?? 0;
            
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
    }

    // Method untuk cek dan perbaiki gap
    public static function fixGaps()
    {
        return self::reindexAllKodes();
    }

    // Method untuk mendapatkan semua kode yang tersedia
    public static function getAvailableNumbers($kodeKategori)
    {
        $existingKodes = self::where('kode_kategori', $kodeKategori)
            ->pluck('kode')
            ->map(function ($kode) use ($kodeKategori) {
                return (int) str_replace($kodeKategori . '-', '', $kode);
            })
            ->sort()
            ->values();

        $availableNumbers = [];
        $expected = 1;
        
        foreach ($existingKodes as $existing) {
            while ($expected < $existing) {
                $availableNumbers[] = $expected;
                $expected++;
            }
            $expected = $existing + 1;
        }

        return $availableNumbers;
    }

    // Method untuk bulk actions
    public static function bulkUpdateStatus($ids, $status)
    {
        return self::whereIn('id', $ids)->update(['status' => $status]);
    }

    public static function bulkDelete($ids)
    {
        return self::whereIn('id', $ids)->delete();
    }

    // New methods for enhanced statistics
    public static function getTotalHargaByKategori($kodeKategori = null)
    {
        $query = self::query();
        if ($kodeKategori) {
            $query->where('kode_kategori', $kodeKategori);
        }
        return $query->sum('harga');
    }

    public static function getAverageHargaByKategori($kodeKategori = null)
    {
        $query = self::query();
        if ($kodeKategori) {
            $query->where('kode_kategori', $kodeKategori);
        }
        return $query->avg('harga');
    }

    public static function getStatisticsByKategori($kodeKategori = null)
    {
        $query = self::query();
        if ($kodeKategori) {
            $query->where('kode_kategori', $kodeKategori);
        }

        return [
            'total_items' => $query->count(),
            'total_harga' => $query->sum('harga'),
            'average_harga' => $query->avg('harga'),
            'active_items' => $query->where('status', true)->count(),
            'inactive_items' => $query->where('status', false)->count()
        ];
    }

    // Method untuk mendapatkan kode berikutnya (deprecated - use generateKode instead)
    public static function getNextKode($kategori)
    {
        return self::generateKodeSimple($kategori);
    }

    // Method untuk mendapatkan data dengan pagination dan filter
    public static function getPaginatedData($kategori = null, $filters = [], $perPage = 10)
    {
        $query = self::query();
        
        if ($kategori) {
            $query->where('kode_kategori', $kategori);
        }
        
        // Apply filters
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
        
        if (isset($filters['status']) && $filters['status'] !== '') {
            $statusValue = $filters['status'] == '1' ? true : false;
            $query->where('status', $statusValue);
        }
        
        // Apply sorting
        if (!empty($filters['sort'])) {
            switch($filters['sort']) {
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'kode_asc':
                    $query->orderBy('kode', 'asc');
                    break;
                case 'kode_desc':
                    $query->orderBy('kode', 'desc');
                    break;
                case 'harga_high':
                    $query->orderBy('harga', 'desc');
                    break;
                case 'harga_low':
                    $query->orderBy('harga', 'asc');
                    break;
                case 'uraian_asc':
                    $query->orderBy('uraian', 'asc');
                    break;
                case 'uraian_desc':
                    $query->orderBy('uraian', 'desc');
                    break;
                default:
                    $query->orderBy('kode', 'asc');
            }
        } else {
            $query->orderBy('kode', 'asc');
        }
        
        return $query->paginate($perPage);
    }

    /**
     * Initialize counters for all categories
     */
    public static function initializeCounters()
    {
        $categories = ['MT', 'JS', 'AT', 'HO', 'SR', 'SB'];
        $now = now()->toDateTimeString();
        
        foreach ($categories as $category) {
            $maxNumber = self::where('kode_kategori', $category)
                ->selectRaw('MAX(CAST(SUBSTR(kode, 4) AS INTEGER)) as max_num')
                ->value('max_num') ?? 0;
            
            $existingCounter = DB::table('data_counters')
                ->where('kode_kategori', $category)
                ->first();

            if (!$existingCounter) {
                DB::table('data_counters')->insert([
                    'kode_kategori' => $category,
                    'terakhir' => $maxNumber,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
        }
    }

    /**
     * Clean up duplicate kodes
     */
    public static function cleanupDuplicateKodes()
    {
        $categories = ['MT', 'JS', 'AT', 'HO', 'SR', 'SB'];
        
        foreach ($categories as $category) {
            // Find duplicate kodes
            $duplicates = self::select('kode', DB::raw('COUNT(*) as count'))
                ->where('kode_kategori', $category)
                ->groupBy('kode')
                ->having('count', '>', 1)
                ->get();
            
            foreach ($duplicates as $duplicate) {
                // Get all records with this kode except the first one
                $records = self::where('kode', $duplicate->kode)
                    ->orderBy('id')
                    ->get();
                
                // Keep the first record, regenerate kodes for the rest
                for ($i = 1; $i < count($records); $i++) {
                    $newKode = self::generateKodeSimple($category);
                    self::where('id', $records[$i]->id)->update(['kode' => $newKode]);
                }
            }
        }
    }
}
