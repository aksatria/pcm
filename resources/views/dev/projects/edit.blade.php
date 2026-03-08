@extends('layouts.dev')

@section('title', 'Edit Project - ' . $project->name)

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Edit Project</h1>
        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Update informasi project</p>
    </div>

    <form action="{{ route('dev.projects.update', $project->id) }}" method="POST" id="projectForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left Column - Project Information -->
                <div class="space-y-4">
                    <!-- Basic Information -->
                    <div>
                        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">Informasi Project</h3>
                        <div class="space-y-3">
                            <!-- Project Code -->
                            <div>
                                <label for="code" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Kode Project
                                </label>
                                <input type="text" name="code" id="code" required readonly
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-700 dark:text-white text-sm"
                                    value="{{ $project->code }}">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kode project</p>
                            </div>

                            <!-- Project Name -->
                            <div>
                                <label for="name" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nama Project *
                                </label>
                                <input type="text" name="name" id="name" required
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white text-sm"
                                    placeholder="Masukkan nama project"
                                    value="{{ old('name', $project->name) }}">
                                @error('name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Client Selection -->
                            <div>
                                <label for="client_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Client *
                                </label>
                                <select name="client_id" id="client_id" required
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white text-sm">
                                    <option value="">Pilih Client</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ old('client_id', $project->client_id) == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }} - {{ $client->company }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Location -->
                            <div>
                                <label for="location" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Lokasi *
                                </label>
                                <input type="text" name="location" id="location" required
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white text-sm"
                                    placeholder="Lokasi project"
                                    value="{{ old('location', $project->location) }}">
                                @error('location')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Province -->
                            <div>
                                <label for="province_id" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Provinsi
                                </label>
                                <select name="province_id" id="province_id"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white text-sm">
                                    <option value="">Pilih Provinsi</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province->id }}" {{ old('province_id', $project->province_id) == $province->id ? 'selected' : '' }}>
                                            {{ $province->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('province_id')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- PIC -->
                            <div>
                                <label for="pic" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    PIC (Penanggung Jawab)
                                </label>
                                <input type="text" name="pic" id="pic"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white text-sm"
                                    placeholder="Nama penanggung jawab"
                                    value="{{ old('pic', $project->pic) }}">
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div>
                        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">Timeline</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <!-- Start Date -->
                            <div>
                                <label for="start_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Tanggal Mulai *
                                </label>
                                <input type="date" name="start_date" id="start_date" required
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white text-sm"
                                    value="{{ old('start_date', $project->start_date->format('Y-m-d')) }}">
                                @error('start_date')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- End Date -->
                            <div>
                                <label for="end_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Tanggal Selesai
                                </label>
                                <input type="date" name="end_date" id="end_date"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white text-sm"
                                    value="{{ old('end_date', $project->end_date ? $project->end_date->format('Y-m-d') : '') }}">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" id="duration-display">
                                    @if($project->start_date && $project->end_date)
                                        @php $diffDays = $project->start_date->diffInDays($project->end_date); @endphp
                                        Durasi: {{ $diffDays }} hari
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Status, Budget, Files, Description -->
                <div class="space-y-4">
                    <!-- Status & Budget -->
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Status -->
                        <div>
                            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">Status Project</h3>
                            <select name="status" id="status"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white text-sm">
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ old('status', $project->status) == $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Budget -->
                        <div>
                            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">Budget Project</h3>
                            <div>
                                <label for="budget" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Jumlah Budget (IDR)
                                </label>
                                <input type="text" name="budget" id="budget"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white text-sm"
                                    value="{{ old('budget', $project->budget ? number_format($project->budget, 0, ',', '.') : '0') }}"
                                    placeholder="0"
                                    oninput="formatCurrency(this)">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" id="budget-preview">
                                    Rp {{ $project->budget ? number_format($project->budget, 0, ',', '.') : '0' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- File Upload - Tambah File Baru -->
                    <div>
                        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">Tambah Dokumen Baru</h3>
                        <div class="space-y-3">
                            <!-- File Upload Area -->
                            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center" id="drop-area">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <div class="mt-3">
                                    <label for="files" class="cursor-pointer">
                                        <span class="text-blue-600 hover:text-blue-500 font-medium text-sm">Upload file baru</span>
                                        <span class="text-gray-600 dark:text-gray-400 text-xs"> atau drag and drop</span>
                                    </label>
                                    <input type="file" name="files[]" id="files" multiple
                                        class="hidden"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        PDF, DOC, XLS, JPG, PNG (Max 10MB per file)
                                    </p>
                                </div>
                            </div>

                            <!-- File List Preview -->
                            <div id="file-list" class="space-y-2 hidden">
                                <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300">File baru yang akan diupload:</h4>
                                <div id="file-items" class="space-y-2"></div>
                            </div>

                            <!-- File Type Selection -->
                            <div id="file-type-container">
                                <!-- Dynamic file type selection akan diisi oleh JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Existing Files -->
                    @if($project->files->count() > 0)
                    <div>
                        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">Dokumen Terlampir</h3>
                        <div class="space-y-2">
                            @foreach($project->files as $file)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <span class="text-base">{{ $file->file_icon }}</span>
                                    <div>
                                        <p class="text-xs font-medium text-gray-900 dark:text-white truncate max-w-xs">
                                            {{ $file->original_name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ number_format($file->file_size / 1024, 1) }} KB •
                                            {{ ucfirst(str_replace('_', ' ', $file->file_type)) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-1">
                                    <a href="{{ $file->url }}" target="_blank"
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1"
                                       title="Download">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                    </a>

                                    <!-- FIX: jangan pakai form di dalam form. -->
                                    <button type="button"
                                            class="text-red-500 hover:text-red-700 p-1"
                                            title="Hapus"
                                            onclick="return submitDeleteFile('{{ $file->id }}', @json($file->original_name));">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Description -->
                    <div>
                        <h3 class="text-base font-medium text-gray-900 dark:text-white mb-3">Deskripsi Project</h3>
                        <div>
                            <textarea name="description" id="description" rows="4"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700 dark:text-white resize-none text-sm"
                                placeholder="Deskripsi lengkap tentang project, tujuan, scope kerja, dan informasi lainnya...">{{ old('description', $project->description) }}</textarea>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Opsional</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-2">
                <a href="{{ route('dev.projects.show', $project->id) }}"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors"
                        onclick="return confirmUpdate()">
                    Update Project
                </button>
            </div>
        </div>
    </form>

    <!-- FIX: Form delete file dipindah ke luar form update (no nested form) -->
    @if($project->files->count() > 0)
        @foreach($project->files as $file)
            <form id="delete-file-form-{{ $file->id }}" action="{{ route('dev.projects.deleteFile', $file) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif
</div>

<script>
// Currency formatting
function formatCurrency(input) {
    let value = input.value.replace(/[^\d]/g, '');
    let number = parseInt(value || 0);
    let formatted = new Intl.NumberFormat('id-ID').format(number);
    input.value = formatted;
    document.getElementById('budget-preview').textContent = 'Rp ' + formatted;
}

// Calculate project duration
function calculateDuration() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const display = document.getElementById('duration-display');

    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        display.textContent = `Durasi: ${diffDays} hari`;
    } else {
        display.textContent = '';
    }
}

// Dynamic file type selection
function updateFileTypeSelection(files) {
    const container = document.getElementById('file-type-container');
    container.innerHTML = '';

    if (files.length > 0) {
        container.innerHTML = '<h4 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Jenis Dokumen untuk File Baru:</h4>';

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileTypeDiv = document.createElement('div');
            fileTypeDiv.className = 'mb-2';
            fileTypeDiv.innerHTML = `
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                    Untuk file: <span class="font-mono">${file.name}</span>
                </label>
                <select name="file_types[${i}]" class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 text-xs dark:bg-gray-700 dark:text-white">
                    <option value="">Pilih jenis dokumen</option>
                    <option value="surat_perintah">Surat Perintah</option>
                    <option value="kontrak">Kontrak</option>
                    <option value="proposal">Proposal</option>
                    <option value="laporan">Laporan</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            `;
            container.appendChild(fileTypeDiv);
        }
    }
}

// File upload handling
document.getElementById('files').addEventListener('change', function(e) {
    const fileList = document.getElementById('file-list');
    const fileItems = document.getElementById('file-items');
    const files = e.target.files;

    fileItems.innerHTML = '';

    if (files.length > 0) {
        fileList.classList.remove('hidden');
        updateFileTypeSelection(files);

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg px-2 py-1';
            fileItem.innerHTML = `
                <div class="flex items-center space-x-2">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-xs text-gray-700 dark:text-gray-300">${file.name}</span>
                    <span class="text-xs text-gray-500">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                </div>
                <button type="button" onclick="removeFile(${i})" class="text-red-500 hover:text-red-700">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `;
            fileItems.appendChild(fileItem);
        }
    } else {
        fileList.classList.add('hidden');
        document.getElementById('file-type-container').innerHTML = '';
    }
});

// Remove file from selection
function removeFile(index) {
    const input = document.getElementById('files');
    const files = Array.from(input.files);
    files.splice(index, 1);

    const dt = new DataTransfer();
    files.forEach(file => dt.items.add(file));
    input.files = dt.files;

    input.dispatchEvent(new Event('change'));
}

// Drag and drop functionality
const dropArea = document.getElementById('drop-area');
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropArea.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, unhighlight, false);
});

function highlight() {
    dropArea.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
}

function unhighlight() {
    dropArea.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
}

dropArea.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    document.getElementById('files').files = files;
    document.getElementById('files').dispatchEvent(new Event('change'));
}

// Form submission handler dengan validasi
function confirmUpdate() {
    const budgetInput = document.getElementById('budget');
    if (budgetInput) {
        budgetInput.value = budgetInput.value.replace(/[^\d]/g, '') || '0';
    }

    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    if (endDate && endDate < startDate) {
        alert('Tanggal selesai tidak boleh sebelum tanggal mulai');
        return false;
    }

    return confirm('Apakah Anda yakin ingin mengupdate project ini?');
}

// FIX: submit delete file form yang ada di luar form update
function submitDeleteFile(fileId, originalName) {
    if (!confirm(`Hapus file ${originalName}?`)) return false;
    const form = document.getElementById(`delete-file-form-${fileId}`);
    if (!form) return false;
    form.submit();
    return false;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const budgetEl = document.getElementById('budget');
    if (budgetEl) formatCurrency(budgetEl);

    document.getElementById('start_date').addEventListener('change', calculateDuration);
    document.getElementById('end_date').addEventListener('change', calculateDuration);
});
</script>
@endsection

