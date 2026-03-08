<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\VendorComparison;

class Spk extends Model
{
    use HasFactory;

    protected $table = 'spks';

    protected $fillable = [
        'project_id',
        'rab_id',
        'vendor_id',
        'vendor_comparison_id',
        'spk_no',
        'spk_date',
        'start_date',
        'end_date',
        'status',
        'rejected_reason',
        'scope',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'spk_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(SpkItem::class, 'spk_id');
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

    public function vendorComparison()
    {
        return $this->belongsTo(VendorComparison::class, 'vendor_comparison_id');
    }
}
