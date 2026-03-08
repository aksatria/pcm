<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'project_id',
        'rab_id',
        'vendor_id',
        'po_no',
        'po_date',
        'contact_person',
        'phone',
        'address',
        'status',
        'rejected_reason',
        'subtotal_amount',
        'tax_percent',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'po_date' => 'date',
        'subtotal_amount' => 'float',
        'tax_percent' => 'float',
        'tax_amount' => 'float',
        'shipping_cost' => 'float',
        'total_amount' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function rab()
    {
        return $this->belongsTo(Rab::class, 'rab_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
