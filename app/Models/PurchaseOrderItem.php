<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'rab_item_id',
        'item_code_snapshot',
        'item_name_snapshot',
        'unit_snapshot',
        'specification',
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

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function rabItem()
    {
        return $this->belongsTo(RabItem::class, 'rab_item_id');
    }
}
