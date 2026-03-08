@extends('layouts.dev')

@section('title', 'Edit RAPP - ' . $rapp->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit RAPP</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $rapp->name }}</p>
                <div class="flex items-center space-x-2 mt-2 text-sm text-gray-500 dark:text-gray-400">
                    <span>Kode: {{ $rapp->code }}</span>
                    <span>•</span>
                    <span>Project: {{ $rapp->project->name }}</span>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('dev.rapps.show', $rapp) }}" 
                   class="flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali
                </a>
            </div>
        </div>
    </div>

    @if(!$rapp->canEdit())
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
        <div class="flex">
            <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    RAPP tidak dapat diedit
                </h3>
                <div class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                    Status RAPP saat ini: <span class="font-semibold">{{ $rapp->status_label }}</span>. 
                    Hanya RAPP dengan status Draft atau Rejected yang dapat diedit.
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
        <form action="{{ route('dev.rapps.update', $rapp) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <!-- Read-only Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Kode RAPP
                        </label>
                        <p class="text-sm text-gray-900 dark:text-white font-medium">{{ $rapp->code }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Nama RAPP
                        </label>
                        <p class="text-sm text-gray-900 dark:text-white font-medium">{{ $rapp->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            ? Nama RAPP digenerate otomatis dari nama project
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Project
                        </label>
                        <p class="text-sm text-gray-900 dark:text-white font-medium">{{ $rapp->project->name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Status
                        </label>
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium 
                            @if($rapp->status == 'draft') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                            @elseif($rapp->status == 'submitted') bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300
                            @elseif($rapp->status == 'approved') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                            @else bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300 @endif">
                            {{ $rapp->status_label }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Total Estimasi
                        </label>
                        <p class="text-sm text-gray-900 dark:text-white font-medium">{{ $rapp->formatted_total_estimate }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Dibuat Oleh
                        </label>
                        <p class="text-sm text-gray-900 dark:text-white font-medium">{{ $rapp->creator->name ?? 'Unknown User' }}</p>
                    </div>
                </div>

                <!-- Description -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-600">
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Deskripsi RAPP
                    </label>
                    <textarea name="description" id="description" rows="4"
                              class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white resize-none"
                              placeholder="Deskripsi detail tentang RAPP ini..."
                              {{ !$rapp->canEdit() ? 'disabled' : '' }}>{{ old('description', $rapp->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Items Summary -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Ringkasan Item Pekerjaan</h3>
                    
                    @if(count($hierarchicalItems) > 0)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="space-y-2">
                                @foreach($hierarchicalItems as $item)
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600 last:border-b-0">
                                        <div>
                                            <span class="font-mono text-sm text-blue-600 dark:text-blue-400">{{ $item->job_code }}</span>
                                            <span class="text-sm text-gray-700 dark:text-gray-300 ml-2">{{ $item->description }}</span>
                                        </div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $item->formatted_total_estimate }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">Belum ada item pekerjaan</p>
                        </div>
                    @endif
                    
                    <div class="mt-4 text-right">
                        <a href="{{ route('dev.rapps.show', $rapp) }}" 
                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                            Kelola Item Pekerjaan ?
                        </a>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            @if($rapp->canEdit())
            <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-600">
                <a href="{{ route('dev.rapps.show', $rapp) }}" 
                   class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Batal
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                    Update RAPP
                </button>
            </div>
            @endif
        </form>
    </div>
</div>
@endsection



