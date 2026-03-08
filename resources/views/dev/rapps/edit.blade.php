@extends('layouts.dev')

@section('title', 'Edit RAPP - ' . $rab->name)

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit RAPP</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Update informasi RAPP untuk project {{ $project->name }}</p>
    </div>

    <form action="{{ route('dev.rab-baseline.update', ['projectId' => $project->id, 'rabId' => $rab->id]) }}" method="POST" id="rabForm">
        @csrf
        @method('PUT')
        
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column - RAPP Information -->
                <div class="space-y-6">
                    <!-- Basic Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Informasi RAPP</h3>
                        <div class="space-y-4">
                            <!-- RAPP Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nama RAPP *
                                </label>
                                <input type="text" name="name" id="name" required
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white"
                                    placeholder="Masukkan nama RAPP"
                                    value="{{ old('name', $rab->name) }}">
                                @error('name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Version -->
                            <div>
                                <label for="version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Versi
                                </label>
                                <input type="text" name="version" id="version" readonly
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 bg-gray-50 dark:bg-gray-700 dark:text-white"
                                    value="{{ $rab->version }}">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Versi RAPP (auto-generated)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Project Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Informasi Project</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Nama Project</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $project->name }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Client</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $project->client->name }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Project Budget</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    Rp {{ number_format($project->budget, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Status, Notes -->
                <div class="space-y-6">
                    <!-- Status -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Status RAPP</h3>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Status Saat Ini</p>
                                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full mt-2
                                        @if($rab->status == 'approved') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                                        @elseif($rab->status == 'draft') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @elseif($rab->status == 'submitted') bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300
                                        @else bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300 @endif">
                                        {{ $rab->status_label }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Budget</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        Rp {{ number_format($rab->total_budget, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                            
                            @if(method_exists($rab,'canEdit') ? $rab->canEdit() : ($rab->status=='draft'))
                            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    RAPP dapat diedit. Anda masih dapat mengedit informasi dan items.
                                </p>
                            </div>
                            @else
                            <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                    RAPP tidak dapat diedit karena status: {{ $rab->status_label }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Catatan RAPP</h3>
                        <div>
                            <textarea name="notes" id="notes" rows="6"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white resize-none"
                                placeholder="Tambahkan catatan tentang RAPP ini (opsional)..."
                                {{ !(method_exists($rab,'canEdit') ? $rab->canEdit() : ($rab->status=='draft')) ? 'readonly' : '' }}>{{ old('notes', $rab->notes) }}</textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Catatan akan membantu memahami konteks dan tujuan RAPP ini.
                            </p>
                        </div>
                    </div>

                    <!-- RAPP Summary -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Ringkasan RAPP</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Items</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $rab->items_count }} items</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Dibuat</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $rab->created_at->format('d M Y') }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Terakhir Diupdate</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $rab->updated_at->format('d M Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div class="flex space-x-3">
                    <a href="{{ route('dev.rab-baseline.show', ['projectId' => $project->id, 'rabId' => $rab->id]) }}" 
                       class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Batal
                    </a>
                    
                    @if(method_exists($rab,'canEdit') ? $rab->canEdit() : ($rab->status=='draft'))
                    <a href="{{ route('dev.rab-baseline.show', ['projectId' => $project->id, 'rabId' => $rab->id]) }}" 
                       class="px-6 py-3 border border-blue-300 dark:border-blue-600 text-blue-700 dark:text-blue-300 rounded-lg font-medium hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                        Kelola Items
                    </a>
                    @endif
                </div>
                
                <div class="flex space-x-3">
                    @if(method_exists($rab,'canEdit') ? $rab->canEdit() : ($rab->status=='draft'))
                    <button type="submit" 
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                            onclick="return confirmUpdate()">
                        Update RAPP
                    </button>
                    @else
                    <button type="button" 
                            class="px-6 py-3 bg-gray-400 text-white rounded-lg font-medium cursor-not-allowed"
                            disabled>
                        Tidak Dapat Diupdate
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <!-- Items Management Section (Hanya untuk RAPP draft) -->
    @if(method_exists($rab,'canEdit') ? $rab->canEdit() : ($rab->status=='draft'))
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Items RAPP</h3>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Kelola items dalam RAPP ini</p>
            </div>
            <a href="{{ route('dev.rab-baseline.show', ['projectId' => $project->id, 'rabId' => $rab->id]) }}" 
               class="flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Kelola Items di Halaman Detail
            </a>
        </div>

        <!-- Quick Items Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
                <p class="text-sm text-blue-700 dark:text-blue-300 font-medium">Total Items</p>
                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $rab->items_count }}</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-700">
                <p class="text-sm text-green-700 dark:text-green-300 font-medium">Total Budget</p>
                <p class="text-xl font-bold text-green-900 dark:text-green-100">
                    Rp {{ number_format($rab->total_budget, 0, ',', '.') }}
                </p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-700">
                <p class="text-sm text-purple-700 dark:text-purple-300 font-medium">Status</p>
                <p class="text-lg font-bold text-purple-900 dark:text-purple-100">Ready to Edit</p>
            </div>
        </div>

        <div class="text-center py-8">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Kelola Items RAPP</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">
                Untuk menambah, mengedit, atau menghapus items, gunakan halaman detail RAPP.
            </p>
            <a href="{{ route('dev.rab-baseline.show', ['projectId' => $project->id, 'rabId' => $rab->id]) }}" 
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-2xl font-medium transition-all duration-300 transform hover:scale-105 hover:shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Buka Halaman Detail RAPP
            </a>
        </div>
    </div>
    @endif
</div>

<script>
function confirmUpdate() {
    const name = document.getElementById('name').value.trim();
    
    if (!name) {
        alert('Nama RAPP harus diisi');
        return false;
    }
    
    return confirm('Apakah Anda yakin ingin mengupdate RAPP ini?');
}

// Disable form jika RAPP tidak dalam status draft
document.addEventListener('DOMContentLoaded', function() {
    @if(!(method_exists($rab,'canEdit') ? $rab->canEdit() : ($rab->status=='draft')))
    const form = document.getElementById('rabForm');
    const inputs = form.querySelectorAll('input, textarea, select, button');
    
    inputs.forEach(input => {
        if (input.type !== 'hidden' && !input.hasAttribute('readonly')) {
            input.setAttribute('readonly', true);
            input.setAttribute('disabled', true);
        }
    });
    @endif
});
</script>
@endsection






