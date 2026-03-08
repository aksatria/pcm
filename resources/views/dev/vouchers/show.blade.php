{{-- resources/views/dev/vouchers/show.blade.php --}}
@extends('layouts.dev')

@section('title', 'Detail Voucher - ' . $voucher->voucher_number)

@section('styles')
<style>
    .item-row:hover {
        background-color: #f8f9fa;
    }
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #dee2e6;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -26px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #0d6efd;
        border: 2px solid white;
    }
    .timeline-item.completed::before {
        background-color: #198754;
    }
    .timeline-item.rejected::before {
        background-color: #dc3545;
    }
    .timeline-item.approved::before {
        background-color: #ffc107;
    }
</style>
@endsection

@section('content')
@php
  $status = $voucher->status ?? 'draft';
  $statusClass = match($status) {
    'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    'submitted' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
    'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    'paid', 'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
  };
  $alertClass = match($voucher->status_alert ?? 'info') {
    'success' => 'border-emerald-200 bg-emerald-50/80 text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-200',
    'warning' => 'border-amber-200 bg-amber-50/80 text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200',
    'danger' => 'border-rose-200 bg-rose-50/80 text-rose-800 dark:border-rose-900/40 dark:bg-rose-900/20 dark:text-rose-200',
    default => 'border-blue-200 bg-blue-50/80 text-blue-800 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-200',
  };
@endphp
<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
    <!-- Header -->
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-lg font-semibold">Detail Voucher</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Proyek: {{ $project->name ?? '-' }} • No: {{ $voucher->voucher_number }}
                • Tanggal: {{ $voucher->tanggal->format('d M Y') }}
                @if($voucher->jatuh_tempo)
                • Jatuh Tempo: {{ $voucher->jatuh_tempo->format('d M Y') }}
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $statusClass }}">
                {{ $voucher->status_label ?? $status }}
            </span>
            <div class="text-right">
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Total Bayar</div>
                <div class="text-lg font-semibold">Rp {{ number_format($voucher->total_bayar, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-2">
                <a href="{{ route('dev.vouchers.index', $project->id) }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800">
                    Kembali
                </a>
                <a href="{{ route('dev.vouchers.print', ['projectId' => $project->id, 'id' => $voucher->id]) }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800">
                    Print
                </a>
                <a href="{{ route('dev.vouchers.pdf', ['projectId' => $project->id, 'id' => $voucher->id]) }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800">
                    Export PDF
                </a>
                
                @if($canEdit)
                <a href="{{ route('dev.vouchers.edit', ['projectId' => $project->id, 'id' => $voucher->id]) }}" 
                   class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">
                    Edit
                </a>
                @endif
                
                @if($canSubmit)
                <button type="button" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium" onclick="submitVoucher()">
                    Submit
                </button>
                @endif
                
                @if($canApprove)
                <button type="button" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium" onclick="approveVoucher()">
                    Approve
                </button>
                @endif
                
                @if($canReject)
                <button type="button" class="inline-flex items-center px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium" onclick="rejectVoucher()">
                    Reject
                </button>
                @endif
                
                @if($canMarkPaid)
                <button type="button" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium" onclick="markAsPaid()">
                    Mark as Paid
                </button>
                @endif
                
                @if($canMarkCompleted)
                <button type="button" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-medium" onclick="markAsCompleted()">
                    Mark as Completed
                </button>
                @endif
                
                <div class="dropdown relative">
                    <button type="button" class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="#" onclick="duplicateVoucher()">
                                <i class="fas fa-copy"></i> Duplicate Voucher
                            </a>
                        </li>
                        @if($voucher->status === 'draft')
                        <li>
                            <a class="dropdown-item text-danger" href="#" onclick="deleteVoucher()">
                                <i class="fas fa-trash"></i> Delete Voucher
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column: Voucher Details -->
        <div class="lg:col-span-8 space-y-6">
            <!-- Vendor Information -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="text-sm font-semibold">Informasi Vendor</h2>
                </div>
                <div class="p-5">
                    <div class="row">
                        @if($voucher->vendor)
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="120">Nama Vendor</th>
                                    <td>{{ $voucher->vendor->nama }}</td>
                                </tr>
                                <tr>
                                    <th>Perusahaan</th>
                                    <td>{{ $voucher->vendor->perusahaan ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Bank</th>
                                    <td>{{ $voucher->bank ?? $voucher->vendor->bank ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="120">No. Rekening</th>
                                    <td>{{ $voucher->no_rekening ?? $voucher->vendor->no_rekening ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Nama Rekening</th>
                                    <td>{{ $voucher->nama_rekening ?? $voucher->vendor->nama_rekening ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Tujuan Transfer</th>
                                    <td>{{ $voucher->tujuan_transfer ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        @else
                        <div class="col-md-12">
                            <div class="rounded-xl border border-blue-200 dark:border-blue-900/40 bg-blue-50/80 dark:bg-blue-900/20 p-4 text-sm text-blue-800 dark:text-blue-200">
                                <i class="fas fa-info-circle mr-1"></i> Tidak ada informasi vendor
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Voucher Items -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h2 class="text-sm font-semibold">Items Voucher</h2>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ $voucher->items->count() }} items</span>
                        @if($canEdit)
                        <button type="button" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 text-xs hover:bg-gray-50 dark:hover:bg-gray-800" data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="fas fa-plus"></i> Tambah Item
                        </button>
                        @endif
                    </div>
                </div>
                <div class="p-0 overflow-x-auto">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">#</th>
                                    <th width="100">KODE</th>
                                    <th>URAIAN</th>
                                    <th width="80">SAT</th>
                                    <th width="100" class="text-end">QTY</th>
                                    <th width="120" class="text-end">HARGA SATUAN</th>
                                    <th width="120" class="text-end">TOTAL</th>
                                    <th width="100">STATUS</th>
                                    @if($canEdit || $canMarkPaid)
                                    <th width="80">AKSI</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($voucher->items as $item)
                                <tr class="item-row">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                            {{ $item->kode }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>{{ $item->uraian }}</div>
                                        @if($item->keterangan)
                                        <small class="text-muted">{{ $item->keterangan }}</small>
                                        @endif
                                        {{-- Budget Control removed --}}
                                    </td>
                                    <td>{{ $item->satuan }}</td>
                                    <td class="text-end">{{ number_format($item->qty, 2, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($item->total_harga, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold
                                            @if($item->status == 'completed') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200
                                            @elseif($item->status == 'delivered') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200
                                            @else bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 @endif">
                                            {{ $item->status }}
                                        </span>
                                    </td>
                                    @if($canEdit || $canMarkPaid)
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if($canEdit)
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="editItem({{ $item->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deleteItem({{ $item->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endif
                                            @if($canMarkPaid && in_array($voucher->status, ['approved', 'paid']))
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="updateItemStatus({{ $item->id }}, 'delivered')"
                                                    title="Mark as Delivered">
                                                <i class="fas fa-truck"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="6" class="text-end">SUBTOTAL</th>
                                    <th class="text-end">Rp {{ number_format($voucher->total_tagihan, 0, ',', '.') }}</th>
                                    <th colspan="{{ $canEdit || $canMarkPaid ? 2 : 1 }}"></th>
                                </tr>
                                @if($voucher->ppn > 0)
                                <tr>
                                    <th colspan="6" class="text-end">PPN (11%)</th>
                                    <th class="text-end">Rp {{ number_format($voucher->ppn, 0, ',', '.') }}</th>
                                    <th colspan="{{ $canEdit || $canMarkPaid ? 2 : 1 }}"></th>
                                </tr>
                                @endif
                                @if($voucher->ongkir > 0)
                                <tr>
                                    <th colspan="6" class="text-end">ONGKIR</th>
                                    <th class="text-end">Rp {{ number_format($voucher->ongkir, 0, ',', '.') }}</th>
                                    <th colspan="{{ $canEdit || $canMarkPaid ? 2 : 1 }}"></th>
                                </tr>
                                @endif
                                <tr class="table-primary">
                                    <th colspan="6" class="text-end">TOTAL BAYAR</th>
                                    <th class="text-end fs-5">Rp {{ number_format($voucher->total_bayar, 0, ',', '.') }}</th>
                                    <th colspan="{{ $canEdit || $canMarkPaid ? 2 : 1 }}"></th>
                                </tr>
                            </tfoot>
                        </table>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="text-sm font-semibold">Informasi Tambahan</h2>
                </div>
                <div class="p-5">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="120">Metode Bayar</th>
                                    <td>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                            {{ strtoupper($voucher->pembayaran) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Diajukan Oleh</th>
                                    <td>{{ $voucher->diajukan_oleh ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Disetujui Oleh</th>
                                    <td>{{ $voucher->disetujui_oleh ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="120">Created At</th>
                                    <td>{{ $voucher->created_at->format('d F Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Updated At</th>
                                    <td>{{ $voucher->updated_at->format('d F Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Project</th>
                                    <td>
                                        <a href="{{ route('dev.projects.show', $project->id) }}" class="text-info">
                                            {{ $project->name }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($voucher->keterangan)
                    <div class="mt-3">
                        <h6>Keterangan:</h6>
                        <p class="mb-0">{{ $voucher->keterangan }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Timeline & Status -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Timeline -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="text-sm font-semibold">Timeline</h2>
                </div>
                <div class="p-5">
                    <div class="timeline">
                        <div class="timeline-item">
                            <h6>Voucher Created</h6>
                            <p class="text-muted mb-1">{{ $voucher->created_at->format('d F Y H:i') }}</p>
                            <p class="mb-0">Status: 
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">Draft</span>
                            </p>
                        </div>
                        
                        @if($voucher->tanggal_pengajuan)
                        <div class="timeline-item">
                            <h6>Submitted for Approval</h6>
                            <p class="text-muted mb-1">{{ $voucher->tanggal_pengajuan->format('d F Y H:i') }}</p>
                            <p class="mb-0">
                                Diajukan oleh: <strong>{{ $voucher->diajukan_oleh ?? 'System' }}</strong>
                            </p>
                        </div>
                        @endif
                        
                        @if($voucher->tanggal_persetujuan)
                        <div class="timeline-item {{ $voucher->status === 'approved' ? 'approved' : '' }}">
                            <h6>
                                @if($voucher->status === 'approved')
                                Approved
                                @elseif($voucher->status === 'rejected')
                                Rejected
                                @endif
                            </h6>
                            <p class="text-muted mb-1">{{ $voucher->tanggal_persetujuan->format('d F Y H:i') }}</p>
                            <p class="mb-0">
                                Disetujui oleh: <strong>{{ $voucher->disetujui_oleh ?? 'System' }}</strong>
                                @if($voucher->catatan_reject)
                                <br>Catatan: {{ $voucher->catatan_reject }}
                                @endif
                            </p>
                        </div>
                        @endif
                        
                        @if($voucher->tanggal_pembayaran)
                        <div class="timeline-item">
                            <h6>Marked as Paid</h6>
                            <p class="text-muted mb-1">{{ $voucher->tanggal_pembayaran->format('d F Y H:i') }}</p>
                            <p class="mb-0">Status: 
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Paid</span>
                            </p>
                        </div>
                        @endif
                        
                        @if($voucher->status === 'completed')
                        <div class="timeline-item completed">
                            <h6>Completed</h6>
                            <p class="text-muted mb-1">{{ $voucher->updated_at->format('d F Y H:i') }}</p>
                            <p class="mb-0">Status: 
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">Completed</span>
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Status Information -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="text-sm font-semibold">Status Information</h2>
                </div>
                <div class="p-5">
                    <div class="mb-3">
                        <h6>Current Status:</h6>
                        <div class="rounded-xl border p-4 text-sm {{ $alertClass }}">
                            <strong>{{ $voucher->status_label }}</strong>
                            <p class="mb-0 mt-2">
                                @switch($voucher->status)
                                    @case('draft')
                                        Voucher masih dalam tahap draft dan dapat diedit.
                                        @break
                                    @case('submitted')
                                        Voucher telah diajukan dan menunggu persetujuan.
                                        @break
                                    @case('approved')
                                        Voucher telah disetujui dan siap untuk dibayar.
                                        @break
                                    @case('paid')
                                        Voucher telah dibayar dan menunggu konfirmasi penerimaan barang/jasa.
                                        @break
                                    @case('completed')
                                        Voucher telah selesai diproses.
                                        @break
                                    @case('rejected')
                                        Voucher ditolak dengan alasan: {{ $voucher->catatan_reject ?? 'Tidak ada alasan spesifik' }}
                                        @break
                                @endswitch
                            </p>
                        </div>
                    </div>
                    
                    @if($voucher->status === 'rejected')
                    <div class="rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/80 dark:bg-rose-900/20 p-4 text-sm text-rose-800 dark:text-rose-200">
                        <h6>Alasan Penolakan:</h6>
                        <p class="mb-0">{{ $voucher->catatan_reject ?? 'Tidak ada alasan spesifik' }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="text-sm font-semibold">Quick Stats</h2>
                </div>
                <div class="p-5">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h3>{{ $voucher->items->count() }}</h3>
                                <small class="text-muted">Total Items</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h3>{{ $voucher->items->where('status', 'completed')->count() }}</h3>
                                <small class="text-muted">Items Completed</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <small class="text-muted">Rata-rata Harga per Item:</small>
                            <h5>Rp {{ number_format($voucher->items->avg('harga_satuan') ?? 0, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
@if($canEdit)
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Item Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Pilih Item dari Control Budget</label>
                        <select class="form-select" id="budgetControlSelect">
                            <option value="">-- Pilih Item --</option>
                            <!-- Items will be loaded via AJAX -->
                        </select>
                    </div>
                    
                    <div id="itemDetails" class="d-none">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Kode</label>
                                <input type="text" class="form-control" id="itemKode" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Uraian</label>
                                <input type="text" class="form-control" id="itemUraian" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Satuan</label>
                                <input type="text" class="form-control" id="itemSatuan" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Available Qty</label>
                                <input type="text" class="form-control" id="itemAvailableQty" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Harga Plan</label>
                                <input type="text" class="form-control" id="itemHargaPlan" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Quantity *</label>
                                <input type="number" step="0.0001" class="form-control" id="itemQty" 
                                       min="0.0001" required>
                                <small class="text-muted" id="maxQtyInfo"></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga Satuan *</label>
                                <input type="number" step="0.01" class="form-control" id="itemHargaSatuan" 
                                       min="0" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Keterangan (Opsional)</label>
                            <textarea class="form-control" id="itemKeterangan" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="addNewItem()">Tambah Item</button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editItemForm">
                    @csrf
                    <input type="hidden" id="editItemId">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Quantity *</label>
                            <input type="number" step="0.0001" class="form-control" id="editItemQty" 
                                   min="0.0001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Harga Satuan *</label>
                            <input type="number" step="0.01" class="form-control" id="editItemHargaSatuan" 
                                   min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" id="editItemKeterangan" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="updateItem()">Update Item</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/40" onclick="closeRejectModal()"></div>
    <div class="relative w-full max-w-md rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-xl">
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Tolak Voucher</div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Isi alasan agar pengaju mengetahui perbaikan yang diperlukan.</p>
        <form id="rejectForm" class="mt-3">
            @csrf
            <textarea class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-200 px-3 py-2" name="reason" rows="3" required placeholder="Berikan alasan penolakan..."></textarea>
        </form>
        <div class="mt-4 flex items-center justify-end gap-2">
            <button type="button" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-200" onclick="closeRejectModal()">Batal</button>
            <button type="button" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold" onclick="submitReject()">Tolak Voucher</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus voucher ini?</p>
                <p class="text-danger"><strong>Perhatian:</strong> Aksi ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">Hapus Voucher</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentItemId = null;
    
    // Budget Control removed
    
    function showItemDetails(item) {
        document.getElementById('itemDetails').classList.remove('d-none');
        document.getElementById('itemKode').value = item.kode;
        document.getElementById('itemUraian').value = item.uraian;
        document.getElementById('itemSatuan').value = item.satuan;
        document.getElementById('itemAvailableQty').value = item.available_qty.toFixed(4);
        document.getElementById('itemHargaPlan').value = 'Rp ' + formatNumber(item.harga_satuan_plan);
        document.getElementById('itemHargaSatuan').value = item.harga_satuan_plan;
        document.getElementById('maxQtyInfo').textContent = `Max: ${item.available_qty.toFixed(4)} ${item.satuan}`;
        
        // Set max for qty input
        const qtyInput = document.getElementById('itemQty');
        qtyInput.max = item.available_qty;
        qtyInput.value = Math.min(1, item.available_qty);
    }
    
    function hideItemDetails() {
        document.getElementById('itemDetails').classList.add('d-none');
    }
    
    function addNewItem() {
        const select = document.getElementById('budgetControlSelect');
        if (!select.value) {
            alert('Pilih item terlebih dahulu');
            return;
        }
        
        const item = JSON.parse(select.options[select.selectedIndex].dataset.item);
        const qty = parseFloat(document.getElementById('itemQty').value) || 0;
        const hargaSatuan = parseFloat(document.getElementById('itemHargaSatuan').value) || 0;
        const keterangan = document.getElementById('itemKeterangan').value;
        
        if (qty <= 0) {
            alert('Quantity harus lebih dari 0');
            return;
        }
        
        if (qty > item.available_qty) {
            alert(`Quantity tidak boleh melebihi ${item.available_qty}`);
            return;
        }
        
        const formData = new FormData();
        formData.append('budget_control_id', item.id);
        formData.append('qty', qty);
        formData.append('harga_satuan', hargaSatuan);
        formData.append('keterangan', keterangan);
        
        fetch(`{{ route('dev.vouchers.items.store', ['projectId' => $project->id, 'id' => $voucher->id]) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal menambahkan item: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        });
    }
    
    function editItem(itemId) {
        currentItemId = itemId;
        
        // Get current item data
        fetch(`{{ route('dev.vouchers.show', ['projectId' => $project->id, 'id' => $voucher->id]) }}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = data.voucher.items.find(i => i.id == itemId);
                    if (item) {
                        document.getElementById('editItemId').value = item.id;
                        document.getElementById('editItemQty').value = item.qty;
                        document.getElementById('editItemHargaSatuan').value = item.harga_satuan;
                        document.getElementById('editItemKeterangan').value = item.keterangan || '';
                        
                        const modal = new bootstrap.Modal(document.getElementById('editItemModal'));
                        modal.show();
                    }
                }
            });
    }
    
    function updateItem() {
        const formData = new FormData();
        formData.append('qty', document.getElementById('editItemQty').value);
        formData.append('harga_satuan', document.getElementById('editItemHargaSatuan').value);
        formData.append('keterangan', document.getElementById('editItemKeterangan').value);
        
        fetch(`{{ route('dev.vouchers.items.update', ['projectId' => $project->id, 'id' => $voucher->id, 'itemId' => '']) }}/${currentItemId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal update item: ' + data.message);
            }
        });
    }
    
    function deleteItem(itemId) {
        if (confirm('Hapus item ini dari voucher?')) {
            fetch(`{{ route('dev.vouchers.items.destroy', ['projectId' => $project->id, 'id' => $voucher->id, 'itemId' => '']) }}/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Gagal menghapus item: ' + data.message);
                }
            });
        }
    }
    
    function updateItemStatus(itemId, status) {
        const formData = new FormData();
        formData.append('status', status);
        
        fetch(`{{ route('dev.vouchers.items.update-status', ['projectId' => $project->id, 'id' => $voucher->id, 'itemId' => '']) }}/${itemId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal update status: ' + data.message);
            }
        });
    }
    
    function submitVoucher() {
        if (confirm('Submit voucher untuk persetujuan?')) {
            fetch(`{{ route('dev.vouchers.submit', ['projectId' => $project->id, 'id' => $voucher->id]) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => {
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Gagal submit voucher');
                }
            });
        }
    }
    
    function approveVoucher() {
        if (confirm('Approve voucher ini?')) {
            fetch(`{{ route('dev.vouchers.approve', ['projectId' => $project->id, 'id' => $voucher->id]) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => {
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Gagal approve voucher');
                }
            });
        }
    }
    
    function openRejectModal() {
        const modal = document.getElementById('rejectModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeRejectModal() {
        const modal = document.getElementById('rejectModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function rejectVoucher() {
        openRejectModal();
    }
    
    function submitReject() {
        const form = document.getElementById('rejectForm');
        const formData = new FormData(form);
        
        fetch(`{{ route('dev.vouchers.reject', ['projectId' => $project->id, 'id' => $voucher->id]) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => {
            if (response.ok) {
                closeRejectModal();
                location.reload();
            } else {
                alert('Gagal reject voucher');
            }
        });
    }
    
    function markAsPaid() {
        if (confirm('Tandai voucher sebagai sudah dibayar?')) {
            fetch(`{{ route('dev.vouchers.mark-paid', ['projectId' => $project->id, 'id' => $voucher->id]) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => {
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Gagal menandai sebagai dibayar');
                }
            });
        }
    }
    
    function markAsCompleted() {
        if (confirm('Tandai voucher sebagai selesai?')) {
            fetch(`{{ route('dev.vouchers.mark-completed', ['projectId' => $project->id, 'id' => $voucher->id]) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => {
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Gagal menandai sebagai selesai');
                }
            });
        }
    }
    
    function duplicateVoucher() {
        if (confirm('Duplicate voucher ini?')) {
            // TODO: Implement duplicate functionality
            alert('Fitur duplikasi akan segera tersedia');
        }
    }
    
    function deleteVoucher() {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
    
    function confirmDelete() {
        fetch(`{{ route('dev.vouchers.destroy', ['projectId' => $project->id, 'id' => $voucher->id]) }}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => {
            if (response.ok) {
                window.location.href = `{{ route('dev.vouchers.index', $project->id) }}`;
            } else {
                alert('Gagal menghapus voucher');
            }
        });
    }
    
    function formatNumber(num) {
        return num.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
</script>
@endsection

