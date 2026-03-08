@extends('layouts.dev')

@section('title', 'Projects')
@section('subtitle', 'Manajemen Data Projects')

@section('content')
<style>
    .status-badge {
        @apply inline-flex items-center px-2.5 py-1 text-[11px] font-semibold rounded-full;
    }
    
    .action-btn {
        @apply p-2 rounded-lg transition-all duration-200 hover:scale-110;
    }
</style>

<div class="space-y-4">
    <!-- Header dengan Add Button -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Manajemen Projects</h1>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Kelola data projects dan informasi terkait</p>
        </div>
        <a href="{{ route('dev.projects.create') }}" 
           class="group relative bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-xl font-medium transition-all duration-300 transform hover:scale-105 hover:shadow-xl flex items-center text-sm">
            <div class="absolute -inset-0.5 bg-blue-600 rounded-xl blur opacity-0 group-hover:opacity-30 transition duration-1000 group-hover:duration-200"></div>
            <span class="relative">
                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Project
            </span>
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $totalProjects = $projects->total() ?? 0;
            $activeProjects = $projects->where('status', 'Active')->count() ?? 0;
            $planningProjects = $projects->where('status', 'Planning')->count() ?? 0;
            $completedProjects = $projects->where('status', 'Completed')->count() ?? 0;
        @endphp

        @foreach([
            [
                'title' => 'Total Projects',
                'value' => $totalProjects,
                'description' => 'Semua projects',
                'color' => 'blue',
                'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2'
            ],
            [
                'title' => 'Active',
                'value' => $activeProjects,
                'description' => 'Sedang berjalan',
                'color' => 'green',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
            ],
            [
                'title' => 'Planning',
                'value' => $planningProjects,
                'description' => 'Dalam perencanaan',
                'color' => 'yellow',
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
            ],
            [
                'title' => 'Completed',
                'value' => $completedProjects,
                'description' => 'Telah selesai',
                'color' => 'purple',
                'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'
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

    <!-- Main Content Container -->
    <div class="group relative">
        <div class="absolute -inset-0.5 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 rounded-xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 transform transition-all duration-300 hover:shadow-xl">
            
            <!-- Table Header dengan Advanced Filters -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Daftar Projects</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Total {{ $projects->total() }} projects ditemukan</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                        <!-- View Toggle -->
                        <div class="flex items-center space-x-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                            <button id="tableViewBtn" class="p-1.5 rounded-lg text-blue-600 bg-white dark:bg-gray-600 shadow-sm view-transition" title="Table View">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                            <button id="cardViewBtn" class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 view-transition" title="Card View">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Search Form -->
                        <form action="{{ route('dev.projects.index') }}" method="GET" class="relative">
                            <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" 
                                   name="search"
                                   class="pl-8 pr-3 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:text-white w-48 placeholder-gray-500 dark:placeholder-gray-400 text-xs"
                                   placeholder="Cari projects..."
                                   value="{{ request('search') }}">
                        </form>
                    </div>
                </div>

                <!-- Advanced Filter System -->
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <!-- Status Filter -->
                    <select name="status" class="text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white">
                        <option value="">Semua Status</option>
                        <option value="Planning" {{ request('status') == 'Planning' ? 'selected' : '' }}>Planning</option>
                        <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="On Hold" {{ request('status') == 'On Hold' ? 'selected' : '' }}>On Hold</option>
                        <option value="Completed" {{ request('status') == 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>

                    <!-- Client Filter -->
                    <select name="client_id" class="text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white">
                        <option value="">Semua Client</option>
                        @foreach($clients ?? [] as $client)
                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Sort Options -->
                    <select name="sort" class="text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Terlama</option>
                        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                    </select>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['search', 'status', 'client_id', 'sort']))
                    <a href="{{ route('dev.projects.index') }}" 
                       class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 px-2 py-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        Clear Filters
                    </a>
                    @endif
                </div>
            </div>

            <!-- Table View -->
            <div id="tableView" class="view-transition">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kode</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Project</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Client</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Lokasi</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal Mulai</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($projects as $project)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-xs font-mono text-gray-900 dark:text-white font-medium">{{ $project->code }}</div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="text-xs font-medium text-gray-900 dark:text-white">{{ $project->name }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-xs text-gray-900 dark:text-white">{{ $project->client->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $project->client->company ?? '' }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-xs text-gray-900 dark:text-white">{{ $project->location }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="status-badge 
                                    @if($project->status == 'Active') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                                    @elseif($project->status == 'Completed') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                    @elseif($project->status == 'Planning') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                    @elseif($project->status == 'On Hold') bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                    {{ $project->status }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-xs text-gray-900 dark:text-white">{{ $project->start_date->format('d M Y') }}</div>
                                @if($project->end_date)
                                <div class="text-xs text-gray-500 dark:text-gray-400">s/d {{ $project->end_date->format('d M Y') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center space-x-1">
                                    <a href="{{ route('dev.rab-breakdown.index', $project->id) }}"
                                       class="action-btn text-sky-600 hover:bg-sky-50 dark:hover:bg-sky-900/20" title="Buka RAB Breakdown Proyek">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 12h10M4 18h16M14 6h6M16 12h4M18 18h2"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('dev.projects.show', $project) }}" 
                                       class="action-btn text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20" title="Lihat Detail">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('dev.projects.edit', $project) }}" 
                                       class="action-btn text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20" title="Edit Project">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('dev.projects.destroy', $project) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus project ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="action-btn text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20" title="Hapus Project">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center">
                                <div class="max-w-md mx-auto">
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
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 leading-relaxed">
                                        @if(request()->hasAny(['search', 'status', 'client_id']))
                                        Coba sesuaikan pencarian atau filter Anda untuk melihat hasil yang berbeda.
                                        @else
                                        Mulai dengan menambahkan project pertama Anda ke sistem untuk mengelola pekerjaan dan timeline.
                                        @endif
                                    </p>
                                    <div class="space-y-2">
                                        <a href="{{ route('dev.projects.create') }}" 
                                           class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl text-sm font-medium transition-all duration-300 transform hover:scale-105 hover:shadow-lg">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Tambah Project Pertama
                                        </a>
                                        @if(request()->hasAny(['search', 'status', 'client_id']))
                                        <div>
                                            <a href="{{ route('dev.projects.index') }}" 
                                               class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 inline-flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                Tampilkan semua projects
                                            </a>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Card View -->
            <div id="cardView" class="view-transition hidden p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @forelse($projects as $project)
                    <div class="group relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-0.5">
                        <!-- Project Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-2 min-w-0">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-xs font-semibold text-gray-900 dark:text-white truncate mb-1">{{ $project->name }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono truncate">{{ $project->code }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <span class="status-badge 
                                @if($project->status == 'Active') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                                @elseif($project->status == 'Completed') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                @elseif($project->status == 'Planning') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                @elseif($project->status == 'On Hold') bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                {{ $project->status }}
                            </span>
                        </div>

                        <!-- Project Details -->
                        <div class="space-y-2 mb-3">
                            <!-- Client Info -->
                            <div class="flex items-center text-xs text-gray-600 dark:text-gray-400">
                                <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="truncate">{{ $project->client->name ?? 'N/A' }}</span>
                            </div>

                            <!-- Location -->
                            <div class="flex items-center text-xs text-gray-600 dark:text-gray-400">
                                <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="truncate">{{ $project->location }}</span>
                            </div>

                            <!-- Timeline -->
                            <div class="flex items-center text-xs text-gray-600 dark:text-gray-400">
                                <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ $project->start_date->format('d M Y') }}</span>
                                @if($project->end_date)
                                <span class="mx-1">-</span>
                                <span>{{ $project->end_date->format('d M Y') }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ $project->pic ?? 'No PIC' }}
                            </div>
                            <div class="flex space-x-1">
                                <a href="{{ route('dev.rab-breakdown.index', $project->id) }}"
                                   class="action-btn text-sky-600 hover:bg-sky-50 dark:hover:bg-sky-900/20" title="Buka RAB Breakdown Proyek">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 12h10M4 18h16M14 6h6M16 12h4M18 18h2"/>
                                    </svg>
                                </a>
                                <a href="{{ route('dev.projects.show', $project) }}" 
                                   class="action-btn text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20" title="Lihat Detail">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('dev.projects.edit', $project) }}" 
                                   class="action-btn text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20" title="Edit Project">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form action="{{ route('dev.projects.destroy', $project) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus project ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="action-btn text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20" title="Hapus Project">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
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
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 leading-relaxed">
                                @if(request()->hasAny(['search', 'status', 'client_id']))
                                Coba sesuaikan pencarian atau filter Anda untuk melihat hasil yang berbeda.
                                @else
                                Mulai dengan menambahkan project pertama Anda ke sistem untuk mengelola pekerjaan dan timeline.
                                @endif
                            </p>
                            <div class="space-y-2">
                                <a href="{{ route('dev.projects.create') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl text-sm font-medium transition-all duration-300 transform hover:scale-105 hover:shadow-lg">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Tambah Project Pertama
                                </a>
                                @if(request()->hasAny(['search', 'status', 'client_id']))
                                <div>
                                    <a href="{{ route('dev.projects.index') }}" 
                                       class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 inline-flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Tampilkan semua projects
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination -->
            @if($projects->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <div class="flex items-center justify-between">
                    <div class="text-xs text-gray-700 dark:text-gray-300">
                        Menampilkan {{ $projects->firstItem() }} - {{ $projects->lastItem() }} dari {{ $projects->total() }} projects
                    </div>
                    <div class="flex space-x-1">
                        {{ $projects->links() }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    // View Toggle Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tableViewBtn = document.getElementById('tableViewBtn');
        const cardViewBtn = document.getElementById('cardViewBtn');
        const tableView = document.getElementById('tableView');
        const cardView = document.getElementById('cardView');

        // Set initial state
        let currentView = 'table';
        
        function switchView(view) {
            if (view === 'table') {
                tableView.classList.remove('hidden');
                cardView.classList.add('hidden');
                tableViewBtn.classList.add('text-blue-600', 'bg-white', 'dark:bg-gray-600', 'shadow-sm');
                tableViewBtn.classList.remove('text-gray-500');
                cardViewBtn.classList.add('text-gray-500');
                cardViewBtn.classList.remove('text-blue-600', 'bg-white', 'dark:bg-gray-600', 'shadow-sm');
                currentView = 'table';
            } else {
                tableView.classList.add('hidden');
                cardView.classList.remove('hidden');
                cardViewBtn.classList.add('text-blue-600', 'bg-white', 'dark:bg-gray-600', 'shadow-sm');
                cardViewBtn.classList.remove('text-gray-500');
                tableViewBtn.classList.add('text-gray-500');
                tableViewBtn.classList.remove('text-blue-600', 'bg-white', 'dark:bg-gray-600', 'shadow-sm');
                currentView = 'card';
            }
            
            // Save to localStorage
            localStorage.setItem('projectsView', view);
        }

        // Load saved view preference
        const savedView = localStorage.getItem('projectsView') || 'table';
        switchView(savedView);

        // Event listeners
        tableViewBtn.addEventListener('click', () => switchView('table'));
        cardViewBtn.addEventListener('click', () => switchView('card'));

        // Auto-submit filter forms
        document.querySelectorAll('select[name="status"], select[name="client_id"], select[name="sort"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    });
</script>
@endsection


