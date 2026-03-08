<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rapp extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'code',
        'name',
        'description',
        'status',
        'total_estimate',
        'created_by',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'total_estimate' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rapp) {
            // created_by default
            if (empty($rapp->created_by)) {
                $userId = auth()->id();
                if (!$userId) {
                    $firstUser = User::first();
                    $userId = $firstUser ? $firstUser->id : 1;
                }
                $rapp->created_by = $userId;
            }

            // Generate code otomatis jika tidak disediakan
            if (empty($rapp->code)) {
                $projectCode = $rapp->project ? $rapp->project->code : 'PROJ';
                $timestamp = now()->format('Ymd-His');
                $rapp->code = "RAB-BL-{$projectCode}-{$timestamp}";
            }

            // Generate name otomatis jika tidak disediakan
            if (empty($rapp->name)) {
                $projectName = $rapp->project ? $rapp->project->name : 'Project';
                $rapp->name = "RAB Baseline - {$projectName}";
            }

            // Default status
            if (empty($rapp->status)) {
                $rapp->status = 'draft';
            }
        });
    }

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault([
            'name' => 'Unknown User',
            'email' => 'unknown@example.com'
        ]);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withDefault([
            'name' => 'Unknown User',
            'email' => 'unknown@example.com'
        ]);
    }

    public function rappItems(): HasMany
    {
        return $this->hasMany(RappItem::class);
    }

    /**
     * RELASI RAB ITEMS
     * RAB items disimpan di tabel rab_items dengan kolom project_id.
     * Kita ingin mendapat semua RabItem untuk project yang sama dengan rapp ini.
     * Implementasi yang paling sederhana dan aman:
     */
    public function rabItems(): HasMany
    {
        // foreign key on rab_items = project_id
        // local key on rapps = project_id (not id)
        return $this->hasMany(RabItem::class, 'project_id', 'project_id');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['draft', 'submitted']);
    }

    // Methods
    public function getFormattedTotalEstimateAttribute(): string
    {
        return 'Rp ' . number_format($this->total_estimate, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        $statuses = [
            'draft' => 'Draft',
            'submitted' => 'Terkirim',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'draft' => 'gray',
            'submitted' => 'blue',
            'approved' => 'green',
            'rejected' => 'red'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        $classes = [
            'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'submitted' => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-300',
            'approved' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-300',
            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-300'
        ];

        return $classes[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function canEdit(): bool
    {
        // HO selalu boleh edit (termasuk approved)
        if (auth()->check() && auth()->user() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO()) {
            return true;
        }

        // Staff hanya draft / rejected
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canSubmit(): bool
    {
        return $this->status === 'draft' && $this->has_items;
    }

    public function canApprove(): bool
    {
        return auth()->check()
            && auth()->user()
            && method_exists(auth()->user(), 'isHO')
            && auth()->user()->isHO()
            && $this->status === 'submitted';
    }

    

    public function canReject(): bool
    {
        return auth()->check()
            && auth()->user()
            && method_exists(auth()->user(), 'isHO')
            && auth()->user()->isHO()
            && $this->status === 'submitted';
    }
public function canDelete(): bool
    {
        return $this->status === 'draft';
    }

    public function recalculateTotalEstimate(): float
    {
        $total = $this->rappItems()
            ->where('level', 1)
            ->sum('total_estimate');

        $this->update(['total_estimate' => $total]);

        return $total;
    }

    public function getHasItemsAttribute(): bool
    {
        return $this->rappItems()->exists();
    }

    public function getItemsCountAttribute(): int
    {
        return $this->rappItems()->count();
    }

    public function getApprovedAtFormattedAttribute(): ?string
    {
        return $this->approved_at ? $this->approved_at->format('d M Y H:i') : null;
    }

    public function getCreatedAtFormattedAttribute(): string
    {
        return $this->created_at->format('d M Y H:i');
    }

    public function getUpdatedAtFormattedAttribute(): string
    {
        return $this->updated_at->format('d M Y H:i');
    }

    public static function projectHasRapp($projectId): bool
    {
        return self::where('project_id', $projectId)->exists();
    }

    public static function getByProject($projectId)
    {
        return self::where('project_id', $projectId)->first();
    }

    public function getHierarchicalItemsAttribute()
    {
        return $this->buildHierarchicalItems($this->rappItems()->orderBy('level')->orderBy('order')->get());
    }

    private function buildHierarchicalItems($items)
    {
        $hierarchical = [];

        foreach ($items as $item) {
            if ($item->level === 1) {
                $hierarchical[] = $this->buildItemTree($item, $items);
            }
        }

        return collect($hierarchical);
    }

    private function buildItemTree($parent, $allItems)
    {
        $treeItem = $parent;
        $treeItem->children = collect();

        foreach ($allItems as $item) {
            if ($item->parent_code === $parent->job_code) {
                $treeItem->children->push($this->buildItemTree($item, $allItems));
            }
        }

        return $treeItem;
    }

    public function getLeafItemsAttribute()
    {
        return $this->rappItems()
            ->whereDoesntHave('children')
            ->get();
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->status === 'approved') return 100;
        if ($this->status === 'submitted') return 66;
        if ($this->status === 'rejected') return 33;
        return 0;
    }

    public function duplicate($newProjectId = null): Rapp
    {
        $newRapp = $this->replicate();
        $newRapp->project_id = $newProjectId ?: $this->project_id;
        $newRapp->code = $this->generateDuplicateCode();
        $newRapp->name = $this->name . ' (Copy)';
        $newRapp->status = 'draft';
        $newRapp->approved_by = null;
        $newRapp->approved_at = null;
        $newRapp->created_by = auth()->id();
        $newRapp->save();

        foreach ($this->rappItems as $item) {
            $newItem = $item->replicate();
            $newItem->rapp_id = $newRapp->id;
            $newItem->save();
        }

        return $newRapp;
    }

    private function generateDuplicateCode(): string
    {
        $baseCode = preg_replace('/-COPY-\d+$/', '', $this->code);
        $counter = 1;

        do {
            $newCode = $baseCode . '-COPY-' . $counter;
            $counter++;
        } while (self::where('code', $newCode)->exists());

        return $newCode;
    }
}

