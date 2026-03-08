<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseOrder;
use App\Models\Lpb;

class PurchaseVoucher extends Model
{
    use HasFactory;

    protected $table = 'purchase_vouchers';

    protected $fillable = [
        // Relasi
        'project_id',
        'rab_id',
        'vendor_id',
        'purchase_order_id',
        'lpb_id',

        // Snapshot proyek
        'project_code_snapshot',
        'project_name_snapshot',

        // Identitas voucher
        'voucher_no',
        'voucher_date',
        'vendor_name',
        'bank',
        'account_no',
        'account_name',
        'transfer_to',
        'payment_method',
        'invoice_no',
        'payment_purpose',
        'notes',

        // Workflow
        'status',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'submitted_by',
        'approved_by',
        'rejected_by',
        'rejected_reason',
        'due_date',

        // Ringkasan keuangan
        'tax_percent',
        'tax_amount',
        'shipping_cost',
        'subtotal_amount',
        'total_amount',
    ];

    protected $casts = [
        'voucher_date'    => 'date',
        'due_date'        => 'date',
        'tax_percent'     => 'float',
        'tax_amount'      => 'float',
        'shipping_cost'   => 'float',
        'subtotal_amount' => 'float',
        'total_amount'    => 'float',
    ];

    // =========================
    // RELATIONSHIPS
    // =========================

    public function items()
    {
        return $this->hasMany(PurchaseVoucherItem::class, 'purchase_voucher_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function rab()
    {
        return $this->belongsTo(Rab::class, 'rab_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function lpb()
    {
        return $this->belongsTo(Lpb::class, 'lpb_id');
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

        // Staff: hanya saat draft (jika kolom status ada). Jika status null, dianggap draft.
        $status = $this->status ?? 'draft';
        return $status === 'draft' || $status === 'rejected';
    }

    public function canApprove(): bool
    {
        $status = $this->status ?? '';
        return auth()->check()
            && auth()->user()
            && method_exists(auth()->user(), 'isHO')
            && auth()->user()->isHO()
            && $status === 'submitted';
    }

    public function canReject(): bool
    {
        $status = $this->status ?? '';
        return auth()->check()
            && auth()->user()
            && method_exists(auth()->user(), 'isHO')
            && auth()->user()->isHO()
            && $status === 'submitted';
    }

}
