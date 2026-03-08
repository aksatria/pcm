<?php
// app/Models/VoucherItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoucherItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'voucher_id',
        'budget_control_id',
        'data_id',
        'kode',
        'uraian',
        'qty',
        'satuan',
        'harga_satuan',
        'total_harga',
        'status',
        'tanggal_pesan',
        'tanggal_terima',
        'keterangan',
        'urutan'
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
        'tanggal_pesan' => 'date',
        'tanggal_terima' => 'date'
    ];

    // Relationships
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function budgetControl()
    {
        return $this->belongsTo(BudgetControl::class);
    }

    public function data()
    {
        return $this->belongsTo(Data::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDelivered($query)
    {
        return $query->whereIn('status', ['delivered', 'completed']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Hitung total harga jika belum dihitung
            if (!$item->total_harga && $item->qty && $item->harga_satuan) {
                $item->total_harga = $item->qty * $item->harga_satuan;
            }
            
            // Set status default
            if (!$item->status) {
                $item->status = 'pending';
            }
        });

        static::created(function ($item) {
            // Update total voucher
            if ($item->voucher) {
                $item->voucher->calculateTotals();
            }
        });

        static::updated(function ($item) {
            // Jika harga atau qty berubah, update total
            if ($item->isDirty(['qty', 'harga_satuan'])) {
                $item->total_harga = $item->qty * $item->harga_satuan;
                $item->saveQuietly(); // Save tanpa trigger event lagi
                
                // Update total voucher
                if ($item->voucher) {
                    $item->voucher->calculateTotals();
                }
            }
        });

        static::deleted(function ($item) {
            // Update total voucher jika item dihapus
            if ($item->voucher) {
                $item->voucher->calculateTotals();
            }
        });
    }

    // Methods
    public function markAsDelivered($deliveryDate = null)
    {
        $this->status = 'delivered';
        $this->tanggal_terima = $deliveryDate ?? now();
        $this->save();

        // Update realisasi di budget control
        if ($this->budgetControl) {
            $this->budgetControl->updateRealisasi();
        }
    }

    public function markAsCompleted()
    {
        $this->status = 'completed';
        $this->save();
    }

    // Accessor untuk status label
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'Pending',
            'ordered' => 'Dipesan',
            'delivered' => 'Diterima',
            'completed' => 'Selesai',
            default => $this->status
        };
    }

    // Accessor untuk status color
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'ordered' => 'info',
            'delivered' => 'primary',
            'completed' => 'success',
            default => 'secondary'
        };
    }
}