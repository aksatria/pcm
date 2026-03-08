{{-- resources/views/dev/vouchers/index.blade.php --}}
@extends('layouts.dev')

@section('title', 'Vouchers - ' . $project->name)

@section('content')
@php
    $statusClass = function($status) {
        return match($status) {
            'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            'submitted' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
            'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
            'paid', 'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        };
    };
@endphp
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="d-flex align-items-center">
                <a href="{{ route('dev.projects.show', $project->id) }}" class="btn btn-light btn-sm me-2">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="mb-1">Voucher Management</h4>
                    <p class="mb-0 text-muted">
                        Project: <strong>{{ $project->name }}</strong> | 
                        Code: <strong>{{ $project->code }}</strong>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('dev.vouchers.create', $project->id) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Buat Voucher Baru
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Voucher</h6>
                    <h3 class="card-title text-primary">{{ $stats['total'] }}</h3>
                    <small>Semua status</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Menunggu Persetujuan</h6>
                    <h3 class="card-title text-info">{{ $stats['submitted'] }}</h3>
                    <small>Status: Submitted</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Disetujui</h6>
                    <h3 class="card-title text-success">{{ $stats['approved'] }}</h3>
                    <small>Status: Approved</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Nilai</h6>
                    <h3 class="card-title text-warning">Rp {{ number_format($stats['total_amount'], 0, ',', '.') }}</h3>
                    <small>Semua voucher</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Dari</label>
                    <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Sampai</label>
                    <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Vouchers Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Daftar Voucher</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th width="150">VOUCHER #</th>
                            <th width="100">TANGGAL</th>
                            <th>VENDOR</th>
                            <th width="100">STATUS</th>
                            <th width="120" class="text-end">TOTAL TAGIHAN</th>
                            <th width="120" class="text-end">TOTAL BAYAR</th>
                            <th width="100">PEMBAYARAN</th>
                            <th width="150" class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $voucher)
                        <tr>
                            <td>
                                <input type="checkbox" class="voucher-checkbox" value="{{ $voucher->id }}">
                            </td>
                            <td>
                                <strong>{{ $voucher->voucher_number }}</strong><br>
                                <small class="text-muted">{{ $voucher->items_count }} items</small>
                            </td>
                            <td>{{ $voucher->tanggal->format('d/m/Y') }}</td>
                            <td>
                                @if($voucher->vendor)
                                <div>
                                    <strong>{{ $voucher->vendor->nama }}</strong><br>
                                    <small class="text-muted">{{ $voucher->vendor->perusahaan }}</small>
                                </div>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php $st = $voucher->status ?? 'draft'; @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $statusClass($st) }}">
                                    {{ $voucher->status_label ?? $st }}
                                </span>
                            </td>
                            <td class="text-end fw-bold">
                                Rp {{ number_format($voucher->total_tagihan, 0, ',', '.') }}
                            </td>
                            <td class="text-end fw-bold">
                                Rp {{ number_format($voucher->total_bayar, 0, ',', '.') }}
                            </td>
                            <td>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                    {{ strtoupper($voucher->pembayaran) }}
                                </span>
                                @if($voucher->jatuh_tempo)
                                <br>
                                <small class="text-muted">Jatuh: {{ $voucher->jatuh_tempo->format('d/m/Y') }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('dev.vouchers.show', ['projectId' => $project->id, 'id' => $voucher->id]) }}" 
                                       class="btn btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($voucher->status === 'draft')
                                    <a href="{{ route('dev.vouchers.edit', ['projectId' => $project->id, 'id' => $voucher->id]) }}" 
                                       class="btn btn-outline-info" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif
                                    @if($voucher->status === 'submitted')
                                    <button class="btn btn-outline-success" 
                                            onclick="approveVoucher({{ $voucher->id }})"
                                            title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="rejectVoucher({{ $voucher->id }})"
                                            title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-file-invoice-dollar fa-2x mb-2"></i>
                                    <p>Belum ada data voucher</p>
                                    <a href="{{ route('dev.vouchers.create', $project->id) }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Buat Voucher Pertama
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Bulk Actions -->
        @if($vouchers->count() > 0)
        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAllVouchers()">
                            <i class="fas fa-check-square"></i> Pilih Semua
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="deselectAllVouchers()">
                            <i class="far fa-square"></i> Batal Pilih
                        </button>
                        <button type="button" class="btn btn-success btn-sm" onclick="bulkApprove()" id="bulkApproveBtn" disabled>
                            <i class="fas fa-check"></i> Approve Selected
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="bulkReject()" id="bulkRejectBtn" disabled>
                            <i class="fas fa-times"></i> Reject Selected
                        </button>
                    </div>
                    <span id="selectedCount" class="ms-2 text-muted">0 voucher terpilih</span>
                </div>
                <div class="col-md-6 text-end">
                    {{ $vouchers->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tolak Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan *</label>
                        <textarea class="form-control" name="reason" rows="3" required 
                                  placeholder="Berikan alasan penolakan..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="submitReject()">Tolak Voucher</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Reject Modal -->
<div class="modal fade" id="bulkRejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tolak Voucher Terpilih</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkRejectForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan *</label>
                        <textarea class="form-control" name="reason" rows="3" required 
                                  placeholder="Berikan alasan penolakan..."></textarea>
                    </div>
                    <div class="rounded-xl border border-amber-200 dark:border-amber-900/40 bg-amber-50/80 dark:bg-amber-900/20 p-3 text-xs text-amber-800 dark:text-amber-200">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Anda akan menolak <span id="bulkRejectCount">0</span> voucher.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="submitBulkReject()">Tolak Semua</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let selectedVouchers = new Set();
    let currentVoucherId = null;

    document.addEventListener('DOMContentLoaded', function() {
        updateSelectedCount();
    });

    document.getElementById('selectAll').addEventListener('change', function(e) {
        const checkboxes = document.querySelectorAll('.voucher-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = e.target.checked;
            if (e.target.checked) {
                selectedVouchers.add(cb.value);
            } else {
                selectedVouchers.delete(cb.value);
            }
        });
        updateSelectedCount();
    });

    document.querySelectorAll('.voucher-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) {
                selectedVouchers.add(this.value);
            } else {
                selectedVouchers.delete(this.value);
            }
            updateSelectedCount();
        });
    });

    function selectAllVouchers() {
        document.querySelectorAll('.voucher-checkbox').forEach(cb => {
            cb.checked = true;
            selectedVouchers.add(cb.value);
        });
        updateSelectedCount();
    }

    function deselectAllVouchers() {
        document.querySelectorAll('.voucher-checkbox').forEach(cb => {
            cb.checked = false;
            selectedVouchers.delete(cb.value);
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const count = selectedVouchers.size;
        document.getElementById('selectedCount').textContent = `${count} voucher terpilih`;
        
        const bulkApproveBtn = document.getElementById('bulkApproveBtn');
        const bulkRejectBtn = document.getElementById('bulkRejectBtn');
        
        if (bulkApproveBtn) bulkApproveBtn.disabled = count === 0;
        if (bulkRejectBtn) bulkRejectBtn.disabled = count === 0;
        
        if (document.getElementById('bulkRejectCount')) {
            document.getElementById('bulkRejectCount').textContent = count;
        }
    }

    function approveVoucher(voucherId) {
        if (confirm('Approve voucher ini?')) {
            fetch(`{{ route('dev.vouchers.approve', [$project->id, '']) }}/${voucherId}`, {
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

    function rejectVoucher(voucherId) {
        currentVoucherId = voucherId;
        const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
        modal.show();
    }

    function submitReject() {
        const form = document.getElementById('rejectForm');
        const formData = new FormData(form);
        
        fetch(`{{ route('dev.vouchers.reject', [$project->id, '']) }}/${currentVoucherId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Gagal reject voucher');
            }
        });
    }

    function bulkApprove() {
        if (selectedVouchers.size === 0) {
            alert('Pilih minimal 1 voucher');
            return;
        }
        
        if (confirm(`Approve ${selectedVouchers.size} voucher terpilih?`)) {
            fetch(`{{ route('dev.vouchers.bulk-approve', $project->id) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    voucher_ids: Array.from(selectedVouchers)
                })
            })
            .then(response => {
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Gagal bulk approve');
                }
            });
        }
    }

    function bulkReject() {
        if (selectedVouchers.size === 0) {
            alert('Pilih minimal 1 voucher');
            return;
        }
        
        const modal = new bootstrap.Modal(document.getElementById('bulkRejectModal'));
        modal.show();
    }

    function submitBulkReject() {
        const form = document.getElementById('bulkRejectForm');
        const formData = new FormData(form);
        
        // Add voucher IDs to form data
        formData.append('voucher_ids', JSON.stringify(Array.from(selectedVouchers)));
        
        fetch(`{{ route('dev.vouchers.bulk-reject', $project->id) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Gagal bulk reject');
            }
        });
    }
</script>
@endsection
