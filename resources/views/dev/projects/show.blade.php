{{-- resources/views/dev/projects/show.blade.php --}}
@extends('layouts.dev')

@section('title', 'Project - ' . ($project->name ?? 'Detail Proyek'))
@section('subtitle', 'Detail Proyek, RAPP, dan RAB Breakdown')

@section('content')
@php
    $fmtDate = function($date) {
        return $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '-';
    };

    $fmtRp = function($n) {
        $n = (float) ($n ?? 0);
        return 'Rp ' . number_format(max($n, 0), 0, ',', '.');
    };

    $rabs        = $project->rabs ?? collect();
    $totalRabs   = $rabs->count();
    $projectName = $project->name ?? '-';
    $clientName  = optional($project->client)->name ?? '-';

    // Ringkasan angka sederhana (tanpa helper model)
    $projectBudget     = (float) ($project->budget ?? 0);
    $totalRabAll       = (float) $rabs->sum('total_budget');
    $latestRab         = $rabs->sortByDesc('created_at')->first();
    $referenceRab      = $rabs->where('status', 'approved')->sortByDesc('approved_at')->first();
    $controlRab        = $referenceRab ?: $latestRab;
    $controlRabTotal   = $controlRab ? (float) ($controlRab->total_budget ?? 0) : 0;
    $remainingBudget   = $projectBudget > 0 ? $projectBudget - $controlRabTotal : 0;
    $percentageUsed    = $projectBudget > 0 ? ($controlRabTotal / $projectBudget * 100) : 0;
    $percentageUsed    = max(0, min(200, $percentageUsed)); // dibatasi 200% supaya bar tidak aneh
    $barWidth          = max(0, min(100, $percentageUsed)); // progress bar maksimal 100%
    $canOpenBreakdown  = !is_null($referenceRab);
@endphp

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white truncate">
                Detail Proyek
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm truncate">
                {{ $projectName }} @if($clientName && $clientName !== '-') Ã¢â‚¬Â¢ {{ $clientName }} @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dev.projects.index') }}"
               class="flex items-center px-3 py-2 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-800">
                &larr; Daftar Proyek
            </a>

            <a href="{{ route('dev.projects.edit', $project->id) }}"
               class="flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium">
                Edit Proyek
            </a>

            <a href="{{ route('dev.rab-baseline.index', $project->id) }}"
               class="flex items-center px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium">
                RAPP
            </a>

            @if($canOpenBreakdown)
                <a href="{{ route('dev.rab-breakdown.index', $project->id) }}"
                   class="flex items-center px-3 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 12h10M4 18h16M14 6h6M16 12h4M18 18h2"/>
                    </svg>
                    RAB Breakdown
                </a>
            @else
                <button type="button" disabled
                        class="flex items-center px-3 py-2 bg-gray-400 text-white rounded-lg text-xs font-semibold cursor-not-allowed"
                        title="RAB Breakdown aktif setelah ada RAPP approved">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-1.414-1.414A2 2 0 0015.536 3H8.464a2 2 0 00-1.414.586L5.636 5A2 2 0 005 6.414V18a2 2 0 002 2h10a2 2 0 002-2V6.414a2 2 0 00-.636-1.414zM9 11h6M9 15h6"/>
                    </svg>
                    RAB Breakdown (Terkunci)
                </button>
            @endif
        </div>
    </div>

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/40 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-xs text-emerald-800 dark:text-emerald-100">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/40 border border-red-200 dark:border-red-800 px-4 py-3 text-xs text-red-800 dark:text-red-100">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Langkah 1</div>
            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">RAPP (Rekap Utama)</div>
            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">Susun item, volume, harga, lalu approve sebagai acuan project.</div>
            <a href="{{ route('dev.rab-baseline.index', $project->id) }}"
               class="inline-flex items-center mt-3 px-3 py-1.5 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium">
                Buka RAPP
            </a>
        </div>
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Langkah 2</div>
            <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">RAB Breakdown (Detail Teknis)</div>
            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">Turunan dari RAPP approved untuk rincian teknis: P/L/T/N, Qty, Qty Beli, Jumlah.</div>
            @if($canOpenBreakdown)
                <a href="{{ route('dev.rab-breakdown.index', $project->id) }}"
                   class="inline-flex items-center mt-3 px-3 py-1.5 rounded-md bg-sky-600 hover:bg-sky-700 text-white text-xs font-medium">
                    Buka RAB Breakdown
                </a>
            @else
                <span class="inline-flex items-center mt-3 px-3 py-1.5 rounded-md bg-amber-100 text-amber-800 text-xs font-medium">
                    Menunggu RAPP Approved
                </span>
            @endif
        </div>
    </div>

    {{-- STAT CARDS SEDERHANA (MIRIP INDEX STYLE) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400">Budget Proyek</div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white truncate">
                {{ $fmtRp($projectBudget) }}
            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Nilai kontrak / rencana
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400">Total Semua RAPP</div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white truncate">
                {{ $fmtRp($totalRabAll) }}
            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Akumulasi seluruh RAPP proyek ini
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400">Jumlah RAPP</div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                {{ $totalRabs }}
            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Draft, diajukan, maupun approved
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400">Persentase Terpakai (RAPP Acuan)</div>
            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                {{ number_format($percentageUsed, 2, ',', '.') }}%
            </div>
            <div class="mt-2 w-full h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                <div class="h-1.5 rounded-full {{ $percentageUsed > 100 ? 'bg-red-500' : 'bg-emerald-500' }}"
                     style="width: {{ $barWidth }}%;"></div>
            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Dibandingkan dengan budget proyek (pakai RAPP approved jika ada)
            </div>
        </div>
    </div>

    {{-- SHORTCUT DOKUMEN PER PROYEK (RAPP APPROVED) --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                Shortcut Dokumen (RAPP Approved)
            </h2>
            <span class="text-[11px] text-gray-500 dark:text-gray-400">
                @if($referenceRab)
                    {{ $referenceRab->name ?? 'RAPP #'.$referenceRab->id }}
                @else
                    Belum ada RAPP approved
                @endif
            </span>
        </div>
        <div class="px-4 py-4">
            @if($referenceRab)
                @php
                    $docShortcuts = [
                        [
                            'label' => 'SPP',
                            'route' => route('dev.rab-baseline.spps.index', [$project->id, $referenceRab->id]),
                            'cardClass' => 'border-sky-200 dark:border-sky-900 bg-sky-50 dark:bg-sky-900/30',
                            'buttonClass' => 'bg-sky-600 hover:bg-sky-700',
                        ],
                        [
                            'label' => 'BPG',
                            'route' => route('dev.rab-baseline.bpgs.index', [$project->id, $referenceRab->id]),
                            'cardClass' => 'border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-900/30',
                            'buttonClass' => 'bg-amber-600 hover:bg-amber-700',
                        ],
                        [
                            'label' => 'LPB',
                            'route' => route('dev.rab-baseline.lpbs.index', [$project->id, $referenceRab->id]),
                            'cardClass' => 'border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-900/30',
                            'buttonClass' => 'bg-emerald-600 hover:bg-emerald-700',
                        ],
                        [
                            'label' => 'PO Material',
                            'route' => route('dev.rab-baseline.purchase-orders.index', [$project->id, $referenceRab->id]),
                            'cardClass' => 'border-indigo-200 dark:border-indigo-900 bg-indigo-50 dark:bg-indigo-900/30',
                            'buttonClass' => 'bg-indigo-600 hover:bg-indigo-700',
                        ],
                        [
                            'label' => 'SPK',
                            'route' => route('dev.rab-baseline.spks.index', [$project->id, $referenceRab->id]),
                            'cardClass' => 'border-rose-200 dark:border-rose-900 bg-rose-50 dark:bg-rose-900/30',
                            'buttonClass' => 'bg-rose-600 hover:bg-rose-700',
                        ],
                        [
                            'label' => 'Komparasi Vendor',
                            'route' => route('dev.rab-baseline.vendor-comparisons.index', [$project->id, $referenceRab->id]),
                            'cardClass' => 'border-purple-200 dark:border-purple-900 bg-purple-50 dark:bg-purple-900/30',
                            'buttonClass' => 'bg-purple-600 hover:bg-purple-700',
                        ],
                        [
                            'label' => 'Voucher Pembelian',
                            'route' => route('dev.rab-baseline.purchase-vouchers.index', [$project->id, $referenceRab->id]),
                            'cardClass' => 'border-teal-200 dark:border-teal-900 bg-teal-50 dark:bg-teal-900/30',
                            'buttonClass' => 'bg-teal-600 hover:bg-teal-700',
                        ],
                    ];
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    @foreach($docShortcuts as $shortcut)
                        @php
                            $shortcutHoOnly = $shortcut['hoOnly'] ?? false;
                            $canSeeShortcut = !$shortcutHoOnly || (auth()->check() && ((auth()->user()->is_admin ?? false) || (method_exists(auth()->user(), 'isHO') && auth()->user()->isHO())));
                        @endphp
                        @if($canSeeShortcut)
                        <div class="border rounded-lg px-3 py-3 {{ $shortcut['cardClass'] }}">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $shortcut['label'] }}
                            </div>
                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                {{ $shortcut['description'] ?? 'Shortcut ke dokumen RAPP terbaru' }}
                            </div>
                            <div class="mt-3">
                                <a href="{{ $shortcut['route'] }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-md text-white text-[11px] {{ $shortcut['buttonClass'] }}">
                                    Buka
                                </a>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Belum ada RAPP approved untuk proyek ini. Sesuai alur konstruksi, dokumen turunan aktif setelah RAPP disetujui.
                </p>
                <a href="{{ route('dev.rab-baseline.create', $project->id) }}"
                   class="inline-flex items-center mt-3 px-3 py-1.5 rounded-md bg-emerald-600 hover:bg-emerald-700 text-[11px] font-medium text-white">
                   + Buat RAPP pertama
                </a>
            @endif
        </div>
    </div>

    {{-- KONTEN UTAMA: FULL WIDTH (BIAR TIDAK MELEBIHI CARD) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- INFO PROYEK --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Informasi Proyek</h2>
            </div>
            <div class="px-4 py-4">
                <table class="w-full text-xs sm:text-sm border-separate border-spacing-y-2">
                    <tbody>
                        <tr>
                            <td class="align-top w-32 sm:w-40 text-gray-500 dark:text-gray-400">Nama Proyek</td>
                            <td class="align-top text-gray-900 dark:text-white font-medium break-words">
                                {{ $projectName }}
                            </td>
                        </tr>
                        <tr>
                            <td class="align-top text-gray-500 dark:text-gray-400">Client</td>
                            <td class="align-top text-gray-900 dark:text-white break-words">
                                {{ $clientName }}
                            </td>
                        </tr>
                        <tr>
                            <td class="align-top text-gray-500 dark:text-gray-400">Kode Proyek</td>
                            <td class="align-top text-gray-900 dark:text-white break-words">
                                {{ $project->code ?? 'PRJ-' . $project->id }}
                            </td>
                        </tr>
                        <tr>
                            <td class="align-top text-gray-500 dark:text-gray-400">Lokasi</td>
                            <td class="align-top text-gray-900 dark:text-white break-words">
                                {{ $project->location ?? '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="align-top text-gray-500 dark:text-gray-400">Provinsi</td>
                            <td class="align-top text-gray-900 dark:text-white break-words">
                                {{ optional($project->province)->name ?? '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="align-top text-gray-500 dark:text-gray-400">Tanggal Mulai</td>
                            <td class="align-top text-gray-900 dark:text-white">
                                {{ $fmtDate($project->start_date ?? null) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="align-top text-gray-500 dark:text-gray-400">Status Proyek</td>
                            <td class="align-top text-gray-900 dark:text-white break-words">
                                {{ $project->status ?? '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="align-top text-gray-500 dark:text-gray-400">Budget Proyek</td>
                            <td class="align-top text-gray-900 dark:text-white">
                                {{ $fmtRp($projectBudget) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="align-top text-gray-500 dark:text-gray-400">Catatan</td>
                            <td class="align-top text-gray-900 dark:text-white whitespace-pre-wrap break-words">
                                {{ $project->notes ?? '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- RINGKASAN RAPP PROYEK --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                    Ringkasan RAPP Proyek (Baseline)
                </h2>
                <span class="text-[11px] text-gray-500 dark:text-gray-400">
                    Total RAPP: {{ $totalRabs }}
                </span>
            </div>
            <div class="px-4 py-4 space-y-4 text-xs sm:text-sm">

                {{-- RAPP ACUAN --}}
                <div class="rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/60 px-3 py-3">
                    @if($controlRab)
                        <div class="flex justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">RAPP Acuan</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $controlRab->name ?? 'RAPP #'.$controlRab->id }}
                                </div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ $fmtDate($controlRab->created_at ?? null) }} Ã¢â‚¬Â¢ Status: {{ $controlRab->status ?? 'draft' }}
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">Total</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $fmtRp($controlRabTotal) }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 border-t border-dashed border-gray-200 dark:border-gray-700 pt-2">
                            <div class="flex justify-between text-[11px] mb-1">
                                <span class="text-gray-500 dark:text-gray-400">Sisa Budget (perkiraan)</span>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ $fmtRp($remainingBudget) }}
                                </span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-1.5 rounded-full {{ $percentageUsed > 100 ? 'bg-red-500' : 'bg-emerald-500' }}"
                                     style="width: {{ $barWidth }}%;"></div>
                            </div>
                        </div>

                        <div class="mt-3 text-right">
                            <a href="{{ route('dev.rab-baseline.table', ['projectId' => $project->id, 'rabId' => $controlRab->id]) }}"
                               class="inline-flex items-center px-3 py-1.5 rounded-md bg-emerald-600 hover:bg-emerald-700 text-[11px] font-medium text-white">
                                Buka Detail RAPP Acuan
                            </a>
                        </div>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Belum ada RAPP yang dibuat untuk proyek ini.
                        </p>
                        <a href="{{ route('dev.rab-baseline.create', $project->id) }}"
                           class="inline-flex items-center mt-2 px-3 py-1.5 rounded-md bg-emerald-600 hover:bg-emerald-700 text-[11px] font-medium text-white">
                           + Buat RAPP pertama
                        </a>
                    @endif
                </div>

                {{-- DAFTAR SEMUA RAPP --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-semibold text-gray-800 dark:text-gray-100">
                            Daftar RAPP Proyek Ini
                        </h3>
                    </div>

                    @if($totalRabs > 0)
                        <div class="border border-gray-100 dark:border-gray-800 rounded-lg overflow-hidden">
                                <table class="min-w-full text-[11px]">
                                    <thead class="bg-gray-50 dark:bg-gray-800/70">
                                        <tr>
                                            <th class="px-2 py-1.5 text-left font-semibold text-[10px] text-gray-500 dark:text-gray-300 uppercase tracking-wide">
                                                Nama RAPP
                                            </th>
                                            <th class="px-2 py-1.5 text-left font-semibold text-[10px] text-gray-500 dark:text-gray-300 uppercase tracking-wide">
                                                Tanggal
                                            </th>
                                            <th class="px-2 py-1.5 text-left font-semibold text-[10px] text-gray-500 dark:text-gray-300 uppercase tracking-wide">
                                                Status
                                            </th>
                                            <th class="px-2 py-1.5 text-right font-semibold text-[10px] text-gray-500 dark:text-gray-300 uppercase tracking-wide">
                                                Total
                                            </th>
                                            <th class="px-2 py-1.5 text-right font-semibold text-[10px] text-gray-500 dark:text-gray-300 uppercase tracking-wide">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                                        @foreach($rabs->sortByDesc('created_at') as $rab)
                                            <tr>
                                                <td class="px-2 py-1.5 text-gray-900 dark:text-white max-w-[220px] whitespace-normal break-words">
                                                    {{ $rab->name ?? 'RAPP #'.$rab->id }}
                                                </td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                                    {{ $fmtDate($rab->created_at ?? null) }}
                                                </td>
                                                <td class="px-2 py-1.5 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                                    {{ $rab->status ?? 'draft' }}
                                                </td>
                                                <td class="px-2 py-1.5 text-right text-gray-900 dark:text-white whitespace-nowrap">
                                                    {{ $fmtRp($rab->total_budget ?? 0) }}
                                                </td>
                                                <td class="px-2 py-1.5 text-right">
                                                    <a href="{{ route('dev.rab-baseline.table', ['projectId' => $project->id, 'rabId' => $rab->id]) }}"
                                                       class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-900 text-white text-[10px] hover:bg-black">
                                                        Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                        </div>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Belum ada RAPP yang terdaftar untuk proyek ini.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- DOKUMEN PROYEK (FULL WIDTH, TABEL SEDERHANA) --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                Dokumen / Lampiran Proyek
            </h2>
        </div>
        <div class="px-4 py-4 text-sm">
            @if($project->files && $project->files->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs sm:text-sm divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800/70">
                            <tr>
                                <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">
                                    Nama File
                                </th>
                                <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">
                                    Keterangan
                                </th>
                                <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">
                                    Upload
                                </th>
                                <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($project->files as $file)
                                <tr>
                                    <td class="px-3 py-2 text-gray-900 dark:text-white break-words max-w-[220px]">
                                        {{ $file->original_name ?? basename($file->path) }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300 break-words max-w-[260px] text-xs">
                                        {{ $file->description ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300 text-xs whitespace-nowrap">
                                        {{ $fmtDate($file->created_at ?? null) }}
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        @if(!empty($file->path))
                                            <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}"
                                               target="_blank"
                                               class="inline-flex items-center px-2 py-1 rounded-md bg-gray-900 text-white text-[11px] hover:bg-black">
                                                Lihat
                                            </a>
                                        @else
                                            <span class="text-[11px] text-gray-400">Path tidak tersedia</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Belum ada file yang diupload untuk proyek ini.
                </p>
            @endif
        </div>
    </div>
</div>
@endsection





