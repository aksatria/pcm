@extends('layouts.dev')

@section('title', 'Approval Center')
@section('subtitle', 'Queue approval dokumen submitted')

@section('content')
@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
    $fmtRp = fn($n) => $n !== null ? ('Rp ' . number_format((float) $n, 0, ',', '.')) : '-';
@endphp

<div class="py-6 px-0 space-y-6 text-gray-900 dark:text-gray-100">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-lg font-semibold">Approval Center</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Daftar semua dokumen status submitted yang menunggu approval.</p>
        </div>
        <a href="{{ route('dev.dashboard') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm">
            Kembali
        </a>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold">Queue Approval</h2>
            <p id="approvalTotalLabel" data-total="{{ $items->total() }}" class="text-[11px] text-gray-500 dark:text-gray-400">{{ $items->total() }} dokumen</p>
        </div>

        <form method="GET" class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-900/50" id="approvalFilters">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Jenis Dokumen</label>
                    <select name="type" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="">Semua Jenis</option>
                        @foreach($types as $typeOption)
                            <option value="{{ $typeOption }}" @selected(($filters['type'] ?? '') === $typeOption)>{{ $typeOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Proyek</label>
                    <select name="project_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="">Semua Proyek</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" @selected(($filters['project_id'] ?? '') == $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Dari</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Sampai</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Urutkan</label>
                    <select name="sort_by" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="date" @selected(($filters['sort_by'] ?? 'date') === 'date')>Tanggal</option>
                        <option value="type" @selected(($filters['sort_by'] ?? '') === 'type')>Jenis</option>
                        <option value="project" @selected(($filters['sort_by'] ?? '') === 'project')>Proyek</option>
                        <option value="amount" @selected(($filters['sort_by'] ?? '') === 'amount')>Total</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Arah</label>
                    <select name="sort_dir" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="desc" @selected(($filters['sort_dir'] ?? 'desc') === 'desc')>Terbaru</option>
                        <option value="asc" @selected(($filters['sort_dir'] ?? '') === 'asc')>Terlama</option>
                    </select>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between">
                <p class="text-[11px] text-gray-500 dark:text-gray-400">Filter berdasarkan jenis, proyek, dan tanggal.</p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('dev.approvals.index') }}" class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Reset
                    </a>
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-xs font-semibold text-white">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>

        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                <input id="selectAllApprovals" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600">
                Pilih Semua
            </label>
            <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                <input id="selectAllPages" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600">
                Pilih semua halaman
            </label>
            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                <span>Batch</span>
                <select id="bulkBatchSize" class="px-2 py-1 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-200">
                    <option value="5">5</option>
                    <option value="8" selected>8</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="bulkApproveBtn" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-xs font-semibold text-white disabled:opacity-60 disabled:cursor-not-allowed" disabled>
                    <span id="bulkApproveLabel">Approve Terpilih</span>
                    <span id="bulkApproveProgress" class="hidden ml-2 text-[10px] text-emerald-100"></span>
                    <span id="bulkApproveSpinner" class="hidden ml-2 inline-flex items-center justify-center w-3 h-3 border-2 border-white/60 border-t-white rounded-full animate-spin"></span>
                </button>
                <button type="button" id="bulkRejectBtn" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-xs font-semibold text-white disabled:opacity-60 disabled:cursor-not-allowed" disabled>
                    <span id="bulkRejectLabel">Reject Terpilih</span>
                    <span id="bulkRejectProgress" class="hidden ml-2 text-[10px] text-rose-100"></span>
                    <span id="bulkRejectSpinner" class="hidden ml-2 inline-flex items-center justify-center w-3 h-3 border-2 border-white/60 border-t-white rounded-full animate-spin"></span>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/70">
                    <tr>
                        <th class="px-3 py-2 text-center w-10">
                            <input id="selectAllApprovalsHead" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600">
                        </th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Jenis</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Nomor</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Tanggal</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Proyek / RAPP</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Vendor</th>
                        <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Total</th>
                        <th class="px-3 py-2 text-center text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($items as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 approval-row">
                            <td class="px-3 py-2 text-center">
                                <input type="checkbox" class="approval-checkbox rounded border-gray-300 dark:border-gray-700 text-blue-600"
                                       data-approve-url="{{ $row['approve_url'] }}"
                                       data-reject-url="{{ $row['reject_url'] }}">
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">
                                    {{ $row['type'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="font-semibold">{{ $row['number'] ?? '-' }}</div>
                                <a href="{{ $row['show_url'] }}" class="text-[11px] text-blue-600 hover:text-blue-700">Lihat detail</a>
                            </td>
                            <td class="px-3 py-2">{{ $fmtDate($row['date'] ?? null) }}</td>
                            <td class="px-3 py-2">
                                <div class="font-medium">{{ $row['project'] ?? '-' }}</div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $row['rab'] ?? '-' }}</div>
                            </td>
                            <td class="px-3 py-2">{{ $row['vendor'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-emerald-700 dark:text-emerald-300">
                                {{ $fmtRp($row['amount'] ?? null) }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <form method="POST" action="{{ $row['approve_url'] }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold">
                                            Approve
                                        </button>
                                    </form>
                                    <button type="button" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-semibold"
                                            data-reject-url="{{ $row['reject_url'] }}" onclick="openRejectModal(this)">
                                        Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Tidak ada dokumen yang menunggu approval.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>

<div id="rejectModal" class="fixed inset-0 hidden items-center justify-center z-50">
    <div class="absolute inset-0 bg-black/40" onclick="closeRejectModal()"></div>
    <form method="POST" class="relative bg-white dark:bg-gray-900 w-full max-w-md rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-xl">
        @csrf
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Alasan Reject</div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Isi alasan agar pengaju mengetahui perbaikan yang diperlukan.</p>
        <textarea name="rejected_reason" rows="3" class="mt-3 w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-200 px-3 py-2" placeholder="Tulis alasan reject..."></textarea>
        <div class="mt-4 flex items-center justify-end gap-2">
            <button type="button" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-200" onclick="closeRejectModal()">Batal</button>
            <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold">Reject</button>
        </div>
    </form>
</div>

<div id="bulkRejectModal" class="fixed inset-0 hidden items-center justify-center z-50">
    <div class="absolute inset-0 bg-black/40" onclick="closeBulkRejectModal()"></div>
    <form id="bulkRejectForm" class="relative bg-white dark:bg-gray-900 w-full max-w-md rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-xl">
        @csrf
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Alasan Reject (Bulk)</div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Berlaku untuk semua dokumen terpilih.</p>
        <textarea name="rejected_reason" rows="3" class="mt-3 w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-200 px-3 py-2" placeholder="Tulis alasan reject..."></textarea>
        <div class="mt-4 flex items-center justify-end gap-2">
            <button type="button" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-200" onclick="closeBulkRejectModal()">Batal</button>
            <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold">Reject Terpilih</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function openRejectModal(button) {
        const modal = document.getElementById('rejectModal');
        const form = modal.querySelector('form');
        form.setAttribute('action', button.getAttribute('data-reject-url'));
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeRejectModal() {
        const modal = document.getElementById('rejectModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    const selectAll = document.getElementById('selectAllApprovals');
    const selectAllHead = document.getElementById('selectAllApprovalsHead');
    const selectAllPages = document.getElementById('selectAllPages');
    const checkboxes = () => Array.from(document.querySelectorAll('.approval-checkbox'));
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');
    const bulkRejectBtn = document.getElementById('bulkRejectBtn');
    const bulkApproveSpinner = document.getElementById('bulkApproveSpinner');
    const bulkRejectSpinner = document.getElementById('bulkRejectSpinner');
    const bulkApproveProgress = document.getElementById('bulkApproveProgress');
    const bulkRejectProgress = document.getElementById('bulkRejectProgress');
    const bulkBatchSize = document.getElementById('bulkBatchSize');
    const filtersForm = document.getElementById('approvalFilters');
    const totalLabel = document.querySelector('[data-total-label]');

    function updateBulkButtons() {
        const selected = checkboxes().filter(cb => cb.checked);
        const hasSelected = selected.length > 0;
        const hasAny = hasSelected || (selectAllPages && selectAllPages.checked);
        if (bulkApproveBtn) bulkApproveBtn.disabled = !hasAny;
        if (bulkRejectBtn) bulkRejectBtn.disabled = !hasAny;
    }

    function syncSelectAllState() {
        const all = checkboxes();
        const checked = all.filter(cb => cb.checked);
        const allChecked = all.length && checked.length === all.length;
        if (selectAll) selectAll.checked = allChecked;
        if (selectAllHead) selectAllHead.checked = allChecked;
    }

    function setAllChecked(checked) {
        checkboxes().forEach(cb => cb.checked = checked);
        updateBulkButtons();
    }

    if (selectAll) {
        selectAll.addEventListener('change', (e) => {
            setAllChecked(e.target.checked);
            if (selectAllHead) selectAllHead.checked = e.target.checked;
        });
    }
    if (selectAllHead) {
        selectAllHead.addEventListener('change', (e) => {
            setAllChecked(e.target.checked);
            if (selectAll) selectAll.checked = e.target.checked;
        });
    }
    checkboxes().forEach(cb => {
        cb.addEventListener('change', () => {
            updateBulkButtons();
            syncSelectAllState();
        });
    });
    updateBulkButtons();

    if (selectAllPages) {
        selectAllPages.addEventListener('change', (e) => {
            if (e.target.checked) {
                setAllChecked(true);
            }
            updateBulkButtons();
        });
    }

    async function postUrl(url, payload = null) {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const body = payload ? new URLSearchParams(payload) : new URLSearchParams();
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body
        });
        return res.ok;
    }

    async function postUrlBatched(items, payload = null, batchSize = 8, onProgress = null) {
        const chunks = [];
        for (let i = 0; i < items.length; i += batchSize) {
            chunks.push(items.slice(i, i + batchSize));
        }
        let done = 0;
        const total = items.length;
        for (const chunk of chunks) {
            await Promise.all(chunk.map((url) => postUrl(url, payload)));
            done += chunk.length;
            if (typeof onProgress === 'function') {
                onProgress(done, total);
            }
        }
    }

    function setBulkLoading(isLoading, mode = 'approve') {
        if (bulkApproveBtn && bulkRejectBtn) {
            bulkApproveBtn.disabled = isLoading || bulkApproveBtn.disabled;
            bulkRejectBtn.disabled = isLoading || bulkRejectBtn.disabled;
        }
        if (mode === 'approve' && bulkApproveSpinner) {
            bulkApproveSpinner.classList.toggle('hidden', !isLoading);
        }
        if (mode === 'reject' && bulkRejectSpinner) {
            bulkRejectSpinner.classList.toggle('hidden', !isLoading);
        }
        if (mode === 'approve' && bulkApproveProgress) {
            bulkApproveProgress.classList.toggle('hidden', !isLoading);
        }
        if (mode === 'reject' && bulkRejectProgress) {
            bulkRejectProgress.classList.toggle('hidden', !isLoading);
        }
    }

    async function fetchAllUrls() {
        const params = new URLSearchParams(window.location.search);
        const res = await fetch(`{{ route('dev.approvals.urls') }}?${params.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) return { items: [], count: 0 };
        return await res.json();
    }

    function removeSelectedRows() {
        checkboxes().filter(cb => cb.checked).forEach(cb => {
            const row = cb.closest('.approval-row');
            if (row) row.remove();
        });
    }

    function refreshCountsLocal(delta) {
        const label = document.querySelector('#approvalTotalLabel');
        if (!label) return;
        const current = Number(label.getAttribute('data-total') || '0');
        const next = Math.max(0, current - delta);
        label.setAttribute('data-total', String(next));
        label.textContent = `${next} dokumen`;
    }

    async function refreshBadges() {
        if (typeof window.refreshNotifications === 'function') {
            await window.refreshNotifications();
        }
    }

    if (bulkApproveBtn) {
        bulkApproveBtn.addEventListener('click', async () => {
            const selected = checkboxes().filter(cb => cb.checked);
            const useAllPages = selectAllPages && selectAllPages.checked;
            if (!selected.length && !useAllPages) return;

            let targets = selected.map(cb => ({ approve_url: cb.getAttribute('data-approve-url') })).filter(t => t.approve_url);
            let totalCount = targets.length;
            if (useAllPages) {
                const all = await fetchAllUrls();
                targets = (all.items || []).filter(t => t.approve_url);
                totalCount = Number(all.count || targets.length);
            }

            if (!confirm(`Approve ${totalCount} dokumen terpilih?`)) return;
            const batchSize = Number(bulkBatchSize?.value || 8);
            setBulkLoading(true, 'approve');
            await postUrlBatched(
                targets.map(t => t.approve_url),
                null,
                batchSize,
                (done, total) => {
                    if (bulkApproveProgress) bulkApproveProgress.textContent = `${done}/${total}`;
                }
            );
            removeSelectedRows();
            refreshCountsLocal(totalCount);
            updateBulkButtons();
            if (selectAll) selectAll.checked = false;
            if (selectAllHead) selectAllHead.checked = false;
            if (selectAllPages) selectAllPages.checked = false;
            await refreshBadges();
            setBulkLoading(false, 'approve');
            if (window.showToast) {
                window.showToast({ title: 'Bulk Approve', message: `Selesai: ${totalCount}/${totalCount} dokumen di-approve.` });
            }
        });
    }

    if (bulkRejectBtn) {
        bulkRejectBtn.addEventListener('click', () => {
            const selected = checkboxes().filter(cb => cb.checked);
            const useAllPages = selectAllPages && selectAllPages.checked;
            if (!selected.length && !useAllPages) return;
            const modal = document.getElementById('bulkRejectModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    }

    function closeBulkRejectModal() {
        const modal = document.getElementById('bulkRejectModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    const bulkRejectForm = document.getElementById('bulkRejectForm');
    if (bulkRejectForm) {
        bulkRejectForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const reason = bulkRejectForm.querySelector('textarea[name="rejected_reason"]')?.value || '';
            const selected = checkboxes().filter(cb => cb.checked);
            const useAllPages = selectAllPages && selectAllPages.checked;

            let targets = selected.map(cb => ({ reject_url: cb.getAttribute('data-reject-url') })).filter(t => t.reject_url);
            let totalCount = targets.length;
            if (useAllPages) {
                const all = await fetchAllUrls();
                targets = (all.items || []).filter(t => t.reject_url);
                totalCount = Number(all.count || targets.length);
            }
            if (!targets.length) {
                closeBulkRejectModal();
                return;
            }
            const batchSize = Number(bulkBatchSize?.value || 8);
            setBulkLoading(true, 'reject');
            await postUrlBatched(
                targets.map(t => t.reject_url),
                { rejected_reason: reason },
                batchSize,
                (done, total) => {
                    if (bulkRejectProgress) bulkRejectProgress.textContent = `${done}/${total}`;
                }
            );
            closeBulkRejectModal();
            removeSelectedRows();
            refreshCountsLocal(totalCount);
            updateBulkButtons();
            if (selectAll) selectAll.checked = false;
            if (selectAllHead) selectAllHead.checked = false;
            if (selectAllPages) selectAllPages.checked = false;
            await refreshBadges();
            setBulkLoading(false, 'reject');
            if (window.showToast) {
                window.showToast({ title: 'Bulk Reject', message: `Selesai: ${totalCount}/${totalCount} dokumen di-reject.` });
            }
        });
    }
</script>
@endpush
@endsection


