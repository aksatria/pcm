<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movements';

    protected $fillable = [
        'project_id',
        'rab_id',
        'rab_item_id',
        'movement_type',
        'doc_type',
        'doc_id',
        'movement_date',
        'qty',
        'unit',
        'notes',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'qty' => 'float',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function rab()
    {
        return $this->belongsTo(Rab::class, 'rab_id');
    }

    public function rabItem()
    {
        return $this->belongsTo(RabItem::class, 'rab_item_id');
    }
}
