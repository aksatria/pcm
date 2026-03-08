<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bpg extends Model
{
    use HasFactory;

    protected $table = 'bpgs';

    protected $fillable = [
        'project_id',
        'rab_id',
        'lpb_id',
        'bpg_no',
        'bpg_date',
        'status',
        'rejected_reason',
        'requested_by',
        'approved_by',
        'known_by',
        'notes',
    ];

    protected $casts = [
        'bpg_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(BpgItem::class, 'bpg_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function rab()
    {
        return $this->belongsTo(Rab::class, 'rab_id');
    }

    public function lpb()
    {
        return $this->belongsTo(Lpb::class, 'lpb_id');
    }
}
