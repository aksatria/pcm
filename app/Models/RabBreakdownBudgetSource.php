<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RabBreakdownBudgetSource extends Model
{
    protected $table = 'rab_breakdown_budget_sources';
    
    protected $fillable = [
        'rab_breakdown_item_id',
        'master_kode',
        'master_kategori',
        'master_uraian',
        'master_satuan',
        'master_harga',
        'allocated_volume',
        'allocated_amount',
        'p',
        'l',
        't',
        'n',
        'n_tul_1',
        'n_tul_2',
        'jarak',
        'dia_1',
        'dia_2',
        'dia_3',
        'berat_1',
        'berat_2',
        'm2_peng',
        'qty',
        'qty_beli',
        'jumlah',
        'notes',
    ];

    protected $casts = [
        'master_harga' => 'decimal:2',
        'allocated_volume' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'p' => 'decimal:4',
        'l' => 'decimal:4',
        't' => 'decimal:4',
        'n' => 'decimal:4',
        'n_tul_1' => 'decimal:4',
        'n_tul_2' => 'decimal:4',
        'jarak' => 'decimal:4',
        'dia_1' => 'decimal:4',
        'dia_2' => 'decimal:4',
        'dia_3' => 'decimal:4',
        'berat_1' => 'decimal:4',
        'berat_2' => 'decimal:4',
        'm2_peng' => 'decimal:6',
        'qty' => 'decimal:6',
        'qty_beli' => 'decimal:6',
        'jumlah' => 'decimal:2',
    ];

    public function rabBreakdownItem(): BelongsTo
    {
        return $this->belongsTo(RabBreakdownItem::class, 'rab_breakdown_item_id');
    }

    public function getFormattedMasterHargaAttribute(): string
    {
        return 'Rp ' . number_format($this->master_harga, 0, ',', '.');
    }

    public function getFormattedAllocatedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->allocated_amount, 0, ',', '.');
    }

    public function calculateAmount(): void
    {
        $this->allocated_amount = $this->allocated_volume * $this->master_harga;
        $this->save();
    }
}



