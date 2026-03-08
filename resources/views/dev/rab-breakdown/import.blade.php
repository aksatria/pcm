@extends('layouts.dev')

@section('title', 'Import RAB Breakdown dari Excel - ' . $project->name)

@section('content')
@php
    $isHO = auth()->check() && auth()->user() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO();
    $canEdit = method_exists($rabBreakdown, 'canEdit') ? $rabBreakdown->canEdit() : $isHO;
    $approvalKey = method_exists($rabBreakdown, 'approvalKey') ? $rabBreakdown->approvalKey() : strtolower((string) ($rabBreakdown->approval_status ?? 'draft'));
@endphp
@include('dev.rab-breakdown.partials.shared-theme')
<style>
    .import-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        position: relative;
    }
    
    .step-indicator::before {
        content: '';
        position: absolute;
        top: 16px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e5e7eb;
        z-index: 1;
    }
    
    .step {
        position: relative;
        z-index: 2;
        text-align: center;
        flex: 1;
    }
    
    .step-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
        border: 2px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        font-weight: 600;
        color: #6b7280;
        transition: all 0.3s ease;
    }
    
    .step.active .step-circle {
        background: #3b82f6;
        border-color: #3b82f6;
        color: white;
    }
    
    .step.completed .step-circle {
        background: #10b981;
        border-color: #10b981;
        color: white;
    }
    
    .step-label {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .step.active .step-label {
        color: #3b82f6;
        font-weight: 600;
    }
    
    .import-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .dark .import-card {
        background: #1f2937;
    }
    
    .file-upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        padding: 3rem 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background: #f9fafb;
    }
    
    .file-upload-area:hover {
        border-color: #3b82f6;
        background: #f0f9ff;
    }
    
    .file-upload-area.dragover {
        border-color: #10b981;
        background: #f0fdf4;
    }
    
    .file-upload-icon {
        width: 3rem;
        height: 3rem;
        color: #9ca3af;
        margin: 0 auto 1rem;
    }
    
    .template-download {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #3b82f6;
        font-weight: 500;
        text-decoration: none;
        margin-top: 1rem;
    }
    
    .template-download:hover {
        color: #2563eb;
        text-decoration: underline;
    }
    
    .validation-results {
        margin-top: 1.5rem;
    }
    
    .validation-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        border-radius: 0.375rem;
        margin-bottom: 0.5rem;
    }
    
    .validation-item.success {
        background: #f0fdf4;
        color: #065f46;
    }
    
    .validation-item.warning {
        background: #fffbeb;
        color: #92400e;
    }
    
    .validation-item.error {
        background: #fef2f2;
        color: #991b1b;
    }
    
    .data-preview-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    
    .data-preview-table th {
        background: #f3f4f6;
        padding: 0.75rem;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #4b5563;
    }
    
    .data-preview-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
        font-size: 0.875rem;
    }
    
    .data-preview-table tbody tr:hover {
        background: #f9fafb;
    }
    
    .mapping-row {
        background: #f0f9ff;
    }
    
    .dark .data-preview-table th {
        background: #374151;
        color: #d1d5db;
    }
    
    .dark .data-preview-table td {
        border-color: #374151;
        color: #e5e7eb;
    }
    
    .dark .data-preview-table tbody tr:hover {
        background: #374151;
    }
    
    .dark .mapping-row {
        background: #1e3a8a;
    }
    
    .import-summary {
        background: #f8fafc;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-top: 1.5rem;
    }
    
    .dark .import-summary {
        background: #374151;
    }
    
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .summary-item {
        text-align: center;
    }
    
    .summary-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    
    .summary-value {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
    }
    
    .dark .summary-value {
        color: #f9fafb;
    }
    
    .action-buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
    }
    
    .dark .action-buttons {
        border-color: #374151;
    }

</style>

<div class="import-container">
    <!-- Back Navigation -->
    <div class="mb-6">
        <a href="{{ route('dev.rab-breakdown.show', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}" 
           class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke RAB Breakdown Detail
        </a>
    </div>
    
    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step active" id="step1">
            <div class="step-circle">1</div>
            <div class="step-label">Upload File</div>
        </div>
        <div class="step" id="step2">
            <div class="step-circle">2</div>
            <div class="step-label">Validasi Data</div>
        </div>
        <div class="step" id="step3">
            <div class="step-circle">3</div>
            <div class="step-label">Preview & Konfirmasi</div>
        </div>
        <div class="step" id="step4">
            <div class="step-circle">4</div>
            <div class="step-label">Proses Import</div>
        </div>
    </div>
    
    <!-- Import Form -->
    <div class="import-card">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Import Item RAB Breakdown dari Excel</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            Upload file Excel yang berisi daftar item untuk RAB Breakdown "{{ $rabBreakdown->name }}"
        </p>
        @include('dev.rab-breakdown.partials.status-chips', ['approvalKey' => $approvalKey, 'canEdit' => $canEdit])
        
        <form id="importForm" action="{{ route('dev.rab-breakdown.import', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}" 
              method="POST" enctype="multipart/form-data">
            @csrf
            
            <!-- Step 1: File Upload -->
            <div id="step1-content">
                <div class="file-upload-area" id="fileUploadArea">
                    <svg class="file-upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Upload File Excel</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Klik untuk memilih file atau drag & drop file Excel di sini
                    </p>
                    <input type="file" 
                           id="excelFile" 
                           name="file" 
                           accept=".xlsx,.xls,.csv" 
                           class="hidden" 
                           required>
                    <button type="button" 
                            onclick="document.getElementById('excelFile').click()"
                            class="rb-btn-primary rb-btn-sm">
                        Pilih File Excel
                    </button>
                    <div id="fileName" class="mt-2 text-sm text-gray-500"></div>
                </div>
                
                <a href="{{ route('dev.rab-breakdown.download-template', ['projectId' => $project->id]) }}" class="template-download">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download Template Excel
                </a>
                
                <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-800 dark:text-blue-300 mb-2">Format File Excel:</h4>
                    <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
                        <li>- Kolom A: <code>item_code</code> (contoh: A.1, B.1, C.1)</li>
                        <li>- Kolom B: <code>uraian</code> (deskripsi item)</li>
                        <li>- Kolom C: <code>volume_rab</code> (angka, contoh: 16.5)</li>
                        <li>- Kolom D: <code>satuan</code> (contoh: OH, m3, Rit, unit)</li>
                        <li>- Kolom E: <code>unit_price</code> (harga satuan, contoh: 150000)</li>
                        <li>- Kolom F: <code>master_kode</code> (opsional, contoh: JS-001, MT-133)</li>
                        <li>- Kolom G: <code>p</code> (opsional)</li>
                        <li>- Kolom H: <code>l</code> (opsional)</li>
                        <li>- Kolom I: <code>t</code> (opsional)</li>
                        <li>- Kolom J: <code>n</code> (opsional)</li>
                        <li>- Kolom K: <code>n_tul_1</code> (opsional)</li>
                        <li>- Kolom L: <code>n_tul_2</code> (opsional)</li>
                        <li>- Kolom M: <code>jarak</code> (opsional)</li>
                        <li>- Kolom N: <code>dia_1</code> (opsional)</li>
                        <li>- Kolom O: <code>dia_2</code> (opsional)</li>
                        <li>- Kolom P: <code>dia_3</code> (opsional)</li>
                        <li>- Kolom Q: <code>berat_1</code> (opsional)</li>
                        <li>- Kolom R: <code>berat_2</code> (opsional)</li>
                        <li>- Kolom S: <code>notes</code> (opsional, catatan tambahan)</li>
                    </ul>
                </div>
            </div>
            
            <!-- Step 2: Validation Results -->
            <div id="step2-content" class="hidden">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Hasil Validasi Data</h3>
                <div id="validationResults" class="validation-results">
                    <!-- Validation results will be loaded here -->
                </div>
                <div id="validationStats" class="import-summary hidden">
                    <!-- Validation stats will be loaded here -->
                </div>
            </div>
            
            <!-- Step 3: Data Preview -->
            <div id="step3-content" class="hidden">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Preview Data</h3>
                <div id="dataPreview" class="overflow-x-auto">
                    <!-- Data preview table will be loaded here -->
                </div>
                <div id="previewSummary" class="import-summary hidden">
                    <!-- Import summary will be loaded here -->
                </div>
                
                <div class="mt-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="autoMapBudget" name="auto_map_budget" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Auto-mapping ke Master Data berdasarkan kode
                        </span>
                    </label>
                    <label class="flex items-center mt-2">
                        <input type="checkbox" id="updateExisting" name="update_existing" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Update items yang sudah ada (berdasarkan item_code)
                        </span>
                    </label>
                </div>
            </div>
            
            <!-- Step 4: Processing -->
            <div id="step4-content" class="hidden">
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500 mb-4"></div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Memproses Import Data</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">Mohon tunggu, data sedang diproses...</p>
                    <div id="progressBar" class="h-2 bg-gray-200 rounded-full overflow-hidden max-w-md mx-auto">
                        <div id="progressFill" class="h-full bg-blue-600 transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <div id="progressText" class="text-sm text-gray-500 mt-2">0% complete</div>
                </div>
            </div>
            
            <!-- Hidden fields -->
            <input type="hidden" id="validationData" name="validation_data">
            <input type="hidden" id="previewData" name="preview_data">
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button type="button" 
                        id="prevBtn" 
                        class="rb-btn-outline rb-btn-sm hidden">
                    Kembali
                </button>
                
                <div class="flex gap-2">
                    <button type="button" 
                            onclick="cancelImport()"
                            class="rb-btn-outline rb-btn-sm">
                        Batalkan
                    </button>
                    
                    <button type="button" 
                            id="nextBtn" 
                            class="rb-btn-primary rb-btn-sm"
                            onclick="nextStep()">
                        Selanjutnya
                    </button>
                    
                    <button type="submit" 
                            id="submitBtn" 
                            class="rb-btn-success rb-btn-sm hidden">
                        Proses Import
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Instructions -->
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <h4 class="font-semibold text-yellow-800 dark:text-yellow-300 mb-2">Tips Import Data:</h4>
        <ul class="text-sm text-yellow-700 dark:text-yellow-400 space-y-1">
            <li>- Pastikan format file sesuai dengan template yang disediakan</li>
            <li>- Kolom <code>item_code</code> harus unik untuk setiap item</li>
            <li>- Kolom <code>master_kode</code> akan di-mapping otomatis ke Master Data</li>
            <li>- Hanya file dengan ekstensi .xlsx, .xls, atau .csv yang didukung</li>
            <li>- Maksimum ukuran file: 10MB</li>
        </ul>
    </div>
</div>

<script>
    let currentStep = 1;
    let excelData = null;
    let validationResults = null;
    let previewData = null;
    
    // DOM Elements
    const steps = document.querySelectorAll('.step');
    const stepContents = [
        document.getElementById('step1-content'),
        document.getElementById('step2-content'),
        document.getElementById('step3-content'),
        document.getElementById('step4-content')
    ];
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const fileUploadArea = document.getElementById('fileUploadArea');
    const excelFileInput = document.getElementById('excelFile');
    const fileNameDisplay = document.getElementById('fileName');
    const validationDataField = document.getElementById('validationData');
    const previewDataField = document.getElementById('previewData');
    
    // File upload drag & drop
    fileUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        fileUploadArea.classList.add('dragover');
    });
    
    fileUploadArea.addEventListener('dragleave', () => {
        fileUploadArea.classList.remove('dragover');
    });
    
    fileUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        fileUploadArea.classList.remove('dragover');
        
        if (e.dataTransfer.files.length) {
            excelFileInput.files = e.dataTransfer.files;
            updateFileName();
        }
    });
    
    excelFileInput.addEventListener('change', updateFileName);
    
    function updateFileName() {
        if (excelFileInput.files.length > 0) {
            const file = excelFileInput.files[0];
            fileNameDisplay.textContent = `File terpilih: ${file.name} (${formatFileSize(file.size)})`;
            fileNameDisplay.className = 'mt-2 text-sm text-green-600 font-medium';
        } else {
            fileNameDisplay.textContent = '';
        }
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Step Navigation
    function updateStepDisplay() {
        // Update step indicators
        steps.forEach((step, index) => {
            step.classList.remove('active', 'completed');
            if (index + 1 < currentStep) {
                step.classList.add('completed');
            } else if (index + 1 === currentStep) {
                step.classList.add('active');
            }
        });
        
        // Update content visibility
        stepContents.forEach((content, index) => {
            if (index + 1 === currentStep) {
                content.classList.remove('hidden');
            } else {
                content.classList.add('hidden');
            }
        });
        
        // Update buttons
        prevBtn.classList.toggle('hidden', currentStep === 1);
        nextBtn.classList.toggle('hidden', currentStep === 4);
        submitBtn.classList.toggle('hidden', currentStep !== 3);
        
        // Update next button text
        if (currentStep === 1) {
            nextBtn.textContent = 'Validasi Data';
        } else if (currentStep === 2) {
            nextBtn.textContent = 'Preview Data';
        } else if (currentStep === 3) {
            nextBtn.textContent = 'Proses Import';
        }
    }
    
    function nextStep() {
        if (currentStep === 1) {
            if (!validateFileUpload()) return;
            validateData();
        } else if (currentStep === 2) {
            if (!validationResults || !validationResults.success) {
                alert('Harap perbaiki data terlebih dahulu sebelum melanjutkan.');
                return;
            }
            showDataPreview();
        } else if (currentStep === 3) {
            currentStep = 4;
            updateStepDisplay();
            simulateImportProcess();
            return;
        }
        
        currentStep++;
        updateStepDisplay();
    }
    
    function prevStep() {
        currentStep--;
        updateStepDisplay();
    }
    
    // File validation
    function validateFileUpload() {
        if (!excelFileInput.files.length) {
            alert('Pilih file Excel terlebih dahulu.');
            return false;
        }
        
        const file = excelFileInput.files[0];
        const validExtensions = ['.xlsx', '.xls', '.csv'];
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        
        if (!validExtensions.includes(fileExtension)) {
            alert('Format file tidak didukung. Gunakan file Excel (.xlsx, .xls) atau CSV.');
            return false;
        }
        
        if (file.size > 10 * 1024 * 1024) { // 10MB
            alert('Ukuran file terlalu besar. Maksimum 10MB.');
            return false;
        }
        
        return true;
    }
    
    // Data validation via AJAX
    function validateData() {
        const formData = new FormData();
        formData.append('file', excelFileInput.files[0]);
        formData.append('rab_breakdown_id', '{{ $rabBreakdown->id }}');
        formData.append('_token', '{{ csrf_token() }}');
        
        nextBtn.disabled = true;
        nextBtn.innerHTML = '<svg class="w-4 h-4 animate-spin mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memvalidasi...';
        
        fetch('{{ route("dev.rab-breakdown.validate-import", ["projectId" => $project->id, "rabBreakdown" => $rabBreakdown->id]) }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            validationResults = data;
            showValidationResults(data);
        })
        .catch(error => {
            console.error('Validation error:', error);
            alert('Terjadi kesalahan saat memvalidasi data.');
        })
        .finally(() => {
            nextBtn.disabled = false;
            nextBtn.textContent = 'Preview Data';
        });
    }
    
    function showValidationResults(data) {
        const container = document.getElementById('validationResults');
        const statsContainer = document.getElementById('validationStats');
        
        if (!data.success) {
            container.innerHTML = `
                <div class="validation-item error">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>${data.message}</span>
                </div>
            `;
            statsContainer.classList.add('hidden');
            return;
        }
        
        let html = '';
        let successCount = 0;
        let warningCount = 0;
        let errorCount = 0;
        
        data.validations.forEach(validation => {
            let icon = '';
            let className = '';
            
            switch (validation.type) {
                case 'success':
                    icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                    className = 'success';
                    successCount++;
                    break;
                case 'warning':
                    icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
                    className = 'warning';
                    warningCount++;
                    break;
                case 'error':
                    icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                    className = 'error';
                    errorCount++;
                    break;
            }
            
            html += `
                <div class="validation-item ${className}">
                    ${icon}
                    <span>${validation.message}</span>
                </div>
            `;
        });
        
        container.innerHTML = html;
        
        // Show stats
        statsContainer.classList.remove('hidden');
        statsContainer.innerHTML = `
            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Statistik Validasi:</h4>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Total Baris</div>
                    <div class="summary-value">${data.total_rows}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Valid</div>
                    <div class="summary-value text-green-600">${successCount}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Warning</div>
                    <div class="summary-value text-yellow-600">${warningCount}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Error</div>
                    <div class="summary-value text-red-600">${errorCount}</div>
                </div>
            </div>
        `;
        
        // Store validation data
        validationDataField.value = JSON.stringify(data);
    }
    
    function showDataPreview() {
        if (!validationResults || !validationResults.data) {
            alert('Tidak ada data untuk dipreview.');
            return;
        }
        
        previewData = validationResults.data;
        const container = document.getElementById('dataPreview');
        const summaryContainer = document.getElementById('previewSummary');
        
        if (previewData.length === 0) {
            container.innerHTML = '<p class="text-gray-500 italic">Tidak ada data yang valid untuk dipreview.</p>';
            summaryContainer.classList.add('hidden');
            return;
        }
        
        // Create table
        let tableHtml = `
            <table class="data-preview-table">
                <thead>
                    <tr>
                        <th>item_code</th>
                        <th>uraian</th>
                        <th>volume_rab</th>
                        <th>satuan</th>
                        <th>unit_price</th>
                        <th>master_kode</th>
                        <th>P</th>
                        <th>L</th>
                        <th>T</th>
                        <th>N</th>
                        <th>N TUL 1</th>
                        <th>N TUL 2</th>
                        <th>JARAK</th>
                        <th>DIA 1</th>
                        <th>DIA 2</th>
                        <th>DIA 3</th>
                        <th>BERAT 1</th>
                        <th>BERAT 2</th>
                        <th>total_price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        let totalBudget = 0;
        
        previewData.forEach((row, index) => {
            const totalPrice = row.volume_rab * row.unit_price;
            totalBudget += totalPrice;
            
            const statusClass = row.validation_status === 'error' ? 'text-red-600' : 
                               row.validation_status === 'warning' ? 'text-yellow-600' : 'text-green-600';
            const statusText = row.validation_status === 'error' ? 'Error' : 
                              row.validation_status === 'warning' ? 'Warning' : 'Valid';
            
            tableHtml += `
                <tr class="${row.validation_status === 'warning' ? 'mapping-row' : ''}">
                    <td>${row.item_code}</td>
                    <td>${row.uraian.substring(0, 50)}${row.uraian.length > 50 ? '...' : ''}</td>
                    <td class="text-right">${row.volume_rab}</td>
                    <td>${row.satuan}</td>
                    <td class="text-right">Rp ${formatNumber(row.unit_price)}</td>
                    <td>${row.master_kode || '-'}</td>
                    <td class="text-right">${row.p ?? '-'}</td>
                    <td class="text-right">${row.l ?? '-'}</td>
                    <td class="text-right">${row.t ?? '-'}</td>
                    <td class="text-right">${row.n ?? '-'}</td>
                    <td class="text-right">${row.n_tul_1 ?? '-'}</td>
                    <td class="text-right">${row.n_tul_2 ?? '-'}</td>
                    <td class="text-right">${row.jarak ?? '-'}</td>
                    <td class="text-right">${row.dia_1 ?? '-'}</td>
                    <td class="text-right">${row.dia_2 ?? '-'}</td>
                    <td class="text-right">${row.dia_3 ?? '-'}</td>
                    <td class="text-right">${row.berat_1 ?? '-'}</td>
                    <td class="text-right">${row.berat_2 ?? '-'}</td>
                    <td class="text-right font-semibold">Rp ${formatNumber(totalPrice)}</td>
                    <td class="${statusClass}">${statusText}</td>
                </tr>
            `;
        });
        
        tableHtml += '</tbody></table>';
        container.innerHTML = tableHtml;
        
        // Show summary
        summaryContainer.classList.remove('hidden');
        summaryContainer.innerHTML = `
            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Ringkasan Import:</h4>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Total Items</div>
                    <div class="summary-value">${previewData.length}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Budget</div>
                    <div class="summary-value">Rp ${formatNumber(totalBudget)}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Items dengan Kode</div>
                    <div class="summary-value">${previewData.filter(row => row.master_kode).length}</div>
                </div>
            </div>
        `;
        
        // Store preview data
        previewDataField.value = JSON.stringify(previewData);
    }
    
    function simulateImportProcess() {
        // This would typically be handled by the form submission
        // Here we just simulate progress
        let progress = 0;
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        const interval = setInterval(() => {
            progress += 10;
            if (progress > 90) {
                clearInterval(interval);
                progress = 90; // Hold at 90% until form submits
            }
            progressFill.style.width = progress + '%';
            progressText.textContent = progress + '% complete';
        }, 500);
        
        // Submit the form after a short delay
        setTimeout(() => {
            document.getElementById('importForm').submit();
        }, 3000);
    }
    
    function cancelImport() {
        if (confirm('Batalkan proses import? Data yang sudah diupload akan hilang.')) {
            window.location.href = '{{ route("dev.rab-breakdown.show", ["projectId" => $project->id, "rabBreakdown" => $rabBreakdown->id]) }}';
        }
    }
    
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    // Event Listeners
    prevBtn.addEventListener('click', prevStep);
    
    // Initialize
    updateStepDisplay();
</script>
@endsection





