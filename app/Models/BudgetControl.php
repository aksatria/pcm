<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetControl extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'rab_id',
        'kode',
        'uraian',
        'satuan',
        'volume_plan',
        'harga_satuan_plan',
        'jumlah_plan',
        'volume_real',
        'jumlah_real',
        'kategori',
        'status',
        'is_selected',
        'keterangan'
    ];

    protected $casts = [
        'volume_plan' => 'decimal:4',
        'harga_satuan_plan' => 'decimal:2',
        'jumlah_plan' => 'decimal:2',
        'volume_real' => 'decimal:4',
        'jumlah_real' => 'decimal:2',
        'is_selected' => 'boolean'
    ];

    protected $appends = [
        'volume_sisa',
        'jumlah_sisa',
        'percentage',
        'is_over_budget',
        'progress_percentage',
        'progress_color',
        'kategori_label' // DITAMBAHKAN
    ];

    // Accessors untuk menghitung sisa
    public function getVolumeSisaAttribute()
    {
        return bcsub($this->volume_plan, $this->volume_real, 4);
    }

    public function getJumlahSisaAttribute()
    {
        return bcsub($this->jumlah_plan, $this->jumlah_real, 2);
    }

    public function getPercentageAttribute()
    {
        if ($this->jumlah_plan <= 0) {
            return 0;
        }
        return number_format(($this->jumlah_real / $this->jumlah_plan) * 100, 2);
    }

    // DITAMBAHKAN: Accessor untuk label kategori
    public function getKategoriLabelAttribute()
    {
        $labels = [
            'MT' => 'Material',
            'JS' => 'Jasa',
            'SB' => 'Subkontrak',
            'AT' => 'Alat',
            'SR' => 'Sewa Ruang',
            'HO' => 'Honorarium',
            'RN' => 'Ransum',
            'OTHER' => 'Lain-lain'
        ];
        
        return $labels[$this->kategori] ?? $this->kategori;
    }

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function rab()
    {
        return $this->belongsTo(Rab::class);
    }

    public function voucherItems()
    {
        return $this->hasMany(VoucherItem::class);
    }

    // Scopes
    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    public function scopeSelected($query)
    {
        return $query->where('is_selected', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Methods
    public function updateRealisasi()
    {
        $totalQty = $this->voucherItems()
            ->whereIn('status', ['delivered', 'completed'])
            ->sum('qty');
        
        $totalAmount = $this->voucherItems()
            ->whereIn('status', ['delivered', 'completed'])
            ->sum('total_harga');
        
        $this->volume_real = $totalQty ?? 0;
        $this->jumlah_real = $totalAmount ?? 0;
        $this->save();
        
        return $this;
    }

    public function getIsOverBudgetAttribute()
    {
        return $this->jumlah_real > $this->jumlah_plan;
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->jumlah_plan <= 0) {
            return 0;
        }
        return min(100, ($this->jumlah_real / $this->jumlah_plan) * 100);
    }

    public function getProgressColorAttribute()
    {
        $percentage = $this->progress_percentage;
        
        if ($percentage >= 90) return 'danger';
        if ($percentage >= 75) return 'warning';
        if ($percentage >= 50) return 'info';
        return 'success';
    }

    // Helper methods
    public function canBeSelectedForVoucher()
    {
        return $this->status === 'active' 
            && $this->jumlah_sisa > 0
            && $this->volume_sisa > 0;
    }

    public function getRemainingBudget()
    {
        return [
            'volume' => $this->volume_sisa,
            'amount' => $this->jumlah_sisa,
            'percentage' => $this->percentage
        ];
    }

    // DITAMBAHKAN: Method untuk mendapatkan daftar kategori valid
    public static function getValidKategories()
    {
        return [
            'MT' => 'Material',
            'JS' => 'Jasa',
            'SB' => 'Subkontrak',
            'AT' => 'Alat',
            'SR' => 'Sewa Ruang',
            'HO' => 'Honorarium',
            'RN' => 'Ransum',
            'OTHER' => 'Lain-lain'
        ];
    }

    // DITAMBAHKAN: Method untuk validasi kategori
    public static function isValidKategori($kategori)
    {
        $validCategories = array_keys(self::getValidKategories());
        return in_array($kategori, $validCategories);
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($budgetControl) {
            // Validasi kategori
            if (!self::isValidKategori($budgetControl->kategori)) {
                throw new \Exception("Kategori '{$budgetControl->kategori}' tidak valid. Gunakan: " . implode(', ', array_keys(self::getValidKategories())));
            }
            
            // Auto-calculate jumlah_plan if not set
            if (!$budgetControl->jumlah_plan && $budgetControl->volume_plan && $budgetControl->harga_satuan_plan) {
                $budgetControl->jumlah_plan = $budgetControl->volume_plan * $budgetControl->harga_satuan_plan;
            }
        });

        static::created(function ($budgetControl) {
            // Log creation
            \Log::info("Budget Control created: {$budgetControl->kode} - {$budgetControl->kategori}");
        });
    }
}