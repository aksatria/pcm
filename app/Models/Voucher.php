<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'vendor_id',
        'voucher_number',
        'tanggal',
        'tujuan_transfer',
        'bank',
        'nama_rekening',
        'no_rekening',
        'pembayaran',
        'jatuh_tempo',
        'diajukan_oleh',
        'disetujui_oleh',
        'tanggal_pengajuan',
        'tanggal_persetujuan',
        'status',
        'total_tagihan',
        'ppn',
        'ongkir',
        'total_bayar',
        'keterangan',
        'catatan_reject'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jatuh_tempo' => 'date',
        'tanggal_pengajuan' => 'date',
        'tanggal_persetujuan' => 'date',
        'total_tagihan' => 'decimal:2',
        'ppn' => 'decimal:2',
        'ongkir' => 'decimal:2',
        'total_bayar' => 'decimal:2'
    ];

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(VoucherItem::class);
    }

    // Scopes
    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePendingApproval($query)
    {
        return $query->whereIn('status', ['draft', 'submitted']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // Methods
    public function calculateTotals()
    {
        $totalTagihan = $this->items()->sum('total_harga');
        $this->total_tagihan = $totalTagihan;
        $this->total_bayar = $totalTagihan + ($this->ppn ?? 0) + ($this->ongkir ?? 0);
        $this->save();
    }

    public function approve($approvedBy)
    {
        $this->status = 'approved';
        $this->disetujui_oleh = $approvedBy;
        $this->tanggal_persetujuan = now();
        $this->save();

        // Update realisasi budget controls
        foreach ($this->items as $item) {
            if ($item->budgetControl) {
                $item->budgetControl->updateRealisasi();
            }
        }
    }

    public function markAsPaid()
    {
        $this->status = 'paid';
        $this->save();
    }

    public function complete()
    {
        $this->status = 'completed';
        $this->save();

        // Update semua items ke status completed
        $this->items()->update(['status' => 'completed']);
    }

    public function reject($reason, $rejectedBy)
    {
        $this->status = 'rejected';
        $this->catatan_reject = $reason;
        $this->disetujui_oleh = $rejectedBy;
        $this->save();
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'draft' => 'secondary',
            'submitted' => 'info',
            'approved' => 'primary',
            'paid' => 'warning',
            'completed' => 'success',
            'rejected' => 'danger',
            default => 'secondary'
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'paid' => 'Dibayar',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
            default => 'Unknown'
        };
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($voucher) {
            if (!$voucher->voucher_number) {
                $voucher->voucher_number = self::generateVoucherNumber($voucher->project_id);
            }
            
            // Set tanggal pengajuan jika status submitted
            if ($voucher->status === 'submitted') {
                $voucher->tanggal_pengajuan = now();
            }
        });

        static::created(function ($voucher) {
            // Hitung total setelah item ditambahkan
            $voucher->calculateTotals();
        });

        static::updated(function ($voucher) {
            // Jika voucher disetujui, update budget controls
            if ($voucher->isDirty('status') && $voucher->status === 'approved') {
                foreach ($voucher->items as $item) {
                    if ($item->budgetControl) {
                        $item->budgetControl->updateRealisasi();
                    }
                }
            }
        });
    }

    private static function generateVoucherNumber($projectId)
    {
        $project = Project::find($projectId);
        $projectCode = $project ? strtoupper(substr($project->code ?? $project->name, 0, 4)) : 'PROJ';
        
        $count = self::withTrashed()
            ->where('project_id', $projectId)
            ->whereYear('created_at', date('Y'))
            ->count() + 1;
        
        return 'VCH/' . $projectCode . '/' . date('Y') . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // =========================
    // ACCESS CONTROL (Staff vs HO)
    // =========================

    public function canEdit(): bool
    {
        // HO boleh edit kapan pun
        if (auth()->check() && auth()->user() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO()) {
            return true;
        }

        // Staff hanya boleh saat draft
        return ($this->status ?? '') === 'draft';
    }

    public function canApprove(): bool
    {
        return auth()->check()
            && auth()->user()
            && method_exists(auth()->user(), 'isHO')
            && auth()->user()->isHO()
            && ($this->status ?? '') === 'submitted';
    }

    public function canReject(): bool
    {
        return auth()->check()
            && auth()->user()
            && method_exists(auth()->user(), 'isHO')
            && auth()->user()->isHO()
            && ($this->status ?? '') === 'submitted';
    }

}
