{{-- resources/views/dev/data/show.blade.php --}}
@extends('layouts.dev')

@section('title', 'Detail Data')
@section('subtitle', 'Detail data master')

@section('content')
@php
    $isHO = auth()->check() && (
        (auth()->user()->is_admin ?? false) ||
        (method_exists(auth()->user(), 'isHO') && auth()->user()->isHO())
    );
@endphp
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-4">
        <nav class="flex mb-3" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-1 text-sm">
                <li>
                    <a href="{{ route('dev.dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-xs">
                        Dashboard
                    </a>
                </li>
                <li class="flex items-center">
                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <a href="{{ route('dev.data.index') }}" class="ml-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-xs">
                        Data Master
                    </a>
                </li>
                <li class="flex items-center">
                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <span class="ml-1 text-gray-700 dark:text-gray-300 font-medium text-xs">Detail Data</span>
                </li>
            </ol>
        </nav>
        
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Detail Data Master</h1>
                <p class="mt-1 text-gray-600 dark:text-gray-400 text-sm">Informasi lengkap item {{ $item->kode }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('dev.data.edit', $item) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 text-sm">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                <a href="{{ route('dev.data.index') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200 text-sm">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Basic Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Informasi Dasar</h3>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Kode</label>
                            <p class="mt-1 text-xs font-mono text-gray-900 dark:text-white">{{ $item->kode }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Kategori</label>
                            <p class="mt-1 text-xs text-gray-900 dark:text-white">{{ $item->kategori }} ({{ $item->kode_kategori }})</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Satuan</label>
                            <p class="mt-1 text-xs text-gray-900 dark:text-white">{{ $item->satuan }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Harga</label>
                            <p class="mt-1 text-xs font-semibold text-gray-900 dark:text-white">Rp {{ number_format($item->harga, 2, ',', '.') }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Status</label>
                        <p class="mt-1">
                            @if($item->status)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                <svg class="w-2.5 h-2.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Aktif
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                <svg class="w-2.5 h-2.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Nonaktif
                            </span>
                            @endif
                        </p>
                        @if($item->delete_status === 'pending')
                            <p class="mt-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">
                                    Pending Delete
                                </span>
                            </p>
                        @elseif($item->delete_status === 'rejected')
                            <p class="mt-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-300">
                                    Delete Ditolak
                                </span>
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Description Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Uraian</h3>
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed text-sm">{{ $item->uraian }}</p>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <!-- Status Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Informasi Sistem</h3>
                <div class="space-y-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Dibuat</label>
                        <p class="mt-1 text-xs text-gray-900 dark:text-white">{{ $item->created_at->format('d M Y H:i') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">oleh {{ $item->created_by ?? 'System' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Diupdate</label>
                        <p class="mt-1 text-xs text-gray-900 dark:text-white">{{ $item->updated_at->format('d M Y H:i') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">oleh {{ $item->updated_by ?? 'System' }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Aksi</h3>
                <div class="space-y-2">
                    <a href="{{ route('dev.data.edit', $item) }}" class="w-full flex items-center justify-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 text-sm">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Data
                    </a>
                    @if($item->delete_status === 'pending')
                        <div class="w-full flex items-center justify-center px-3 py-1.5 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 rounded-lg text-sm font-medium">
                            Menunggu Approval HO
                        </div>
                        @if($isHO)
                            <form action="{{ route('dev.data.approve-delete', $item->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center justify-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors duration-200 text-sm">
                                    Approve Delete
                                </button>
                            </form>
                            <form action="{{ route('dev.data.reject-delete', $item->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center justify-center px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition-colors duration-200 text-sm">
                                    Reject Delete
                                </button>
                            </form>
                        @endif
                    @else
                        <button onclick="confirmDelete({{ $item->id }}, '{{ $item->kode }}')" class="w-full flex items-center justify-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200 text-sm">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Hapus Data
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, kode) {
    if (confirm(`Apakah Anda yakin ingin menghapus data "${kode}"?`)) {
        fetch(`/dev/data/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                if (result.pending) {
                    alert(result.message || 'Permintaan hapus dikirim dan menunggu approval HO.');
                    window.location.reload();
                } else {
                    window.location.href = '/dev/data';
                }
            } else {
                alert('Gagal menghapus data: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus data');
        });
    }
}
</script>
@endsection
