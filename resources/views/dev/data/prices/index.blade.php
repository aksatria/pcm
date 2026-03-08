@extends('layouts.dev')

@section('title', $title)
@section('subtitle', $subtitle)

@section('content')
<div class="space-y-6">
    <!-- Header dengan Master Data Info -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $masterData->kode_item }}</h1>
                        <p class="text-gray-600 dark:text-gray-400">{{ $masterData->uraian_item }}</p>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $masterData->kategori_label }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Harga Dasar: Rp {{ number_format($masterData->harga_satuan_1, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dev.data.show', $masterData->id) }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Kembali ke Detail
                    </a>
                    <button onclick="openAddPriceModal()" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Harga
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Price Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
            <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Harga</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $priceStats['total_prices'] ?? 0 }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
            <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Harga Aktif</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $priceStats['active_prices'] ?? 0 }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
            <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Provinsi Tercover</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $priceStats['provinces_covered'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Prices Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar Harga</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Kelola harga berdasarkan provinsi dan periode</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Provinsi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Periode Efektif</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($prices as $price)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @if($price->province)
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $price->province->name }}</span>
                                @else
                                <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Global</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($price->price, 0, ',', '.') }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                @if($price->price > $masterData->harga_satuan_1)
                                <span class="text-red-600">+{{ number_format((($price->price - $masterData->harga_satuan_1) / $masterData->harga_satuan_1) * 100, 1) }}%</span>
                                @elseif($price->price < $masterData->harga_satuan_1)
                                <span class="text-green-600">{{ number_format((($price->price - $masterData->harga_satuan_1) / $masterData->harga_satuan_1) * 100, 1) }}%</span>
                                @else
                                <span class="text-gray-500">0%</span>
                                @endif
                                dari harga dasar
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <div>
                                <div class="font-medium">Mulai: {{ $price->effective_from ? \Carbon\Carbon::parse($price->effective_from)->format('d M Y') : 'Selamanya' }}</div>
                                <div class="text-gray-500 dark:text-gray-400">Sampai: {{ $price->effective_to ? \Carbon\Carbon::parse($price->effective_to)->format('d M Y') : 'Tidak Terbatas' }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $now = now()->toDateString();
                                $isActive = (!$price->effective_from || $price->effective_from <= $now) && 
                                           (!$price->effective_to || $price->effective_to >= $now);
                            @endphp
                            @if($isActive)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Aktif
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Tidak Aktif
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $price->supplier_id ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <button onclick="openEditPriceModal({{ $price->id }})" 
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 px-2 py-1 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                Edit
                            </button>
                            <button onclick="confirmDeletePrice({{ $price->id }})" 
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 px-2 py-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                Hapus
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center">
                            <div class="text-center">
                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Belum ada harga</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Mulai dengan menambahkan harga untuk provinsi tertentu.</p>
                                <button onclick="openAddPriceModal()" 
                                        class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Tambah Harga Pertama
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Price Comparison Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Perbandingan Harga Aktif</h3>
        <div id="priceComparisonChart" class="space-y-3">
            <!-- Price comparison will be loaded here via AJAX -->
            <div class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Memuat perbandingan harga...</p>
            </div>
        </div>
    </div>
</div>

<!-- Price Modal -->
@include('dev.data.prices._price-modal')

<script>
// Price Modal Management Functions
function openPriceModal(title, htmlContent, actionUrl = null, method = 'POST') {
    document.getElementById('priceModalTitle').innerText = title;
    document.getElementById('priceModalBody').innerHTML = `
        <form id="priceModalForm" action="${actionUrl || ''}" method="post" class="space-y-4">
            <input type="hidden" name="_token" value="${getCsrfToken()}">
            ${method !== 'POST' ? `<input type="hidden" name="_method" value="${method}">` : ''}
            <div id="priceModalFields">${htmlContent}</div>
        </form>
    `;

    // Reset submit button state
    const submitBtn = document.getElementById('priceModalSubmit');
    const spinner = document.getElementById('priceModalSpinner');
    const submitText = document.getElementById('priceModalSubmitText');
    
    submitBtn.disabled = false;
    spinner.classList.add('hidden');
    submitText.innerText = 'Simpan';

    // Attach submit handler
    submitBtn.onclick = function() {
        submitPriceModal(actionUrl, method);
    };

    // Show modal dengan animation
    const modal = document.getElementById('priceModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Focus first input
    setTimeout(() => {
        const firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) firstInput.focus();
    }, 100);
}

function closePriceModal() {
    const modal = document.getElementById('priceModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('priceModalBody').innerHTML = '';
}

function submitPriceModal(actionUrl, method = 'POST') {
    const form = document.getElementById('priceModalForm');
    const submitBtn = document.getElementById('priceModalSubmit');
    const spinner = document.getElementById('priceModalSpinner');
    const submitText = document.getElementById('priceModalSubmitText');
    
    if (!form) {
        showNotification('Form tidak ditemukan', 'error');
        return;
    }

    // Show loading state
    submitBtn.disabled = true;
    spinner.classList.remove('hidden');
    submitText.innerText = 'Menyimpan...';

    const formData = new FormData(form);
    
    fetch(actionUrl, {
        method: method.toUpperCase(),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async (res) => {
        const contentType = res.headers.get('content-type') || '';
        const isJson = contentType.indexOf('application/json') !== -1;
        const data = isJson ? await res.json() : null;

        if (!res.ok) {
            const msg = data?.message || (data?.errors ? Object.values(data.errors).flat().join(', ') : `HTTP ${res.status}`);
            throw new Error(msg);
        }

        return data;
    })
    .then(data => {
        showNotification(data.message || 'Harga berhasil disimpan', 'success');
        closePriceModal();
        setTimeout(() => { 
            window.location.reload(); 
        }, 1000);
    })
    .catch(err => {
        console.error('Error:', err);
        showNotification(err.message || 'Terjadi kesalahan saat menyimpan harga', 'error');
        
        // Reset button state
        submitBtn.disabled = false;
        spinner.classList.add('hidden');
        submitText.innerText = 'Simpan';
    });
}

// Helper function untuk CSRF Token
function getCsrfToken() {
    const el = document.querySelector('meta[name="csrf-token"]');
    return el ? el.getAttribute('content') : '';
}

// Notification helper
function showNotification(message, type = 'info') {
    // Remove existing notifications
    document.querySelectorAll('.notification-toast').forEach(el => el.remove());

    const bgColor = type === 'success' ? 'bg-green-500' : 
                   type === 'error' ? 'bg-red-500' : 
                   type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
    
    const icon = type === 'success' ? 'OK' :
                type === 'error' ? 'X' :
                type === 'warning' ? '!' : 'i';

    const wrapper = document.createElement('div');
    wrapper.className = `notification-toast ${bgColor} text-white px-4 py-3 rounded-lg shadow-lg fixed top-4 right-4 z-60 transform transition-transform duration-300 translate-x-full`;
    wrapper.innerHTML = `
        <div class="flex items-center space-x-2">
            <span class="font-semibold">${icon}</span>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(wrapper);
    
    // Animate in
    setTimeout(() => {
        wrapper.classList.remove('translate-x-full');
    }, 10);
    
    // Auto remove
    setTimeout(() => {
        wrapper.classList.add('translate-x-full');
        setTimeout(() => wrapper.remove(), 300);
    }, 3500);
}

// Open Add Price Modal
function openAddPriceModal() {
    const formFields = `
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Provinsi</label>
                <select name="province_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">Harga Global (Semua Provinsi)</option>
                    @foreach($provinces as $province)
                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Pilih provinsi untuk harga spesifik, atau kosongkan untuk harga global</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Harga *</label>
                <input type="number" name="price" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm" 
                       placeholder="350000" min="0" step="0.01" required>
                <p class="text-xs text-gray-500 mt-1">Harga dasar: Rp {{ number_format($masterData->harga_satuan_1, 0, ',', '.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mulai Efektif</label>
                    <input type="date" name="effective_from" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                    <p class="text-xs text-gray-500 mt-1">Kosongkan untuk mulai sekarang</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Berakhir Pada</label>
                    <input type="date" name="effective_to" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                    <p class="text-xs text-gray-500 mt-1">Kosongkan untuk tidak terbatas</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Supplier (Opsional)</label>
                <input type="text" name="supplier_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm" 
                       placeholder="Kode atau nama supplier">
            </div>
        </div>
    `;
    
    openPriceModal('Tambah Harga Baru', formFields, "/dev/data/{{ $masterData->id }}/prices");
}

// Open Edit Price Modal - FIXED URL
function openEditPriceModal(priceId) {
    // ✅ PERBAIKI: Gunakan URL yang benar sesuai route definition
    const editUrl = `/dev/data/prices/${priceId}/edit`;
    
    fetch(editUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const price = data.data;
            const formFields = `
                <input type="hidden" name="_method" value="PUT">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Provinsi</label>
                        <select name="province_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">Harga Global (Semua Provinsi)</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province->id }}" ${price.province_id == {{ $province->id }} ? 'selected' : ''}>{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Harga *</label>
                        <input type="number" name="price" value="${price.price}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm" 
                               placeholder="350000" min="0" step="0.01" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mulai Efektif</label>
                            <input type="date" name="effective_from" value="${price.effective_from ? price.effective_from.split('T')[0] : ''}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Berakhir Pada</label>
                            <input type="date" name="effective_to" value="${price.effective_to ? price.effective_to.split('T')[0] : ''}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Supplier (Opsional)</label>
                        <input type="text" name="supplier_id" value="${price.supplier_id || ''}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm" 
                               placeholder="Kode atau nama supplier">
                    </div>
                </div>
            `;
            
            // ✅ PERBAIKI: Gunakan URL yang benar untuk update
            const updateUrl = `/dev/data/prices/${priceId}`;
            openPriceModal('Edit Harga', formFields, updateUrl, 'POST');
        } else {
            showNotification('Gagal memuat data harga', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat memuat data', 'error');
    });
}

// Confirm Delete Price - FIXED URL
function confirmDeletePrice(priceId) {
    if (confirm('Apakah Anda yakin ingin menghapus harga ini?')) {
        const csrfToken = getCsrfToken();
        
        // ✅ PERBAIKI: Gunakan URL yang benar untuk delete
        const deleteUrl = `/dev/data/prices/${priceId}`;
        
        fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan saat menghapus harga', 'error');
        });
    }
}

// Load Price Comparison
function loadPriceComparison() {
    fetch(`/dev/data/{{ $masterData->id }}/prices/comparison`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderPriceComparison(data.data);
            } else {
                document.getElementById('priceComparisonChart').innerHTML = 
                    '<p class="text-center text-gray-500 dark:text-gray-400 py-8">Gagal memuat perbandingan harga</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('priceComparisonChart').innerHTML = 
                '<p class="text-center text-gray-500 dark:text-gray-400 py-8">Terjadi kesalahan saat memuat data</p>';
        });
}

// Render Price Comparison
function renderPriceComparison(prices) {
    const container = document.getElementById('priceComparisonChart');
    
    if (prices.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500 dark:text-gray-400 py-8">Tidak ada data perbandingan</p>';
        return;
    }

    let html = '';
    const basePrice = prices.find(p => p.is_base)?.price || 0;
    
    prices.forEach(price => {
        const isBase = price.is_base;
        const isGlobal = price.is_global;
        const percentage = !isBase ? ((price.price - basePrice) / basePrice * 100).toFixed(1) : 0;
        
        html += `
            <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors ${isBase ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-700' : ''}">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg ${isBase ? 'bg-yellow-500' : isGlobal ? 'bg-gray-500' : 'bg-blue-500'} flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">${price.province_name}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            ${price.effective_from ? 'Efektif: ' + new Date(price.effective_from).toLocaleDateString('id-ID') : 'Selamanya'}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-gray-900 dark:text-white">${price.price_formatted}</p>
                    ${!isBase ? `<p class="text-sm ${price.price > basePrice ? 'text-red-600' : 'text-green-600'}">
                        ${price.price > basePrice ? '+' : ''}${percentage}%
                    </p>` : ''}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadPriceComparison();
    
    // Close modal on outside click
    document.getElementById('priceModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closePriceModal();
        }
    });
});
</script>
@endsection



