{{-- resources/views/dev/RAPPs/create.blade.php --}}
@extends('layouts.dev')

@section('title', 'Buat RAPP Baru - ' . $project->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Buat RAPP Baru</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Project: {{ $project->name }} - {{ $project->client->name }}
                </p>
            </div>
            {{-- PERBAIKAN: Ganti route name dari dev.projects.rabs.index menjadi dev.rab-baseline.index --}}
            <a href="{{ route('dev.rab-baseline.index', $project->id) }}" 
               class="flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Daftar RAPP
            </a>
        </div>

        <!-- Form -->
        <div class="group relative">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-200 to-blue-300 dark:from-blue-700 dark:to-blue-800 rounded-2xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 transform transition-all duration-300 hover:shadow-xl">
                
                {{-- PERBAIKAN: Pastikan route store juga benar --}}
                <form action="{{ route('dev.rab-baseline.store', $project->id) }}" method="POST">
                    @csrf

                    <div class="space-y-6">
                        <!-- Basic Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Dasar RAPP</h3>
                            
                            <div class="grid grid-cols-1 gap-4">
                                <!-- RAPP Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Nama RAPP <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           name="name" 
                                           id="name"
                                           value="{{ old('name', $defaultName) }}"
                                           required
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                           placeholder="Masukkan nama RAPP">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div>
                                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Catatan (Opsional)
                                    </label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              rows="3"
                                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                              placeholder="Tambahkan catatan atau deskripsi RAPP">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Project Info -->
                        <div class="border-t border-gray-200 dark:border-gray-600 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Project</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Nama Project</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $project->name }}</p>
                                </div>
                                
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Client</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $project->client->name }}</p>
                                </div>
                                
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Lokasi</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $project->location }}</p>
                                </div>
                                
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Budget Project</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $project->budget_formatted }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="border-t border-gray-200 dark:border-gray-600 pt-6 flex justify-end space-x-3">
                            {{-- PERBAIKAN: Ganti route cancel juga --}}
                            <a href="{{ route('dev.rab-baseline.index', $project->id) }}" 
                               class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Batal
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Buat RAPP & Lanjutkan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Box -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Tips Membuat RAPP</h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Beri nama RAPP yang deskriptif untuk memudahkan identifikasi</li>
                            <li>RAPP akan dibuat dalam status <strong>Draft</strong> dan dapat diedit kapan saja</li>
                            <li>Setelah RAPP dibuat, Anda dapat menambahkan items dari data master</li>
                            <li>Gunakan fitur duplicate untuk membuat versi baru dari RAPP yang sudah ada</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection






