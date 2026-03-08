@extends('layouts.dev')

@section('title', 'Edit Klien')

@section('subtitle', 'Edit Data Klien')

@section('content')
<div class="max-w-4xl mx-auto px-2 sm:px-4">
    <div class="group relative">
        <div class="absolute -inset-0.5 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 rounded-xl sm:rounded-2xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl border border-gray-200 dark:border-gray-700 p-4 sm:p-6 lg:p-8 transform transition-all duration-300 hover:shadow-xl">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Edit Klien</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Perbarui informasi klien: {{ $client->name }}</p>
                    </div>
                    <div class="flex items-center space-x-2 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Terakhir update: {{ $client->updated_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            <form action="{{ route('dev.clients.update', $client) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <!-- Name -->
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nama Klien *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <input type="text" name="name" id="name" required
                                class="pl-9 sm:pl-10 block w-full border border-gray-300 dark:border-gray-600 rounded-lg sm:rounded-xl shadow-sm py-2 sm:py-3 px-3 sm:px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white transition-colors duration-200 text-sm sm:text-base"
                                placeholder="Masukkan nama klien"
                                value="{{ old('name', $client->name) }}">
                        </div>
                        @error('name')
                            <p class="text-red-500 text-xs mt-2 flex items-center">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Company -->
                    <div>
                        <label for="company" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Perusahaan
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <input type="text" name="company" id="company"
                                class="pl-9 sm:pl-10 block w-full border border-gray-300 dark:border-gray-600 rounded-lg sm:rounded-xl shadow-sm py-2 sm:py-3 px-3 sm:px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white transition-colors duration-200 text-sm sm:text-base"
                                placeholder="Nama perusahaan"
                                value="{{ old('company', $client->company) }}">
                        </div>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Telepon
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <input type="text" name="phone" id="phone"
                                class="pl-9 sm:pl-10 block w-full border border-gray-300 dark:border-gray-600 rounded-lg sm:rounded-xl shadow-sm py-2 sm:py-3 px-3 sm:px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white transition-colors duration-200 text-sm sm:text-base"
                                placeholder="Nomor telepon"
                                value="{{ old('phone', $client->phone) }}">
                        </div>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Kategori *
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <select name="category" id="category" required
                                class="pl-9 sm:pl-10 block w-full border border-gray-300 dark:border-gray-600 rounded-lg sm:rounded-xl shadow-sm py-2 sm:py-3 px-3 sm:px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white transition-colors duration-200 text-sm sm:text-base appearance-none">
                                <option value="">Pilih Kategori</option>
                                <option value="Corporate" {{ old('category', $client->category) == 'Corporate' ? 'selected' : '' }}>Corporate</option>
                                <option value="Government" {{ old('category', $client->category) == 'Government' ? 'selected' : '' }}>Government</option>
                                <option value="Individual" {{ old('category', $client->category) == 'Individual' ? 'selected' : '' }}>Individual</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        @error('category')
                            <p class="text-red-500 text-xs mt-2 flex items-center">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input type="email" name="email" id="email"
                                class="pl-9 sm:pl-10 block w-full border border-gray-300 dark:border-gray-600 rounded-lg sm:rounded-xl shadow-sm py-2 sm:py-3 px-3 sm:px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white transition-colors duration-200 text-sm sm:text-base"
                                placeholder="Alamat email"
                                value="{{ old('email', $client->email) }}">
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Alamat
                        </label>
                        <div class="relative">
                            <div class="absolute top-3 left-3 flex items-start pointer-events-none">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <textarea name="address" id="address" rows="3"
                                class="pl-9 sm:pl-10 block w-full border border-gray-300 dark:border-gray-600 rounded-lg sm:rounded-xl shadow-sm py-2 sm:py-3 px-3 sm:px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white transition-colors duration-200 text-sm sm:text-base resize-none"
                                placeholder="Alamat lengkap">{{ old('address', $client->address) }}</textarea>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Catatan
                        </label>
                        <div class="relative">
                            <div class="absolute top-3 left-3 flex items-start pointer-events-none">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                                </svg>
                            </div>
                            <textarea name="notes" id="notes" rows="3"
                                class="pl-9 sm:pl-10 block w-full border border-gray-300 dark:border-gray-600 rounded-lg sm:rounded-xl shadow-sm py-2 sm:py-3 px-3 sm:px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white transition-colors duration-200 text-sm sm:text-base resize-none"
                                placeholder="Catatan tambahan tentang klien">{{ old('notes', $client->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-end gap-3">
                    <a href="{{ route('dev.clients.index') }}" 
                       class="group relative px-4 sm:px-6 py-2 sm:py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg sm:rounded-2xl font-medium transition-all duration-300 hover:bg-gray-50 dark:hover:bg-gray-700 transform hover:scale-105 flex items-center justify-center text-sm sm:text-base">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Batal
                    </a>
                    <button type="submit" 
                            class="group relative bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg sm:rounded-2xl font-medium transition-all duration-300 transform hover:scale-105 hover:shadow-lg flex items-center justify-center text-sm sm:text-base">
                        <div class="absolute -inset-0.5 bg-green-600 rounded-lg sm:rounded-2xl blur opacity-0 group-hover:opacity-30 transition duration-1000 group-hover:duration-200"></div>
                        <span class="relative flex items-center">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update Klien
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection