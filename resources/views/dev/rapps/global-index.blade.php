@extends('layouts.dev')

@section('title', 'RAPP Global')
@section('subtitle', 'Manajemen RAPP Semua Projects')

@section('content')
<style>
    .status-badge {
        @apply inline-flex items-center px-2.5 py-1 text-[11px] font-semibold rounded-full;
    }
    
    .action-btn {
        @apply p-2 rounded-lg transition-all duration-200 hover:scale-110;
    }
    
    .progress-bar {
        @apply h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden;
    }
    
    .progress-fill {
        @apply h-full bg-gradient-to-r from-blue-500 to-purple-600 transition-all duration-500;
    }
</style>

<div class="space-y-4">
    <!-- Header - lebih kompak -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">RAPP Global</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Kelola Rencana Anggaran Biaya semua projects</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('dev.rab.summary') }}" 
               class="group relative bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 flex items-center">
                <div class="absolute -inset-0.5 bg-purple-600 rounded-xl blur opacity-0 group-hover:opacity-30 transition duration-1000 group-hover:duration-200"></div>
                <span class="relative">
                    <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Summary
                </span>
            </a>
        </div>
    </div>

    <!-- Stats Cards - lebih kecil -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            [
                'title' => 'Total Projects',
                'value' => $projects->total(),
                'description' => 'Semua projects',
                'color' => 'blue',
                'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2'
            ],
            [
                'title' => 'Projects RAPP',
                'value' => $projectsWithRab,
                'description' => 'Sudah ada RAPP',
                'color' => 'green',
                'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'
            ],
            [
                'title' => 'Total Items',
                'value' => $totalRabItems,
                'description' => 'Semua item RAPP',
                'color' => 'purple',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
            ],
            [
                'title' => 'Nilai RAPP',
                'value' => 'Rp ' . number_format($globalRabTotal, 0, ',', '.'),
                'description' => 'Total semua RAPP',
                'color' => 'orange',
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 2 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'
            ]
        ] as $stat)
        <div class="group relative min-h-[110px]">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 rounded-xl blur opacity-20 group-hover:opacity-30 transition duration-1000 group-hover:duration-200"></div>
            <div class="relative bg-gradient-to-br from-{{ $stat['color'] }}-500 via-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 text-white rounded-xl p-4 h-full transform transition-all duration-300 hover:scale-[1.01] hover:shadow-lg flex flex-col">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                        <p class="text-{{ $stat['color'] }}-100 text-xs font-medium mb-1">{{ $stat['title'] }}</p>
                        <p class="text-lg font-bold truncate">{{ $stat['value'] }}</p>
                    </div>
                    <div class="bg-{{ $stat['color'] }}-400/20 p-1.5 rounded-lg backdrop-blur-sm border border-{{ $stat['color'] }}-300/20 ml-2 flex-shrink-0">
                        <svg class="w-4 h-4 transform group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-auto">
                    <p class="text-{{ $stat['color'] }}-100 text-xs truncate">{{ $stat['description'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Main Content Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        
        {{-- FORM GLOBAL UNTUK SEARCH + FILTER --}}
        <form action="{{ route('dev.rab.index') }}" method="GET">
            <!-- Table Header dengan Advanced Filters - lebih kompak -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Daftar Projects dengan RAPP</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Total {{ $projects->total() }} projects</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                        <!-- Search Input -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" 
                                   name="search"
                                   class="pl-8 pr-3 py-1.5 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:text-white w-56 placeholder-gray-500 dark:placeholder-gray-400"
                                   placeholder="Cari projects..."
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                </div>

                <!-- Advanced Filter System - lebih kecil -->
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <!-- Status Filter -->
                    <select name="status" class="text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-2.5 py-1.5 bg-white dark:bg-gray-700 focus:ring-1 focus:ring-blue-500 dark:text-white">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Client Filter -->
                    <select name="client_id" class="text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-2.5 py-1.5 bg-white dark:bg-gray-700 focus:ring-1 focus:ring-blue-500 dark:text-white">
                        <option value="">Semua Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Sort Options -->
                    <select name="sort" class="text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-2.5 py-1.5 bg-white dark:bg-gray-700 focus:ring-1 focus:ring-blue-500 dark:text-white">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Terlama</option>
                        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                        <option value="rab_high" {{ request('sort') == 'rab_high' ? 'selected' : '' }}>RAPP Tertinggi</option>
                        <option value="rab_low" {{ request('sort') == 'rab_low' ? 'selected' : '' }}>RAPP Terendah</option>
                    </select>

                    <!-- Tombol Clear Filters -->
                    @if(request()->hasAny(['search', 'status', 'client_id', 'sort']))
                    <a href="{{ route('dev.rab.index') }}" 
                       class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 px-2.5 py-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        Clear Filters
                    </a>
                    @endif
                </div>
            </div>
        </form>

        <!-- Projects Grid - lebih padat -->
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse($projects as $project)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-all duration-200">
                    <!-- Project Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center space-x-2 min-w-0 flex-1">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    <a href="{{ route('dev.projects.show', $project) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $project->name }}
                                    </a>
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $project->client->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Status & RAPP Info -->
                    <div class="mb-3 flex items-center justify-between">
                        <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold rounded-full 
                            @if($project->status == 'Active') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                            @elseif($project->status == 'Completed') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                            @elseif($project->status == 'Planning') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                            @elseif($project->status == 'On Hold') bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300
                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                            {{ $project->status }}
                        </span>
                        
                        @if($project->rab_total > 0)
                        <div class="text-right">
                            <p class="text-xs font-semibold text-purple-600 dark:text-purple-400">
                                Rp {{ number_format($project->rab_total, 0, ',', '.') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $project->rab_items_count }} items
                            </p>
                        </div>
                        @endif
                    </div>

                    <!-- Project Details -->
                    <div class="space-y-2 mb-3">
                        <!-- Location -->
                        <div class="flex items-center text-xs text-gray-600 dark:text-gray-400">
                            <svg class="w-3 h-3 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="truncate">{{ $project->location }}</span>
                        </div>

                        <!-- Timeline -->
                        <div class="flex items-center text-xs text-gray-600 dark:text-gray-400">
                            <svg class="w-3 h-3 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ $project->start_date->format('d M') }}</span>
                            @if($project->end_date)
                            <span class="mx-1">-</span>
                            <span>{{ $project->end_date->format('d M') }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ $project->pic ?? 'No PIC' }}
                        </div>
                        <div class="flex space-x-1">
                            <a href="{{ route('dev.projects.show', $project) }}" 
                               class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors" title="Lihat Detail">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('dev.rab-baseline.index', $project->id) }}" 
                               class="p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-md transition-colors" title="Lihat RAPP">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full">
                    <div class="text-center py-8">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">
                            @if(request()->hasAny(['search', 'status', 'client_id']))
                            Tidak ada project yang cocok
                            @else
                            Belum ada project
                            @endif
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            @if(request()->hasAny(['search', 'status', 'client_id']))
                            Coba sesuaikan pencarian atau filter Anda untuk melihat hasil yang berbeda.
                            @else
                            Mulai dengan menambahkan project pertama Anda.
                            @endif
                        </p>
                        <div class="space-y-2">
                            <a href="{{ route('dev.projects.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl text-sm font-medium transition-all duration-300">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Tambah Project
                            </a>
                            @if(request()->hasAny(['search', 'status', 'client_id']))
                            <div>
                                <a href="{{ route('dev.rab.index') }}" 
                                   class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 inline-flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Tampilkan semua
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Pagination - lebih kecil -->
        @if($projects->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <div class="flex items-center justify-between">
                <div class="text-xs text-gray-700 dark:text-gray-300">
                    Menampilkan {{ $projects->firstItem() }} - {{ $projects->lastItem() }} dari {{ $projects->total() }} projects
                </div>
                <div class="flex space-x-1">
                    {{ $projects->links('pagination::tailwind') }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    // Auto-submit filter forms
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('select[name="status"], select[name="client_id"], select[name="sort"]').forEach(select => {
            select.addEventListener('change', function() {
                if (this.form) {
                    this.form.submit();
                }
            });
        });
    });
</script>
@endsection






