@extends('layouts.dev')

@section('title', 'Tabel RAPP - ' . ($rab->name ?? $project->name))
@section('subtitle', 'Tabel Detail RAPP (Acuan Tahap Breakdown)')

@section('content')
@php
    $fmtRp = fn($n) => 'Rp ' . number_format((float) ($n ?? 0), 0, ',', '.');
    $statusKey = method_exists($rab, 'statusKey')
        ? strtolower((string) $rab->statusKey())
        : strtolower(trim((string) ($rab->status ?? 'draft')));
    $isApproved = $statusKey === 'approved';
    $categoryConfig = [
        'MT' => ['label' => 'Material', 'class' => 'header-mt'],
        'MATERIAL' => ['label' => 'Material', 'class' => 'header-mt'],
        'JS' => ['label' => 'Jasa', 'class' => 'header-js'],
        'JASA' => ['label' => 'Jasa', 'class' => 'header-js'],
        'AL' => ['label' => 'Alat', 'class' => 'header-at'],
        'ALAT' => ['label' => 'Alat', 'class' => 'header-at'],
        'HO' => ['label' => 'Head Office', 'class' => 'header-ho'],
        'HEAD OFFICE' => ['label' => 'Head Office', 'class' => 'header-ho'],
        'HEAD_OFFICE' => ['label' => 'Head Office', 'class' => 'header-ho'],
        'SR' => ['label' => 'Sirkulasi', 'class' => 'header-sr'],
        'SIRKULASI' => ['label' => 'Sirkulasi', 'class' => 'header-sr'],
        'SB' => ['label' => 'Subkon', 'class' => 'header-sb'],
        'SUBKON' => ['label' => 'Subkon', 'class' => 'header-sb'],
        'LAINNYA' => ['label' => 'Lainnya', 'class' => 'header-default'],
        'UNCATEGORIZED' => ['label' => 'Lainnya', 'class' => 'header-default'],
    ];

    $itemsByCategory = $items->groupBy(function ($item) {
        $raw = strtoupper(trim((string) ($item->data->kategori ?? $item->item_type ?? '')));
        return $raw !== '' ? $raw : 'LAINNYA';
    });
@endphp

<style>
    .table-container {
        margin-bottom: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    .dark .table-container {
        background: #020617;
        border-color: #1f2937;
    }
    .table-header {
        padding: 0.65rem 0.9rem 0.55rem;
        color: #fff;
        border-bottom: 1px solid rgba(15, 23, 42, 0.25);
        cursor: pointer;
        user-select: none;
    }
    .table-header-inner {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        align-items: flex-start;
        flex-wrap: wrap;
    }
    .title-wrap {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .collapse-icon {
        width: 0.95rem;
        height: 0.95rem;
        transition: transform 0.2s ease;
        transform: rotate(0deg);
    }
    .collapsed .collapse-icon {
        transform: rotate(-90deg);
    }
    .table-title-main {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex-wrap: wrap;
    }
    .table-title-badge {
        background: rgba(255, 255, 255, 0.18);
        padding: 0.16rem 0.5rem;
        border-radius: 999px;
        font-size: 0.65rem;
        font-weight: 600;
    }
    .header-mt { background: linear-gradient(135deg, #16a34a, #15803d); }
    .header-js { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
    .header-at { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .header-ho { background: linear-gradient(135deg, #7c3aed, #5b21b6); }
    .header-sr { background: linear-gradient(135deg, #ec4899, #db2777); }
    .header-sb { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
    .header-default { background: linear-gradient(135deg, #6b7280, #4b5563); }
    .category-meta-pills {
        display: flex;
        gap: 0.45rem;
        flex-wrap: wrap;
        margin-top: 0.45rem;
    }
    .category-meta-pill {
        padding: 0.14rem 0.45rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.2);
        font-size: 0.65rem;
        font-weight: 600;
    }
    .section-content.collapsed {
        display: none;
    }
</style>

<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Tabel RAPP</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $project->name }} - {{ optional($project->client)->name ?? '-' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dev.rab-baseline.index', $project->id) }}"
               class="px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200">
                Kembali Daftar RAPP
            </a>
            <a href="{{ route('dev.rab-baseline.show', ['projectId' => $project->id, 'rabId' => $rab->id]) }}"
               class="px-3 py-2 text-xs rounded-lg border border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-200">
                Halaman Operasional
            </a>
            @if($isApproved)
                <a href="{{ route('dev.rab-breakdown.index', $project->id) }}"
                   class="px-3 py-2 text-xs rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-semibold">
                    Lanjut RAB Breakdown
                </a>
            @else
                <span class="px-3 py-2 text-xs rounded-lg bg-amber-100 text-amber-800 font-semibold">
                    Breakdown menunggu RAPP approved
                </span>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 px-4 py-2.5 text-xs text-blue-800 dark:text-blue-200 flex flex-wrap items-center justify-between gap-2">
        <span>URL canonical tabel ini: <span class="font-semibold">/rapps/{{ $rab->id }}/table</span></span>
        <button type="button" data-copy-link="{{ route('dev.rab-baseline.table', ['projectId' => $project->id, 'rabId' => $rab->id]) }}"
                class="px-2.5 py-1 rounded-md border border-blue-300 dark:border-blue-700 hover:bg-blue-100 dark:hover:bg-blue-900/40 font-semibold">
            Copy Link
        </button>
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div class="text-gray-500 dark:text-gray-400">Dokumen</div>
                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $rab->name }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div class="text-gray-500 dark:text-gray-400">Status</div>
                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ strtoupper($statusKey ?: 'DRAFT') }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div class="text-gray-500 dark:text-gray-400">Total Baseline</div>
                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $fmtRp($grandTotal) }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                <div class="text-gray-500 dark:text-gray-400">% thd Budget Proyek</div>
                <div class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format((float) $budgetPercent, 2, ',', '.') }}%</div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="flex items-center justify-end gap-2 text-xs">
            <button type="button" id="expandAllBtn"
                    class="px-2.5 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                Expand All
            </button>
            <button type="button" id="collapseAllBtn"
                    class="px-2.5 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                Collapse All
            </button>
        </div>
        @forelse($itemsByCategory as $categoryKey => $categoryItems)
            @php
                $cfg = $categoryConfig[$categoryKey] ?? null;
                $categoryName = $cfg['label'] ?? \Illuminate\Support\Str::headline(strtolower((string) $categoryKey));
                $headerClass = $cfg['class'] ?? 'header-default';
                $catTotal = (float) $categoryItems->sum(fn ($it) => (float) ($it->volume ?? 0) * (float) ($it->harga_satuan ?? 0));
                $catVolume = (float) $categoryItems->sum(fn ($it) => (float) ($it->volume ?? 0));
                $sectionId = 'cat-section-' . $loop->index;
            @endphp
            <div class="table-container" data-section-wrapper="{{ $sectionId }}">
                <div class="table-header {{ $headerClass }}" data-toggle-section="{{ $sectionId }}">
                    <div class="table-header-inner">
                        <div class="title-wrap">
                            <svg class="collapse-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            <div class="table-title-main">
                                <span>{{ $categoryName }}</span>
                                <span class="table-title-badge">{{ $categoryItems->count() }} item</span>
                            </div>
                        </div>
                        <div class="category-meta-pills">
                            <span class="category-meta-pill">Vol: {{ number_format($catVolume, 2, ',', '.') }}</span>
                            <span class="category-meta-pill">Subtotal: {{ $fmtRp($catTotal) }}</span>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto section-content" id="{{ $sectionId }}">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800/70">
                            <tr>
                                <th class="px-3 py-2 text-left">No</th>
                                <th class="px-3 py-2 text-left">Kode Item</th>
                                <th class="px-3 py-2 text-left">Uraian Pekerjaan</th>
                                <th class="px-3 py-2 text-left">Satuan</th>
                                <th class="px-3 py-2 text-right">Volume Baseline</th>
                                <th class="px-3 py-2 text-right">Harga Satuan Baseline</th>
                                <th class="px-3 py-2 text-right">Jumlah Baseline</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($categoryItems->values() as $idx => $item)
                                @php
                                    $volume = (float) ($item->volume ?? 0);
                                    $harga = (float) ($item->harga_satuan ?? 0);
                                    $jumlah = $volume * $harga;
                                    $kode = $item->data->kode ?? '-';
                                    $uraian = $item->data->uraian ?? $item->keterangan ?? '-';
                                    $satuan = $item->satuan ?? ($item->data->satuan ?? '-');
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $idx + 1 }}</td>
                                    <td class="px-3 py-2 font-mono text-gray-900 dark:text-gray-100">{{ $kode }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $uraian }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $satuan }}</td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($volume, 2, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-100">{{ $fmtRp($harga) }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ $fmtRp($jumlah) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                Belum ada item pada RAPP ini.
            </div>
        @endforelse
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 text-sm font-semibold text-gray-900 dark:text-white">
            Rekap Total RAPP
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($categoryTotals as $category => $catTotal)
                        <tr>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">Subtotal Kategori {{ $category }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ $fmtRp($catTotal) }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray-50 dark:bg-gray-800/60">
                        <td class="px-3 py-2 font-bold text-gray-900 dark:text-white">Grand Total RAPP</td>
                        <td class="px-3 py-2 text-right font-bold text-gray-900 dark:text-white">{{ $fmtRp($grandTotal) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggles = document.querySelectorAll('[data-toggle-section]');
    const wrappers = document.querySelectorAll('[data-section-wrapper]');

    function setCollapsed(sectionId, collapsed) {
        const content = document.getElementById(sectionId);
        const wrapper = document.querySelector('[data-section-wrapper="' + sectionId + '"]');
        if (!content || !wrapper) return;
        content.classList.toggle('collapsed', collapsed);
        wrapper.classList.toggle('collapsed', collapsed);
    }

    toggles.forEach(function (el) {
        el.addEventListener('click', function () {
            const sectionId = el.getAttribute('data-toggle-section');
            const content = document.getElementById(sectionId);
            if (!content) return;
            const isCollapsed = content.classList.contains('collapsed');
            setCollapsed(sectionId, !isCollapsed);
        });
    });

    const expandAllBtn = document.getElementById('expandAllBtn');
    const collapseAllBtn = document.getElementById('collapseAllBtn');

    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', function () {
            wrappers.forEach(function (w) {
                const sectionId = w.getAttribute('data-section-wrapper');
                setCollapsed(sectionId, false);
            });
        });
    }

    if (collapseAllBtn) {
        collapseAllBtn.addEventListener('click', function () {
            wrappers.forEach(function (w) {
                const sectionId = w.getAttribute('data-section-wrapper');
                setCollapsed(sectionId, true);
            });
        });
    }

    const copyBtn = document.querySelector('[data-copy-link]');
    if (copyBtn) {
        copyBtn.addEventListener('click', async function () {
            const url = copyBtn.getAttribute('data-copy-link');
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(url);
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = url;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    ta.remove();
                }
                copyBtn.textContent = 'Copied';
                setTimeout(() => copyBtn.textContent = 'Copy Link', 1200);
            } catch (e) {
                copyBtn.textContent = 'Gagal Copy';
                setTimeout(() => copyBtn.textContent = 'Copy Link', 1400);
            }
        });
    }
});
</script>
@endpush


