@extends('layouts.dev')

@section('title', $rapp->name)

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-3 mb-2 flex-wrap">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white break-words">{{ $rapp->name }}</h1>
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full flex-shrink-0
                        @if($rapp->status == 'draft') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                        @elseif($rapp->status == 'submitted') bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300
                        @elseif($rapp->status == 'approved') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                        @else bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300 @endif">
                        {{ $rapp->status_label }}
                    </span>
                </div>
                <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400 flex-wrap gap-2">
                    <div class="flex items-center space-x-1">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        <span class="break-all">Kode: {{ $rapp->code }}</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                        <span>Project: {{ $rapp->project->name }}</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Dibuat oleh: {{ $rapp->creator->name ?? 'Unknown User' }}</span>
                    </div>
                </div>
            </div>
            <div class="flex space-x-3 flex-shrink-0 mt-4 lg:mt-0">
                @if($rapp->canEdit())
                <a href="{{ route('dev.rapps.edit', $rapp) }}" 
                   class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                @endif

                @if($rapp->canSubmit())
                <form action="{{ route('dev.rapps.submit', $rapp) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors"
                            onclick="return confirm('Kirim RAPP untuk approval?')">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                        </svg>
                        Submit
                    </button>
                </form>
                @endif

                @if($rapp->canApprove())
                <div class="flex space-x-2">
                    <form action="{{ route('dev.rapps.approve', $rapp) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors"
                                onclick="return confirm('Setujui RAPP ini?')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Approve
                        </button>
                    </form>
                    <form action="{{ route('dev.rapps.reject', $rapp) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors"
                                onclick="return confirm('Tolak RAPP ini?')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Reject
                        </button>
                    </form>
                </div>
                @endif

                @if($rapp->status == 'approved')
                <form action="{{ route('dev.rapps.generate-rab', $rapp) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition-colors"
                            onclick="return confirm('Generate RAB dari RAPP ini?')">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Generate RAB
                    </button>
                </form>
                @endif

                <a href="{{ route('dev.rapps.index') }}" 
                   class="flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $rapp->rapp_items_count }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total Item</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ count($hierarchicalItems) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pekerjaan Utama</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ $rapp->formatted_total_estimate }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total Estimasi</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                {{ $rapp->created_at->format('d M Y') }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Dibuat</div>
        </div>
    </div>

    <!-- Description -->
    @if($rapp->description)
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Deskripsi RAPP</h3>
        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line break-words">{{ $rapp->description }}</p>
    </div>
    @endif

    <!-- RAPP Items -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Struktur Pekerjaan</h3>
                @if($rapp->canEdit())
                <button type="button" onclick="openAddItemModal()"
                        class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Item
                </button>
                @endif
            </div>
        </div>

        <div class="p-6">
            @if(count($hierarchicalItems) > 0)
                <div class="space-y-4">
                    @foreach($hierarchicalItems as $item)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-mono text-sm font-bold text-blue-600 dark:text-blue-400">{{ $item->job_code }}</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->description }}</span>
                                    </div>
                                    @if($item->unit)
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Volume: {{ $item->formatted_volume }} {{ $item->unit }} 
                                        @if($item->unit_cost_estimate > 0)
                                        • Harga: {{ $item->formatted_unit_cost_estimate }}
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                        {{ $item->formatted_total_estimate }}
                                    </div>
                                    @if($item->has_children)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $item->children->count() }} sub-item
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Children Items -->
                            @if($item->children && $item->children->count() > 0)
                                <div class="mt-3 pl-4 border-l-2 border-gray-300 dark:border-gray-600 space-y-2">
                                    @foreach($item->children as $child)
                                        @include('dev.rapps.partials.rapp-item-child', ['item' => $child, 'level' => 1])
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Belum ada item pekerjaan</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mulai dengan menambahkan item pekerjaan pertama.</p>
                    @if($rapp->canEdit())
                    <div class="mt-6">
                        <button type="button" onclick="openAddItemModal()"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tambah Item Pertama
                        </button>
                    </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Add Item Modal -->
@if($rapp->canEdit())
<div id="addItemModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-4 border w-full max-w-md shadow-lg rounded-lg bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tambah Item Pekerjaan</h3>
            <button type="button" onclick="closeAddItemModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form id="addItemForm" method="POST" action="{{ route('dev.rapps.store-item', $rapp) }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="parent_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Parent Item (Opsional)
                    </label>
                    <select name="parent_code" id="parent_code" 
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                        <option value="">-- Pekerjaan Utama --</option>
                        @foreach($rapp->rappItems as $item)
                            @if($item->canHaveChildren())
                            <option value="{{ $item->job_code }}">{{ $item->job_code }} - {{ $item->description }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Deskripsi Pekerjaan *
                    </label>
                    <textarea name="description" id="description" required rows="3"
                              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                              placeholder="Deskripsi detail pekerjaan..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Satuan
                        </label>
                        <select name="unit" id="unit"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">-- Pilih Satuan --</option>
                            @foreach(\App\Models\RappItem::getUnitOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="volume" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Volume
                        </label>
                        <input type="number" name="volume" id="volume" step="0.0001" min="0"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                               placeholder="0.0000">
                    </div>
                </div>

                <div>
                    <label for="unit_cost_estimate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Harga Satuan (Rp)
                    </label>
                    <input type="number" name="unit_cost_estimate" id="unit_cost_estimate" step="0.01" min="0"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                           placeholder="0"
                           oninput="formatCurrency(this)">
                    <div class="text-xs text-gray-500 mt-1" id="unit_cost_estimate_formatted"></div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeAddItemModal()"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
// Format currency input
function formatCurrency(input) {
    const value = parseFloat(input.value) || 0;
    const formatted = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
    
    document.getElementById('unit_cost_estimate_formatted').textContent = formatted;
}

function openAddItemModal() {
    document.getElementById('addItemModal').classList.remove('hidden');
}

function closeAddItemModal() {
    document.getElementById('addItemModal').classList.add('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addItemModal');
    if (event.target === modal) {
        closeAddItemModal();
    }
}

// Form submission
document.getElementById('addItemForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Terjadi kesalahan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
});
</script>
@endsection



