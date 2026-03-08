<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spp extends Model
{
    use HasFactory;

    protected $table = 'spps';

    protected $fillable = [
        'project_id',
        'rab_id',
        'vendor_id',
        'spp_no',
        'spp_date',
        'schedule_date',
        'status',
        'rejected_reason',
        'requested_by',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'spp_date' => 'date',
        'schedule_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(SppItem::class, 'spp_id');
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
