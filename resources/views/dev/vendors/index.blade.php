
@extends('layouts.dev')

@section('title', 'Vendor')
@section('subtitle', 'Manajemen Data Vendor')

@section('content')
@php
    $isHO = auth()->check() && (
        (auth()->user()->is_admin ?? false) ||
        (method_exists(auth()->user(), 'isHO') && auth()->user()->isHO())
    );

    $totalVouchers = $vendors->sum('vouchers_count') ?? 0;
@endphp
<style>
    .elegant-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .elegant-scrollbar::-webkit-scrollbar-track { background: rgba(243, 244, 246, 0.5); border-radius: 8px; }
    .elegant-scrollbar::-webkit-scrollbar-thumb { background: rgba(156, 163, 175, 0.6); border-radius: 8px; transition: all 0.3s ease; }
    .elegant-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(107, 114, 128, 0.8); }
    .dark .elegant-scrollbar::-webkit-scrollbar-track { background: rgba(55, 65, 81, 0.5); }
    .dark .elegant-scrollbar::-webkit-scrollbar-thumb { background: rgba(75, 85, 99, 0.6); }
    .view-transition { transition: all 0.3s ease-in-out; }

    .responsive-table { width: 100%; table-layout: auto; }
    .responsive-table th, .responsive-table td { padding: 0.5rem 0.4rem; vertical-align: middle; }

    .responsive-table th:nth-child(1), .responsive-table td:nth-child(1) { width: 24%; min-width: 170px; }
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 16%; min-width: 130px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 16%; min-width: 110px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 20%; min-width: 160px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 12%; min-width: 100px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 12%; min-width: 170px; }

    .vendor-name { min-width: 0; overflow: hidden; }
</style>

<div class="space-y-4 px-2">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">Manajemen Vendor</h1>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Kelola data vendor, supplier, dan informasi rekening</p>
        </div>
        <a href="{{ route('dev.vendors.create') }}"
           class="group relative bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-3 py-2 rounded-lg font-medium transition-all duration-300 transform hover:scale-105 hover:shadow flex items-center justify-center w-full sm:w-auto text-xs">
            <div class="absolute -inset-0.5 bg-blue-600 rounded-lg blur opacity-0 group-hover:opacity-30 transition duration-1000 group-hover:duration-200"></div>
            <span class="relative flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Vendor
            </span>
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        @php
            $statsCards = [
                ['title' => 'Total Vendor', 'value' => $stats['total'] ?? 0, 'description' => 'Semua vendor', 'color' => 'blue', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                ['title' => 'Aktif', 'value' => $stats['active'] ?? 0, 'description' => 'Vendor aktif', 'color' => 'green', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['title' => 'Non Aktif', 'value' => $stats['inactive'] ?? 0, 'description' => 'Vendor non aktif', 'color' => 'orange', 'icon' => 'M18.364 5.636l-1.414-1.414L12 9.172 7.05 4.222 5.636 5.636 10.586 10.586 5.636 15.536l1.414 1.414L12 12l4.95 4.95 1.414-1.414-4.95-4.95z'],
                ['title' => 'Total Voucher', 'value' => $totalVouchers, 'description' => 'Akumulasi transaksi', 'color' => 'purple', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 8v2m-6-6H6m12 0h-2']
            ];
        @endphp

        @foreach($statsCards as $stat)
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

    @if($vendors->isEmpty() && !request()->hasAny(['search', 'scope', 'project_id', 'status', 'kategori']))
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

    <div class="group relative">
        <div class="absolute -inset-0.5 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 rounded-lg blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 transform transition-all duration-300 hover:shadow overflow-hidden">

            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Daftar Vendor</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Total {{ $vendors->total() }} vendor ditemukan</p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                        <div class="flex items-center space-x-1 bg-gray-100 dark:bg-gray-700 rounded-md p-1">
                            <button id="tableViewBtn" class="p-1 rounded-md text-blue-600 bg-white dark:bg-gray-600 shadow-sm view-transition" title="Table View">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                            <button id="cardViewBtn" class="p-1 rounded-md text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 view-transition" title="Card View">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            </button>
                        </div>

                        <form id="searchForm" action="{{ route('dev.vendors.index') }}" method="GET" class="relative w-full sm:w-auto">
                            <input type="hidden" name="scope" value="{{ request('scope', 'all') }}">
                            <input type="hidden" name="project_id" value="{{ request('project_id') }}">
                            <input type="hidden" name="status" value="{{ request('status') }}">
                            <input type="hidden" name="kategori" value="{{ request('kategori') }}">
                            <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input type="text" name="search" class="pl-7 pr-3 py-1 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:text-white w-full sm:w-48 placeholder-gray-500 dark:placeholder-gray-400 text-xs" placeholder="Cari vendor..." value="{{ request('search') }}">
                        </form>
                    </div>
                </div>

                <form id="filterForm" action="{{ route('dev.vendors.index') }}" method="GET" class="mt-3 flex flex-wrap items-center gap-2">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <select name="scope" class="text-xs border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white">
                        <option value="all" {{ request('scope', 'all') == 'all' ? 'selected' : '' }}>Semua Scope</option>
                        <option value="global" {{ request('scope') == 'global' ? 'selected' : '' }}>Global</option>
                        <option value="project" {{ request('scope') == 'project' ? 'selected' : '' }}>Per Proyek</option>
                    </select>
                    <select name="project_id" class="text-xs border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white">
                        <option value="">Semua Proyek</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ (string) request('project_id') === (string) $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="text-xs border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>

                    <select name="kategori" class="text-xs border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 bg-white dark:bg-gray-700 focus:ring-2 focus:ring-blue-500 dark:text-white">
                        <option value="">Semua Kategori</option>
                        @foreach(['Toko Bangunan', 'Supplier Material', 'Jasa Kontraktor', 'Jasa Arsitek', 'Jasa Konsultan', 'Logistik', 'Lainnya'] as $category)
                            <option value="{{ $category }}" {{ request('kategori') === $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>

                    @if(auth()->check() && auth()->user()->isHO())
                        <a href="{{ route('dev.vendors.export') }}?format=excel" class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 px-2 py-1 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors">Export Excel</a>
                    @endif

                    @if(request()->hasAny(['search', 'scope', 'project_id', 'status', 'kategori']))
                    <a href="{{ route('dev.vendors.index') }}" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors">Clear Filters</a>
                    @endif
                </form>
            </div>

            <div id="tableView" class="view-transition">
                <div class="overflow-x-auto elegant-scrollbar">
                    <table class="responsive-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vendor</th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ruang Lingkup</th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pekerjaan</th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rekening</th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($vendors as $vendor)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                <td>
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                            <span class="text-white font-medium text-xs">{{ strtoupper(substr($vendor->nama, 0, 1)) }}</span>
                                        </div>
                                        <div class="ml-2 vendor-name">
                                            <div class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ $vendor->nama }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $vendor->perusahaan ?? '-' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $vendor->kode_vendor }}{{ $vendor->telepon ? ' • ' . $vendor->telepon : '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-300">{{ $vendor->project ? $vendor->project->name : 'Global' }}</span>
                                </td>
                                <td>
                                    <div class="text-xs text-gray-900 dark:text-white">{{ $vendor->pekerjaan ?? '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $vendor->vouchers_count ?? 0 }} voucher</div>
                                </td>
                                <td>
                                    <div class="text-xs text-gray-900 dark:text-white">{{ $vendor->bank ?? '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $vendor->no_rekening ?? '-' }} {{ $vendor->nama_rekening ? ' - ' . $vendor->nama_rekening : '' }}</div>
                                </td>
                                <td>
                                    @if($vendor->status === 'active')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">Active</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inactive</span>
                                    @endif
                                    @if($vendor->delete_status === 'pending')
                                        <span class="mt-1 inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">Pending Delete</span>
                                    @elseif($vendor->delete_status === 'rejected')
                                        <span class="mt-1 inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">Delete Ditolak</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('dev.vendors.edit', $vendor->id) }}" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 transition-colors duration-200 flex items-center group text-xs">
                                            <svg class="w-3 h-3 mr-1 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Edit
                                        </a>
                                        @if($vendor->delete_status === 'pending')
                                            <span class="inline-flex px-2 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">Menunggu HO</span>
                                            @if($isHO)
                                                <form action="{{ route('dev.vendors.approve-delete', $vendor->id) }}" method="POST" class="inline">@csrf<button type="submit" class="text-emerald-600 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors duration-200 text-xs">Approve</button></form>
                                                <form action="{{ route('dev.vendors.reject-delete', $vendor->id) }}" method="POST" class="inline">@csrf<button type="submit" class="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300 transition-colors duration-200 text-xs">Reject</button></form>
                                            @endif
                                        @else
                                            <form action="{{ route('dev.vendors.destroy', $vendor->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus vendor ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-200 flex items-center group text-xs">
                                                    <svg class="w-3 h-3 mr-1 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center">
                                    <div class="max-w-md mx-auto">
                                        <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </div>
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                                            @if(request()->hasAny(['search', 'scope', 'project_id', 'status', 'kategori'])) Tidak ada vendor yang cocok @else Belum ada vendor @endif
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 leading-relaxed">
                                            @if(request()->hasAny(['search', 'scope', 'project_id', 'status', 'kategori'])) Coba sesuaikan pencarian atau filter Anda untuk melihat hasil yang berbeda. @else Mulai dengan menambahkan vendor pertama Anda ke sistem untuk mengelola transaksi. @endif
                                        </p>
                                        <div class="space-y-2">
                                            <a href="{{ route('dev.vendors.create') }}" class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg font-medium transition-all duration-300 transform hover:scale-105 hover:shadow text-xs">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                Tambah Vendor Pertama
                                            </a>
                                            @if(request()->hasAny(['search', 'scope', 'project_id', 'status', 'kategori']))
                                            <div>
                                                <a href="{{ route('dev.vendors.index') }}" class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 inline-flex items-center">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    Tampilkan semua vendor
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
            <div id="cardView" class="view-transition hidden p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($vendors as $vendor)
                    <div class="group relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow transition-all duration-300 transform hover:-translate-y-1">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <span class="text-white font-medium text-xs">{{ strtoupper(substr($vendor->nama, 0, 1)) }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate text-xs">{{ $vendor->nama }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $vendor->perusahaan ?? '-' }}</p>
                                </div>
                            </div>
                            <span class="inline-flex px-1 py-0.5 text-xs font-semibold rounded-full flex-shrink-0 @if($vendor->status === 'active') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                {{ $vendor->status === 'active' ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="space-y-1 mb-3">
                            <div class="flex items-center text-xs text-gray-600 dark:text-gray-400"><span class="truncate">{{ $vendor->kode_vendor }}</span></div>
                            <div class="flex items-center text-xs text-gray-600 dark:text-gray-400"><span class="truncate">{{ $vendor->project ? $vendor->project->name : 'Global' }}</span></div>
                            <div class="flex items-center text-xs text-gray-600 dark:text-gray-400"><span class="truncate">{{ $vendor->bank ?? '-' }} - {{ $vendor->no_rekening ?? '-' }}</span></div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $vendor->vouchers_count ?? 0 }} voucher</div>
                            <div class="flex space-x-2">
                                <a href="{{ route('dev.vendors.edit', $vendor->id) }}" class="text-green-600 hover:text-green-800 dark:text-green-400 text-xs font-medium">Edit</a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full"></div>
                    @endforelse
                </div>
            </div>

            @if($vendors->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="text-xs text-gray-700 dark:text-gray-300">Menampilkan {{ $vendors->firstItem() }} - {{ $vendors->lastItem() }} dari {{ $vendors->total() }} vendor</div>
                    <div class="flex space-x-1">{{ $vendors->links() }}</div>
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
        const searchForm = document.getElementById('searchForm');
        const filterForm = document.getElementById('filterForm');

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
            localStorage.setItem('vendorsView', view);
        }

        const savedView = localStorage.getItem('vendorsView') || 'table';
        switchView(savedView);

        tableViewBtn.addEventListener('click', () => switchView('table'));
        cardViewBtn.addEventListener('click', () => switchView('card'));

        if (searchForm) {
            searchForm.addEventListener('submit', function() {
                if (filterForm) {
                    searchForm.querySelector('input[name="scope"]').value = filterForm.querySelector('select[name="scope"]').value;
                    searchForm.querySelector('input[name="project_id"]').value = filterForm.querySelector('select[name="project_id"]').value;
                    searchForm.querySelector('input[name="status"]').value = filterForm.querySelector('select[name="status"]').value;
                    searchForm.querySelector('input[name="kategori"]').value = filterForm.querySelector('select[name="kategori"]').value;
                }
            });
        }

        if (filterForm) {
            filterForm.querySelectorAll('select[name="scope"], select[name="project_id"], select[name="status"], select[name="kategori"]').forEach(select => {
                select.addEventListener('change', function() {
                    filterForm.submit();
                });
            });
        }
    });
</script>
@endsection
