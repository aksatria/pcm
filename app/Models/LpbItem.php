<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LpbItem extends Model
{
    use HasFactory;

    protected $table = 'lpb_items';

    protected $fillable = [
        'lpb_id',
        'rab_item_id',
        'item_code_snapshot',
        'item_name_snapshot',
        'unit_snapshot',
        'qty',
        'unit',
        'arrival_date',
        'doc_reference',
        'work_notes',
    ];

    protected $casts = [
        'qty' => 'float',
        'arrival_date' => 'date',
    ];

    public function lpb()
    {
        return $this->belongsTo(Lpb::class, 'lpb_id');
    }

    public function rabItem()
    {
        return $this->belongsTo(RabItem::class, 'rab_item_id');
    }
}
