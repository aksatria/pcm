@extends('layouts.dev')

@section('title', 'RAPP - ' . $project->name)
@section('subtitle', 'Daftar Dokumen & Workflow RAPP')

@section('content')
@php
    $fmtRp = fn($n) => 'Rp ' . number_format((float) ($n ?? 0), 0, ',', '.');
@endphp

<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">RAPP</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $project->name }} - {{ optional($project->client)->name ?? '-' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dev.projects.show', $project->id) }}" class="px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200">Kembali Project</a>
            <a href="{{ route('dev.rab-baseline.create', $project->id) }}" class="px-3 py-2 text-xs rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">Buat RAPP</a>
            @if($latestApproved)
                <a href="{{ route('dev.rab-breakdown.index', $project->id) }}" class="px-3 py-2 text-xs rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-semibold">Buka RAB Breakdown</a>
            @else
                <span class="px-3 py-2 text-xs rounded-lg bg-gray-300 text-gray-700 font-semibold cursor-not-allowed" title="RAB Breakdown aktif setelah ada RAPP approved">RAB Breakdown Terkunci</span>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 px-4 py-2.5 text-xs text-blue-800 dark:text-blue-200 flex flex-wrap items-center justify-between gap-2">
        <span>URL canonical halaman ini: <span class="font-semibold">/rapps</span></span>
        <button type="button" data-copy-link="{{ route('dev.rab-baseline.index', ['projectId' => $project->id]) }}"
                class="px-2.5 py-1 rounded-md border border-blue-300 dark:border-blue-700 hover:bg-blue-100 dark:hover:bg-blue-900/40 font-semibold">
            Copy Link
        </button>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        <div class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"><div class="text-[11px] text-gray-500">Total RAPP</div><div class="text-lg font-bold text-gray-900 dark:text-white">{{ $summary['count'] }}</div></div>
        <div class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"><div class="text-[11px] text-gray-500">Total Nilai</div><div class="text-sm font-bold text-gray-900 dark:text-white">{{ $fmtRp($summary['total']) }}</div></div>
        <div class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"><div class="text-[11px] text-gray-500">Approved</div><div class="text-lg font-bold text-green-600">{{ $summary['approved'] }}</div></div>
        <div class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"><div class="text-[11px] text-gray-500">Submitted</div><div class="text-lg font-bold text-indigo-600">{{ $summary['submitted'] }}</div></div>
        <div class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"><div class="text-[11px] text-gray-500">Draft</div><div class="text-lg font-bold text-amber-600">{{ $summary['draft'] }}</div></div>
        <div class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"><div class="text-[11px] text-gray-500">Rejected</div><div class="text-lg font-bold text-rose-600">{{ $summary['rejected'] }}</div></div>
    </div>

    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 px-4 py-3 text-xs text-slate-700 dark:text-slate-200">
        <b>Alur standar:</b> Draft RAPP -> Submit ke HO -> Approved -> lanjut RAB Breakdown.
    </div>

    @if($latestApproved)
        <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-3 text-xs text-emerald-800 dark:text-emerald-200">
            RAPP aktif (approved): <b>{{ $latestApproved->name }}</b> | Nilai: <b>{{ $fmtRp($latestApproved->total_budget) }}</b>
        </div>
    @else
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-xs text-amber-800 dark:text-amber-200">
            Belum ada RAPP berstatus approved. Sesuai alur umum, approve RAPP dulu sebelum lanjut ke RAB Breakdown.
        </div>
    @endif

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 text-sm font-semibold text-gray-900 dark:text-white">Daftar RAPP</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                    <tr>
                        <th class="px-3 py-2 text-left">Nama</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-left">Update</th>
                        <th class="px-3 py-2 text-left">Tahap Berikutnya</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($rabs as $rab)
                        <tr>
                            <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $rab->name }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold
                                    @if(($rab->status ?? '') === 'approved') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300
                                    @elseif(($rab->status ?? '') === 'submitted') bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300
                                    @elseif(($rab->status ?? '') === 'rejected') bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300
                                    @else bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 @endif">
                                    {{ strtoupper($rab->status ?? 'draft') }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-100">{{ $fmtRp($rab->total_budget) }}</td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ optional($rab->updated_at)->format('d-m-Y H:i') }}</td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                @if(($rab->status ?? '') === 'draft' || ($rab->status ?? '') === 'rejected')
                                    Lengkapi item lalu submit
                                @elseif(($rab->status ?? '') === 'submitted')
                                    Menunggu approval HO
                                @elseif(($rab->status ?? '') === 'approved')
                                    Lanjut ke RAB Breakdown
                                @else
                                    Review dokumen
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('dev.rab-baseline.table', ['projectId' => $project->id, 'rabId' => $rab->id]) }}" class="px-2 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white">Buka Tabel Baseline</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">Belum ada RAPP.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.querySelector('[data-copy-link]');
    if (!btn) return;
    btn.addEventListener('click', async function () {
        const url = btn.getAttribute('data-copy-link');
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
            btn.textContent = 'Copied';
            setTimeout(() => btn.textContent = 'Copy Link', 1200);
        } catch (e) {
            btn.textContent = 'Gagal Copy';
            setTimeout(() => btn.textContent = 'Copy Link', 1400);
        }
    });
});
</script>
@endpush


