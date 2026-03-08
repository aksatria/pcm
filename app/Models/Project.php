<?php
// app/Models/Project.php - REVISI LENGKAP DENGAN RAB

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'client_id',
        'province_id',
        'location',
        'pic',
        'start_date',
        'end_date',
        'budget',
        'status',
        'description'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Relationship dengan Client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relationship dengan Province
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Relationship dengan Project Files
     */
    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    /**
     * Relationship dengan RABs (Rencana Anggaran Biaya)
     */
    public function rabs()
    {
        return $this->hasMany(Rab::class);
    }

    /**
     * Relationship dengan RAB Breakdown
     */
    public function rabBreakdowns()
    {
        return $this->hasMany(RabBreakdown::class);
    }

    /**
     * Relationship dengan latest approved RAB
     */
    public function latestApprovedRab()
    {
        return $this->hasOne(Rab::class)->approved()->latest();
    }

    /**
     * Relationship dengan draft RAB
     */
    public function draftRab()
    {
        return $this->hasOne(Rab::class)->draft()->latest();
    }

    /**
     * Relationship dengan active RAB (approved atau draft terbaru)
     */
    public function activeRab()
    {
        return $this->hasOne(Rab::class)->whereIn('status', ['approved', 'draft'])->latest();
    }

    // ==================== ACCESSORS ====================

    /**
     * Get formatted start date
     */
    public function getStartDateFormattedAttribute()
    {
        return $this->start_date ? $this->start_date->format('d M Y') : '-';
    }

    /**
     * Get formatted end date
     */
    public function getEndDateFormattedAttribute()
    {
        return $this->end_date ? $this->end_date->format('d M Y') : '-';
    }

    /**
     * Get formatted budget
     */
    public function getBudgetFormattedAttribute()
    {
        return $this->budget ? 'Rp ' . number_format($this->budget, 0, ',', '.') : '-';
    }

    /**
     * Get budget in numeric format
     */
    public function getBudgetNumericAttribute()
    {
        return $this->budget ? (float) $this->budget : 0;
    }

    /**
     * Calculate project duration in days
     */
    public function getDurationAttribute()
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date);
        }
        return 0;
    }

    /**
     * Get province name with fallback
     */
    public function getProvinceNameAttribute()
    {
        return $this->province ? $this->province->name : 'Belum dipilih';
    }

    /**
     * Get client name with fallback
     */
    public function getClientNameAttribute()
    {
        return $this->client ? $this->client->name : 'N/A';
    }

    /**
     * Get client company with fallback
     */
    public function getClientCompanyAttribute()
    {
        return $this->client ? ($this->client->company ?? '-') : '-';
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'Active' => 'green',
            'Completed' => 'blue',
            'Planning' => 'yellow',
            'On Hold' => 'orange',
            'Cancelled' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get progress percentage (placeholder - bisa diintegrasikan dengan timeline)
     */
    public function getProgressPercentageAttribute()
    {
        // Logic untuk menghitung progress bisa disesuaikan
        // Saat ini return static value, bisa diintegrasikan dengan timeline actual
        return match($this->status) {
            'Planning' => 10,
            'Active' => 45,
            'On Hold' => 60,
            'Completed' => 100,
            'Cancelled' => 0,
            default => 0
        };
    }

    // ==================== SCOPES ====================

    /**
     * Scope untuk project aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope untuk project completed
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    /**
     * Scope untuk project on hold
     */
    public function scopeOnHold($query)
    {
        return $query->where('status', 'On Hold');
    }

    /**
     * Scope untuk project planning
     */
    public function scopePlanning($query)
    {
        return $query->where('status', 'Planning');
    }

    /**
     * Scope untuk project cancelled
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'Cancelled');
    }

    /**
     * Scope untuk project by province
     */
    public function scopeByProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }

    /**
     * Scope untuk project by client
     */
    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope untuk search project
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%")
              ->orWhere('pic', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhereHas('client', function($clientQuery) use ($search) {
                  $clientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%");
              });
        });
    }

    /**
     * Scope untuk project dengan budget range
     */
    public function scopeBudgetRange($query, $minBudget, $maxBudget)
    {
        return $query->whereBetween('budget', [$minBudget, $maxBudget]);
    }

    /**
     * Scope untuk project dengan date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }

    // ==================== RAB RELATED METHODS ====================

    /**
     * Get current active RAB (approved atau draft terbaru)
     */
    public function getCurrentRabAttribute()
    {
        return $this->activeRab ?? $this->rabs()->latest()->first();
    }

    /**
     * Get approved RAB budget
     */
    public function getApprovedRabBudgetAttribute()
    {
        $approvedRab = $this->latestApprovedRab;
        return $approvedRab ? $approvedRab->total_budget : 0;
    }

    /**
     * Get draft RAB budget
     */
    public function getDraftRabBudgetAttribute()
    {
        $draftRab = $this->draftRab;
        return $draftRab ? $draftRab->total_budget : 0;
    }

    /**
     * Check if project has RAB
     */
    public function getHasRabAttribute()
    {
        return $this->rabs()->exists();
    }

    /**
     * Check if project has approved RAB
     */
    public function getHasApprovedRabAttribute()
    {
        return $this->rabs()->approved()->exists();
    }

    /**
     * Check if project has draft RAB
     */
    public function getHasDraftRabAttribute()
    {
        return $this->rabs()->draft()->exists();
    }

    /**
     * Get RAB status summary
     */
    public function getRabStatusSummaryAttribute()
    {
        $totalRabs = $this->rabs()->count();
        $approvedRabs = $this->rabs()->approved()->count();
        $draftRabs = $this->rabs()->draft()->count();
        $submittedRabs = $this->rabs()->submitted()->count();

        return [
            'total' => $totalRabs,
            'approved' => $approvedRabs,
            'draft' => $draftRabs,
            'submitted' => $submittedRabs,
            'has_rab' => $totalRabs > 0,
            'has_approved_rab' => $approvedRabs > 0,
            'latest_status' => $this->rabs()->latest()->first()->status ?? 'none'
        ];
    }

    /**
     * Compare project budget with RAB budget
     */
    public function compareBudgetWithRab()
    {
        $projectBudget = $this->budget_numeric;
        $rabBudget = $this->approved_rab_budget;

        if ($projectBudget == 0 || $rabBudget == 0) {
            return null;
        }

        $difference = $rabBudget - $projectBudget;
        $percentage = ($difference / $projectBudget) * 100;

        return [
            'project_budget' => $projectBudget,
            'rab_budget' => $rabBudget,
            'difference' => $difference,
            'percentage' => $percentage,
            'is_over_budget' => $difference > 0,
            'is_under_budget' => $difference < 0,
            'is_on_budget' => $difference == 0
        ];
    }

    /**
     * Create initial draft RAB untuk project
     */
    public function createInitialRab($name = null)
    {
        try {
            $rabName = $name ?? 'RAB ' . $this->name;

            // Cek apakah sudah ada draft RAB
            $existingDraft = $this->draftRab;
            if ($existingDraft) {
                return $existingDraft;
            }

            $rab = Rab::create([
                'project_id' => $this->id,
                'name' => $rabName,
                'version' => '1.0',
                'status' => 'draft',
                'notes' => 'Rencana Anggaran Biaya awal untuk project ' . $this->name
            ]);

            Log::info('✅ Initial RAB created', [
                'project_id' => $this->id,
                'project_name' => $this->name,
                'rab_id' => $rab->id
            ]);

            return $rab;

        } catch (\Exception $e) {
            Log::error('❌ Failed to create initial RAB', [
                'project_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Duplicate latest RAB sebagai draft baru
     */
    public function duplicateLatestRab($newName = null)
    {
        try {
            $latestRab = $this->rabs()->latest()->first();
            
            if (!$latestRab) {
                return $this->createInitialRab($newName);
            }

            $newRab = $latestRab->duplicate($newName);

            Log::info('✅ RAB duplicated', [
                'project_id' => $this->id,
                'original_rab_id' => $latestRab->id,
                'new_rab_id' => $newRab->id
            ]);

            return $newRab;

        } catch (\Exception $e) {
            Log::error('❌ Failed to duplicate RAB', [
                'project_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get RAB budget breakdown
     */
    public function getRabBudgetBreakdown()
    {
        $currentRab = $this->currentRab;
        
        if (!$currentRab) {
            return [];
        }

        return $currentRab->getBreakdownByCategory();
    }

    /**
     * Get RAB statistics
     */
    public function getRabStatistics()
    {
        $totalRabs = $this->rabs()->count();
        $totalRabBudget = $this->rabs()->sum('total_budget');
        $averageRabBudget = $totalRabs > 0 ? $totalRabBudget / $totalRabs : 0;

        return [
            'total_rabs' => $totalRabs,
            'total_rab_budget' => $totalRabBudget,
            'average_rab_budget' => $averageRabBudget,
            'budget_comparison' => $this->compareBudgetWithRab(),
            'breakdown' => $this->getRabBudgetBreakdown()
        ];
    }

    // ==================== PROJECT MANAGEMENT METHODS ====================

    /**
     * Update project budget
     */
    public function updateBudget($newBudget, $notes = null)
    {
        $oldBudget = $this->budget;
        
        $this->update([
            'budget' => $newBudget
        ]);

        Log::info('💰 Project budget updated', [
            'project_id' => $this->id,
            'old_budget' => $oldBudget,
            'new_budget' => $newBudget,
            'notes' => $notes
        ]);

        return $this;
    }

    /**
     * Update project status
     */
    public function updateStatus($newStatus, $notes = null)
    {
        $oldStatus = $this->status;
        
        $this->update([
            'status' => $newStatus
        ]);

        Log::info('🔄 Project status updated', [
            'project_id' => $this->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes
        ]);

        return $this;
    }

    /**
     * Check if project is active
     */
    public function isActive()
    {
        return $this->status === 'Active';
    }

    /**
     * Check if project is completed
     */
    public function isCompleted()
    {
        return $this->status === 'Completed';
    }

    /**
     * Check if project is overdue
     */
    public function isOverdue()
    {
        if (!$this->end_date || $this->isCompleted()) {
            return false;
        }

        return now()->gt($this->end_date);
    }

    /**
     * Get days remaining (jika ada end_date)
     */
    public function getDaysRemainingAttribute()
    {
        if (!$this->end_date || $this->isCompleted()) {
            return null;
        }

        return now()->diffInDays($this->end_date, false); // negative jika overdue
    }

    // ==================== VALIDATION METHODS ====================

    /**
     * Validation rules untuk create project
     */
    public static function getValidationRules($id = null)
    {
        return [
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'province_id' => 'nullable|exists:provinces,id',
            'location' => 'required|string|max:500',
            'pic' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0|max:999999999999',
            'status' => 'required|in:Planning,Active,On Hold,Completed,Cancelled',
            'description' => 'nullable|string|max:2000'
        ];
    }

    /**
     * Validation messages
     */
    public static function getValidationMessages()
    {
        return [
            'name.required' => 'Nama project wajib diisi',
            'client_id.required' => 'Client wajib dipilih',
            'location.required' => 'Lokasi project wajib diisi',
            'start_date.required' => 'Tanggal mulai wajib diisi',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai',
            'budget.max' => 'Budget terlalu besar',
            'status.in' => 'Status project tidak valid'
        ];
    }

    // ==================== STATISTICAL METHODS ====================

    /**
     * Get project statistics
     */
    public static function getStatistics()
    {
        $totalProjects = self::count();
        $totalBudget = self::sum('budget');
        $activeProjects = self::active()->count();
        $completedProjects = self::completed()->count();

        return [
            'total_projects' => $totalProjects,
            'total_budget' => $totalBudget,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'average_budget' => $totalProjects > 0 ? $totalBudget / $totalProjects : 0
        ];
    }

    /**
     * Get projects by status
     */
    public static function getProjectsByStatus()
    {
        return self::select('status', \DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });
    }

    /**
     * Get projects by province
     */
    public static function getProjectsByProvince()
    {
        return self::with('province')
            ->select('province_id', \DB::raw('COUNT(*) as count'))
            ->groupBy('province_id')
            ->get()
            ->mapWithKeys(function ($item) {
                $provinceName = $item->province ? $item->province->name : 'Unknown';
                return [$provinceName => $item->count];
            });
    }

    // ==================== EXPORT METHODS ====================

    /**
     * Prepare data untuk export
     */
    public function toExportArray()
    {
        return [
            'Kode Project' => $this->code,
            'Nama Project' => $this->name,
            'Client' => $this->client_name,
            'Perusahaan Client' => $this->client_company,
            'Lokasi' => $this->location,
            'Provinsi' => $this->province_name,
            'PIC' => $this->pic ?? '-',
            'Tanggal Mulai' => $this->start_date_formatted,
            'Tanggal Selesai' => $this->end_date_formatted,
            'Durasi (hari)' => $this->duration,
            'Budget Project' => $this->budget_formatted,
            'Status' => $this->status,
            'Progress' => $this->progress_percentage . '%',
            'Deskripsi' => $this->description ?? '-',
            'Total RAB' => $this->rabs()->count(),
            'RAB Approved' => $this->has_approved_rab ? 'Ya' : 'Tidak',
            'Budget RAB Approved' => 'Rp ' . number_format($this->approved_rab_budget, 0, ',', '.')
        ];
    }
}


