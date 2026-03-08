<?php
// app/Models/Vendor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Project;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'kode_vendor',
        'nama',
        'perusahaan',
        'pekerjaan',
        'bank',
        'no_rekening',
        'nama_rekening',
        'alamat',
        'telepon',
        'email',
        'status',
        'delete_status',
        'delete_requested_by',
        'delete_requested_at',
        'delete_reason',
        'delete_reviewed_by',
        'delete_reviewed_at',
        'delete_review_note'
    ];

    // Relationships
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByBank($query, $bank)
    {
        return $query->where('bank', $bank);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where(function ($q) use ($projectId) {
            $q->whereNull('project_id')
              ->orWhere('project_id', $projectId);
        });
    }
}
