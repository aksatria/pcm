@extends('layouts.dev')

@section('title', 'Buat RAPP Baru')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Buat RAPP Baru</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Rencana Anggaran Pekerjaan Project</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('dev.rapps.index') }}?project_id={{ $projectId }}" 
                   class="flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Info tentang 1 project = 1 RAPP -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
        <div class="flex">
            <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    Satu Project = Satu RAPP
                </h3>
                <div class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                    Setiap project hanya dapat memiliki satu RAPP (Rencana Anggaran Pekerjaan). 
                    <strong>Kode dan Nama RAPP akan digenerate otomatis.</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <form action="{{ route('dev.rapps.store') }}" method="POST">
            @csrf
            
            <div class="space-y-6">
                <!-- Project Selection -->
                <div>
                    <label for="project_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Pilih Project *
                    </label>
                    <select name="project_id" id="project_id" required
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">-- Pilih Project --</option>
                        @foreach($projects as $project)
                            @php
                                $hasRapp = \App\Models\Rapp::where('project_id', $project->id)->exists();
                            @endphp
                            <option value="{{ $project->id }}" 
                                    {{ $projectId == $project->id ? 'selected' : '' }}
                                    {{ $hasRapp ? 'disabled' : '' }}
                                    data-code="{{ $project->code }}"
                                    data-name="{{ $project->name }}"
                                    data-has-rapp="{{ $hasRapp ? 'true' : 'false' }}">
                                {{ $project->name }} - {{ $project->client->name }} ({{ $project->code }})
                                @if($hasRapp) - Sudah memiliki RAPP @endif
                            </option>
                        @endforeach
                    </select>
                    @error('project_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Info: kode dan nama RAPP yang akan digenerate -->
                <div id="rappInfo" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hidden">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">RAPP yang akan dibuat:</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Kode RAPP:</span>
                            <div class="font-mono font-bold text-blue-600 dark:text-blue-400" id="previewCode">-</div>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Nama RAPP:</span>
                            <div class="font-bold text-green-600 dark:text-green-400" id="previewName">-</div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Deskripsi RAPP (Opsional)
                    </label>
                    <textarea name="description" id="description" rows="4"
                              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white resize-none"
                              placeholder="Deskripsi detail tentang RAPP ini...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Default Item (Optional) -->
                <div class="border-t border-gray-200 dark:border-gray-600 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Item Pekerjaan Awal (Opsional)</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Anda bisa menambahkan item pekerjaan pertama sekarang atau nanti setelah RAPP dibuat.
                    </p>

                    <div class="space-y-4">
                        <div>
                            <label for="default_item_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Deskripsi Item
                            </label>
                            <input type="text" name="default_item_description" id="default_item_description"
                                   class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                                   placeholder="Deskripsi pekerjaan utama">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="default_item_unit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Satuan
                                </label>
                                <select name="default_item_unit" id="default_item_unit"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                                    <option value="">-- Pilih Satuan --</option>
                                    @foreach(\App\Models\RappItem::getUnitOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="default_item_volume" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Volume
                                </label>
                                <input type="number" name="default_item_volume" id="default_item_volume" step="0.0001" min="0"
                                       class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                                       placeholder="0.0000">
                            </div>
                            <div>
                                <label for="default_item_unit_cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Harga Satuan (Rp)
                                </label>
                                <input type="number" name="default_item_unit_cost" id="default_item_unit_cost" step="0.01" min="0"
                                       class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                                       placeholder="0"
                                       oninput="formatCurrency(this)">
                                <div class="text-xs text-gray-500 mt-1" id="default_item_unit_cost_formatted"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-600">
                <a href="{{ route('dev.rapps.index') }}" 
                   class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Batal
                </a>
                <button type="submit" id="submitBtn"
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                    Buat RAPP
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Format currency input
function formatCurrency(input) {
    const value = parseFloat(input.value) || 0;
    const formatted = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
    
    document.getElementById('default_item_unit_cost_formatted').textContent = formatted;
}

// Update RAPP info preview
document.getElementById('project_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const projectCode = selectedOption.getAttribute('data-code');
    const projectName = selectedOption.getAttribute('data-name');
    const hasRapp = selectedOption.getAttribute('data-has-rapp') === 'true';
    
    const infoDiv = document.getElementById('rappInfo');
    const previewCode = document.getElementById('previewCode');
    const previewName = document.getElementById('previewName');
    const submitBtn = document.getElementById('submitBtn');
    
    if (projectCode && projectName) {
        // Generate kode dan nama RAPP
        const rappCode = 'RAB-BL-' + projectCode + '-' + new Date().toISOString().slice(0,10).replace(/-/g, '');
        const rappName = 'RAPP - ' + projectName;
        
        previewCode.textContent = rappCode;
        previewName.textContent = rappName;
        infoDiv.classList.remove('hidden');
        
        if (hasRapp) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            infoDiv.innerHTML = `
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded p-3">
                    <div class="flex items-center text-red-800 dark:text-red-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium">Project ini sudah memiliki RAPP. Pilih project lain.</span>
                    </div>
                </div>
            `;
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    } else {
        infoDiv.classList.add('hidden');
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Trigger change event to show initial state
    const projectSelect = document.getElementById('project_id');
    if (projectSelect.value) {
        projectSelect.dispatchEvent(new Event('change'));
    }
    
    // Format initial currency if exists
    const unitCostInput = document.getElementById('default_item_unit_cost');
    if (unitCostInput.value) {
        formatCurrency(unitCostInput);
    }
});
</script>
@endsection


