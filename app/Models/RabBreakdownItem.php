<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RabBreakdownItem extends Model
{
    protected $table = 'rab_breakdown_items';
    
    protected $fillable = [
        'rab_breakdown_id',
        'item_code',
        'uraian',
        'volume_rab',
        'satuan',
        'unit_price',
        'total_price',
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
        'rab_item_id',
        'notes',
    ];

    protected $casts = [
        'volume_rab' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
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

    public function rabBreakdown(): BelongsTo
    {
        return $this->belongsTo(RabBreakdown::class, 'rab_breakdown_id');
    }

    public function rabItem(): BelongsTo
    {
        return $this->belongsTo(RabItem::class);
    }

    public function budgetSources(): HasMany
    {
        return $this->hasMany(RabBreakdownBudgetSource::class, 'rab_breakdown_item_id');
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    public function calculateTotal(): void
    {
        $this->total_price = $this->volume_rab * $this->unit_price;
        $this->save();
        
        $this->rabBreakdown->updateBudget();
    }

    public function linkToRabItem(RabItem $rabItem): void
    {
        $this->rab_item_id = $rabItem->id;
        $this->save();
    }
}



