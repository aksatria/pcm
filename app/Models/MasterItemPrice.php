<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterItemPrice extends Model
{
    use HasFactory;

    protected $table = 'master_item_prices';

    protected $fillable = [
        'master_data_id', // ✅ UBAH: dari master_item_id
        'province_id', 
        'supplier_id', 
        'price',
        'effective_from', 
        'effective_to'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    // ✅ UPDATE RELASI
    public function masterData()
    {
        return $this->belongsTo(MasterData::class, 'master_data_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    // Scopes
    public function scopeActive($query, $date = null)
    {
        $date = $date ?: now()->toDateString();
        
        return $query->where(function($q) use ($date) {
            $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date);
        })->where(function($q) use ($date) {
            $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
        });
    }

    public function scopeByProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('province_id');
    }

    public function getIsActiveAttribute(): bool
    {
        $now = now()->toDateString();
        return (!$this->effective_from || $this->effective_from <= $now) && 
               (!$this->effective_to || $this->effective_to >= $now);
    }

    // ✅ HELPER: Format price untuk display
    public function getPriceFormattedAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    // ✅ HELPER: Dapatkan nama provinsi atau "Global"
    public function getProvinceNameAttribute()
    {
        return $this->province ? $this->province->name : 'Global';
    }

    // ✅ HELPER: Cek apakah harga global
    public function getIsGlobalAttribute()
    {
        return is_null($this->province_id);
    }

    // ✅ HELPER: Format periode efektif
    public function getEffectivePeriodAttribute()
    {
        $from = $this->effective_from ? \Carbon\Carbon::parse($this->effective_from)->format('d M Y') : 'Selamanya';
        $to = $this->effective_to ? \Carbon\Carbon::parse($this->effective_to)->format('d M Y') : 'Tidak Terbatas';
        
        return "{$from} - {$to}";
    }

    // ✅ METHOD: Bandingkan dengan harga dasar
    public function getPercentageDifferenceAttribute()
    {
        if (!$this->masterData) {
            return 0;
        }

        $basePrice = $this->masterData->harga_satuan_1;
        if ($basePrice == 0) {
            return 0;
        }

        return (($this->price - $basePrice) / $basePrice) * 100;
    }

    // ✅ METHOD: Dapatkan status warna berdasarkan perbedaan harga
    public function getPriceDifferenceColorAttribute()
    {
        $percentage = $this->percentage_difference;
        
        if ($percentage > 0) {
            return 'text-red-600';
        } elseif ($percentage < 0) {
            return 'text-green-600';
        } else {
            return 'text-gray-500';
        }
    }

    // ✅ METHOD: Format persentase perbedaan
    public function getPercentageDifferenceFormattedAttribute()
    {
        $percentage = $this->percentage_difference;
        
        if ($percentage > 0) {
            return '+' . number_format($percentage, 1) . '%';
        } elseif ($percentage < 0) {
            return number_format($percentage, 1) . '%';
        } else {
            return '0%';
        }
    }
}