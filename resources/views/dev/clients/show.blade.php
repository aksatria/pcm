@extends('layouts.dev')

@section('title', $client->name)

@section('content')
@php
    $isHO = auth()->check() && (
        (auth()->user()->is_admin ?? false) ||
        (method_exists(auth()->user(), 'isHO') && auth()->user()->isHO())
    );
@endphp
<div class="max-w-7xl mx-auto px-2 sm:px-4">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
            <div class="flex-1 min-w-0">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white truncate">{{ $client->name }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 truncate">{{ $client->company ?? 'Tidak ada perusahaan' }}</p>
                @if($client->delete_status === 'pending')
                    <span class="mt-2 inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                        Pending Delete
                    </span>
                @elseif($client->delete_status === 'rejected')
                    <span class="mt-2 inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                        Delete Ditolak
                    </span>
                @endif
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('dev.clients.edit', $client) }}" 
                   class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs sm:text-sm font-medium transition-colors whitespace-nowrap">
                    Edit
                </a>
                @if($client->delete_status === 'pending' && $isHO)
                    <form action="{{ route('dev.clients.approve-delete', $client->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs sm:text-sm font-medium transition-colors whitespace-nowrap">
                            Approve Delete
                        </button>
                    </form>
                    <form action="{{ route('dev.clients.reject-delete', $client->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs sm:text-sm font-medium transition-colors whitespace-nowrap">
                            Reject Delete
                        </button>
                    </form>
                @endif
                <a href="{{ route('dev.clients.index') }}" 
                   class="px-3 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-xs sm:text-sm font-medium transition-colors whitespace-nowrap">
                    Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Left Column - Client Information -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
                <h2 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-3 sm:mb-4">Informasi Klien</h2>
                
                <div class="space-y-3 sm:space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-1">Kategori</label>
                            <p class="text-sm sm:text-base text-gray-900 dark:text-white font-medium">{{ $client->category ?? 'Corporate' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-1">Email</label>
                            <p class="text-sm sm:text-base text-gray-900 dark:text-white break-words">{{ $client->email ?? '-' }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-1">Telepon</label>
                            <p class="text-sm sm:text-base text-gray-900 dark:text-white">{{ $client->phone ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-1">Bergabung</label>
                            <p class="text-sm sm:text-base text-gray-900 dark:text-white">{{ $client->created_at->format('d M Y') }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-1">Alamat</label>
                        <p class="text-sm sm:text-base text-gray-900 dark:text-white break-words">{{ $client->address ?? '-' }}</p>
                    </div>

                    @if($client->notes)
                    <div>
                        <label class="block text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-1">Catatan</label>
                        <p class="text-sm sm:text-base text-gray-900 dark:text-white whitespace-pre-line break-words">{{ $client->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Projects Card -->
            @if($client->projects_count > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <h2 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">Daftar Project</h2>
                        <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ $client->projects_count }} project</span>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <!-- Grid Layout sebagai alternatif tabel -->
                    <div class="space-y-3 sm:space-y-4">
                        @foreach($client->projects as $project)
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3 sm:p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 sm:gap-0 mb-2 sm:mb-3">
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('dev.projects.show', $project) }}" 
                                       class="text-sm sm:text-base font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 block truncate">
                                        {{ $project->name }}
                                    </a>
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $project->start_date->format('d M Y') }}
                                        @if($project->end_date)
                                        - {{ $project->end_date->format('d M Y') }}
                                        @endif
                                    </p>
                                </div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full sm:ml-4 flex-shrink-0
                                    @if($project->status == 'Active') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                                    @elseif($project->status == 'Completed') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                    @elseif($project->status == 'Planning') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                    @elseif($project->status == 'On Hold') bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                    {{ $project->status }}
                                </span>
                            </div>
                            
                            <div class="text-xs sm:text-sm">
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Budget</p>
                                    <p class="font-medium text-gray-900 dark:text-white">Rp {{ number_format($project->budget, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Total Summary -->
                    <div class="mt-4 sm:mt-6 pt-3 sm:pt-4 border-t border-gray-200 dark:border-gray-600">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 text-xs sm:text-sm">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Total Budget</p>
                                <p class="font-semibold text-gray-900 dark:text-white">Rp {{ number_format($client->total_budget, 0, ',', '.') }}</p>
                            </div>
                            <div class="text-right">
                                <a href="{{ route('dev.projects.index') }}?client_id={{ $client->id }}" 
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                    Lihat semua →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
                <svg class="w-12 h-12 sm:w-16 sm:h-16 text-gray-300 dark:text-gray-600 mx-auto mb-3 sm:mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3 sm:mb-4">Belum ada project untuk klien ini</p>
                <a href="{{ route('dev.projects.create') }}?client_id={{ $client->id }}" 
                   class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs sm:text-sm font-medium transition-colors">
                    Buat Project Pertama
                </a>
            </div>
            @endif
        </div>

        <!-- Right Column - Sidebar -->
        <div class="space-y-4 sm:space-y-6">
            <!-- Projects Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
                <h2 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-3 sm:mb-4">Ringkasan Project</h2>
                
                <div class="space-y-3 sm:space-y-4">
                    <!-- Total Project -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 sm:p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs sm:text-sm font-medium text-blue-600 dark:text-blue-400">Total Project</p>
                                <p class="text-xl sm:text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $client->projects_count ?? 0 }}</p>
                            </div>
                            <div class="p-2 bg-blue-100 dark:bg-blue-800 rounded-lg">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Budget -->
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 sm:p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs sm:text-sm font-medium text-green-600 dark:text-green-400">Total Budget</p>
                                <p class="text-base sm:text-lg font-bold text-green-700 dark:text-green-300 truncate">
                                    Rp {{ number_format($client->total_budget ?? 0, 0, ',', '.') }}
                                </p>
                            </div>
                            <div class="p-2 bg-green-100 dark:bg-green-800 rounded-lg ml-2 sm:ml-3 flex-shrink-0">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
                <h2 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-3 sm:mb-4">Aksi Cepat</h2>
                
                <div class="space-y-2 sm:space-y-3">
                    <a href="{{ route('dev.projects.create') }}?client_id={{ $client->id }}" 
                       class="w-full flex items-center justify-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs sm:text-sm font-medium transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Buat Project Baru
                    </a>
                    
                    <form action="{{ route('dev.clients.destroy', $client) }}" method="POST" 
                          onsubmit="return confirm('Hapus klien {{ $client->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="w-full flex items-center justify-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs sm:text-sm font-medium transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Hapus Klien
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

