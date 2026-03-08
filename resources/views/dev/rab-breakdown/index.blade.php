@extends('layouts.dev')

@section('title', 'RAB Breakdown - ' . $project->name)
@section('subtitle', 'Ringkasan, Daftar Breakdown, dan Detail Item')

@section('content')
@include('dev.rab-breakdown.partials.shared-theme')
@php
    $fmtRp = fn($n) => 'Rp ' . number_format((float) ($n ?? 0), 0, ',', '.');
    $isHO = auth()->check() && auth()->user() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO();
    $showActual = false; // Toggle tampilan nilai aktual (sementara disembunyikan)

    $firstBreakdown = $rabBreakdownList->first();
    $firstBreakdownUrl = $firstBreakdown
        ? route('dev.rab-breakdown.show', ['projectId' => $project->id, 'rabBreakdown' => $firstBreakdown->id])
        : null;

    $step1Done = !empty($referenceRab) && strtolower((string) ($referenceRab->status ?? '')) === 'approved';
    $step2Done = $rabBreakdownList->count() > 0;
    $step3Done = $rabBreakdownList->sum('items_count') > 0;
@endphp

<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">RAB Breakdown</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $project->name }} - {{ optional($project->client)->name ?? '-' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dev.projects.show', $project->id) }}" class="rb-btn-outline rb-btn-xs">Kembali Project</a>
            <a href="{{ route('dev.rab-baseline.index', $project->id) }}" class="rb-btn-outline rb-btn-xs">RAPP</a>
            <a href="{{ route('dev.rab-breakdown.create', $project->id) }}" class="rb-btn-primary rb-btn-xs">+ Buat Breakdown</a>
        </div>
    </div>

    <div class="rounded-xl border border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/20 px-4 py-3">
        <div class="text-xs font-semibold text-sky-800 dark:text-sky-200">MODE OPERASIONAL</div>
        <div class="text-xs text-sky-700 dark:text-sky-300 mt-1">
            Halaman ini dipakai untuk kontrol header breakdown dan ringkasan item fundamental.
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 px-4 py-3">
        <div class="text-xs font-semibold text-slate-700 dark:text-slate-200 mb-2">ALUR WAJIB</div>
        <ol class="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs">
            <li class="rounded-lg px-3 py-2 {{ $step1Done ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800' : 'bg-white dark:bg-gray-900 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300' }}">
                <span class="font-bold">1. Acuan:</span> RAPP harus approved.
            </li>
            <li class="rounded-lg px-3 py-2 {{ $step2Done ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800' : 'bg-white dark:bg-gray-900 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300' }}">
                <span class="font-bold">2. Header:</span> Buat breakdown per paket kerja.
            </li>
            <li class="rounded-lg px-3 py-2 {{ $step3Done ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800' : 'bg-white dark:bg-gray-900 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300' }}">
                <span class="font-bold">3. Item:</span> Isi item teknis + kontrol guardrail.
            </li>
        </ol>
    </div>

    <section class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">1) Ringkasan Proyek</h2>
            @if($firstBreakdownUrl)
                <a href="{{ $firstBreakdownUrl }}" class="inline-flex items-center justify-center px-3 py-1.5 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm">Buka Breakdown Pertama</a>
            @endif
        </div>

        <div class="grid grid-cols-2 {{ $showActual ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-3">
            <div class="p-3 rb-surface"><div class="text-[11px] text-gray-500">Total Breakdown</div><div class="text-lg font-bold text-gray-900 dark:text-white">{{ $projectSummary['total_rab_breakdowns'] ?? 0 }}</div></div>
            <div class="p-3 rb-surface"><div class="text-[11px] text-gray-500">Total Budget</div><div class="text-sm font-bold text-gray-900 dark:text-white">{{ $fmtRp($projectSummary['total_budget'] ?? 0) }}</div></div>
            @if($showActual)
                <div class="p-3 rb-surface"><div class="text-[11px] text-gray-500">Total Aktual</div><div class="text-sm font-bold text-gray-900 dark:text-white">{{ $fmtRp($projectSummary['total_actual'] ?? 0) }}</div></div>
            @endif
            <div class="p-3 rb-surface"><div class="text-[11px] text-gray-500">Rata-rata Progress</div><div class="text-lg font-bold text-sky-600">{{ number_format((float) ($projectSummary['avg_progress'] ?? 0), 1, ',', '.') }}%</div></div>
        </div>
    </section>

    <section class="space-y-3">
        <h2 class="text-sm font-bold text-gray-900 dark:text-white">2) Daftar Breakdown + Detail Item</h2>
        <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 px-3 py-2 text-[11px] text-slate-700 dark:text-slate-300">
            Daftar di bawah ini menampilkan data fundamental item. Dimensi teknis diinput/diubah dari halaman <b>Edit Item</b>.
        </div>
        <div class="space-y-3">
            @forelse($rabBreakdownList as $rb)
                @php
                    $status = strtolower((string) ($rb->status ?? 'draft'));
                    $approvalKey = method_exists($rb, 'approvalKey')
                        ? $rb->approvalKey()
                        : strtolower((string) ($rb->approval_status ?? 'draft'));
                    $canEditRb = $isHO || (method_exists($rb, 'canEdit') ? $rb->canEdit() : !in_array($approvalKey, ['submitted', 'approved'], true));
                    $lockReason = $canEditRb
                        ? null
                        : ('Terkunci: menunggu approval HO (' . strtoupper($approvalKey) . ')');
                    $progress = min(100, max(0, (float) ($rb->progress_percentage ?? 0)));
                    $wbsTopCode = null;
                    if (preg_match('/^RAB-(\d{3})$/i', (string) $rb->rab_breakdown_code, $m) === 1) {
                        $num = (int) $m[1];
                        if ($num >= 1 && $num <= 26) {
                            $wbsTopCode = chr(ord('A') + $num - 1) . '.';
                        }
                    }
                @endphp
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                    <div class="px-4 py-3 bg-sky-50/70 dark:bg-sky-900/20 border-b border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 text-xs">
                            <div class="lg:col-span-4">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 break-words">
                                    <span class="text-sky-700 dark:text-sky-300">{{ $wbsTopCode ?? ($rb->rab_breakdown_code . '.') }}</span>
                                    {{ $rb->name }}
                                </div>
                            </div>
                            <div class="lg:col-span-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold
                                        @if($status === 'approved') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300
                                        @elseif($status === 'submitted') bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300
                                        @elseif($status === 'in_progress') bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300
                                        @elseif($status === 'rejected') bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300
                                        @else bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 @endif">
                                        {{ strtoupper($rb->status ?? 'draft') }}
                                    </span>
                                </div>
                                <div class="mt-1">
                                    <span class="inline-flex">
                                        @include('dev.rab-breakdown.partials.approval-chip', ['approvalKey' => $approvalKey, 'prefix' => 'APP'])
                                    </span>
                                </div>
                                <div class="mt-1 text-[11px] text-gray-600 dark:text-gray-300">{{ number_format($progress, 1, ',', '.') }}%</div>
                                <div class="mt-1 w-full h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-1.5 rounded-full bg-sky-600" style="width: {{ $progress }}%;"></div>
                                </div>
                            </div>
                            <div class="lg:col-span-4 grid {{ $showActual ? 'grid-cols-3' : 'grid-cols-2' }} gap-2">
                                <div><div class="text-[10px] text-gray-500">Budget</div><div class="font-semibold text-gray-900 dark:text-gray-100">{{ $fmtRp($rb->budget_amount) }}</div></div>
                                @if($showActual)
                                    <div><div class="text-[10px] text-gray-500">Aktual</div><div class="font-semibold text-gray-900 dark:text-gray-100">{{ $fmtRp($rb->actual_amount) }}</div></div>
                                @endif
                                <div><div class="text-[10px] text-gray-500">Item</div><div class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format((int) ($rb->items_count ?? 0), 0, ',', '.') }}</div></div>
                            </div>
                            <div class="lg:col-span-2 flex flex-wrap lg:justify-end gap-2">
                                <a href="{{ route('dev.rab-breakdown.show', ['projectId' => $project->id, 'rabBreakdown' => $rb->id]) }}"
                                   class="inline-flex items-center justify-center px-3 py-1.5 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm min-w-[72px]">
                                    Detail
                                </a>
                                @if($canEditRb)
                                    <a href="{{ route('dev.rab-breakdown.edit', ['projectId' => $project->id, 'rabBreakdown' => $rb->id]) }}"
                                       class="inline-flex items-center justify-center px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs font-semibold min-w-[72px]">
                                        Edit
                                    </a>
                                @else
                                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-semibold min-w-[72px] cursor-not-allowed"
                                          title="{{ $lockReason }}">
                                        Terkunci
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if(!$canEditRb)
                        <div class="px-4 py-2 text-[11px] border-b border-gray-200 dark:border-gray-700 bg-amber-50/70 dark:bg-amber-900/20 text-amber-700 dark:text-amber-200">
                            {{ $lockReason }}.
                        </div>
                    @endif

                    <div class="p-3">
                        @if($rb->items->isEmpty())
                            <div class="text-xs text-gray-500 dark:text-gray-400">Belum ada item pada breakdown ini.</div>
                        @else
                            <table class="w-full table-fixed text-[10px] leading-tight">
                                <thead class="bg-gray-50 dark:bg-gray-800/50">
                                    <tr>
                                        <th class="px-1.5 py-1.5 text-left w-[5.5%]">Kode</th>
                                        <th class="px-1.5 py-1.5 text-left w-[25.5%]">Uraian</th>
                                        <th class="px-1.5 py-1.5 text-left w-[4.5%]">Sat</th>
                                        <th class="px-1.5 py-1.5 text-right w-[7.5%] whitespace-nowrap">Vol</th>
                                        <th class="px-1.5 py-1.5 text-right w-[9.5%] whitespace-nowrap">Harga</th>
                                        <th class="px-1.5 py-1.5 text-right w-[10%] whitespace-nowrap">Nilai RAB</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($rb->items as $item)
                                        @php
                                            $primarySource = $item->budgetSources->first();
                                            $masterKode = $primarySource->master_kode ?? optional(optional($item->rabItem)->data)->kode ?? '-';
                                            $qtyBeli = $item->qty_beli ?? optional($primarySource)->qty_beli;
                                            $jumlah = $item->jumlah ?? optional($primarySource)->jumlah;
                                            $nilaiRab = (float) ($item->total_price ?? 0);
                                            $selisih = (float) ($jumlah ?? 0) - $nilaiRab;
                                        @endphp
                                        <tr>
                                            <td class="px-1.5 py-1.5 font-semibold break-words">{{ $item->item_code }}</td>
                                            <td class="px-1.5 py-1.5 break-words">{{ $item->uraian }}</td>
                                            <td class="px-1.5 py-1.5 break-words">{{ $item->satuan ?? '-' }}</td>
                                            <td class="px-1.5 py-1.5 text-right whitespace-nowrap">{{ number_format((float) ($item->volume_rab ?? 0), 2, ',', '.') }}</td>
                                            <td class="px-1.5 py-1.5 text-right whitespace-nowrap">{{ $fmtRp($item->unit_price) }}</td>
                                            <td class="px-1.5 py-1.5 text-right whitespace-nowrap">{{ $fmtRp($nilaiRab) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 px-4 py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                    Belum ada breakdown.
                </div>
            @endforelse
        </div>
    </section>

    <div class="rounded-xl border border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/20 px-4 py-3 text-xs text-sky-800 dark:text-sky-200">
        Guardrail aktif: non-LS (qty max 120%, jumlah max 125%), LS (qty max volume acuan, jumlah max 105%).
    </div>

    @include('dev.rab-breakdown.partials.footnote-terms')
</div>
@endsection

