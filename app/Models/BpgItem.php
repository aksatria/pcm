<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpgItem extends Model
{
    use HasFactory;

    protected $table = 'bpg_items';

    protected $fillable = [
        'bpg_id',
        'rab_item_id',
        'item_code_snapshot',
        'item_name_snapshot',
        'unit_snapshot',
        'qty',
        'unit',
        'issue_date',
        'work_notes',
    ];

    protected $casts = [
        'qty' => 'float',
        'issue_date' => 'date',
    ];

    public function bpg()
    {
        return $this->belongsTo(Bpg::class, 'bpg_id');
    }

    public function rabItem()
    {
        return $this->belongsTo(RabItem::class, 'rab_item_id');
    }
}
