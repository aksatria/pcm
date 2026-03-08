@extends('layouts.dev')

@section('title', 'RAB Breakdown - Laporan Global')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">RAB Breakdown - Laporan Global</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Ringkasan status RAB Breakdown dan proyek yang sudah memiliki RAB Breakdown.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dev.projects.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-medium">
                Kembali ke Projects
            </a>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $statusMap = [
                'not_started' => ['label' => 'Belum Mulai', 'color' => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200'],
                'in_progress' => ['label' => 'Dalam Progress', 'color' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'],
                'completed' => ['label' => 'Selesai', 'color' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'],
                'delayed' => ['label' => 'Terlambat', 'color' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200'],
            ];
            $totalRabBreakdowns = collect($rabBreakdownStats)->sum('count');
            $totalBudget = collect($rabBreakdownStats)->sum('total_budget');
        @endphp

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total RAB Breakdown</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $totalRabBreakdowns }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Semua status</div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Budget</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">Rp {{ number_format($totalBudget, 0, ',', '.') }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Akumulasi budget RAB Breakdown</div>
        </div>

        @foreach($statusMap as $key => $meta)
            @php
                $row = collect($rabBreakdownStats)->firstWhere('status', $key);
                $count = $row->count ?? 0;
            @endphp
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $meta['label'] }}</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $count }}</div>
                <span class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $meta['color'] }}">
                    {{ $meta['label'] }}
                </span>
            </div>
        @endforeach
    </div>

    <div class="mt-8 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Proyek dengan RAB Breakdown</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Klik proyek untuk membuka RAB Breakdown per proyek.</p>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($projectsWithRabBreakdowns as $project)
                <a href="{{ route('dev.rab-breakdown.index', $project->id) }}"
                   class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-900 dark:text-white truncate">{{ $project->name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $project->code ?? '-' }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                            {{ $project->rab_breakdowns_count ?? 0 }} RAB Breakdown
                        </span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            @empty
                <div class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                    Belum ada proyek yang memiliki RAB Breakdown.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection




