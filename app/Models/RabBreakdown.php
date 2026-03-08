<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RabBreakdown extends Model
{
    protected $table = 'rab_breakdowns';
    
    protected $fillable = [
        'project_id',
        'rab_breakdown_code',
        'name',
        'order_number',
        'description',
        'budget_amount',
        'actual_amount',
        'progress_percentage',
        'start_date',
        'end_date',
        'status',
        'approval_status',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'submitted_by',
        'approved_by',
        'rejected_by',
        'rejected_reason',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'progress_percentage' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RabBreakdownItem::class, 'rab_breakdown_id');
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'not_started' => 'Belum Mulai',
            'in_progress' => 'Dalam Progress',
            'completed' => 'Selesai',
            'delayed' => 'Terlambat',
            default => 'Unknown'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'not_started' => 'gray',
            'in_progress' => 'blue',
            'completed' => 'green',
            'delayed' => 'red',
            default => 'gray'
        };
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return match($this->approvalKey()) {
            'draft' => 'Draft',
            'submitted' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Draft',
        };
    }

    public function getApprovalStatusColorAttribute(): string
    {
        return match($this->approvalKey()) {
            'draft' => 'gray',
            'submitted' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    public function approvalKey(): string
    {
        $s = strtolower(trim((string) ($this->approval_status ?? 'draft')));
        $map = [
            'draft' => 'draft',
            'submitted' => 'submitted',
            'approved' => 'approved',
            'rejected' => 'rejected',
            'draf' => 'draft',
            'submit' => 'submitted',
            'disetujui' => 'approved',
            'ditolak' => 'rejected',
        ];

        return $map[$s] ?? 'draft';
    }

    public function canEdit(): bool
    {
        if (auth()->check() && auth()->user() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO()) {
            return true;
        }

        return in_array($this->approvalKey(), ['draft', 'rejected', 'approved'], true);
    }

    public function canSubmit(): bool
    {
        return in_array($this->approvalKey(), ['draft', 'rejected'], true) && $this->items()->count() > 0;
    }

    public function canApprove(): bool
    {
        return auth()->check()
            && auth()->user()
            && method_exists(auth()->user(), 'isHO')
            && auth()->user()->isHO()
            && $this->approvalKey() === 'submitted';
    }

    public function canReject(): bool
    {
        return auth()->check()
            && auth()->user()
            && method_exists(auth()->user(), 'isHO')
            && auth()->user()->isHO()
            && $this->approvalKey() === 'submitted';
    }

    public function getFormattedBudgetAttribute(): string
    {
        return 'Rp ' . number_format($this->budget_amount, 0, ',', '.');
    }

    public function getFormattedActualAttribute(): string
    {
        return 'Rp ' . number_format($this->actual_amount, 0, ',', '.');
    }

    public function calculateProgress(): void
    {
        if ($this->budget_amount > 0) {
            $this->progress_percentage = min(100, ($this->actual_amount / $this->budget_amount) * 100);
        } else {
            $this->progress_percentage = 0;
        }
        $this->save();
    }

    public function updateBudget(): void
    {
        $total = $this->items()->sum('total_price');
        $this->budget_amount = $total;
        $this->calculateProgress();
        $this->save();
    }
}


