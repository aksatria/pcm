<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SppItem extends Model
{
    use HasFactory;

    protected $table = 'spp_items';

    protected $fillable = [
        'spp_id',
        'rab_item_id',
        'vendor_id',
        'item_code_snapshot',
        'item_name_snapshot',
        'unit_snapshot',
        'qty',
        'unit',
        'schedule_date',
        'work_notes',
    ];

    protected $casts = [
        'qty' => 'float',
        'schedule_date' => 'date',
    ];

    public function spp()
    {
        return $this->belongsTo(Spp::class, 'spp_id');
    }

    public function rabItem()
    {
        return $this->belongsTo(RabItem::class, 'rab_item_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
