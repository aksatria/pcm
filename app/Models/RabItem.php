<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RabItem extends Model
{
    use HasFactory;

    protected $table = 'rab_items';

    protected $fillable = [
        'rab_id',
        'data_id',
        'item_type',
        'volume',
        'satuan',
        'harga_satuan',
        'keterangan',
        'urutan',
        'realisasi_volume',
        'realisasi_amount',
        'sisa_volume',
        'sisa_amount',
        'created_by',
    ];

    protected $casts = [
        'volume'           => 'float',
        'harga_satuan'     => 'float',
        'realisasi_volume' => 'float',
        'realisasi_amount' => 'float',
        'sisa_volume'      => 'float',
        'sisa_amount'      => 'float',
    ];

    // =========================
    // RELATIONS
    // =========================

    public function rab()
    {
        return $this->belongsTo(Rab::class, 'rab_id');
    }

    public function data()
    {
        return $this->belongsTo(Data::class, 'data_id');
    }

    public function voucherItems()
    {
        return $this->hasMany(PurchaseVoucherItem::class, 'rab_item_id');
    }

    // =========================
    // BUDGET CALCULATIONS
    // =========================

    /**
     * Total harga budget RAB Baseline untuk item ini
     */
    public function getTotalHargaAttribute(): float
    {
        $vol = (float) ($this->volume ?? 0);
        $hs  = (float) ($this->harga_satuan ?? 0);
        return $vol * $hs;
    }

    public function getBudgetVolumeAttribute(): float
    {
        return (float) ($this->volume ?? 0);
    }

    public function getBudgetAmountAttribute(): float
    {
        return (float) $this->total_harga;
    }

    // =========================
    // REALISASI CALCULATIONS
    // =========================

    /**
     * Volume realisasi berdasarkan voucher
     */
    public function getRealisasiVolumeAttribute(): float
    {
        // Prioritaskan nilai dari database
        $dbValue = (float) ($this->attributes['realisasi_volume'] ?? 0);
        
        // Hitung dari voucher untuk validasi
        if ($this->relationLoaded('voucherItems')) {
            $voucherTotal = (float) $this->voucherItems->sum('qty');
        } else {
            $voucherTotal = (float) $this->voucherItems()->sum('qty');
        }
        
        // Jika ada perbedaan, sync otomatis
        if (abs($voucherTotal - $dbValue) > 0.01) {
            $this->updateRealisasiFromVoucher();
            return $voucherTotal;
        }
        
        return $dbValue;
    }

    /**
     * Rupiah realisasi
     */
    public function getRealisasiAmountAttribute(): float
    {
        $realisasiVol = (float) $this->realisasi_volume;
        $hs           = (float) ($this->harga_satuan ?? 0);
        return $realisasiVol * $hs;
    }

    // =========================
    // SISA CALCULATIONS
    // =========================

    public function getSisaVolumeAttribute(): float
    {
        $budgetVol    = (float) ($this->volume ?? 0);
        $realisasiVol = (float) $this->realisasi_volume;
        $sisa = $budgetVol - $realisasiVol;
        
        return max(0, $sisa);
    }

    public function getSisaAmountAttribute(): float
    {
        $budgetAmt    = (float) $this->total_harga;
        $realisasiAmt = (float) $this->realisasi_amount;
        $sisa = $budgetAmt - $realisasiAmt;
        
        return max(0, $sisa);
    }

    /**
     * Persentase pemakaian
     */
    public function getPercentUsedAttribute(): float
    {
        $budgetVol = (float) ($this->volume ?? 0);
        if ($budgetVol <= 0) {
            return 0.0;
        }

        $realisasiVol = (float) $this->realisasi_volume;
        return ($realisasiVol / $budgetVol) * 100;
    }

    // =========================
    // METHODS FOR SYNCING
    // =========================

    /**
     * Update realisasi dari voucher
     */
    public function updateRealisasiFromVoucher()
    {
        // Hitung dari voucher
        $realisasiVolume = (float) $this->voucherItems()->sum('qty');
        $realisasiAmount = $realisasiVolume * ((float) $this->harga_satuan ?? 0);
        
        // Hitung sisa
        $budgetVolume = (float) ($this->volume ?? 0);
        $budgetAmount = (float) $this->total_harga;
        
        $sisaVolume = max(0, $budgetVolume - $realisasiVolume);
        $sisaAmount = max(0, $budgetAmount - $realisasiAmount);
        
        // Update ke database
        \Illuminate\Support\Facades\DB::table('rab_items')
            ->where('id', $this->id)
            ->update([
                'realisasi_volume' => $realisasiVolume,
                'realisasi_amount' => $realisasiAmount,
                'sisa_volume'      => $sisaVolume,
                'sisa_amount'      => $sisaAmount,
                'updated_at'       => now()
            ]);
        
        // Update atribut model
        $this->attributes['realisasi_volume'] = $realisasiVolume;
        $this->attributes['realisasi_amount'] = $realisasiAmount;
        $this->attributes['sisa_volume'] = $sisaVolume;
        $this->attributes['sisa_amount'] = $sisaAmount;
        
        return true;
    }

    // =========================
    // BOOT METHOD
    // =========================

    protected static function boot()
    {
        parent::boot();

        // Auto-sync saat item di-update
        static::updated(function ($item) {
            if ($item->wasChanged(['volume', 'harga_satuan'])) {
                $item->updateRealisasiFromVoucher();
            }
        });
    }
}
