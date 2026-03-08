@extends('layouts.dev')

@section('title', 'Data')
@section('subtitle', 'Manajemen Data Master')

@section('content')
@php
    $isHO = auth()->check() && (
        (auth()->user()->is_admin ?? false) ||
        (method_exists(auth()->user(), 'isHO') && auth()->user()->isHO())
    );
@endphp
<style>
    .elegant-scrollbar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    .elegant-scrollbar::-webkit-scrollbar-track {
        background: rgba(243, 244, 246, 0.5);
        border-radius: 10px;
    }
    
    .elegant-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.6);
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .elegant-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(107, 114, 128, 0.8);
    }
    
    .dark .elegant-scrollbar::-webkit-scrollbar-track {
        background: rgba(55, 65, 81, 0.5);
    }
    
    .dark .elegant-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(75, 85, 99, 0.6);
    }

    .view-transition {
        transition: all 0.3s ease-in-out;
    }

    .bulk-actions-container {
        transition: all 0.3s ease;
        opacity: 0;
        height: 0;
        overflow: hidden;
    }

    .bulk-actions-container.show {
        opacity: 1;
        height: auto;
        padding: 1rem;
        margin-bottom: 0;
    }

    /* Smaller text sizes for better laptop display */
    .text-smaller {
        font-size: 0.8125rem;
    }
    
    .text-smallest {
        font-size: 0.75rem;
    }
    
    .table-compact td,
    .table-compact th {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Data Master</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1 text-smaller">Kelola data material, jasa, alat, dan lainnya</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="openImportModal()" 
                    class="group relative bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-3 rounded-2xl font-medium transition-all duration-300 transform hover:scale-105 hover:shadow-2xl flex items-center text-smaller">
                <div class="absolute -inset-0.5 bg-green-600 rounded-2xl blur opacity-0 group-hover:opacity-30 transition duration-1000 group-hover:duration-200"></div>
                <span class="relative">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                    Import Excel
                </span>
            </button>
            
            <!-- Maintenance Dropdown -->
            <div class="relative group">
                <button class="group relative bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white px-6 py-3 rounded-2xl font-medium transition-all duration-300 transform hover:scale-105 hover:shadow-2xl flex items-center text-smaller">
                    <div class="absolute -inset-0.5 bg-yellow-600 rounded-2xl blur opacity-0 group-hover:opacity-30 transition duration-1000 group-hover:duration-200"></div>
                    <span class="relative">
                        <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Maintenance
                    </span>
                </button>
                
                <!-- Dropdown Menu -->
                <div class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden">
                    <div class="py-2">
                        <button onclick="fixGaps()" class="w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors flex items-center border-b border-gray-100 dark:border-gray-700">
                            <svg class="w-4 h-4 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-smaller">Perbaiki Penomoran</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Isi nomor yang kosong</div>
                            </div>
                        </button>
                        <button onclick="resetCounters()" class="w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors flex items-center">
                            <svg class="w-4 h-4 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <div>
                                <div class="font-medium text-smaller">Reset Counter ke 0</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Mulai penomoran dari awal</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <button onclick="openQuickAdd()" 
                    class="group relative bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-2xl font-medium transition-all duration-300 transform hover:scale-105 hover:shadow-2xl flex items-center text-smaller">
                <div class="absolute -inset-0.5 bg-blue-600 rounded-2xl blur opacity-0 group-hover:opacity-30 transition duration-1000 group-hover:duration-200"></div>
                <span class="relative">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Data
                </span>
            </button>
        </div>
    </div>

    <!-- Stats Cards - Row 1 -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        @php
            $totalData = method_exists($data, 'total') ? $data->total() : 0;
            
            try {
                $activeItemsCount = \App\Models\Data::where('status', true)->count();
            } catch (\Exception $e) {
                $activeItemsCount = 0;
            }
            
            $stats = [
                [
                    'title' => 'Total Data',
                    'value' => $totalData,
                    'description' => 'Semua item',
                    'color' => 'blue',
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
                ],
                [
                    'title' => 'Material',
                    'value' => $kategoriCounts['MT'] ?? 0,
                    'description' => 'Bahan & material',
                    'color' => 'green',
                    'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'
                ],
                [
                    'title' => 'Jasa',
                    'value' => $kategoriCounts['JS'] ?? 0,
                    'description' => 'Pekerjaan jasa',
                    'color' => 'purple',
                    'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'
                ],
                [
                    'title' => 'Alat',
                    'value' => $kategoriCounts['AT'] ?? 0,
                    'description' => 'Peralatan & mesin',
                    'color' => 'orange',
                    'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'
                ]
            ];
        @endphp

        @foreach($stats as $stat)
        <div class="group relative min-h-[120px]">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 rounded-2xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
            <div class="relative bg-gradient-to-br from-{{ $stat['color'] }}-500 via-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 text-white rounded-2xl p-4 h-full transform transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl flex flex-col">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                        <p class="text-{{ $stat['color'] }}-100 text-xs font-medium mb-1">{{ $stat['title'] }}</p>
                        <p class="text-2xl font-bold">{{ $stat['value'] }}</p>
                    </div>
                    <div class="bg-{{ $stat['color'] }}-400/20 p-2 rounded-xl backdrop-blur-sm border border-{{ $stat['color'] }}-300/20 ml-3 flex-shrink-0">
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

    <!-- Stats Cards - Row 2 -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        @php
            $statsRow2 = [
                [
                    'title' => 'Head Office',
                    'value' => $kategoriCounts['HO'] ?? 0,
                    'description' => 'Head Office',
                    'color' => 'yellow',
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
                ],
                [
                    'title' => 'Sirkulasi',
                    'value' => $kategoriCounts['SR'] ?? 0,
                    'description' => 'Sirkulasi',
                    'color' => 'indigo',
                    'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'
                ],
                [
                    'title' => 'SubKon',
                    'value' => $kategoriCounts['SB'] ?? 0,
                    'description' => 'Sub Kontraktor',
                    'color' => 'pink',
                    'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'
                ],
                [
                    'title' => 'Aktif',
                    'value' => $activeItemsCount,
                    'description' => 'Data aktif',
                    'color' => 'emerald',
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                ]
            ];
        @endphp

        @foreach($statsRow2 as $stat)
        <div class="group relative min-h-[120px]">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 rounded-2xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
            <div class="relative bg-gradient-to-br from-{{ $stat['color'] }}-500 via-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 text-white rounded-2xl p-4 h-full transform transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl flex flex-col">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex-1">
                        <p class="text-{{ $stat['color'] }}-100 text-xs font-medium mb-1">{{ $stat['title'] }}</p>
                        <p class="text-2xl font-bold">{{ $stat['value'] }}</p>
                    </div>
                    <div class="bg-{{ $stat['color'] }}-400/20 p-2 rounded-xl backdrop-blur-sm border border-{{ $stat['color'] }}-300/20 ml-3 flex-shrink-0">
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

    <!-- Loading States -->
    @if($data->count() === 0 && !request()->has('search'))
    <div class="animate-pulse space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach(range(1,8) as $i)
            <div class="bg-gray-200 dark:bg-gray-700 rounded-2xl h-24"></div>
            @endforeach
        </div>
        <div class="space-y-4">
            @foreach(range(1,6) as $i)
            <div class="flex items-center space-x-4 p-4">
                <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
                    <div class="h-2 bg-gray-300 dark:bg-gray-600 rounded w-1/2"></div>
                </div>
                <div class="h-5 bg-gray-300 dark:bg-gray-600 rounded w-16"></div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Main Content Container -->
    <div class="group relative">
        <div class="absolute -inset-0.5 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 rounded-2xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 transform transition-all duration-300 hover:shadow-xl overflow-hidden">
            
            <!-- Bulk Actions Container -->
            <div id="bulkActionsContainer" class="bulk-actions-container bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <span id="selectedCount" class="text-sm font-medium text-blue-700 dark:text-blue-300 text-smaller">
                            0 item dipilih
                        </span>
                        <select id="bulkActionSelect" class="text-sm border border-blue-300 dark:border-blue-600 rounded-lg px-3 py-1 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white text-smaller">
                            <option value="">Pilih Aksi</option>
                            <option value="activate">Aktifkan</option>
                            <option value="deactivate">Nonaktifkan</option>
                            <option value="delete">Hapus</option>
                        </select>
                        <button id="applyBulkAction" class="px-4 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 text-smaller">
                            Terapkan
                        </button>
                    </div>
                    <button id="clearSelection" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-smaller">
                        Hapus pilihan
                    </button>
                </div>
                <div class="mt-2 text-xs text-blue-700/80 dark:text-blue-200/80 text-smaller">
                    Catatan: aksi <strong>Hapus</strong> bisa berubah menjadi <strong>pending delete</strong> dan menunggu approval HO.
                </div>
            </div>

            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar Data Master</h3>
                        <p class="text-gray-600 dark:text-gray-400 mt-1 text-smaller">
                            Total {{ method_exists($data, 'total') ? $data->total() : 0 }} data ditemukan
                        </p>
                    </div>
                    
                    <!-- Controls -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                        <!-- View Toggle -->
                        <div class="flex items-center space-x-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                            <button id="tableViewBtn" class="p-2 rounded-lg text-blue-600 bg-white dark:bg-gray-600 shadow-sm view-transition" title="Table View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                            <button id="cardViewBtn" class="p-2 rounded-lg text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 view-transition" title="Card View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Search Form -->
                        <form action="{{ route('dev.data.index') }}" method="GET" class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" 
                                   name="search"
                                   class="pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:text-white w-64 placeholder-gray-500 dark:placeholder-gray-400 text-smaller"
                                   placeholder="Cari kode, uraian..."
                                   value="{{ request('search') }}">
                        </form>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <!-- Category Filter -->
                    <select name="kategori" onchange="this.form.submit()" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white text-smaller">
                        <option value="">Semua Kategori</option>
                        @foreach($kategoriList as $kode => $nama)
                            <option value="{{ $kode }}" {{ request('kategori') == $kode ? 'selected' : '' }}>
                                {{ $nama }} ({{ $kode }})
                            </option>
                        @endforeach
                    </select>

                    <!-- Status Filter -->
                    <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white text-smaller">
                        <option value="">Semua Status</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>

                    <!-- Sort Options -->
                    <select name="sort" onchange="this.form.submit()" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white text-smaller">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Terlama</option>
                        <option value="kode_asc" {{ request('sort') == 'kode_asc' ? 'selected' : '' }}>Kode A-Z</option>
                        <option value="kode_desc" {{ request('sort') == 'kode_desc' ? 'selected' : '' }}>Kode Z-A</option>
                        <option value="harga_high" {{ request('sort') == 'harga_high' ? 'selected' : '' }}>Harga Tertinggi</option>
                        <option value="harga_low" {{ request('sort') == 'harga_low' ? 'selected' : '' }}>Harga Terendah</option>
                    </select>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['search', 'kategori', 'status', 'sort']))
                    <a href="{{ route('dev.data.index') }}" 
                       class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors text-smaller">
                        Clear Filters
                    </a>
                    @endif
                </div>
            </div>

            <!-- Table View -->
            <div id="tableView" class="view-transition">
                <div class="overflow-x-auto elegant-scrollbar">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-compact">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-10">
                                    <input type="checkbox" id="selectAllCheckbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kode</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Uraian</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Satuan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @if($data->count() > 0)
                                @foreach($data as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200" data-item-id="{{ $item->id }}">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <input type="checkbox" class="row-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" value="{{ $item->id }}">
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-mono font-medium text-gray-900 dark:text-white text-smaller">{{ $item->kode }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex px-2.5 py-1 text-[11px] font-semibold rounded-full
                                            @if($item->kode_kategori == 'MT') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                            @elseif($item->kode_kategori == 'JS') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                                            @elseif($item->kode_kategori == 'AT') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300
                                            @elseif($item->kode_kategori == 'HO') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                            @elseif($item->kode_kategori == 'SR') bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300
                                            @elseif($item->kode_kategori == 'SB') bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-300
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                            {{ $kategoriList[$item->kode_kategori] ?? $item->kode_kategori }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900 dark:text-white text-smaller">{{ $item->uraian }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white text-smaller">{{ $item->satuan }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white text-smaller">
                                            Rp {{ number_format($item->harga, 0, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-flex px-2.5 py-1 text-[11px] font-semibold rounded-full
                                                {{ $item->status ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300' }}">
                                                {{ $item->status ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                            @if($item->delete_status === 'pending')
                                                <span class="inline-flex px-2.5 py-1 text-[11px] font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                    Pending Delete
                                                </span>
                                            @elseif($item->delete_status === 'rejected')
                                                <span class="inline-flex px-2.5 py-1 text-[11px] font-semibold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                                    Delete Ditolak
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="openQuickEdit({{ $item->id }})" 
                                                    class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-400 transition-colors p-1 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30"
                                                    title="Quick Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            @if($item->delete_status === 'pending')
                                                <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                    Menunggu HO
                                                </span>
                                                @if($isHO)
                                                    <form action="{{ route('dev.data.approve-delete', $item->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-emerald-600 hover:text-emerald-900 dark:hover:text-emerald-300 transition-colors p-1 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/30" title="Approve Delete">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('dev.data.reject-delete', $item->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-rose-600 hover:text-rose-900 dark:hover:text-rose-300 transition-colors p-1 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/30" title="Reject Delete">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <button onclick="confirmDelete({{ $item->id }})" 
                                                        class="text-red-600 hover:text-red-900 dark:hover:text-red-400 transition-colors p-1 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30"
                                                        title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                            <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                            </svg>
                                            <p class="text-lg font-medium mb-2">Tidak ada data</p>
                                            <p class="text-sm mb-4">Data tidak ditemukan untuk kriteria yang dipilih.</p>
                                            @if(request()->hasAny(['search', 'kategori', 'status']))
                                            <a href="{{ route('dev.data.index') }}" 
                                               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-smaller">
                                                Reset Filter
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($data->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-700 dark:text-gray-300 text-smaller">
                            Menampilkan {{ $data->firstItem() ?? 0 }} - {{ $data->lastItem() ?? 0 }} dari {{ $data->total() }} data
                        </div>
                        <div class="flex items-center space-x-1">
                            {{ $data->links() }}
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Card View -->
            <div id="cardView" class="hidden view-transition">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                    @if($data->count() > 0)
                        @foreach($data as $item)
                        <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 border border-gray-200 dark:border-gray-600 rounded-2xl p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                            <!-- Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-smaller">{{ $item->kode }}</h3>
                                    <span class="inline-block mt-1 px-2.5 py-1 text-[11px] font-semibold rounded-full
                                        @if($item->kode_kategori == 'MT') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                        @elseif($item->kode_kategori == 'JS') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                                        @elseif($item->kode_kategori == 'AT') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300
                                        @elseif($item->kode_kategori == 'HO') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                        @elseif($item->kode_kategori == 'SR') bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300
                                        @elseif($item->kode_kategori == 'SB') bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                        {{ $kategoriList[$item->kode_kategori] ?? $item->kode_kategori }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" class="row-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" value="{{ $item->id }}">
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex px-2.5 py-1 text-[11px] font-semibold rounded-full
                                            {{ $item->status ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300' }}">
                                            {{ $item->status ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                        @if($item->delete_status === 'pending')
                                            <span class="inline-flex px-2.5 py-1 text-[11px] font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                Pending Delete
                                            </span>
                                        @elseif($item->delete_status === 'rejected')
                                            <span class="inline-flex px-2.5 py-1 text-[11px] font-semibold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                                Delete Ditolak
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="space-y-3 mb-4">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1 text-smallest">Uraian</p>
                                    <p class="text-gray-900 dark:text-white text-smaller line-clamp-2">{{ $item->uraian }}</p>
                                </div>
                                <div class="flex justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1 text-smallest">Satuan</p>
                                        <p class="text-gray-900 dark:text-white font-medium text-smaller">{{ $item->satuan }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1 text-smallest">Harga</p>
                                        <p class="text-gray-900 dark:text-white font-bold text-smaller">Rp {{ number_format($item->harga, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-600">
                                <div class="text-xs text-gray-500 dark:text-gray-400 text-smallest">
                                    ID: {{ $item->id }}
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button onclick="openQuickEdit({{ $item->id }})" 
                                            class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-400 transition-colors p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 text-smaller">
                                        Edit
                                    </button>
                                    @if($item->delete_status === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                            Menunggu HO
                                        </span>
                                        @if($isHO)
                                            <form action="{{ route('dev.data.approve-delete', $item->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-emerald-600 hover:text-emerald-900 dark:hover:text-emerald-300 transition-colors p-2 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/30 text-smaller">
                                                    Approve
                                                </button>
                                            </form>
                                            <form action="{{ route('dev.data.reject-delete', $item->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-rose-600 hover:text-rose-900 dark:hover:text-rose-300 transition-colors p-2 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/30 text-smaller">
                                                    Reject
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <button onclick="confirmDelete({{ $item->id }})" 
                                                class="text-red-600 hover:text-red-900 dark:hover:text-red-400 transition-colors p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 text-smaller">
                                            Hapus
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                    <div class="col-span-full text-center py-12">
                        <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                            <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="text-lg font-medium mb-2">Tidak ada data</p>
                            <p class="text-sm mb-4">Data tidak ditemukan untuk kriteria yang dipilih.</p>
                            @if(request()->hasAny(['search', 'kategori', 'status']))
                            <a href="{{ route('dev.data.index') }}" 
                               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-smaller">
                                Reset Filter
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Modal -->
<div id="quickAddModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 backdrop-blur-sm"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tambah Data Baru</h3>
                <button type="button" onclick="closeQuickAdd()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="quickAddForm" action="{{ route('dev.data.quickStore') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-smaller">Kategori</label>
                        <select name="kode_kategori" required class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-smaller">
                            <option value="">Pilih Kategori</option>
                            @foreach($kategoriList as $kode => $nama)
                                <option value="{{ $kode }}">{{ $nama }} ({{ $kode }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-smaller">Uraian</label>
                        <textarea name="uraian" required rows="3" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-smaller" placeholder="Masukkan uraian lengkap..."></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-smaller">Satuan</label>
                            <input type="text" name="satuan" required class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-smaller" placeholder="pcs, kg, m, etc">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-smaller">Harga</label>
                            <input type="number" name="harga" required min="0" step="1" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-smaller" placeholder="0">
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeQuickAdd()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-lg transition-colors text-smaller">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors text-smaller">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Edit Modal -->
<div id="quickEditModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 backdrop-blur-sm"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Data</h3>
                <button type="button" onclick="closeQuickEdit()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="quickEditForm" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-smaller">Kode</label>
                        <input type="text" id="editKode" readonly class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-600 text-gray-500 dark:text-gray-400 text-smaller">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-smaller">Uraian</label>
                        <textarea name="uraian" id="editUraian" required rows="3" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-smaller"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-smaller">Satuan</label>
                            <input type="text" name="satuan" id="editSatuan" required class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-smaller">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-smaller">Harga</label>
                            <input type="number" name="harga" id="editHarga" required min="0" step="1" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-smaller">
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="status" id="editStatus" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="editStatus" class="ml-2 block text-sm text-gray-700 dark:text-gray-300 text-smaller">Aktif</label>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeQuickEdit()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-lg transition-colors text-smaller">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors text-smaller">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 backdrop-blur-sm"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Import Data dari Excel</h3>
                <button type="button" onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="importForm" action="{{ route('dev.data.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-smaller">File Excel</label>
                        <div class="flex items-center justify-center w-full">
                            <label for="file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:border-gray-600">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400 text-smaller"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 text-smallest">XLS, XLSX (MAX. 10MB)</p>
                                </div>
                                <input id="file" name="file" type="file" class="hidden" accept=".xls,.xlsx" required />
                            </label>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-2 text-smaller">Format yang didukung:</h4>
                        <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1 text-smallest">
                            <li>• Kolom A: Kode (otomatis)</li>
                            <li>• Kolom B: Kategori (MT, JS, AT, HO, SR, SB)</li>
                            <li>• Kolom C: Uraian</li>
                            <li>• Kolom D: Satuan</li>
                            <li>• Kolom E: Harga (angka)</li>
                            <li>• Kolom F: Status (1=Aktif, 0=Nonaktif)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeImportModal()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-lg transition-colors text-smaller">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors text-smaller">
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // View Toggle
        const tableViewBtn = document.getElementById('tableViewBtn');
        const cardViewBtn = document.getElementById('cardViewBtn');
        const tableView = document.getElementById('tableView');
        const cardView = document.getElementById('cardView');

        // Set initial view to table
        showTableView();

        tableViewBtn.addEventListener('click', showTableView);
        cardViewBtn.addEventListener('click', showCardView);

        function showTableView() {
            tableView.classList.remove('hidden');
            cardView.classList.add('hidden');
            tableViewBtn.classList.add('text-blue-600', 'bg-white', 'dark:bg-gray-600', 'shadow-sm');
            tableViewBtn.classList.remove('text-gray-500');
            cardViewBtn.classList.remove('text-blue-600', 'bg-white', 'dark:bg-gray-600', 'shadow-sm');
            cardViewBtn.classList.add('text-gray-500');
        }

        function showCardView() {
            tableView.classList.add('hidden');
            cardView.classList.remove('hidden');
            cardViewBtn.classList.add('text-blue-600', 'bg-white', 'dark:bg-gray-600', 'shadow-sm');
            cardViewBtn.classList.remove('text-gray-500');
            tableViewBtn.classList.remove('text-blue-600', 'bg-white', 'dark:bg-gray-600', 'shadow-sm');
            tableViewBtn.classList.add('text-gray-500');
        }

        // Bulk Actions
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        const bulkActionsContainer = document.getElementById('bulkActionsContainer');
        const selectedCount = document.getElementById('selectedCount');
        const clearSelection = document.getElementById('clearSelection');
        const applyBulkAction = document.getElementById('applyBulkAction');
        const bulkActionSelect = document.getElementById('bulkActionSelect');

        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateBulkActions();
        });

        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActions);
        });

        clearSelection.addEventListener('click', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            selectAllCheckbox.checked = false;
            updateBulkActions();
        });

        applyBulkAction.addEventListener('click', function() {
            const action = bulkActionSelect.value;
            const selectedIds = getSelectedIds();

            if (!action) {
                showNotification('Pilih aksi terlebih dahulu', 'error');
                return;
            }

            if (selectedIds.length === 0) {
                showNotification('Pilih data terlebih dahulu', 'error');
                return;
            }

            if (action === 'delete') {
                if (!confirm(`Anda yakin ingin menghapus ${selectedIds.length} data?`)) {
                    return;
                }
            }

            performBulkAction(action, selectedIds);
        });

        function updateBulkActions() {
            const selectedIds = getSelectedIds();
            const count = selectedIds.length;

            if (count > 0) {
                bulkActionsContainer.classList.add('show');
                selectedCount.textContent = `${count} item dipilih`;
                selectAllCheckbox.checked = count === rowCheckboxes.length;
            } else {
                bulkActionsContainer.classList.remove('show');
                selectedCount.textContent = '0 item dipilih';
                selectAllCheckbox.checked = false;
            }
        }

        function getSelectedIds() {
            const selectedIds = [];
            rowCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedIds.push(checkbox.value);
                }
            });
            return selectedIds;
        }

        function getActionText(action) {
            const actions = {
                'activate': 'mengaktifkan',
                'deactivate': 'menonaktifkan',
                'delete': 'menghapus'
            };
            return actions[action] || 'melakukan aksi pada';
        }

        function performBulkAction(action, ids) {
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('action', action);
            ids.forEach(id => formData.append('ids[]', id));

            // PERBAIKAN: Gunakan URL langsung untuk menghindari error route
            fetch('/dev/data/bulk-action', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan saat memproses permintaan', 'error');
            });
        }
    });

    // Modal Functions
    function openQuickAdd() {
        document.getElementById('quickAddModal').classList.remove('hidden');
    }

    function closeQuickAdd() {
        document.getElementById('quickAddModal').classList.add('hidden');
    }

    function openQuickEdit(id) {
        fetch(`/dev/data/${id}/ajax`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = data.data;
                    document.getElementById('editKode').value = item.kode;
                    document.getElementById('editUraian').value = item.uraian;
                    document.getElementById('editSatuan').value = item.satuan;
                    document.getElementById('editHarga').value = item.harga;
                    document.getElementById('editStatus').checked = item.status;
                    
                    document.getElementById('quickEditForm').action = `/dev/data/${id}`;
                    document.getElementById('quickEditModal').classList.remove('hidden');
                } else {
                    showNotification('Gagal memuat data', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan', 'error');
            });
    }

    function closeQuickEdit() {
        document.getElementById('quickEditModal').classList.add('hidden');
    }

    function openImportModal() {
        document.getElementById('importModal').classList.remove('hidden');
    }

    function closeImportModal() {
        document.getElementById('importModal').classList.add('hidden');
    }

    // Maintenance Functions
    function fixGaps() {
        if (!confirm('Perbaiki penomoran yang kosong?')) return;
        
        // PERBAIKAN: Gunakan URL langsung untuk menghindari error route
        fetch('/dev/data/fix-gaps', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan', 'error');
        });
    }

    function resetCounters() {
        if (!confirm('Reset semua counter ke 0? Tindakan ini akan mengatur ulang semua penomoran.')) return;
        
        // PERBAIKAN: Gunakan URL langsung untuk menghindari error route
        fetch('/dev/data/reset-counters', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan', 'error');
        });
    }

    // Delete Confirmation
    function confirmDelete(id) {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            fetch(`/dev/data/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if (result.pending) {
                        showNotification(result.message || 'Permintaan hapus dikirim dan menunggu approval HO.', 'warning');
                    } else {
                        showNotification('Data berhasil dihapus', 'success');
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification('Gagal menghapus data: ' + result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan saat menghapus data', 'error');
            });
        }
    }

    // Notification System
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        const types = {
            success: { bg: 'bg-green-500', border: 'border-green-400' },
            error: { bg: 'bg-red-500', border: 'border-red-400' },
            warning: { bg: 'bg-yellow-500', border: 'border-yellow-400' },
            info: { bg: 'bg-blue-500', border: 'border-blue-400' }
        };

        notification.className = `fixed top-4 right-4 z-50 ${types[type].bg} ${types[type].border} text-white px-6 py-3 rounded-2xl shadow-2xl transform transition-all duration-300 translate-x-full`;
        notification.innerHTML = `
            <div class="flex items-center space-x-2">
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Animate out and remove
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Handle form submissions
    document.getElementById('quickAddForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Data berhasil ditambahkan', 'success');
                closeQuickAdd();
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(data.message || 'Gagal menambahkan data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan', 'error');
        });
    });

    document.getElementById('quickEditForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Data berhasil diupdate', 'success');
                closeQuickEdit();
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(data.message || 'Gagal mengupdate data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan', 'error');
        });
    });

    document.getElementById('importForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.textContent = 'Mengimport...';
        submitBtn.disabled = true;
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                closeImportModal();
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(data.message || 'Gagal mengimport data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan', 'error');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });

    // File input display
    const fileInput = document.getElementById('file');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const label = fileInput.closest('label');
                const existingText = label.querySelector('.upload-text');
                if (existingText) {
                    existingText.textContent = fileName;
                } else {
                    const text = document.createElement('p');
                    text.className = 'upload-text text-sm text-gray-900 dark:text-white font-medium text-smaller';
                    text.textContent = fileName;
                    label.querySelector('.flex-col').appendChild(text);
                }
            }
        });
    }
</script>
@endsection

