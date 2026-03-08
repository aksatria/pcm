<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorComparison extends Model
{
    use HasFactory;

    protected $table = 'vendor_comparisons';

    protected $fillable = [
        'project_id',
        'rab_id',
        'comparison_no',
        'comparison_date',
        'status',
        'rejected_reason',
        'decision_vendor_id',
        'final_amount',
        'difference_amount',
        'notes',
    ];

    protected $casts = [
        'comparison_date' => 'date',
        'final_amount' => 'float',
        'difference_amount' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(VendorComparisonItem::class, 'vendor_comparison_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function rab()
    {
        return $this->belongsTo(Rab::class, 'rab_id');
    }

    public function decisionVendor()
    {
        return $this->belongsTo(Vendor::class, 'decision_vendor_id');
    }
}
