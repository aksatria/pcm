<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorComparisonItem extends Model
{
    use HasFactory;

    protected $table = 'vendor_comparison_items';

    protected $fillable = [
        'vendor_comparison_id',
        'rab_item_id',
        'item_code_snapshot',
        'item_name_snapshot',
        'unit_snapshot',
        'qty',
        'unit',
        'rapp_unit_price',
        'rapp_total',
        'vendor1_id',
        'vendor1_unit_price',
        'vendor1_total',
        'vendor2_id',
        'vendor2_unit_price',
        'vendor2_total',
        'vendor3_id',
        'vendor3_unit_price',
        'vendor3_total',
    ];

    protected $casts = [
        'qty' => 'float',
        'rapp_unit_price' => 'float',
        'rapp_total' => 'float',
        'vendor1_unit_price' => 'float',
        'vendor1_total' => 'float',
        'vendor2_unit_price' => 'float',
        'vendor2_total' => 'float',
        'vendor3_unit_price' => 'float',
        'vendor3_total' => 'float',
    ];

    public function comparison()
    {
        return $this->belongsTo(VendorComparison::class, 'vendor_comparison_id');
    }

    public function rabItem()
    {
        return $this->belongsTo(RabItem::class, 'rab_item_id');
    }

    public function vendor1()
    {
        return $this->belongsTo(Vendor::class, 'vendor1_id');
    }

    public function vendor2()
    {
        return $this->belongsTo(Vendor::class, 'vendor2_id');
    }

    public function vendor3()
    {
        return $this->belongsTo(Vendor::class, 'vendor3_id');
    }
}
