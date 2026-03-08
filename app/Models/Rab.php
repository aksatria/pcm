<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Rab extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'status',
        'version',
        'is_active',
        'total_budget',
        'breakdown',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'rejected_reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'breakdown'     => 'array',
        'submitted_at'  => 'datetime',
        'approved_at'   => 'datetime',
        'rejected_at'   => 'datetime',
        'is_active'     => 'boolean',
        'total_budget'  => 'decimal:2',
    ];

    // =========================
    // RELATIONSHIPS
    // =========================

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function items()
    {
        return $this->hasMany(RabItem::class, 'rab_id');
    }

    // =========================
    // SCOPES
    // =========================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

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

    // =========================
    // BUDGET / TOTALS - PERBAIKAN UTAMA
    // =========================

    /**
     * Hitung total budget RAB Baseline dari semua items
     */
    public function calculateTotalBudget(): float
    {
        $itemsTotal = $this->items()->sum(
            DB::raw('(COALESCE(volume, 0) * COALESCE(harga_satuan, 0))')
        );
        
        return (float) $itemsTotal;
    }

    /**
     * Hitung total realisasi dari semua items
     */
    public function calculateTotalRealisasi(): float
    {
        // PERBAIKAN: Hitung dari realisasi_amount di database
        $totalRealisasi = $this->items()->sum(
            DB::raw('COALESCE(realisasi_amount, 0)')
        );
        
        return (float) $totalRealisasi;
    }

    /**
     * Hitung total sisa global
     */
    public function calculateTotalSisa(): float
    {
        $totalBudget = $this->calculateTotalBudget();
        $totalRealisasi = $this->calculateTotalRealisasi();
        
        return max(0, $totalBudget - $totalRealisasi);
    }

    /**
     * Hitung persentase realisasi
     */
    public function calculateRealisasiPercentage(): float
    {
        $totalBudget = $this->calculateTotalBudget();
        if ($totalBudget <= 0) {
            return 0.0;
        }
        
        $totalRealisasi = $this->calculateTotalRealisasi();
        return min(100, ($totalRealisasi / $totalBudget) * 100);
    }

    /**
     * Validasi konsistensi data
     */
    public function validateConsistency(): array
    {
        $calculatedTotal = $this->calculateTotalBudget();
        $storedTotal = (float) ($this->attributes['total_budget'] ?? 0);
        $difference = abs($calculatedTotal - $storedTotal);
        
        return [
            'is_consistent' => $difference < 1,
            'calculated_total' => $calculatedTotal,
            'stored_total' => $storedTotal,
            'difference' => $difference,
            'items_count' => $this->items()->count(),
            'needs_repair' => $difference >= 1
        ];
    }

    /**
     * Repair data yang tidak konsisten
     */
    public function repairInconsistentData(): bool
    {
        $validation = $this->validateConsistency();
        
        if ($validation['needs_repair']) {
            Log::warning('Repairing Inconsistent RAB Baseline Data', [
                'rab_id' => $this->id,
                'old_total' => $validation['stored_total'],
                'new_total' => $validation['calculated_total'],
                'difference' => $validation['difference']
            ]);
            
            // Update langsung ke database
            DB::table('rabs')
                ->where('id', $this->id)
                ->update([
                    'total_budget' => $validation['calculated_total'],
                    'breakdown' => json_encode($this->getBreakdownByCategory()),
                    'updated_at' => now()
                ]);
            
            // Update atribut model
            $this->attributes['total_budget'] = $validation['calculated_total'];
            
            return true;
        }
        
        return false;
    }

    /**
     * Ringkasan budget vs realisasi internal RAB Baseline
     */
    public function getBudgetSummary()
    {
        $totalBudget = $this->calculateTotalBudget();
        $totalRealisasi = $this->calculateTotalRealisasi();
        $totalSisa = $this->calculateTotalSisa();
        $percentage = $this->calculateRealisasiPercentage();

        return [
            'total_budget'    => $totalBudget,
            'total_realisasi' => $totalRealisasi,
            'total_sisa'      => $totalSisa,
            'percentage'      => $percentage,
            'is_over_budget'  => $totalRealisasi > $totalBudget,
        ];
    }

    /**
     * Bandingkan total RAB Baseline dengan budget proyek.
     */
    public function compareWithProjectBudget()
    {
        $project = $this->relationLoaded('project')
            ? $this->project
            : $this->project()->first();

        if (!$project) {
            return [
                'project_budget'   => 0.0,
                'rapp_total'       => (float) $this->calculateTotalBudget(),
                'difference'       => 0.0,
                'percentage'       => 0.0,
                'is_over_budget'   => false,
            ];
        }

        $projectBudget =
            $project->budget
            ?? $project->total_budget
            ?? $project->project_budget
            ?? 0;

        $projectBudget = (float) $projectBudget;
        $rappTotal     = (float) $this->calculateTotalBudget();
        $difference    = $rappTotal - $projectBudget;

        $percentage = $projectBudget > 0
            ? round(($rappTotal / $projectBudget) * 100, 1)
            : 0.0;

        return [
            'project_budget' => $projectBudget,
            'rapp_total'     => $rappTotal,
            'difference'     => $difference,
            'percentage'     => $percentage,
            'is_over_budget' => $rappTotal > $projectBudget,
        ];
    }

    /**
     * Simpan ulang total & breakdown ke kolom di RAB Baseline
     */
    public function updateTotals()
    {
        $calculatedTotal = $this->calculateTotalBudget();
        
        // Update langsung ke database
        DB::table('rabs')
            ->where('id', $this->id)
            ->update([
                'total_budget' => $calculatedTotal,
                'breakdown' => json_encode($this->getBreakdownByCategory()),
                'updated_at' => now()
            ]);
        
        // Update atribut model
        $this->attributes['total_budget'] = $calculatedTotal;
        
        Log::debug('RAB Baseline Totals Updated', [
            'rab_id' => $this->id,
            'total_budget' => $calculatedTotal,
            'items_count' => $this->items()->count()
        ]);
    }

    /**
     * Sync semua realisasi dari voucher untuk semua items
     */
    public function syncAllRealisasiFromVoucher(): int
    {
        $updatedCount = 0;
        
        foreach ($this->items as $item) {
            if (method_exists($item, 'updateRealisasiFromVoucher')) {
                $item->updateRealisasiFromVoucher();
                $updatedCount++;
            }
        }
        
        // Update total setelah sync
        $this->updateTotals();
        
        Log::info('RAB Baseline Realisasi Synced', [
            'rab_id' => $this->id,
            'items_updated' => $updatedCount
        ]);
        
        return $updatedCount;
    }

    /**
     * Breakdown per kategori
     */
    public function getBreakdownByCategory()
    {
        $breakdown = $this->items()
            ->with('data')
            ->get()
            ->groupBy(function ($item) {
                $data     = $item->data;
                $kategori = $data->kategori ?? $data->category ?? null;

                if (!$kategori && property_exists($item, 'item_type')) {
                    $kategori = $item->item_type;
                }

                if (!$kategori) {
                    return 'Uncategorized';
                }

                $kategori = trim(strtolower($kategori));

                return match ($kategori) {
                    'material', 'mt', 'm' => 'Material',
                    'jasa', 'js'          => 'Jasa',
                    'alat', 'al'          => 'Alat',
                    'ho', 'overhead'      => 'Head Office',
                    'sarana', 'sr'        => 'Sarana',
                    'lainnya', 'other'    => 'Lainnya',
                    'subkon', 'sb'        => 'Subkon',
                    'sirkulasi'           => 'Sirkulasi',
                    default               => ucwords($kategori),
                };
            })
            ->map(function ($items, $category) {
                $totalVolume = $items->sum('volume');
                $totalHarga  = $items->sum(function ($item) {
                    return ($item->volume ?? 0) * ($item->harga_satuan ?? 0);
                });
                $totalRealisasi = $items->sum('realisasi_amount');
                $totalSisa = max(0, $totalHarga - $totalRealisasi);

                return [
                    'category'        => $category,
                    'total_volume'    => (float) $totalVolume,
                    'total_harga'     => (float) $totalHarga,
                    'total_realisasi' => (float) $totalRealisasi,
                    'total_sisa'      => (float) $totalSisa,
                    'items_count'     => $items->count(),
                ];
            })
            ->values()
            ->toArray();

        $order = ['Material', 'Jasa', 'Alat', 'Head Office', 'Sirkulasi', 'Subkon', 'Lainnya', 'Uncategorized'];

        usort($breakdown, function ($a, $b) use ($order) {
            $posA = array_search($a['category'], $order);
            $posB = array_search($b['category'], $order);

            $posA = $posA !== false ? $posA : 999;
            $posB = $posB !== false ? $posB : 999;

            return $posA <=> $posB;
        });

        return $breakdown;
    }

    /**
     * Group items per kategori untuk tampilan tabel RAB Baseline
     */
    public function getItemsGroupedByCategory()
    {
        $items = $this->items()
            ->with('data')
            ->get()
            ->groupBy(function ($item) {
                $data     = $item->data;
                $kategori = $data->kategori ?? $data->category ?? null;

                if (!$kategori && property_exists($item, 'item_type')) {
                    $kategori = $item->item_type;
                }

                if (!$kategori) {
                    return 'Uncategorized';
                }

                $kategori = trim(strtolower($kategori));

                return match ($kategori) {
                    'material', 'mt', 'm' => 'Material',
                    'jasa', 'js'          => 'Jasa',
                    'alat', 'al'          => 'Alat',
                    'ho', 'overhead'      => 'Head Office',
                    'sarana', 'sr'        => 'Sarana',
                    'lainnya', 'other'    => 'Lainnya',
                    'subkon', 'sb'        => 'Subkon',
                    'sirkulasi'           => 'Sirkulasi',
                    default               => ucwords($kategori),
                };
            });

        $order = ['Material', 'Jasa', 'Alat', 'Head Office', 'Sirkulasi', 'Subkon', 'Lainnya', 'Uncategorized'];

        return $items->sortBy(function ($group, $category) use ($order) {
            return array_search($category, $order) !== false
                ? array_search($category, $order)
                : 999;
        });
    }

    // =========================
    // STATUS HELPERS
    // =========================

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'draft'     => 'gray',
            'submitted' => 'blue',
            'approved'  => 'green',
            'rejected'  => 'red',
            default     => 'gray',
        };
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'draft'     => 'Draft',
            'submitted' => 'Menunggu Persetujuan',
            'approved'  => 'Disetujui',
            'rejected'  => 'Ditolak',
            default     => 'Tidak Diketahui',
        };
    }

    public function statusKey(): string
    {
        $s = strtolower(trim((string) ($this->status ?? '')));
        $map = [
            'draft'     => 'draft',
            'submitted' => 'submitted',
            'approved'  => 'approved',
            'rejected'  => 'rejected',

            'draf'      => 'draft',
            'dikirim'   => 'submitted',
            'submit'    => 'submitted',
            'disetujui' => 'approved',
            'approve'   => 'approved',
            'ditolak'   => 'rejected',
            'tolak'     => 'rejected',
        ];

        return $map[$s] ?? $s;
    }

    public function canEdit()
    {
        if (auth()->check() && auth()->user() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO()) {
            return true;
        }

        return in_array($this->statusKey(), ['draft', 'rejected', 'approved']);
    }

    public function canSubmit()
    {
        return in_array($this->statusKey(), ['draft', 'rejected']) && $this->items()->count() > 0;
    }

    public function canApprove()
    {
        return auth()->check()
            && auth()->user()
            && method_exists(auth()->user(), 'isHO')
            && auth()->user()->isHO()
            && $this->statusKey() === 'submitted';
    }

    public function canReject()
    {
        return auth()->check()
            && auth()->user()
            && method_exists(auth()->user(), 'isHO')
            && auth()->user()->isHO()
            && $this->statusKey() === 'submitted';
    }

    public function submit()
    {
        if (!$this->canSubmit()) return false;

        $this->status       = 'submitted';
        $this->submitted_at = now();
        $this->save();

        return true;
    }

    public function approve()
    {
        if (!$this->canApprove()) return false;

        $this->status      = 'approved';
        $this->approved_at = now();
        $this->is_active   = true;
        $this->save();

        return true;
    }

    public function reject($reason = null)
    {
        if (!$this->canReject()) return false;

        $this->status          = 'rejected';
        $this->rejected_at     = now();
        $this->rejected_reason = $reason;
        $this->save();

        return true;
    }

    public function duplicate($attributes = [])
    {
        $newRab = $this->replicate();
        $newRab->status       = 'draft';
        $newRab->is_active    = false;
        $newRab->version      = $this->version + 1;
        $newRab->total_budget = null;
        $newRab->breakdown    = null;

        foreach ($attributes as $key => $value) {
            $newRab->{$key} = $value;
        }

        $newRab->save();

        foreach ($this->items as $item) {
            $newItem         = $item->replicate();
            $newItem->rab_id = $newRab->id;
            $newItem->save();
        }

        $newRab->updateTotals();

        return $newRab;
    }

    // =========================
    // ACCESSORS
    // =========================

    public function getTotalBudgetAttribute($value)
    {
        return (float) $value;
    }

    // =========================
    // BOOT
    // =========================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rab) {
            if (empty($rab->status)) {
                $rab->status = 'draft';
            }

            if (empty($rab->version)) {
                $rab->version = 1;
            }

            if (is_null($rab->is_active)) {
                $rab->is_active = false;
            }

            if (auth()->check()) {
                $rab->created_by = auth()->id();
            }

            if (empty($rab->name) && $rab->project) {
                $rab->name = 'RAB Baseline ' . $rab->project->name . ' ' . now()->format('YmdHis');
            }
        });

        static::saved(function ($rab) {
            if ($rab->is_active && $rab->project) {
                $rab->project->rabs()
                    ->where('id', '!=', $rab->id)
                    ->update(['is_active' => false]);
            }
        });
    }

    // =========================
    // UTILITIES
    // =========================

    public function generateSlug()
    {
        return Str::slug($this->name);
    }
}

