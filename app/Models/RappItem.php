<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RappItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'rapp_id',
        'job_code',
        'parent_code',
        'level',
        'description',
        'unit',
        'volume',
        'unit_cost_estimate',
        'total_estimate',
        'order'
    ];

    protected $casts = [
        'volume' => 'decimal:4',
        'unit_cost_estimate' => 'decimal:2',
        'total_estimate' => 'decimal:2',
    ];

    public static function getUnitOptions(): array
    {
        return [
            'm' => 'm (Meter)',
            'cm' => 'cm (Centimeter)',
            'mm' => 'mm (Milimeter)',
            'km' => 'km (Kilometer)',

            'm²' => 'm² (Meter Persegi)',
            'ha' => 'ha (Hektar)',

            'm³' => 'm³ (Meter Kubik)',
            'l' => 'l (Liter)',

            'kg' => 'kg (Kilogram)',
            'gr' => 'gr (Gram)',
            'ton' => 'ton',

            'hari' => 'Hari',
            'jam' => 'Jam',
            'minggu' => 'Minggu',
            'bulan' => 'Bulan',

            'orang' => 'Orang',
            'OH' => 'OH (Orang Hari)',
            'OJ' => 'OJ (Orang Jam)',

            'unit' => 'Unit',
            'buah' => 'Buah',
            'bh' => 'bh (Buah)',
            'pcs' => 'pcs (Piece)',
            'set' => 'Set',
            'paket' => 'Paket',
            'lot' => 'Lot',

            'roll' => 'Roll',
            'btg' => 'btg (Batang)',
            'lbr' => 'lbr (Lembar)',
            'sak' => 'Sak',
            'kaleng' => 'Kaleng',
            'dus' => 'Dus',

            'ls' => 'ls (Lumpsum)',
            'titik' => 'Titik',
            'pt' => 'pt (Point)',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            if ($item->isDirty(['volume', 'unit_cost_estimate']) && !$item->has_children) {
                $item->total_estimate = $item->volume * $item->unit_cost_estimate;
            }
        });

        static::creating(function ($item) {
            if (empty($item->order)) {
                $lastOrder = static::where('rapp_id', $item->rapp_id)
                    ->where('parent_code', $item->parent_code)
                    ->max('order');
                $item->order = $lastOrder ? $lastOrder + 1 : 1;
            }
        });

        static::saved(function ($item) {
            if ($item->parent_code) {
                $parent = static::where('rapp_id', $item->rapp_id)
                    ->where('job_code', $item->parent_code)
                    ->first();
                if ($parent) {
                    $parent->calculateTotalEstimate();
                }
            }

            if ($item->rapp) {
                $item->rapp->recalculateTotalEstimate();
            }
        });

        static::deleted(function ($item) {
            if ($item->parent_code) {
                $parent = static::where('rapp_id', $item->rapp_id)
                    ->where('job_code', $item->parent_code)
                    ->first();
                if ($parent) {
                    $parent->calculateTotalEstimate();
                }
            }

            if ($item->rapp) {
                $item->rapp->recalculateTotalEstimate();
            }
        });
    }

    // Relationships
    public function rapp(): BelongsTo
    {
        return $this->belongsTo(Rapp::class);
    }

    public function parent(): BelongsTo
    {
        // parent_code references job_code of parent within same rapp_id
        return $this->belongsTo(RappItem::class, 'parent_code', 'job_code')
                    ->where('rapp_id', $this->rapp_id);
    }

    public function children(): HasMany
    {
        return $this->hasMany(RappItem::class, 'parent_code', 'job_code')
                    ->where('rapp_id', $this->rapp_id)
                    ->orderBy('order');
    }

    public function rabItems(): HasMany
    {
        return $this->hasMany(RabItem::class, 'job_code', 'job_code');
    }

    // Accessors & Mutators
    public function getFormattedUnitCostEstimateAttribute(): string
    {
        return 'Rp ' . number_format($this->unit_cost_estimate, 0, ',', '.');
    }

    public function getFormattedTotalEstimateAttribute(): string
    {
        return 'Rp ' . number_format($this->total_estimate, 0, ',', '.');
    }

    public function getFormattedVolumeAttribute(): string
    {
        $volume = (float) $this->volume;
        return number_format($volume, 4, ',', '.');
    }

    public function getVolumeWithoutFormatAttribute(): float
    {
        return (float) $this->volume;
    }

    public function getHasChildrenAttribute(): bool
    {
        return $this->children()->exists();
    }

    public function getIsLeafAttribute(): bool
    {
        return !$this->has_children;
    }

    public function getIsRootAttribute(): bool
    {
        return $this->level === 1;
    }

    public function getIndentedDescriptionAttribute(): string
    {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $this->level - 1);
        return $indent . $this->job_code . ' - ' . $this->description;
    }

    public function getFullJobCodeAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_job_code . ' > ' . $this->job_code;
        }
        return $this->job_code;
    }

    // Methods
    public function calculateTotalEstimate(): void
    {
        if ($this->has_children) {
            $total = $this->children()->sum('total_estimate');
            $this->total_estimate = $total;
        } else {
            $this->total_estimate = $this->volume * $this->unit_cost_estimate;
        }

        $this->save();

        if ($this->parent) {
            $this->parent->calculateTotalEstimate();
        }
    }

    public function getFullDescriptionAttribute(): string
    {
        return $this->job_code . ' - ' . $this->description;
    }

    public function getDisplayDescriptionAttribute(): string
    {
        $indent = str_repeat('── ', max(0, $this->level - 1));
        return $indent . $this->job_code . ' ' . $this->description;
    }

    // Scopes
    public function scopeRootItems($query)
    {
        return $query->where('level', 1);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeLeafItems($query)
    {
        return $query->whereDoesntHave('children');
    }

    public function scopeParentItems($query)
    {
        return $query->whereHas('children');
    }

    public function scopeByParent($query, $parentCode)
    {
        return $query->where('parent_code', $parentCode);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('description', 'like', "%{$search}%")
                    ->orWhere('job_code', 'like', "%{$search}%");
    }

    // Validation Rules
    public static function getValidationRules($rappId = null): array
    {
        return [
            'description' => 'required|string|max:1000',
            'unit' => 'nullable|string|max:50',
            'volume' => 'nullable|numeric|min:0',
            'unit_cost_estimate' => 'nullable|numeric|min:0',
            'parent_code' => 'nullable|exists:rapp_items,job_code,rapp_id,' . $rappId,
        ];
    }

    // Business Logic
    public function canHaveChildren(): bool
    {
        return $this->level < 4;
    }

    public function getMaxChildrenLevel(): int
    {
        return 4 - $this->level;
    }

    public function getChildLevel(): int
    {
        return $this->level + 1;
    }

    public function duplicate($newRappId = null): RappItem
    {
        $newItem = $this->replicate();
        $newItem->rapp_id = $newRappId ?: $this->rapp_id;
        $newItem->job_code = $this->generateDuplicateJobCode();
        $newItem->save();

        foreach ($this->children as $child) {
            $child->duplicate($newItem->rapp_id);
        }

        return $newItem;
    }

    private function generateDuplicateJobCode(): string
    {
        $baseCode = $this->job_code;
        $counter = 1;

        do {
            $newCode = $baseCode . '-COPY-' . $counter;
            $counter++;
        } while (static::where('rapp_id', $this->rapp_id)->where('job_code', $newCode)->exists());

        return $newCode;
    }

    public function moveToParent($newParentCode = null): bool
    {
        $oldParentCode = $this->parent_code;

        $this->parent_code = $newParentCode;
        $this->level = $newParentCode ?
            (static::where('rapp_id', $this->rapp_id)->where('job_code', $newParentCode)->value('level') + 1) : 1;

        $result = $this->save();

        if ($oldParentCode) {
            $oldParent = static::where('rapp_id', $this->rapp_id)->where('job_code', $oldParentCode)->first();
            if ($oldParent) {
                $oldParent->calculateTotalEstimate();
            }
        }

        if ($newParentCode) {
            $newParent = static::where('rapp_id', $this->rapp_id)->where('job_code', $newParentCode)->first();
            if ($newParent) {
                $newParent->calculateTotalEstimate();
            }
        }

        return $result;
    }
}
