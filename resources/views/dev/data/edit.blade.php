{{-- resources/views/dev/data/edit.blade.php --}}
@extends('layouts.dev')

@section('title', 'Edit Data')
@section('subtitle', 'Edit data master')

@section('content')
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
                    <span class="ml-1 text-gray-700 dark:text-gray-300 font-medium text-xs">Edit Data</span>
                </li>
            </ol>
        </nav>
        
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Edit Data Master</h1>
                <p class="mt-1 text-gray-600 dark:text-gray-400 text-sm">Edit item {{ $item->kode }}</p>
            </div>
            <a href="{{ route('dev.data.index') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200 text-sm">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali
            </a>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Form Header -->
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900">
            <div class="flex items-center">
                <div class="w-8 h-8 flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 rounded-lg mr-2">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Form Edit Data</h2>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Perbarui informasi data master</p>
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <form method="POST" action="{{ route('dev.data.update', $item->id) }}" class="p-5">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <!-- Kode (readonly) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Kode
                    </label>
                    <input type="text" value="{{ $item->kode }}" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm" readonly>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kode tidak dapat diubah</p>
                </div>

                <!-- Kategori (readonly) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Kategori
                    </label>
                    <input type="text" value="{{ $item->kategori }} ({{ $item->kode_kategori }})" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm" readonly>
                </div>

                <!-- Uraian -->
                <div>
                    <label for="uraian" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Uraian <span class="text-red-500">*</span>
                    </label>
                    <textarea id="uraian" name="uraian" rows="3" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 transition-colors duration-200 resize-none text-sm" placeholder="Masukkan uraian lengkap item..." required>{{ old('uraian', $item->uraian) }}</textarea>
                    @error('uraian')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Satuan dan Harga -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Satuan -->
                    <div>
                        <label for="satuan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Satuan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="satuan" name="satuan" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 transition-colors duration-200 appearance-none text-sm" required>
                                <option value="">Pilih Satuan</option>
                                @foreach($satuanList as $satuan)
                                    <option value="{{ $satuan }}" {{ $item->satuan == $satuan ? 'selected' : '' }}>{{ $satuan }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        @error('satuan')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Harga -->
                    <div>
                        <label for="harga" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Harga <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 text-sm">Rp</span>
                            </div>
                            <input type="number" id="harga" name="harga" value="{{ old('harga', $item->harga) }}" min="0" step="0.01" class="w-full pl-10 pr-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 transition-colors duration-200 text-sm" required>
                        </div>
                        @error('harga')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Status
                    </label>
                    <div class="relative">
                        <select id="status" name="status" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 transition-colors duration-200 appearance-none text-sm">
                            <option value="1" {{ $item->status ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ !$item->status ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-2">
                <a href="{{ route('dev.data.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 font-medium text-sm">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center text-sm">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Update Data
                </button>
            </div>
        </form>
    </div>
</div>
@endsection