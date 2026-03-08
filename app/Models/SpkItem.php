<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpkItem extends Model
{
    use HasFactory;

    protected $table = 'spk_items';

    protected $fillable = [
        'spk_id',
        'rab_item_id',
        'item_code_snapshot',
        'item_name_snapshot',
        'unit_snapshot',
        'description',
        'qty',
        'unit',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_price' => 'float',
        'total_price' => 'float',
    ];

    public function spk()
    {
        return $this->belongsTo(Spk::class, 'spk_id');
    }

    public function rabItem()
    {
        return $this->belongsTo(RabItem::class, 'rab_item_id');
    }
}
