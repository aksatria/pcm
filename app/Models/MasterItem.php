<?php
// app/Models/MasterItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterItem extends Model
{
    use HasFactory;

    protected $table = 'master_items';

    protected $fillable = [
        'code', 'name', 'unit', 'category', 'base_price', 'notes'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
    ];

    // ✅ DEFINE CATEGORY CONSTANTS
    public const CATEGORY_MATERIAL = 'MT';
    public const CATEGORY_JASA = 'JS';
    public const CATEGORY_ALAT = 'AT';
    public const CATEGORY_HEAD_OFFICE = 'HO';
    public const CATEGORY_SUBKON = 'SB';
    public const CATEGORY_SIRKULASI = 'SR';

    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_MATERIAL => 'Material',
            self::CATEGORY_JASA => 'Jasa',
            self::CATEGORY_ALAT => 'Alat',
            self::CATEGORY_HEAD_OFFICE => 'Head Office',
            self::CATEGORY_SUBKON => 'Subkon',
            self::CATEGORY_SIRKULASI => 'Sirkulasi',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::getCategoryOptions()[$this->category] ?? $this->category;
    }

    // Relationships
    public function prices()
    {
        return $this->hasMany(MasterItemPrice::class, 'master_item_id');
    }

    public function activePrices()
    {
        return $this->prices()->where(function($query) {
            $query->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', now());
        })->where(function($query) {
            $query->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', now());
        });
    }

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
    }
}