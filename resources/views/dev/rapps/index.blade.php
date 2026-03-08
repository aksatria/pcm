@extends('layouts.dev')

@section('title', 'RAPP - ' . $project->name)
@section('subtitle', 'RAPP (Rekap Utama)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">RAPP Project</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1 break-words">
                {{ $project->name }} - {{ $project->client->name }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 lg:justify-end">
            <a href="{{ route('dev.projects.show', $project->id) }}" 
               class="flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Project
            </a>
            
            {{-- Tampilkan tombol buat RAPP baru jika belum ada RAPP aktif --}}
            @php
                $existingRab = $project->rabs()->latest()->first();
                $canCreateNewRab = !$existingRab || ($existingRab && $existingRab->canEdit() && $existingRab->status == 'rejected');
            @endphp
            
            @if($canCreateNewRab)
            <a href="{{ route('dev.rab-baseline.create', $project->id) }}" 
               class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat RAPP Baru
            </a>
            @else
            <button disabled
                    class="flex items-center px-4 py-2 bg-gray-400 text-white rounded-lg text-sm font-medium cursor-not-allowed whitespace-normal text-left max-w-full lg:ml-auto">
                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="break-words">
                    @if($existingRab && $existingRab->status == 'approved')
                    Project Sudah Memiliki RAPP yang Disetujui
                    @elseif($existingRab && $existingRab->status == 'submitted')
                    RAPP Sedang Menunggu Persetujuan
                    @else
                    Project Sudah Memiliki RAPP
                    @endif
                </span>
            </button>
            @endif
        </div>
    </div>

    <!-- Summary Cards (match /dev/projects style) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 w-full">
        @php
            $totalRabs = $rabs->total();
            $draftRabs = $project->rabs()->draft()->count();
            $submittedRabs = $project->rabs()->submitted()->count();
            $approvedRabs = $project->rabs()->approved()->count();
            $rejectedRabs = $project->rabs()->rejected()->count();
        @endphp

        @foreach([
            [
                'title' => 'Total RAPP',
                'value' => $totalRabs,
                'description' => 'Semua versi RAPP',
                'color' => 'blue',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
            ],
            [
                'title' => 'Draft',
                'value' => $draftRabs,
                'description' => 'Dalam pengerjaan',
                'color' => 'purple',
                'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'
            ],
            [
                'title' => 'Submitted',
                'value' => $submittedRabs,
                'description' => 'Menunggu persetujuan',
                'color' => 'yellow',
                'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
            ],
            [
                'title' => 'Approved/Rejected',
                'value' => $approvedRabs . '/' . $rejectedRabs,
                'description' => 'Disetujui / Ditolak',
                'color' => 'green',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
            ]
        ] as $stat)
        <div class="group relative min-h-[120px]">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 rounded-xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
            <div class="relative bg-gradient-to-br from-{{ $stat['color'] }}-500 via-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 text-white rounded-xl p-4 h-full transform transition-all duration-300 hover:scale-[1.02] hover:shadow-xl flex flex-col">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                        <p class="text-{{ $stat['color'] }}-100 text-xs font-medium mb-1">{{ $stat['title'] }}</p>
                        <p class="text-2xl font-bold">{{ $stat['value'] }}</p>
                    </div>
                    <div class="bg-{{ $stat['color'] }}-400/20 p-1.5 rounded-lg backdrop-blur-sm border border-{{ $stat['color'] }}-300/20 ml-2 flex-shrink-0">
                        <svg class="w-5 h-5 transform group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-auto">
                    <p class="text-{{ $stat['color'] }}-100 text-xs">{{ $stat['description'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- RAPP Cards -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar RAPP</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total {{ $totalRabs }} RAPP ditemukan</p>
                </div>
            </div>

            @forelse($rabs as $rab)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 sm:p-5 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div class="min-w-0 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white break-words">
                                            {{ $rab->name }}
                                        </h4>
                                        <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold rounded-full 
                                            @if($rab->status == 'approved') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                                            @elseif($rab->status == 'draft') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                            @elseif($rab->status == 'submitted') bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300
                                            @elseif($rab->status == 'rejected') bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                            {{ $rab->status_label }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 break-words">Versi {{ $rab->version }}</span>
                                    </div>

                                    @if($rab->notes)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 break-words">{{ $rab->notes }}</p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2 lg:justify-end">
                                    <a href="{{ route('dev.rab-baseline.show', ['projectId' => $project->id, 'rabId' => $rab->id]) }}" 
                                       class="px-3 py-2 text-sm font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                                        Lihat Detail
                                    </a>

                                    @if($rab->canEdit())
                                    <a href="{{ route('dev.rab-baseline.edit', ['projectId' => $project->id, 'rabId' => $rab->id]) }}" 
                                       class="px-3 py-2 text-sm font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors">
                                        Edit
                                    </a>
                                    @endif

                                    @if($rab->canEdit())
                                    <form action="{{ route('dev.rab-baseline.destroy', ['projectId' => $project->id, 'rabId' => $rab->id]) }}" method="POST" class="inline" onsubmit="return confirm('Hapus RAPP {{ $rab->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="px-3 py-2 text-sm font-medium text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-900/20 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 text-sm w-full">
                                    <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-3">
                                        <div class="text-xs text-blue-700 dark:text-blue-200">Total Budget</div>
                                        <div class="font-semibold text-blue-900 dark:text-blue-100 break-words">
                                            Rp {{ number_format($rab->total_budget, 0, ',', '.') }}
                                        </div>
                                    </div>
                                    <div class="rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 p-3">
                                        <div class="text-xs text-emerald-700 dark:text-emerald-200">Jumlah Item</div>
                                        <div class="font-semibold text-emerald-900 dark:text-emerald-100">{{ $rab->items_count }} item</div>
                                    </div>
                                    <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3">
                                        <div class="text-xs text-amber-700 dark:text-amber-200">Terakhir Diupdate</div>
                                        <div class="font-semibold text-amber-900 dark:text-amber-100">
                                            {{ $rab->updated_at->format('d M Y') }}
                                        </div>
                                        <div class="text-xs text-amber-700/80 dark:text-amber-200/80">{{ $rab->updated_at->format('H:i') }}</div>
                                    </div>
                                    <div class="rounded-lg border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 p-3">
                                        <div class="text-xs text-violet-700 dark:text-violet-200">Dibuat</div>
                                        <div class="font-semibold text-violet-900 dark:text-violet-100">
                                            {{ $rab->created_at->format('d M Y') }}
                                        </div>
                                        <div class="text-xs text-violet-700/80 dark:text-violet-200/80">{{ $rab->created_at->format('H:i') }}</div>
                                    </div>
                                </div>
                        </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 dark:bg-gray-800 rounded-2xl flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Belum ada RAPP</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Mulai dengan membuat RAPP pertama untuk project ini.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('dev.rab-baseline.create', $project->id) }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Buat RAPP Pertama
                        </a>
                    </div>
                </div>
            @endforelse

            @if($rabs->hasPages())
            <div class="pt-2">
                {{ $rabs->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection





