<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseVoucherItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_voucher_items';

    protected $fillable = [
        // Relasi
        'purchase_voucher_id',
        'rab_item_id',

        // Transaksi voucher (nota)
        'qty',
        'unit',
        'description',
        'price',
        'amount',

        // Snapshot RAB Baseline (untuk kontrol Excel)
        'rab_harga_satuan_snapshot',
        'rab_volume_snapshot',
    ];

    protected $casts = [
        'qty'                     => 'float',
        'price'                   => 'float',
        'amount'                  => 'float',
        'rab_harga_satuan_snapshot' => 'float',
        'rab_volume_snapshot'     => 'float',
    ];

    // =========================
    // RELATIONSHIPS
    // =========================

    public function voucher()
    {
        return $this->belongsTo(PurchaseVoucher::class, 'purchase_voucher_id');
    }

    public function rabItem()
    {
        return $this->belongsTo(RabItem::class, 'rab_item_id');
    }
}

