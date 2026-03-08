{{-- resources/views/dev/RAPPs/show.blade.php --}}
@extends('layouts.dev')

@section('title', 'RAPP - ' . ($rab->name ?? $project->name))
@section('subtitle', 'Tabel Detail RAPP')

@section('content')
@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;

    $isAdmin = auth()->check() && ((auth()->user()->is_admin ?? false) || (method_exists(auth()->user(), 'isHO') && auth()->user()->isHO()));

    // Hak edit
    $editable = method_exists($rab, 'canEdit')
        ? $rab->canEdit()
        : in_array($rab->status, ['draft', 'rejected', 'approved']);

    // Perubahan struktur diizinkan saat mode editable, termasuk approved.
    // Setelah ada perubahan dari approved, status akan dikembalikan ke submitted untuk approval ulang HO.
    $statusKey = method_exists($rab, 'statusKey') ? $rab->statusKey() : ($rab->status ?? 'draft');
    $canStructureEdit = $editable && in_array($statusKey, ['draft', 'rejected', 'approved']);

    // Group items per kategori kalau belum dikirim dari controller
    if (!isset($itemsByCategory) || empty($itemsByCategory)) {
        $itemsByCategory = $rab->getItemsGroupedByCategory();
    }

    // PERBAIKAN: Hitung total RAPP dengan benar dan validasi konsistensi
    $projectBudget  = $comparison['project_budget'] ?? ($project->budget ?? 0);
    
    // Hitung total RAPP dari items (sumber kebenaran)
    $calculatedRabTotal = 0;
    $calculatedBreakdown = [];
    
    foreach ($itemsByCategory as $category => $items) {
        $categoryTotal = $items->sum(function($item) {
            return ($item->volume ?? 0) * ($item->harga_satuan ?? 0);
        });
        $calculatedRabTotal += $categoryTotal;
        $calculatedBreakdown[$category] = $categoryTotal;
    }
    
    // Gunakan total yang dihitung, bukan dari DB
    $rabTotal = $calculatedRabTotal;
    
    // Validasi konsistensi dengan data dari controller
    $controllerRabTotal = $comparison['rapp_total'] ?? $rab->calculateTotalBudget();
    $isDataConsistent = abs($rabTotal - $controllerRabTotal) < 1;
    
    // Log warning jika tidak konsisten
    if (!$isDataConsistent) {
        Log::warning('RAPP Data Inconsistency in View', [
            'project_id' => $project->id,
            'rab_id' => $rab->id,
            'view_calculated_total' => $rabTotal,
            'controller_total' => $controllerRabTotal,
            'difference' => $rabTotal - $controllerRabTotal
        ]);
    }

    // Perbandingan dengan budget project
    $difference     = 0;
    $percentage     = 0;
    $isOverBudget   = false;

    if ($projectBudget > 0) {
        if ($rabTotal <= $projectBudget) {
            $difference   = $projectBudget - $rabTotal;
            $isOverBudget = false;
        } else {
            $difference   = $rabTotal - $projectBudget;
            $isOverBudget = true;
        }
        $percentage = ($difference / $projectBudget) * 100;
    }

    $hasItems = $rab->items && $rab->items->count() > 0;

    // Wajib isi sebelum submit HO: Volume RAPP & Harga Satuan harus > 0 untuk semua item
    $needFillBeforeSubmit = $hasItems && $rab->items->filter(function ($it) {
        return (float) ($it->volume ?? 0) <= 0 || (float) ($it->harga_satuan ?? 0) <= 0;
    })->count() > 0;

    // Global realisasi & sisa RAPP
    $globalRealization = $rab->items->sum(function ($item) {
        return (float) ($item->realisasi_amount ?? 0);
    });
    $globalRemaining = max(0, (float) $rabTotal - (float) $globalRealization);
    $realizationPercentage = $rabTotal > 0
        ? min(100, ($globalRealization / $rabTotal) * 100)
        : 0;

    // Breakdown kategori global untuk ringkasan atas
    $categoryBreakdown = [];
    
    // Gunakan breakdown yang sudah dihitung
    foreach ($calculatedBreakdown as $category => $total) {
        $pct = $rabTotal > 0 ? ($total / $rabTotal) * 100 : 0;
        
        $categoryBreakdown[] = [
            'label'      => $category,
            'total'      => $total,
            'percentage' => $pct,
        ];
    }

    // Summary voucher global untuk RAPP ini
    $voucherSummary = \App\Models\PurchaseVoucher::query()
        ->where('project_id', $project->id)
        ->where('rab_id', $rab->id)
        ->selectRaw('COUNT(*) AS voucher_count, COALESCE(SUM(total_amount), 0) AS total_amount')
        ->first();

    $voucherCount       = (int) ($voucherSummary->voucher_count ?? 0);
    $voucherTotalAmount = (float) ($voucherSummary->total_amount ?? 0.0);
    $voucherOutstanding = max(0, (float) $rabTotal - (float) $voucherTotalAmount);
    // Ringkasan jumlah dokumen per RAPP
    $flowCounts = [
        'spp' => \App\Models\Spp::where('project_id', $project->id)->where('rab_id', $rab->id)->count(),
        'komparasi' => \App\Models\VendorComparison::where('project_id', $project->id)->where('rab_id', $rab->id)->count(),
        'po' => \App\Models\PurchaseOrder::where('project_id', $project->id)->where('rab_id', $rab->id)->count(),
        'lpb' => \App\Models\Lpb::where('project_id', $project->id)->where('rab_id', $rab->id)->count(),
        'bpg' => \App\Models\Bpg::where('project_id', $project->id)->where('rab_id', $rab->id)->count(),
        'spk' => \App\Models\Spk::where('project_id', $project->id)->where('rab_id', $rab->id)->count(),
        'voucher' => $voucherCount,
    ];
@endphp

<style>
    /* ====== CARD / TABLE CONTAINER ====== */

    .table-container {
        margin-bottom: 1.25rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        overflow: hidden;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        transition: box-shadow 0.25s ease;
    }

    .table-container:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }

    .dark .table-container {
        background: #020617;
        border-color: #1f2937;
    }

    .table-header {
        padding: 0.65rem 0.9rem 0.55rem;
        display: flex;
        align-items: center;
        cursor: pointer;
        color: #fff;
        border-bottom: 1px solid rgba(15, 23, 42, 0.25);
    }

    .table-title-main {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .table-title-badge {
        background: rgba(255, 255, 255, 0.18);
        padding: 0.16rem 0.5rem;
        border-radius: 999px;
        font-size: 0.65rem;
        font-weight: 600;
    }

    .collapse-icon {
        width: 1rem;
        height: 1rem;
        transition: transform 0.2s ease;
        flex-shrink: 0;
    }

    /* Warna header per kategori */
    .header-mt { background: linear-gradient(135deg, #16a34a, #15803d); }
    .header-js { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
    .header-at { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .header-ho { background: linear-gradient(135deg, #7c3aed, #5b21b6); }
    .header-sr { background: linear-gradient(135deg, #ec4899, #db2777); }
    .header-sb { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
    .header-default { background: linear-gradient(135deg, #6b7280, #4b5563); }

    .table-content {
        background: #f9fafb;
    }

    .dark .table-content {
        background: #020617;
    }

    .table-scroll-container {
        width: 100%;
        overflow-x: visible;
        border-top: 1px solid #e5e7eb;
    }

    .dark .table-scroll-container {
        border-color: #374151;
    }

    .data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.78rem;
        table-layout: auto;
    }

    .data-table thead {
        background: #f3f4f6;
    }

    .dark .data-table thead {
        background: #111827;
    }

    .data-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        font-weight: 600;
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
        border-bottom: 1px solid #e5e7eb;
        background: inherit;
        white-space: normal;
    }

    .dark .data-table thead th {
        color: #9ca3af;
        border-color: #374151;
    }

    .data-table tbody tr:nth-child(even) {
        background: rgba(255, 255, 255, 0.7);
    }

    .dark .data-table tbody tr:nth-child(even) {
        background: rgba(15, 23, 42, 0.55);
    }

    .data-table tbody tr:hover {
        background: rgba(59, 130, 246, 0.04);
    }

    .dark .data-table tbody tr:hover {
        background: rgba(59, 130, 246, 0.15);
    }

    .data-table th,
    .data-table td {
        padding: 0.4rem 0.55rem;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: middle;
        white-space: normal;
    }

    .dark .data-table td {
        border-color: #374151;
    }

    .cell-subtotal,
    .cell-realisasi,
    .cell-sisa {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.78rem;
    }

    .badge-percent {
        display: inline-flex;
        align-items: center;
        gap: 0.15rem;
        padding: 0.1rem 0.4rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 600;
    }

    .badge-percent-green {
        background: #dcfce7;
        color: #15803d;
    }

    .badge-percent-amber {
        background: #fef3c7;
        color: #b45309;
    }

    .badge-percent-red {
        background: #fee2e2;
        color: #b91c1c;
    }

    .btn-icon {
        border-radius: 999px;
        padding: 0.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        color: #6b7280;
    }

    .btn-icon:hover {
        background: #e5e7eb;
        color: #111827;
    }

    .dark .btn-icon {
        color: #9ca3af;
    }

    .dark .btn-icon:hover {
        background: #1f2937;
        color: #e5e7eb;
    }

    .btn-icon-danger {
        border-radius: 999px;
        padding: 0.2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        color: #ef4444;
    }

    .btn-icon-danger:hover {
        background: #fee2e2;
        color: #b91c1c;
    }

    .dark .btn-icon-danger:hover {
        background: rgba(248, 113, 113, 0.15);
    }

    /* ====== HEADER KATEGORI: JUDUL KIRI, META KANAN ====== */

    .category-header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        gap: 0.75rem;
    }

    .category-meta {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        align-items: flex-end;
    }

    .category-meta-pills {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.25rem;
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.9);
    }

    .category-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.12rem 0.55rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.25);
        border: 1px solid rgba(148, 163, 184, 0.5);
        backdrop-filter: blur(4px);
        white-space: nowrap;
    }

    .category-meta-label {
        opacity: 0.78;
    }

    .category-meta-value {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.7rem;
    }

    .category-risk-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        padding: 0.12rem 0.45rem;
        border-radius: 999px;
        font-size: 0.65rem;
        font-weight: 600;
        margin-left: 0.35rem;
        background: rgba(248, 113, 113, 0.18);
        color: #fee2e2;
        border: 1px solid rgba(254, 202, 202, 0.7);
    }

    .category-risk-badge span:first-child {
        font-size: 0.7rem;
    }

    /* Progress bar tipis, sekarang di dalam blok meta (di bawah Vol/RAPP/Real/Sisa) */
    .category-progress-wrapper {
        width: 100%;
        margin-top: 0.1rem;
    }

    .category-progress-bg {
        width: 100%;
        height: 3px;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.25);
        overflow: hidden;
    }

    .category-progress-bar {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #22c55e, #eab308, #ef4444);
        transition: width 0.25s ease;
    }

    /* Quick search */
    .table-search-input {
        width: 230px;
        max-width: 100%;
        font-size: 0.75rem;
        border-radius: 999px;
        border: 1px solid #d1d5db;
        padding: 0.25rem 0.75rem;
        background: #f9fafb;
        color: #111827;
    }

    .table-search-input::placeholder {
        color: #9ca3af;
    }

    .table-search-input:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 1px rgba(79, 70, 229, 0.35);
        background: #ffffff;
    }

    .dark .table-search-input {
        border-color: #4b5563;
        background: #020617;
        color: #e5e7eb;
    }

    .dark .table-search-input::placeholder {
        color: #6b7280;
    }

    .dark .table-search-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 1px rgba(129, 140, 248, 0.4);
        background: #020617;
    }

    /* Toast error */
    .rapp-error-toast {
        position: fixed;
        top: 0.75rem;
        right: 0.75rem;
        z-index: 60;
        max-width: 280px;
        padding: 0.55rem 0.75rem;
        border-radius: 0.75rem;
        background: #fef2f2;
        color: #b91c1c;
        font-size: 0.75rem;
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
        border: 1px solid #fecaca;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .rapp-error-toast.hidden {
        display: none;
    }

    .dark .rapp-error-toast {
        background: #7f1d1d;
        border-color: #fecaca;
        color: #fee2e2;
    }

    /* PERBAIKAN: Data inconsistency warning */
    .data-warning-banner {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 1px solid #f59e0b;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .dark .data-warning-banner {
        background: linear-gradient(135deg, #78350f, #92400e);
        border-color: #f59e0b;
    }
    
    .data-warning-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .data-warning-text {
        font-size: 0.8rem;
        color: #92400e;
        font-weight: 500;
    }
    
    .dark .data-warning-text {
        color: #fde68a;
    }

    /* Responsif */
    @media (max-width: 768px) {
        .category-header-inner {
            flex-direction: column;
            align-items: flex-start;
        }
        .category-meta {
            align-items: flex-start;
        }
        .category-meta-pills {
            justify-content: flex-start;
        }
    }

    @media (max-width: 480px) {
        .category-meta-pill--vol {
            display: none;
        }
    }

    /* Inline edit (Volume RAPP & Harga Satuan) - dengan styling yang lebih baik */
    .rapp-inline-input{
        width: 100%;
        max-width: 110px;
        padding: 4px 8px;
        border: 1px solid rgba(148,163,184,.35);
        border-radius: 6px;
        background: white;
        color: #1f2937;
        font-size: 0.78rem;
        line-height: 1.1rem;
        outline: none;
        text-align: right;
        transition: all 0.2s ease;
    }
    .dark .rapp-inline-input {
        background: #1f2937;
        color: #e5e7eb;
        border-color: #4b5563;
    }
    .rapp-inline-input:focus{
        border-color: rgba(99,102,241,.65);
        box-shadow: 0 0 0 2px rgba(99,102,241,.18);
    }
    .rapp-inline-input.volume-input {
        max-width: 90px;
    }
    .rapp-inline-input.harga-input {
        max-width: 130px;
    }
</style>

{{-- Overlay loading global --}}
<div id="globalLoadingOverlay"
     class="fixed inset-0 bg-black/30 dark:bg-black/50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-slate-900 rounded-xl px-4 py-3 shadow-lg flex items-center gap-2 text-sm text-gray-700 dark:text-gray-100">
        <svg class="w-4 h-4 animate-spin text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        <span>Memproses, mohon tunggu...</span>
    </div>
</div>

{{-- Toast error --}}
<div id="rappErrorToast" class="rapp-error-toast hidden">
    <span>?</span>
    <span id="rappErrorToastMessage"></span>
</div>

{{-- PERBAIKAN: Warning jika data tidak konsisten --}}
@if(!$isDataConsistent && $isAdmin)
<div class="data-warning-banner">
    <div class="data-warning-content">
        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        <div>
            <p class="data-warning-text">? Data RAPP tidak konsisten</p>
            <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                Total DB: Rp {{ number_format($controllerRabTotal, 0, ',', '.') }} | 
                Total Hitung: Rp {{ number_format($rabTotal, 0, ',', '.') }}
            </p>
        </div>
    </div>
    @if($isAdmin)
    <form method="POST" action="{{ route('dev.rab-baseline.repair', [$project->id, $rab->id]) }}" 
          onsubmit="return confirm('Repair data RAPP? Total akan diupdate ke: Rp {{ number_format($rabTotal, 0, ',', '.') }}')">
        @csrf
        <button type="submit" class="px-3 py-1.5 text-xs bg-amber-600 text-white rounded-lg hover:bg-amber-700">
            Repair Data
        </button>
    </form>
    @endif
</div>
@endif

<div class="space-y-6">

    {{-- Breadcrumb --}}
    <nav class="flex mb-1 text-xs text-gray-500 dark:text-gray-400" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1">
            <li>
                <a href="{{ route('dev.dashboard') }}" class="hover:text-gray-700 dark:hover:text-gray-200">
                    Dashboard
                </a>
            </li>
            <li><span class="mx-1">/</span></li>
            <li>
                <a href="{{ route('dev.projects.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">
                    Proyek
                </a>
            </li>
            <li><span class="mx-1">/</span></li>
            <li>
                <a href="{{ route('dev.rab-baseline.index', $project->id) }}" class="hover:text-gray-700 dark:hover:text-gray-200">
                    RAPP
                </a>
            </li>
            <li><span class="mx-1">/</span></li>
            <li class="font-medium text-gray-800 dark:text-gray-200">
                {{ $rab->name ?? 'RAPP' }}
            </li>
        </ol>
    </nav>

    <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 px-4 py-2.5 text-xs text-blue-800 dark:text-blue-200 flex flex-wrap items-center justify-between gap-2">
        <span>URL canonical halaman ini: <span class="font-semibold">/rapps/{{ $rab->id }}</span></span>
        <button type="button" id="copyCanonicalLinkBtn"
                data-copy-link="{{ route('dev.rab-baseline.show', ['projectId' => $project->id, 'rabId' => $rab->id]) }}"
                class="px-2.5 py-1 rounded-md border border-blue-300 dark:border-blue-700 hover:bg-blue-100 dark:hover:bg-blue-900/40 font-semibold">
            Copy Link
        </button>
    </div>

    {{-- Header RAPP --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 sm:p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $rab->name ?? 'RAPP Proyek' }}
                    </h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium
                        @if($rab->status === 'approved')
                            bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300
                        @elseif($rab->status === 'rejected')
                            bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300
                        @elseif($rab->status === 'submitted')
                            bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300
                        @else
                            bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200
                        @endif
                    ">
                        {{ Str::ucfirst($rab->status ?? 'draft') }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Proyek:
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $project->name }}</span>
                    @if($project->code)
                        <span class="mx-1 text-gray-400">Ã¢â‚¬Â¢</span>
                        Kode:
                        <span class="font-mono text-gray-800 dark:text-gray-100">{{ $project->code }}</span>
                    @endif
                </p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                    Halaman ini berisi tabel item baseline: volume, harga satuan, jumlah, dan rekap biaya sebagai acuan proyek.
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-start md:justify-end gap-2">
                {{-- PERBAIKAN: Tombol debug untuk admin --}}
                @if($isAdmin)
                <a href="{{ route('dev.rab-baseline.debug', [$project->id, $rab->id]) }}" 
                   target="_blank"
                   class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 text-[11px] font-medium rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    Debug Data
                </a>
                @endif

                {{-- Tombol Rekap Voucher --}}
                <a href="{{ route('dev.rab-baseline.purchase-vouchers.index', [$project->id, $rab->id]) }}"
                   class="inline-flex items-center px-2.5 py-1.5 border border-indigo-200 dark:border-indigo-700 text-[11px] font-medium rounded-lg text-indigo-700 dark:text-indigo-200 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/40">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 7h18M7 7v10m10-10v10M5 21h14M9 3h6"/>
                    </svg>
                    Rekap Voucher
                </a>

                {{-- Tombol Detail Project --}}
                <a href="{{ route('dev.projects.show', $project->id) }}"
                   class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 text-[11px] font-medium rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                    Detail Project
                </a>

                {{-- Tombol RAB Breakdown --}}
                <a href="{{ route('dev.rab-breakdown.index', $project->id) }}"
                   class="inline-flex items-center px-3 py-1.5 border border-sky-600 text-[11px] font-semibold rounded-lg text-white bg-sky-600 hover:bg-sky-700 dark:bg-sky-600 dark:hover:bg-sky-700">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h6M4 12h10M4 18h16M14 6h6M16 12h4M18 18h2"/>
                    </svg>
                    Buka RAB Breakdown
                </a>

                @if($canStructureEdit)
                    <button type="button"
                            onclick="openQuickAddModal()"
                            class="inline-flex items-center px-2.5 py-1.5 border border-emerald-200 dark:border-emerald-700 text-[11px] font-medium rounded-lg text-emerald-700 dark:text-emerald-200 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/40">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Quick Add
                    </button>

                    <button type="button"
                            onclick="openAddItemsModal()"
                            class="inline-flex items-center px-2.5 py-1.5 border border-blue-200 dark:border-blue-700 text-[11px] font-medium rounded-lg text-blue-700 dark:text-blue-200 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Item
                    </button>

                    <button type="button"
                            onclick="openImportExcelModal()"
                            class="inline-flex items-center px-2.5 py-1.5 border border-emerald-200 dark:border-emerald-700 text-[11px] font-medium rounded-lg text-emerald-700 dark:text-emerald-200 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/40">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5-5m0 0l5 5m-5-5v12"/>
                        </svg>
                        Import Excel
                    </button>
                @endif

                @if($editable)
                    <a href="{{ route('dev.rab-baseline.edit', [$project->id, $rab->id]) }}"
                       class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 text-[11px] font-medium rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15.232 5.232l3.536 3.536M9 13l6.232-6.232M4 20h4l10-10"/>
                        </svg>
                        Edit
                    </a>
                @endif

                @if(method_exists($rab, 'canSubmit') ? $rab->canSubmit() : in_array($rab->status, ['draft', 'rejected']))
                    @if($needFillBeforeSubmit)
                        <div class="mt-2 mb-2 px-3 py-2 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-xs">
                            ? Sebelum Submit ke HO, lengkapi dulu <b>Volume RAPP</b> dan <b>Harga Satuan</b> untuk semua item.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('dev.rab-baseline.submit', [$project->id, $rab->id]) }}" class="inline"
                          onsubmit="@if($needFillBeforeSubmit) return false; @else return confirm('Submit RAPP ini ke Head Office?'); @endif">
                        @csrf
                        <button type="submit"
                                @if($needFillBeforeSubmit) disabled title="Lengkapi Volume RAPP & Harga Satuan dulu" @endif
                                class="inline-flex items-center px-2.5 py-1.5 text-[11px] font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 @if($needFillBeforeSubmit) opacity-60 cursor-not-allowed hover:bg-indigo-600 @endif">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 13l4 4L19 7"/>
                            </svg>
                            Submit HO
                        </button>
                    </form>
                @endif

                @if($isAdmin && (method_exists($rab, 'canApprove') ? $rab->canApprove() : $rab->status === 'submitted'))
                    <form method="POST" action="{{ route('dev.rab-baseline.approve', [$project->id, $rab->id]) }}" class="inline"
                          onsubmit="return confirm('Approve RAPP ini?')">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-2.5 py-1.5 text-[11px] font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                            Approve
                        </button>
                    </form>
                    <label for="rejectRAPPModal"
                           class="inline-flex items-center px-2.5 py-1.5 text-[11px] font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 cursor-pointer">
                        Reject
                    </label>
                @endif

                @if($isAdmin)
                    {{-- Export Excel & PDF --}}
                    <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <a href="{{ route('dev.rab-baseline.export.excel', [$project->id, $rab->id]) }}"
                           class="px-2.5 py-1.5 border-r border-gray-200 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v4H4zM4 8l4 12h8l4-12"/>
                            </svg>
                            Excel
                        </a>
                        <a href="{{ route('dev.rab-baseline.export.pdf', [$project->id, $rab->id]) }}"
                           class="px-2.5 py-1.5 border border-gray-300 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4h10v16H7z"/>
                            </svg>
                            PDF
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($rab->status === 'rejected' && ($rab->rejected_reason ?? null))
        <div class="mt-3 rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/80 dark:bg-rose-900/20 p-4 text-sm text-rose-800 dark:text-rose-200">
            <div class="font-semibold mb-1">Alasan Reject</div>
            <div class="text-xs leading-relaxed">{{ $rab->rejected_reason }}</div>
        </div>
    @endif

    @if($isAdmin && (method_exists($rab, 'canApprove') ? $rab->canApprove() : $rab->status === 'submitted'))
        <input id="rejectRAPPModal" type="checkbox" class="peer hidden" />
        <div class="fixed inset-0 hidden peer-checked:flex items-center justify-center z-50">
            <label for="rejectRAPPModal" class="absolute inset-0 bg-black/40"></label>
            <form method="POST" action="{{ route('dev.rab-baseline.reject', [$project->id, $rab->id]) }}"
                  class="relative bg-white dark:bg-gray-900 w-full max-w-md rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-xl">
                @csrf
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Alasan Reject RAPP</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Isi alasan agar pengaju mengetahui perbaikan yang diperlukan.</p>
                <textarea name="rejected_reason" rows="3"
                          class="mt-3 w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-200 px-3 py-2"
                          placeholder="Tulis alasan reject..."></textarea>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <label for="rejectRAPPModal"
                           class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-200 cursor-pointer">Batal</label>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold">Reject</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Alur Dokumen (Ringkas & Jelas) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 sm:p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Alur Dokumen Setelah RAPP</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Pilih alur sesuai jenis kebutuhan: <span class="font-medium">Material</span> atau <span class="font-medium">Jasa</span>.
                </p>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                RAPP: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $rab->name ?? 'RAPP' }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900/40">
                <div class="text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide mb-2">Alur Material</div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">SPP</span>
                    <span class="text-gray-400">&rarr;</span>
                    <span class="px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">Komparasi (opsional)</span>
                    <span class="text-gray-400">&rarr;</span>
                    <span class="px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">PO</span>
                    <span class="text-gray-400">&rarr;</span>
                    <span class="px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">LPB</span>
                    <span class="text-gray-400">&rarr;</span>
                    <span class="px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">BPG</span>
                    <span class="text-gray-400">&rarr;</span>
                    <span class="px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">Voucher</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <a class="px-2 py-1 rounded bg-sky-600 hover:bg-sky-700 text-white" href="{{ route('dev.rab-baseline.spps.index', [$project->id, $rab->id]) }}">SPP <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-semibold">{{ $flowCounts['spp'] }}</span></a>
                    <a class="px-2 py-1 rounded bg-purple-600 hover:bg-purple-700 text-white" href="{{ route('dev.rab-baseline.vendor-comparisons.index', [$project->id, $rab->id]) }}">Komparasi <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-semibold">{{ $flowCounts['komparasi'] }}</span></a>
                    <a class="px-2 py-1 rounded bg-indigo-600 hover:bg-indigo-700 text-white" href="{{ route('dev.rab-baseline.purchase-orders.index', [$project->id, $rab->id]) }}">PO <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-semibold">{{ $flowCounts['po'] }}</span></a>
                    <a class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white" href="{{ route('dev.rab-baseline.lpbs.index', [$project->id, $rab->id]) }}">LPB <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-semibold">{{ $flowCounts['lpb'] }}</span></a>
                    <a class="px-2 py-1 rounded bg-amber-600 hover:bg-amber-700 text-white" href="{{ route('dev.rab-baseline.bpgs.index', [$project->id, $rab->id]) }}">BPG <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-semibold">{{ $flowCounts['bpg'] }}</span></a>
                    <a class="px-2 py-1 rounded bg-teal-600 hover:bg-teal-700 text-white" href="{{ route('dev.rab-baseline.purchase-vouchers.index', [$project->id, $rab->id]) }}">Voucher <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-semibold">{{ $flowCounts['voucher'] }}</span></a>
                </div>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900/40">
                <div class="text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide mb-2">Alur Jasa</div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">SPP</span>
                    <span class="text-gray-400">&rarr;</span>
                    <span class="px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">SPK</span>
                    <span class="text-gray-400">&rarr;</span>
                    <span class="px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">Voucher</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <a class="px-2 py-1 rounded bg-sky-600 hover:bg-sky-700 text-white" href="{{ route('dev.rab-baseline.spps.index', [$project->id, $rab->id]) }}">SPP <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-semibold">{{ $flowCounts['spp'] }}</span></a>
                    <a class="px-2 py-1 rounded bg-rose-600 hover:bg-rose-700 text-white" href="{{ route('dev.rab-baseline.spks.index', [$project->id, $rab->id]) }}">SPK <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-semibold">{{ $flowCounts['spk'] }}</span></a>
                    <a class="px-2 py-1 rounded bg-teal-600 hover:bg-teal-700 text-white" href="{{ route('dev.rab-baseline.purchase-vouchers.index', [$project->id, $rab->id]) }}">Voucher <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-semibold">{{ $flowCounts['voucher'] }}</span></a>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary cards budget vs RAPP --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Budget Proyek</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                Rp {{ number_format($projectBudget, 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total RAPP</p>
            <p id="total-rapp-amount" class="text-lg font-semibold text-gray-900 dark:text-white">
                Rp {{ number_format($rabTotal, 0, ',', '.') }}
            </p>
            @if(!$isDataConsistent)
            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                ! Data tidak konsisten dengan DB
            </p>
            @endif
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                {{ $isOverBudget ? 'Melebihi Budget' : 'Sisa Budget' }}
            </p>
            <p class="text-lg font-semibold
                @if($isOverBudget) text-red-600 dark:text-red-400 @else text-green-600 dark:text-green-400 @endif">
                Rp {{ number_format($difference, 0, ',', '.') }}
                <span class="text-[11px] text-gray-500 dark:text-gray-400">
                    ({{ number_format($percentage, 1, ',', '.') }}%)
                </span>
            </p>
        </div>
    </div>

    {{-- GLOBAL SUMMARY REALISASI RAPP --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Realisasi Global</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                Rp {{ number_format($globalRealization, 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Sisa Global</p>
            <p class="text-lg font-semibold @if($globalRemaining <= 0) text-red-600 dark:text-red-400 @else text-emerald-600 dark:text-emerald-400 @endif">
                Rp {{ number_format($globalRemaining, 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Persentase Realisasi dari RAPP</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ number_format($realizationPercentage, 1, ',', '.') }}%
            </p>
        </div>
    </div>

    {{-- BREAKDOWN KATEGORI GLOBAL --}}
    @if(!empty($categoryBreakdown))
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mt-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                    Breakdown total RAPP per kategori
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Total: Rp {{ number_format($rabTotal, 0, ',', '.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                @foreach($categoryBreakdown as $cb)
                    <div class="flex items-center justify-between px-2.5 py-1.5 rounded-lg bg-gray-50/70 dark:bg-slate-900/60 border border-gray-100 dark:border-slate-700 hover:border-gray-300 dark:hover:border-slate-500 transition-colors">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex w-1.5 h-1.5 rounded-full
                                @switch($cb['label'])
                                    @case('Material') bg-emerald-500 @break
                                    @case('Jasa') bg-blue-500 @break
                                    @case('Alat') bg-amber-500 @break
                                    @case('Head Office') bg-purple-500 @break
                                    @case('HO') bg-purple-500 @break
                                    @case('Sirkulasi') bg-pink-500 @break
                                    @case('Subkon') bg-cyan-500 @break
                                    @default bg-slate-400
                                @endswitch
                            "></span>
                            <span class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                                {{ $cb['label'] }}
                            </span>
                        </div>

                        <div class="text-right leading-tight">
                            <div class="text-[11px] font-mono text-gray-900 dark:text-gray-50">
                                Rp {{ number_format($cb['total'], 0, ',', '.') }}
                            </div>
                            <div class="text-[10px] font-medium text-gray-500 dark:text-gray-400">
                                {{ number_format($cb['percentage'], 1, ',', '.') }}%
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- SUMMARY GLOBAL VOUCHER --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Jumlah Voucher</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ number_format($voucherCount, 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Nilai Voucher</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                Rp {{ number_format($voucherTotalAmount, 0, ',', '.') }}
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Outstanding (belum dibelanjakan)</p>
            <p class="text-lg font-semibold text-amber-600 dark:text-amber-400">
                Rp {{ number_format($voucherOutstanding, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- TABEL ITEMS: CONTROL BAR + SEARCH --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-4 mb-1 text-[11px] text-gray-500 dark:text-gray-400">
        <span>Klik header kategori untuk collapse / expand.</span>
        <div class="flex items-center gap-2 sm:gap-3">
            <input type="text"
                   id="itemsSearchInput"
                   class="table-search-input"
                   placeholder="Cari item (kode / nama)..." 
                   oninput="onSearchItems(this.value)">
            @if($editable)
                <form id="bulkDeleteForm" method="POST" action="{{ route('dev.rab-baseline.items.bulk.delete', ['projectId' => $project->id, 'rabId' => $rab->id]) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                    <div id="bulkDeleteIds"></div>
                </form>
                <button type="button" id="bulkDeleteBtn" onclick="bulkDeleteSelected()" disabled
                        class="px-2 py-1 border border-red-300 text-red-700 dark:border-red-600/50 dark:text-red-300 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20 disabled:opacity-50 disabled:cursor-not-allowed">
                    Hapus Terpilih <span id="bulkDeleteCount" class="ml-1">(0)</span>
                </button>
            @endif
            <div class="inline-flex gap-1.5">
                <button type="button"
                        onclick="expandAllCategories()"
                        class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-full text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Expand All
                </button>
                <button type="button"
                        onclick="collapseAllCategories()"
                        class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-full text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Collapse All
                </button>
            </div>
        </div>
    </div>

    {{-- TABEL ITEMS PER KATEGORI --}}
    <div class="space-y-4">
        @forelse($itemsByCategory as $category => $items)
            @php
                $mapHeader = [
                    'Material'   => 'header-mt',
                    'MT'         => 'header-mt',
                    'Jasa'       => 'header-js',
                    'JS'         => 'header-js',
                    'Alat'       => 'header-at',
                    'AL'         => 'header-at',
                    'Head Office'=> 'header-ho',
                    'HO'         => 'header-ho',
                    'Sirkulasi'  => 'header-sr',
                    'SR'         => 'header-sr',
                    'Subkon'     => 'header-sb',
                    'Lainnya'    => 'header-sb',
                    'Uncategorized' => 'header-default',
                ];

                $categoryLabel = $category ?: 'Lainnya';
                $headerClass   = $mapHeader[$categoryLabel] ?? 'header-default';

                $categorySlug = Str::slug($categoryLabel ?: 'lainnya') . '-' . Str::random(4);

                // PERBAIKAN: Hitung dengan benar
                $categoryTotal     = 0;
                $categoryRealisasi = 0;
                $categorySisa      = 0;
                $categoryVolume    = 0;

                foreach ($items as $item) {
                    $volume     = $item->volume ?? 0;
                    $harga      = $item->harga_satuan ?? 0;
                    $subtotal   = $volume * $harga;
                    $realAmt    = $item->realisasi_amount ?? 0;

                    $categoryVolume    += $volume;
                    $categoryTotal     += $subtotal;
                    $categoryRealisasi += $realAmt;
                }

                $categorySisa    = max(0, $categoryTotal - $categoryRealisasi);
                $sisaPct         = $categoryTotal > 0 ? ($categorySisa / $categoryTotal) * 100 : 0;
                $categoryRealPct = $categoryTotal > 0 ? ($categoryRealisasi / $categoryTotal) * 100 : 0;

                $riskLabel = null;
                if ($categoryRealPct >= 100 || $categorySisa <= 0) {
                    $riskLabel = 'Over budget';
                } elseif ($categoryRealPct >= 90 || $sisaPct <= 10) {
                    $riskLabel = 'Hampir habis';
                }
            @endphp

            <div class="table-container">
                <div class="table-header {{ $headerClass }}"
                     onclick="toggleCategory('{{ $categorySlug }}')">
                    <div class="category-header-inner">
                        <div class="flex items-center gap-3">
                            <svg id="chevron-{{ $categorySlug }}"
                                 class="collapse-icon transform -rotate-90"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 19l-7-7 7-7"/>
                            </svg>
                            <div>
                                <div class="table-title-main">
                                    <span>{{ $categoryLabel }}</span>
                                    <span class="table-title-badge">
                                        {{ $items->count() }} item
                                    </span>
                                    @if($riskLabel)
                                        <span class="category-risk-badge">
                                            <span>?</span>
                                            <span>{{ $riskLabel }}</span>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="category-meta">
                            <div class="category-meta-pills">
                                <span class="category-meta-pill category-meta-pill--vol">
                                    <span class="category-meta-label">Vol</span>
                                    <span class="category-meta-value">
                                        {{ number_format($categoryVolume, 2, ',', '.') }}
                                    </span>
                                </span>
                                <span class="category-meta-pill">
                                    <span class="category-meta-label">RAPP</span>
                                    <span class="category-meta-value">
                                        Rp {{ number_format($categoryTotal, 0, ',', '.') }}
                                    </span>
                                </span>
                                <span class="category-meta-pill">
                                    <span class="category-meta-label">Real</span>
                                    <span class="category-meta-value">
                                        Rp {{ number_format($categoryRealisasi, 0, ',', '.') }}
                                    </span>
                                </span>
                                <span class="category-meta-pill">
                                    <span class="category-meta-label">Sisa</span>
                                    <span class="category-meta-value">
                                        Rp {{ number_format($categorySisa, 0, ',', '.') }}
                                        ({{ number_format($sisaPct, 1, ',', '.') }}%)
                                    </span>
                                </span>
                            </div>

                            {{-- progress kategori, tepat di bawah Vol/RAPP/Real/Sisa --}}
                            <div class="category-progress-wrapper">
                                <div class="category-progress-bg">
                                    <div class="category-progress-bar"
                                         style="width: {{ number_format(min(100, max(0, $categoryRealPct)), 2, '.', '') }}%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="table-content-{{ $categorySlug }}" class="table-content">
                    <div class="table-scroll-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="w-8 text-center">
                                        <input type="checkbox"
                                               class="select-all-checkbox"
                                               onclick="toggleSelectAllCategory('{{ $categorySlug }}', this)">
                                    </th>
                                    <th class="w-24">Kode</th>
                                    <th>Nama Item</th>
                                    <th class="w-16">Sat</th>
                                    <th class="w-24">Volume RAPP</th>
                                    <th class="w-28">Harga Satuan</th>
                                    <th class="w-28">Subtotal</th>
                                    <th class="w-28">Realisasi</th>
                                    <th class="w-28">Sisa</th>
                                    @if($editable)
                                        <th class="w-16 text-center">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($items as $item)
                                @php
                                    $dataItem   = $item->data ?? null;
                                    $kode       = $dataItem->kode ?? $item->kode ?? '-';
                                    $nama       = $dataItem->nama ?? $dataItem->uraian ?? $item->uraian_manual ?? 'Tanpa nama';
                                    $satuan     = $item->satuan ?? $dataItem->satuan ?? '-';

                                    $volume     = $item->volume ?? 0;
                                    $harga      = $item->harga_satuan ?? 0;
                                    $subtotal   = $volume * $harga;

                                    $realVol    = $item->realisasi_volume ?? 0;
                                    $realAmt    = $item->realisasi_amount ?? 0;

                                    $sisaAmt    = max(0, $subtotal - $realAmt);
                                    $realPctRow = $subtotal > 0 ? ($realAmt / $subtotal) * 100 : 0;
                                @endphp
                                <tr data-search-text="{{ Str::lower($kode . ' ' . $nama) }}">
                                    <td class="text-center">
                                        <input type="checkbox"
                                               class="row-item-checkbox category-{{ $categorySlug }}"
                                               data-item-id="{{ $item->id }}"
                                               data-item-name="{{ $nama }}"
                                               data-item-satuan="{{ $satuan }}"
                                               data-item-harga-rapp="{{ $harga }}"
                                               data-item-sisa-vol="{{ max(0, $volume - $realVol) }}"
                                               data-item-sisa-amount="{{ $sisaAmt }}"
                                               onchange="onRowCheckboxChanged(this)">
                                    </td>
                                    <td class="font-mono text-xs text-gray-800 dark:text-gray-100">
                                        {{ $kode }}
                                    </td>
                                    <td>
                                        <div class="text-xs text-gray-900 dark:text-gray-100">
                                            {{ $nama }}
                                        </div>
                                        @if($item->keterangan)
                                            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ $item->keterangan }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center text-xs text-gray-700 dark:text-gray-200">
                                        {{ $satuan }}
                                    </td>

                                    {{-- Volume RAPP inline --}}
                                    <td class="text-right text-xs text-gray-800 dark:text-gray-100">
                                        @if($editable)
                                            <input
                                                type="text"
                                                class="rapp-inline-input volume-input"
                                                id="inline-volume-{{ $item->id }}"
                                                value="{{ (float)$volume > 0 ? number_format((float)$volume, 2, ',', '.') : '' }}"
                                                placeholder="0,00"
                                                data-original-value="{{ (float)$volume > 0 ? number_format((float)$volume, 2, ',', '.') : '' }}"
                                                onblur="saveInlineRappItem({{ $item->id }}, 'volume', this)"
                                                onfocus="formatVolumeForEdit(this)"
                                            >
                                        @else
                                            {{ number_format($volume, 2, ',', '.') }}
                                        @endif
                                    </td>

                                    {{-- Harga Satuan inline --}}
                                    <td class="text-right cell-subtotal text-gray-800 dark:text-gray-100">
                                        @if($editable)
                                            <input
                                                type="text"
                                                class="rapp-inline-input harga-input"
                                                id="inline-harga-{{ $item->id }}"
                                                value="{{ (float)$harga > 0 ? number_format((float)$harga, 0, ',', '.') : '' }}"
                                                placeholder="0"
                                                data-original-value="{{ (float)$harga > 0 ? number_format((float)$harga, 0, ',', '.') : '' }}"
                                                onblur="saveInlineRappItem({{ $item->id }}, 'harga_satuan', this)"
                                                onfocus="formatHargaForEdit(this)"
                                            >
                                        @else
                                            Rp {{ number_format($harga, 0, ',', '.') }}
                                        @endif
                                    </td>

                                    {{-- Subtotal --}}
                                    <td class="text-right cell-subtotal text-gray-900 dark:text-gray-100" data-cell="subtotal">
                                        Rp {{ number_format($subtotal, 0, ',', '.') }}
                                    </td>

                                    {{-- Realisasi --}}
                                    <td class="text-right cell-realisasi">
                                        <div class="text-xs text-gray-900 dark:text-gray-100" data-cell="realisasi">
                                            Rp {{ number_format($realAmt, 0, ',', '.') }}
                                        </div>
                                        <div class="mt-0.5">
                                            @php
                                                $badgeClass = 'badge-percent-green';
                                                if ($realPctRow >= 100) {
                                                    $badgeClass = 'badge-percent-red';
                                                } elseif ($realPctRow >= 80) {
                                                    $badgeClass = 'badge-percent-amber';
                                                }
                                            @endphp
                                            <span class="badge-percent {{ $badgeClass }}" data-cell="badge">
                                                {{ number_format($realPctRow, 1, ',', '.') }}%
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Sisa --}}
                                    <td class="text-right cell-sisa">
                                        <div class="text-xs text-gray-900 dark:text-gray-100" data-cell="sisa">
                                            Rp {{ number_format($sisaAmt, 0, ',', '.') }}
                                        </div>
                                    </td>

                                    {{-- Aksi: HAPUS pensil (edit) karena inline edit sudah cukup --}}
                                    @if($editable)
                                        <td class="text-center">
                                            <form method="POST"
                                                  action="{{ route('dev.rab-baseline.items.destroy', [$project->id, $rab->id, $item->id]) }}"
                                                  class="inline"
                                                  onsubmit="return confirm('Hapus item ini dari RAPP?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon-danger">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Belum ada item di RAPP ini.
                </div>

                @if($canStructureEdit)
                    <div class="mt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <button type="button"
                                onclick="openAddItemsModal()"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 4v16m8-8H4"/>
                            </svg>
                            Tambah Items dari Data Master
                        </button>

                        <button type="button"
                                onclick="openImportExcelModal()"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5-5m0 0l5 5m-5-5v12"/>
                            </svg>
                            Import Excel
                        </button>

                        <a href="{{ route('dev.data.index') }}"
                           target="_blank"
                           class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Kelola Data Master
                        </a>
                    </div>
                @endif
            </div>
        @endforelse
    </div>
</div>

{{-- MODAL TAMBAH / EDIT ITEM RAPP --}}
@include('dev.rapps.partials.items-modal')

{{-- MODAL QUICK ADD (1 ITEM) --}}
<div id="quick-add-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeQuickAddModal()"></div>

        <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <button type="button" onclick="closeQuickAddModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="sm:flex sm:items-start">
                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                    <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-white mb-2">
                        Quick Add Item (1 item)
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        Tambahkan 1 item dari Data Master dengan mengisi Volume & Harga langsung.
                    </p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Pilih Item Data Master <span class="text-red-500">*</span>
                            </label>
                            <select id="quickAddDataId"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm"
                                    onchange="onQuickAddItemSelected()">
                                <option value="">-- pilih item --</option>
                                @foreach($availableItems as $d)
                                    <option value="{{ $d->id }}" 
                                            data-satuan="{{ $d->satuan ?? '' }}"
                                            data-kode="{{ $d->kode ?? '' }}"
                                            data-nama="{{ $d->uraian ?? '' }}">
                                        {{ $d->kode }} Ã¢â‚¬â€ {{ $d->uraian }} ({{ $d->satuan }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Volume RAPP <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="quickAddVolume"
                                       class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm"
                                       placeholder="0,00"
                                       value="1">
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                    Contoh: 1,00 atau 2,50
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Satuan
                                </label>
                                <input type="text" 
                                       id="quickAddSatuan"
                                       class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm bg-gray-50 dark:bg-gray-800"
                                       readonly>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Harga Satuan <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="quickAddHarga"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm"
                                   placeholder="0"
                                   value="0">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                Contoh: 1.280.000 atau 1280000
                            </p>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-3">
                            <p class="text-xs font-medium text-blue-800 dark:text-blue-200 mb-1">
                                Preview Subtotal:
                            </p>
                            <p id="quickAddSubtotalPreview" class="text-sm font-semibold text-blue-900 dark:text-blue-100">
                                Rp 0
                            </p>
                        </div>

                        <div id="quickAddError" class="hidden text-xs text-red-600 dark:text-red-300 p-2 bg-red-50 dark:bg-red-900/20 rounded-lg"></div>

                        <div class="mt-4 flex items-center justify-end gap-3">
                            <button type="button" onclick="closeQuickAddModal()"
                                    class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm">
                                Batal
                            </button>
                            <button type="button" onclick="submitQuickAdd()"
                                    class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-sm">
                                Tambah Item
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL IMPORT EXCEL --}}
<div id="import-excel-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeImportExcelModal()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200 dark:border-gray-700">
            <div class="bg-white dark:bg-gray-800 px-6 pt-5 pb-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-gray-100">
                            Import Item dari Excel
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                            Upload Excel berisi kolom: <b>Kode</b>, <b>Nama Item</b>, <b>Sat</b>, <b>Volume RAPP</b>, <b>Harga Satuan</b>.
                            Subtotal, Realisasi, dan Sisa dihitung otomatis.
                        </p>
                    </div>
                    <button type="button" onclick="closeImportExcelModal()" class="ml-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <a href="{{ route('dev.rab-baseline.template.excel', ['projectId' => $project->id, 'rabId' => $rab->id]) }}"
                       class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/>
                        </svg>
                        Download Template
                    </a>

                    <div class="text-xs text-gray-500 dark:text-gray-300">
                        Format angka boleh: <span class="font-mono">1.280.000</span> / <span class="font-mono">1280000</span> / <span class="font-mono">Rp 1.280.000</span>
                    </div>
                </div>

                <form action="{{ route('dev.rab-baseline.import.excel', ['projectId' => $project->id, 'rabId' => $rab->id]) }}"
                      method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">File Excel (.xlsx)</label>
                        <input type="file" name="excel_file" accept=".xlsx" required
                               class="mt-2 block w-full text-sm text-gray-900 dark:text-gray-100
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-lg file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-emerald-600 file:text-white
                                      hover:file:bg-emerald-700
                                      bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Mode Import</label>
                        <select name="import_mode"
                                class="mt-2 w-full rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2">
                            <option value="skip" selected>Skip jika Kode sudah ada di RAPP</option>
                            <option value="update">Update jika Kode sudah ada (Volume & Harga Satuan)</option>
                        </select>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" onclick="closeImportExcelModal()"
                                class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                            Upload & Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- MODAL VOUCHER PEMBELIAN --}}
@include('dev.rapps.partials.purchase-voucher-modal')

<script>
    // =========================
    // FUNGSI FORMATTING - PERBAIKAN
    // =========================
    
    // Format rupiah untuk display (dengan titik ribuan)
    function formatRupiahDisplay(number) {
        if (!number && number !== 0) return '0';
        
        const num = parseFloat(number);
        if (isNaN(num)) return '0';
        
        // Format: 1.234.567
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    
    // Format volume untuk display (2 desimal, koma sebagai pemisah)
    function formatVolumeDisplay(number) {
        if (!number && number !== 0) return '0,00';
        
        const num = parseFloat(number);
        if (isNaN(num)) return '0,00';
        
        // Format: 1.234,56
        return num.toFixed(2).replace('.', ',');
    }
    
    // Parse input rupiah ke angka - PERBAIKAN lebih akurat
    function parseRupiahToNumber(val) {
        if (val === null || val === undefined) return 0;
        const s = String(val).replace(/[^\d.,-]/g, '').trim();
        if (!s) return 0;

        // Format ID: 1.234.567,89
        if (s.includes('.') && s.includes(',')) {
            return parseFloat(s.replace(/\./g, '').replace(',', '.')) || 0;
        }

        // Koma bisa berarti desimal (ID: 1,25) atau ribuan (US/Excel: 44,000)
        if (s.includes(',') && !s.includes('.')) {
            const parts = s.split(',');
            if (parts.length === 2) {
                const left  = parts[0];
                const right = parts[1];

                const leftDigits  = /^-?\d+$/.test(left);
                const rightDigits = /^\d+$/.test(right);

                if (leftDigits && rightDigits) {
                    // ? 3 digit di belakang biasanya ribuan (US): 1,234 => 1234 ; 44,000 => 44000
                    if (right.length === 3) {
                        return parseFloat(left + right) || 0;
                    }

                    // ? 1-2 digit di belakang biasanya desimal Indonesia: 1,25 => 1.25
                    if (right.length >= 1 && right.length <= 2) {
                        return parseFloat(left + '.' + right) || 0;
                    }

                    // Fallback: gabungkan saja
                    return parseFloat(left + right) || 0;
                }

                // Fallback: coba parse sebagai desimal
                return parseFloat(left + '.' + right) || 0;
            }

            // Jika lebih dari 1 koma: anggap pemisah ribuan
            return parseFloat(s.replace(/,/g, '')) || 0;
        }

        // Titik sebagai pemisah ribuan (Format ID: 1.234.567)
        if (s.includes('.') && !s.includes(',')) {
            const parts = s.split('.');
            if (parts.length > 1) {
                // Jika ada lebih dari 1 bagian, dan semua bagian <= 3 digit kecuali yang pertama
                const allPartsValid = parts.every((part, index) => {
                    if (index === 0) return true; // Bagian pertama bisa berapa pun digit
                    return part.length === 3;
                });
                
                if (allPartsValid && parts.length > 1) {
                    return parseFloat(s.replace(/\./g, '')) || 0;
                }
            }
        }

        // Default: hapus semua titik/koma
        return parseFloat(s.replace(/[.,]/g, '')) || 0;
    }
    
    // Format harga untuk edit (hapus titik, ubah koma ke titik)
    function formatHargaForEdit(input) {
        const value = parseRupiahToNumber(input.value);
        if (value > 0) {
            input.value = value.toString();
        } else {
            input.value = '';
        }
        input.dataset.lastValue = input.value;
    }
    
    // Format volume untuk edit (ubah koma ke titik)
    function formatVolumeForEdit(input) {
        const value = parseFloat(input.value.replace(',', '.')) || 0;
        if (value > 0) {
            input.value = value.toString();
        } else {
            input.value = '';
        }
        input.dataset.lastValue = input.value;
    }
    
    // Format kembali setelah edit
    function formatAfterEdit(input, type) {
        const value = type === 'volume' 
            ? parseFloat(input.value.replace(',', '.')) || 0
            : parseRupiahToNumber(input.value);
            
        if (value > 0) {
            input.value = type === 'volume' 
                ? formatVolumeDisplay(value)
                : formatRupiahDisplay(value);
        } else {
            input.value = '';
        }
        input.dataset.lastValue = input.value;
    }

    // =========================
    // QUICK ADD FUNCTIONALITY
    // =========================

    function onQuickAddItemSelected() {
        const select = document.getElementById('quickAddDataId');
        const selectedOption = select.options[select.selectedIndex];
        const satuan = selectedOption ? selectedOption.getAttribute('data-satuan') : '';
        
        if (satuan) {
            document.getElementById('quickAddSatuan').value = satuan;
        }
        
        // Update preview subtotal
        updateQuickAddSubtotal();
    }

    function updateQuickAddSubtotal() {
        const volume = parseFloat(document.getElementById('quickAddVolume').value.replace(',', '.')) || 0;
        const harga = parseRupiahToNumber(document.getElementById('quickAddHarga').value);
        const subtotal = volume * harga;
        
        document.getElementById('quickAddSubtotalPreview').textContent = 
            'Rp ' + formatRupiahDisplay(subtotal);
    }

    async function submitQuickAdd() {
        const select = document.getElementById('quickAddDataId');
        const volumeInput = document.getElementById('quickAddVolume');
        const hargaInput = document.getElementById('quickAddHarga');
        const err = document.getElementById('quickAddError');
        
        const dataId = select ? select.value : '';
        const rawVolume = volumeInput ? volumeInput.value : '';
        const rawHarga = hargaInput ? hargaInput.value : '';
        
        // Reset error
        if (err) { 
            err.textContent = ''; 
            err.classList.add('hidden'); 
        }
        
        // Validasi
        if (!dataId) {
            if (err) { 
                err.textContent = 'Pilih item dari Data Master terlebih dahulu.'; 
                err.classList.remove('hidden'); 
            }
            select.focus();
            return;
        }
        
        const volume = parseFloat(rawVolume.replace(',', '.')) || 0;
        if (volume <= 0) {
            if (err) { 
                err.textContent = 'Volume harus lebih besar dari 0.'; 
                err.classList.remove('hidden'); 
            }
            volumeInput.focus();
            return;
        }
        
        const harga = parseRupiahToNumber(rawHarga);
        if (harga <= 0) {
            if (err) { 
                err.textContent = 'Harga satuan harus lebih besar dari 0.'; 
                err.classList.remove('hidden'); 
            }
            hargaInput.focus();
            return;
        }
        
        showRappLoading();
        
        try {
            const response = await fetch("{{ route('dev.rab-baseline.quick-add', [$project->id, $rab->id]) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    data_id: parseInt(dataId),
                    volume: volume,
                    harga_satuan: harga
                })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Gagal menambahkan item.');
            }
            
            if (data.success) {
                // Tutup modal dan reload halaman
                closeQuickAddModal();
                window.location.reload();
            } else {
                throw new Error(data.message || 'Gagal menambahkan item.');
            }
            
        } catch (error) {
            hideRappLoading();
            if (err) { 
                err.textContent = error.message || 'Terjadi kesalahan. Silakan coba lagi.'; 
                err.classList.remove('hidden'); 
            }
            console.error('Quick Add Error:', error);
        }
    }

    // =========================
    // FUNGSI UTILITAS
    // =========================
    
    function openQuickAddModal() {
        const m = document.getElementById('quick-add-modal');
        if (!m) return;
        const err = document.getElementById('quickAddError');
        if (err) { err.textContent = ''; err.classList.add('hidden'); }
        const sel = document.getElementById('quickAddDataId');
        if (sel) sel.value = '';
        const satuan = document.getElementById('quickAddSatuan');
        if (satuan) satuan.value = '';
        const volume = document.getElementById('quickAddVolume');
        if (volume) volume.value = '1';
        const harga = document.getElementById('quickAddHarga');
        if (harga) harga.value = '0';
        const preview = document.getElementById('quickAddSubtotalPreview');
        if (preview) preview.textContent = 'Rp 0';
        
        m.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    
    function closeQuickAddModal() {
        const m = document.getElementById('quick-add-modal');
        if (!m) return;
        m.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function openImportExcelModal() {
        const m = document.getElementById('import-excel-modal');
        if (!m) return;
        m.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    
    function closeImportExcelModal() {
        const m = document.getElementById('import-excel-modal');
        if (!m) return;
        m.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function showRappLoading() {
        const el = document.getElementById('globalLoadingOverlay');
        if (el) el.classList.remove('hidden');
    }

    function hideRappLoading() {
        const el = document.getElementById('globalLoadingOverlay');
        if (el) el.classList.add('hidden');
    }

    function showRappError(message) {
        const toast = document.getElementById('rappErrorToast');
        const msgEl = document.getElementById('rappErrorToastMessage');
        if (!toast || !msgEl) return;

        msgEl.textContent = message || 'Terjadi kesalahan.';
        toast.classList.remove('hidden');

        clearTimeout(window.__rappErrorToastTimeout);
        window.__rappErrorToastTimeout = setTimeout(() => {
            toast.classList.add('hidden');
        }, 4000);
    }

    function onRowCheckboxChanged(cb) {
        updateVoucherActionBar();
    }

    function toggleCategory(slug) {
        const content = document.getElementById('table-content-' + slug);
        const icon    = document.getElementById('chevron-' + slug);
        if (!content) return;

        const isHidden = content.style.display === 'none';
        content.style.display = isHidden ? '' : 'none';

        if (icon) {
            icon.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(-90deg)';
        }
    }

    function collapseAllCategories() {
        const contents = document.querySelectorAll('[id^="table-content-"]');
        contents.forEach(c => c.style.display = 'none');

        const icons = document.querySelectorAll('[id^="chevron-"]');
        icons.forEach(icon => icon.style.transform = 'rotate(-90deg)');
    }

    function expandAllCategories() {
        const contents = document.querySelectorAll('[id^="table-content-"]');
        contents.forEach(c => c.style.display = '');

        const icons = document.querySelectorAll('[id^="chevron-"]');
        icons.forEach(icon => icon.style.transform = 'rotate(0deg)');
    }

    function toggleSelectAllCategory(slug, source) {
        const checked = source.checked;
        const checkboxes = document.querySelectorAll('.row-item-checkbox.category-' + slug);
        checkboxes.forEach(cb => cb.checked = checked);
        if (typeof updateBulkDeleteUI === 'function') updateBulkDeleteUI();
        updateVoucherActionBar();
    }

    function updateVoucherActionBar() {
        const bar = document.getElementById('voucherActionBar');
        const countEl = document.getElementById('selectedCount');
        if (!bar || !countEl) return;

        const selected = document.querySelectorAll('.row-item-checkbox:checked');
        const count    = selected.length;

        if (count > 0) bar.classList.remove('hidden');
        else bar.classList.add('hidden');

        countEl.textContent = String(count);
    }

    function onSearchItems(term) {
        const value = (term || '').toString().toLowerCase().trim();
        const rows  = document.querySelectorAll('table.data-table tbody tr');

        if (!rows) return;

        rows.forEach(row => {
            const text = (row.dataset.searchText || '').toLowerCase();
            if (!value) {
                row.style.display = '';
            } else {
                row.style.display = text.includes(value) ? '' : 'none';
            }
        });
    }

    @if($hasItems)
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('rapp-voucher-fab')) return;

        var wrapper = document.createElement('div');
        wrapper.id = 'rapp-voucher-fab-wrapper';
        wrapper.className = 'flex flex-col items-end gap-2';
        wrapper.style.position = 'fixed';
        wrapper.style.bottom = '1.5rem';
        wrapper.style.right  = '1.5rem';
        wrapper.style.zIndex = '1000';

        var menu = document.createElement('div');
        menu.id = 'rapp-voucher-menu';
        menu.className = 'hidden mb-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg text-xs md:text-sm overflow-hidden';

        var btnCreate = document.createElement('button');
        btnCreate.type = 'button';
        btnCreate.className = 'block w-full text-left px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700';
        btnCreate.textContent = 'Buat Voucher Pembelian';
        btnCreate.onclick = function () {
            if (typeof openPurchaseVoucherModal === 'function') {
                openPurchaseVoucherModal();
            }
        };

        var linkRekap = document.createElement('a');
        linkRekap.href = '{{ route('dev.rab-baseline.purchase-vouchers.index', ['projectId' => $project->id, 'rabId' => $rab->id]) }}';
        linkRekap.className = 'block w-full text-left px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 border-t border-gray-100 dark:border-gray-700';
        linkRekap.textContent = 'Lihat Rekap Voucher';

        menu.appendChild(btnCreate);
        menu.appendChild(linkRekap);

        var fab = document.createElement('button');
        fab.type = 'button';
        fab.id = 'rapp-voucher-fab';
        fab.className = 'w-11 h-11 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500';
        fab.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>';

        fab.onclick = function () {
            menu.classList.toggle('hidden');
        };

        wrapper.appendChild(menu);
        wrapper.appendChild(fab);
        document.body.appendChild(wrapper);

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target) && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
            }
        });
    });
    @endif

    // ================================
    // Inline edit Volume RAPP / Harga Satuan (NO RELOAD)
    // ================================

    function updateRowUI(itemId, newVolume, newHarga, newSubtotal){
        const volInput = document.getElementById('inline-volume-' + itemId);
        const hargaInput = document.getElementById('inline-harga-' + itemId);
        const row = (volInput || hargaInput) ? (volInput || hargaInput).closest('tr') : null;
        if (!row) return;

        // Format input dengan benar
        if (volInput){
            // Volume: tampilkan dengan 2 desimal, koma sebagai pemisah desimal
            const volFormatted = (Number(newVolume) > 0) 
                ? formatVolumeDisplay(newVolume)
                : '';
            volInput.value = volFormatted;
            volInput.dataset.lastValue = volFormatted;
        }
        
        if (hargaInput){
            // Harga: format rupiah dengan titik pemisah ribuan
            const hargaFormatted = (Number(newHarga) > 0) 
                ? formatRupiahDisplay(newHarga)
                : '';
            hargaInput.value = hargaFormatted;
            hargaInput.dataset.lastValue = hargaFormatted;
        }

        // Update subtotal cell dengan format rupiah
        const subtotalCell = row.querySelector('[data-cell="subtotal"]');
        if (subtotalCell){
            subtotalCell.textContent = 'Rp ' + formatRupiahDisplay(newSubtotal);
        }

        // realisasi amount ambil dari cell realisasi
        const realCell = row.querySelector('[data-cell="realisasi"]');
        const realText = realCell ? realCell.textContent.replace('Rp ', '').replace(/\./g, '') : '0';
        const realAmt = parseFloat(realText.replace(',', '.')) || 0;

        // update sisa dengan format rupiah
        const sisa = Math.max(0, (Number(newSubtotal) || 0) - (Number(realAmt) || 0));
        const sisaCell = row.querySelector('[data-cell="sisa"]');
        if (sisaCell){
            sisaCell.textContent = 'Rp ' + formatRupiahDisplay(sisa);
        }

        // update badge %
        const badge = row.querySelector('[data-cell="badge"]');
        if (badge){
            const pct = (Number(newSubtotal) > 0) ? ((realAmt / newSubtotal) * 100) : 0;
            badge.textContent = pct.toFixed(1).replace('.', ',') + '%';

            badge.classList.remove('badge-percent-green','badge-percent-amber','badge-percent-red');
            if (pct >= 100) badge.classList.add('badge-percent-red');
            else if (pct >= 80) badge.classList.add('badge-percent-amber');
            else badge.classList.add('badge-percent-green');
        }
    }

    function updateTotalRappUI(totalBudget){
        const elTotalRapp = document.getElementById('total-rapp-amount');
        if (elTotalRapp){
            elTotalRapp.textContent = 'Rp ' + formatRupiahDisplay(totalBudget);
        }
    }

    // PERBAIKAN: Save inline dengan validasi dan konsistensi
    async function saveInlineRappItem(itemId, field, inputElement) {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrf) {
                showRappError('CSRF token tidak ditemukan. Pastikan meta csrf-token ada di layout.');
                return;
            }

        let inputEl = inputElement;
        if (!inputEl) {
            if (field === 'volume') inputEl = document.getElementById('inline-volume-' + itemId);
            if (field === 'harga_satuan') inputEl = document.getElementById('inline-harga-' + itemId);
        }
        
        if (!inputEl) return;

        const raw = inputEl.value;
        
        // Parsing yang lebih baik untuk berbagai format
        let value = 0;
        if (field === 'volume') {
            // Untuk volume, parse dengan support koma desimal
            value = parseFloat(raw.replace(',', '.')) || 0;
        } else {
            // Untuk harga, gunakan parser rupiah yang lebih komprehensif
            value = parseRupiahToNumber(raw);
        }
        
        if (!isFinite(value)) value = 0;

        // Validasi minimal
        if (value < 0) {
            showRappError('Nilai tidak boleh negatif.');
            // Reset ke nilai sebelumnya
            const originalValue = inputEl.getAttribute('data-original-value') || 
                                 (field === 'volume' ? '0,00' : '0');
            inputEl.value = originalValue;
            inputEl.dataset.lastValue = originalValue;
            return;
        }

        // Simpan nilai terakhir untuk fallback
        inputEl.dataset.lastValue = inputEl.value;

        showRappLoading();

        const url = `{{ route('dev.rab-baseline.items.update', [$project->id, $rab->id, 'ITEM_ID']) }}`.replace('ITEM_ID', itemId);

        const res = await fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ field, value })
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data.success) {
            const msg = data.message || 'Gagal menyimpan perubahan.';
            showRappError(msg);
            // Kembalikan ke nilai sebelumnya
            const lastValue = inputEl.dataset.lastValue || inputEl.getAttribute('data-original-value') || '';
            inputEl.value = lastValue;
            return;
        }

        // Update UI row + total RAPP tanpa reload
        if (data.item) {
            updateRowUI(
                data.item.id,
                data.item.volume,
                data.item.harga_satuan,
                data.item.subtotal
            );
        }
        if (typeof data.total_budget !== 'undefined') {
            updateTotalRappUI(data.total_budget);
        }

        } catch (e) {
            showRappError(e?.message || 'Terjadi error saat menyimpan.');
            // Kembalikan ke nilai sebelumnya
            if (inputEl && inputEl.dataset.lastValue) {
                inputEl.value = inputEl.dataset.lastValue;
            }
        } finally {
            hideRappLoading();
        }
    }

    // =========================
    // BULK DELETE (Hapus Terpilih)
    // =========================
    function getSelectedItemIds() {
        return Array.from(document.querySelectorAll('.row-item-checkbox:checked'))
            .map(cb => cb.getAttribute('data-item-id'))
            .filter(Boolean);
    }

    function updateBulkDeleteUI() {
        const btn = document.getElementById('bulkDeleteBtn');
        const countEl = document.getElementById('bulkDeleteCount');
        if (!btn || !countEl) return;

        const ids = getSelectedItemIds();
        countEl.textContent = '(' + ids.length + ')';
        btn.disabled = ids.length === 0;
    }

    function bulkDeleteSelected() {
        const form = document.getElementById('bulkDeleteForm');
        const holder = document.getElementById('bulkDeleteIds');
        if (!form || !holder) return;

        const ids = getSelectedItemIds();
        if (ids.length === 0) {
            updateBulkDeleteUI();
            return;
        }

        const msg = 'Hapus ' + ids.length + ' item yang dipilih?\\nTindakan ini tidak bisa dibatalkan.';
        if (!confirm(msg)) return;

        holder.innerHTML = '';
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'item_ids[]';
            input.value = id;
            holder.appendChild(input);
        });

        form.submit();
    }

    // =========================
    // PERBAIKAN: Data consistency validation
    // =========================
    function validateDataConsistency() {
        const totalRappElement = document.getElementById('total-rapp-amount');
        if (!totalRappElement) return true;
        
        const displayedTotal = parseRupiahToNumber(totalRappElement.textContent);
        
        // Hitung total dari semua items yang ditampilkan
        let calculatedTotal = 0;
        document.querySelectorAll('[data-cell="subtotal"]').forEach(cell => {
            const subtotal = parseRupiahToNumber(cell.textContent);
            calculatedTotal += subtotal;
        });
        
        const difference = Math.abs(displayedTotal - calculatedTotal);
        const isConsistent = difference < 1; // Toleransi 1 untuk rounding
        
        if (!isConsistent) {
            console.warn('Data inconsistency detected:', {
                displayed: displayedTotal,
                calculated: calculatedTotal,
                difference: difference
            });
        }
        
        return isConsistent;
    }

    // =========================
    // EVENT LISTENERS & INIT
    // =========================
    document.addEventListener('DOMContentLoaded', function () {
        // Format nilai awal saat halaman load
        document.querySelectorAll('input[class*="rapp-inline-input"]').forEach(input => {
            if (input.id.includes('inline-harga-')) {
                const value = parseRupiahToNumber(input.value);
                if (value > 0) {
                    input.value = formatRupiahDisplay(value);
                    input.dataset.lastValue = input.value;
                }
            } else if (input.id.includes('inline-volume-')) {
                const value = parseFloat(input.value.replace(',', '.')) || 0;
                if (value > 0) {
                    input.value = formatVolumeDisplay(value);
                    input.dataset.lastValue = input.value;
                }
            }
        });

        // Quick Add event listeners
        const volumeInput = document.getElementById('quickAddVolume');
        const hargaInput = document.getElementById('quickAddHarga');
        
        if (volumeInput) {
            volumeInput.addEventListener('input', function() {
                // Format volume dengan koma desimal
                let value = this.value.replace(/[^\d,]/g, '');
                value = value.replace(',', '.');
                if (value.includes('.')) {
                    const parts = value.split('.');
                    if (parts[1].length > 2) {
                        parts[1] = parts[1].substring(0, 2);
                        value = parts.join('.');
                    }
                }
                this.value = value.replace('.', ',');
                updateQuickAddSubtotal();
            });
        }
        
        if (hargaInput) {
            hargaInput.addEventListener('input', function() {
                // Format harga dengan pemisah ribuan
                let value = this.value.replace(/[^\d]/g, '');
                if (value) {
                    const num = parseInt(value, 10);
                    this.value = formatRupiahDisplay(num);
                }
                updateQuickAddSubtotal();
            });
        }

        // Enter jangan submit form (Enter => blur => auto save)
        document.addEventListener('keydown', function (e) {
            const t = e.target;
            if (!t) return;
            if (t.classList && t.classList.contains('rapp-inline-input')) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    t.blur();
                }
            }
        });

        // Format real-time saat blur
        document.querySelectorAll('.rapp-inline-input').forEach(input => {
            input.addEventListener('blur', function(e) {
                if (this.id.includes('inline-harga-')) {
                    formatAfterEdit(this, 'harga');
                } else if (this.id.includes('inline-volume-')) {
                    formatAfterEdit(this, 'volume');
                }
            });
        });

        // update on any checkbox changes (row & select-all)
        document.querySelectorAll('.row-item-checkbox, .select-all-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkDeleteUI);
        });
        
        updateBulkDeleteUI();
        
        // Initial quick add preview
        updateQuickAddSubtotal();
        
        // Validasi konsistensi data saat load
        setTimeout(() => {
            const isConsistent = validateDataConsistency();
            if (!isConsistent && @json($isAdmin)) {
                console.warn('? Data RAPP tidak konsisten antara tampilan dan perhitungan');
            }
        }, 500);

        const copyBtn = document.getElementById('copyCanonicalLinkBtn');
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
@endsection











