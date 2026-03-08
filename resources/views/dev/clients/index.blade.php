@extends('layouts.dev')

@section('title', 'Klien')
@section('subtitle', 'Manajemen Data Klien')

@section('content')
@php
    $isHO = auth()->check() && (
        (auth()->user()->is_admin ?? false) ||
        (method_exists(auth()->user(), 'isHO') && auth()->user()->isHO())
    );
@endphp
<style>
    .elegant-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    
    .elegant-scrollbar::-webkit-scrollbar-track {
        background: rgba(243, 244, 246, 0.5);
        border-radius: 8px;
    }
    
    .elegant-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.6);
        border-radius: 8px;
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

    /* Responsive table styles */
    .responsive-table {
        width: 100%;
        table-layout: auto;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 0.5rem 0.4rem;
        vertical-align: middle;
    }
    
    /* Column widths for better distribution */
    .responsive-table th:nth-child(1),
    .responsive-table td:nth-child(1) {
        width: 22%;
        min-width: 140px;
    }
    
    .responsive-table th:nth-child(2),
    .responsive-table td:nth-child(2) {
        width: 25%;
        min-width: 140px;
    }
    
    .responsive-table th:nth-child(3),
    .responsive-table td:nth-child(3) {
        width: 18%;
        min-width: 100px;
    }
    
    .responsive-table th:nth-child(4),
    .responsive-table td:nth-child(4) {
        width: 15%;
        min-width: 90px;
    }
    
    .responsive-table th:nth-child(5),
    .responsive-table td:nth-child(5) {
        width: 20%;
        min-width: 140px;
    }

    /* Ensure text is visible */
    .client-name {
        min-width: 0;
        overflow: hidden;
    }
</style>

<div class="space-y-4 px-2">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">Manajemen Klien</h1>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Kelola data klien dan informasi perusahaan</p>
        </div>
        <a href="{{ route('dev.clients.create') }}" 
           class="group relative bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-3 py-2 rounded-lg font-medium transition-all duration-300 transform hover:scale-105 hover:shadow flex items-center justify-center w-full sm:w-auto text-xs">
            <div class="absolute -inset-0.5 bg-blue-600 rounded-lg blur opacity-0 group-hover:opacity-30 transition duration-1000 group-hover:duration-200"></div>
            <span class="relative flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Klien
            </span>
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        @php
            $stats = [
                [
                    'title' => 'Total Klien',
                    'value' => $clients->total(),
                    'description' => 'Semua klien',
                    'color' => 'blue',
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
                ],
                [
                    'title' => 'Corporate',
                    'value' => $clients->where('category', 'Corporate')->count(),
                    'description' => 'Perusahaan swasta',
                    'color' => 'purple',
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
                ],
                [
                    'title' => 'Government',
                    'value' => $clients->where('category', 'Government')->count(),
                    'description' => 'Instansi pemerintah',
                    'color' => 'green',
                    'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
                ],
                [
                    'title' => 'Individual',
                    'value' => $clients->where('category', 'Individual')->count(),
                    'description' => 'Klien perorangan',
                    'color' => 'orange',
                    'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
                ]
            ];
        @endphp

        @foreach($stats as $stat)
        <div class="group relative min-h-[80px]">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 rounded-lg blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
            <div class="relative bg-gradient-to-br from-{{ $stat['color'] }}-500 via-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 text-white rounded-lg p-3 h-full transform transition-all duration-300 hover:scale-[1.02] hover:shadow flex flex-col">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex-1 min-w-0">
                        <p class="text-{{ $stat['color'] }}-100 text-xs font-medium mb-1">{{ $stat['title'] }}</p>
                        <p class="text-lg font-bold">{{ $stat['value'] }}</p>
                    </div>
                    <div class="bg-{{ $stat['color'] }}-400/20 p-1 rounded-md backdrop-blur-sm border border-{{ $stat['color'] }}-300/20 ml-2 flex-shrink-0">
                        <svg class="w-4 h-4 transform group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    @if($clients->isEmpty() && !request()->has('search'))
    <div class="animate-pulse space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach(range(1,4) as $i)
            <div class="bg-gray-200 dark:bg-gray-700 rounded-lg h-20"></div>
            @endforeach
        </div>
        <div class="space-y-3">
            @foreach(range(1,6) as $i)
            <div class="flex items-center space-x-3 p-3">
                <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
                    <div class="h-2 bg-gray-300 dark:bg-gray-600 rounded w-1/2"></div>
                </div>
                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-16"></div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Main Content Container -->
    <div class="group relative">
        <div class="absolute -inset-0.5 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 rounded-lg blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 transform transition-all duration-300 hover:shadow overflow-hidden">
            
            <!-- Table Header -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Daftar Klien</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Total {{ $clients->total() }} klien ditemukan</p>
                    </div>
                    
                    <!-- Controls -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                        <!-- View Toggle -->
                        <div class="flex items-center space-x-1 bg-gray-100 dark:bg-gray-700 rounded-md p-1">
                            <button id="tableViewBtn" class="p-1 rounded-md text-blue-600 bg-white dark:bg-gray-600 shadow-sm view-transition" title="Table View">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                            <button id="cardViewBtn" class="p-1 rounded-md text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 view-transition" title="Card View">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Search Form -->
                        <form action="{{ route('dev.clients.index') }}" method="GET" class="relative w-full sm:w-auto">
                            <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" 
                                   name="search"
                                   class="pl-7 pr-3 py-1 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:text-white w-full sm:w-48 placeholder-gray-500 dark:placeholder-gray-400 text-xs"
                                   placeholder="Cari klien..."
                                   value="{{ request('search') }}">
                        </form>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <!-- Category Filter -->
                    <select name="category" class="text-xs border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white">
                        <option value="">Semua Kategori</option>
                        <option value="Corporate" {{ request('category') == 'Corporate' ? 'selected' : '' }}>Corporate</option>
                        <option value="Government" {{ request('category') == 'Government' ? 'selected' : '' }}>Government</option>
                        <option value="Individual" {{ request('category') == 'Individual' ? 'selected' : '' }}>Individual</option>
                    </select>

                    <!-- Sort Options -->
                    <select name="sort" class="text-xs border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Terlama</option>
                        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
                        <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
                    </select>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['search', 'category', 'sort']))
                    <a href="{{ route('dev.clients.index') }}" 
                       class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors">
                        Clear Filters
                    </a>
                    @endif
                </div>
            </div>

            <!-- Table View -->
            <div id="tableView" class="view-transition">
                <div class="overflow-x-auto elegant-scrollbar">
                    <table class="responsive-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama</th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Perusahaan</th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Telepon</th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($clients as $client)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                <td>
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                            <span class="text-white font-medium text-xs">
                                                {{ strtoupper(substr($client->name, 0, 1)) }}
                                            </span>
                                        </div>
                                        <div class="ml-2 client-name">
                                            <div class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ $client->name }}</div>
                                            @if($client->email)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $client->email }}</div>
                                            @endif
                                            @if($client->delete_status === 'pending')
                                                <span class="mt-1 inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                    Pending Delete
                                                </span>
                                            @elseif($client->delete_status === 'rejected')
                                                <span class="mt-1 inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                                    Delete Ditolak
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-xs text-gray-900 dark:text-white truncate">{{ $client->company ?? '-' }}</div>
                                    @if($client->address)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Str::limit($client->address, 35) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-xs text-gray-900 dark:text-white">{{ $client->phone ?? '-' }}</div>
                                </td>
                                <td>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($client->category == 'Corporate') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                        @elseif($client->category == 'Government') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                                        @elseif($client->category == 'Individual') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                        {{ $client->category ?? 'Corporate' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('dev.clients.show', $client) }}" 
                                           class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200 flex items-center group text-xs">
                                            <svg class="w-3 h-3 mr-1 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Lihat
                                        </a>
                                        <a href="{{ route('dev.clients.edit', $client) }}" 
                                           class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 transition-colors duration-200 flex items-center group text-xs">
                                            <svg class="w-3 h-3 mr-1 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        @if($client->delete_status === 'pending')
                                            <span class="inline-flex px-2 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                Menunggu HO
                                            </span>
                                            @if($isHO)
                                                <form action="{{ route('dev.clients.approve-delete', $client->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-emerald-600 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors duration-200 flex items-center group text-xs">
                                                        <svg class="w-3 h-3 mr-1 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        Approve
                                                    </button>
                                                </form>
                                                <form action="{{ route('dev.clients.reject-delete', $client->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300 transition-colors duration-200 flex items-center group text-xs">
                                                        <svg class="w-3 h-3 mr-1 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                        Reject
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <form action="{{ route('dev.clients.destroy', $client) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus klien ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200 flex items-center group text-xs">
                                                    <svg class="w-3 h-3 mr-1 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center">
                                    <div class="max-w-md mx-auto">
                                        <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </div>
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                                            @if(request()->hasAny(['search', 'category']))
                                            Tidak ada klien yang cocok
                                            @else
                                            Belum ada klien
                                            @endif
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 leading-relaxed">
                                            @if(request()->hasAny(['search', 'category']))
                                            Coba sesuaikan pencarian atau filter Anda untuk melihat hasil yang berbeda.
                                            @else
                                            Mulai dengan menambahkan klien pertama Anda ke sistem untuk mengelola proyek dan informasi kontak.
                                            @endif
                                        </p>
                                        <div class="space-y-2">
                                            <a href="{{ route('dev.clients.create') }}" 
                                               class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg font-medium transition-all duration-300 transform hover:scale-105 hover:shadow text-xs">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Tambah Klien Pertama
                                            </a>
                                            @if(request()->hasAny(['search', 'category']))
                                            <div>
                                                <a href="{{ route('dev.clients.index') }}" 
                                                   class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 inline-flex items-center">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                    </svg>
                                                    Tampilkan semua klien
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
            </div>

            <!-- Card View -->
            <div id="cardView" class="view-transition hidden p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($clients as $client)
                    <div class="group relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <span class="text-white font-medium text-xs">
                                        {{ strtoupper(substr($client->name, 0, 1)) }}
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate text-xs">{{ $client->name }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $client->company ?? 'No Company' }}</p>
                                </div>
                            </div>
                            <span class="inline-flex px-1 py-0.5 text-xs font-semibold rounded-full flex-shrink-0
                                @if($client->category == 'Corporate') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                @elseif($client->category == 'Government') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                                @elseif($client->category == 'Individual') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                {{ $client->category ?? 'Corporate' }}
                            </span>
                        </div>

                        <div class="space-y-1 mb-3">
                            @if($client->email)
                            <div class="flex items-center text-xs text-gray-600 dark:text-gray-400">
                                <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span class="truncate">{{ $client->email }}</span>
                            </div>
                            @endif
                            @if($client->phone)
                            <div class="flex items-center text-xs text-gray-600 dark:text-gray-400">
                                <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span class="truncate">{{ $client->phone }}</span>
                            </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $client->projects_count ?? 0 }} projects
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ route('dev.clients.show', $client) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-medium">
                                    View
                                </a>
                                <a href="{{ route('dev.clients.edit', $client) }}" class="text-green-600 hover:text-green-800 dark:text-green-400 text-xs font-medium">
                                    Edit
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full">
                        <!-- Empty state for card view -->
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination -->
            @if($clients->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="text-xs text-gray-700 dark:text-gray-300">
                        Menampilkan {{ $clients->firstItem() }} - {{ $clients->lastItem() }} dari {{ $clients->total() }} klien
                    </div>
                    <div class="flex space-x-1">
                        {{ $clients->links() }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableViewBtn = document.getElementById('tableViewBtn');
        const cardViewBtn = document.getElementById('cardViewBtn');
        const tableView = document.getElementById('tableView');
        const cardView = document.getElementById('cardView');

        function switchView(view) {
            if (view === 'table') {
                tableView.classList.remove('hidden');
                cardView.classList.add('hidden');
                tableViewBtn.classList.add('text-blue-600', 'bg-white', 'dark:bg-gray-600', 'shadow-sm');
                tableViewBtn.classList.remove('text-gray-500');
                cardViewBtn.classList.add('text-gray-500');
                cardViewBtn.classList.remove('text-blue-600', 'bg-white', 'dark:bg-gray-600', 'shadow-sm');
            } else {
                tableView.classList.add('hidden');
                cardView.classList.remove('hidden');
                cardViewBtn.classList.add('text-blue-600', 'bg-white', 'dark:bg-gray-600', 'shadow-sm');
                cardViewBtn.classList.remove('text-gray-500');
                tableViewBtn.classList.add('text-gray-500');
                tableViewBtn.classList.remove('text-blue-600', 'bg-white', 'dark:bg-gray-600', 'shadow-sm');
            }
            
            localStorage.setItem('clientsView', view);
        }

        const savedView = localStorage.getItem('clientsView') || 'table';
        switchView(savedView);

        tableViewBtn.addEventListener('click', () => switchView('table'));
        cardViewBtn.addEventListener('click', () => switchView('card'));

        document.querySelectorAll('select[name="category"], select[name="sort"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    });
</script>
@endsection
