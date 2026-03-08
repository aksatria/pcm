<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lpb extends Model
{
    use HasFactory;

    protected $table = 'lpbs';

    protected $fillable = [
        'project_id',
        'rab_id',
        'vendor_id',
        'purchase_order_id',
        'lpb_no',
        'lpb_date',
        'status',
        'rejected_reason',
        'delivered_by',
        'received_by',
        'known_by',
        'notes',
    ];

    protected $casts = [
        'lpb_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(LpbItem::class, 'lpb_id');
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

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
