<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterData extends Model
{
    use HasFactory;

    protected $table = 'master_data';

    protected $fillable = [
        'code',
        'category',
        'name',
        'unit',
        'price',
        'description',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    public function prices()
    {
        return $this->hasMany(MasterItemPrice::class, 'master_data_id');
    }

    // Optional: scope helpers
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function categories()
    {
        return [
            'MT' => 'Material',
            'JS' => 'Jasa',
            'AT' => 'Alat',
            'HO' => 'Head Office',
            'SR' => 'Sirkulasi',
            'SB' => 'SubKon'
        ];
    }

    // Legacy compatibility accessors for price-management views/controllers
    public function getKodeItemAttribute(): string
    {
        return (string) $this->code;
    }

    public function getUraianItemAttribute(): string
    {
        return (string) $this->name;
    }

    public function getHargaSatuan1Attribute(): float
    {
        return (float) $this->price;
    }

    public function getTanggalUpdateAttribute(): ?string
    {
        return $this->updated_at ? $this->updated_at->toDateString() : null;
    }

    public function getKategoriLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? (string) $this->category;
    }
}
