@extends('layouts.dev')

@section('title', 'Tambah RAB Breakdown - ' . $project->name)

@section('content')
<style>
    .form-container {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .form-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 2rem;
    }
    
    .dark .form-card {
        background: #1f2937;
    }
    
    .rab-breakdown-templates {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .dark .rab-breakdown-templates {
        background: #1e3a8a;
        border-color: #3b82f6;
    }
    
    .template-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }
    
    .template-card {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .template-card:hover {
        border-color: #3b82f6;
        transform: translateY(-2px);
    }
    
    .template-card.selected {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    
    .dark .template-card {
        background: #374151;
        border-color: #4b5563;
    }
    
    .dark .template-card.selected {
        background: #1e40af;
        border-color: #3b82f6;
    }
    
    .template-code {
        font-family: 'SF Mono', monospace;
        font-weight: 700;
        color: #3b82f6;
        margin-bottom: 0.25rem;
    }
    
    .template-name {
        font-size: 0.875rem;
        color: #4b5563;
    }
    
    .dark .template-name {
        color: #d1d5db;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.375rem;
    }
    
    .dark .form-label {
        color: #d1d5db;
    }
    
    .form-input {
        width: 100%;
        padding: 0.625rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        background: white;
        color: #374151;
    }
    
    .dark .form-input {
        background: #374151;
        border-color: #4b5563;
        color: white;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .date-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .counter-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #e5e7eb;
        color: #4b5563;
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .dark .counter-badge {
        background: #374151;
        color: #d1d5db;
    }
</style>

<div class="form-container">
    <!-- Back Navigation -->
    <div class="mb-6">
        <a href="{{ route('dev.rab-breakdown.index', $project->id) }}" 
           class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar RAB Breakdown
        </a>
    </div>
    
    <div class="form-card">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Tambah Work Breakdown Structure</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Project: <span class="font-semibold">{{ $project->name }}</span>
                </p>
            </div>
            <div class="counter-badge">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                {{ count($availableRabBreakdowns) }} template tersedia
            </div>
        </div>
        
        <!-- RAB Breakdown Templates Section -->
        @if(count($availableRabBreakdowns) > 0)
        <div class="rab-breakdown-templates">
            <h3 class="font-semibold text-blue-800 dark:text-blue-300 mb-2">
                Pilih Template RAB Breakdown Standar
            </h3>
            <p class="text-sm text-blue-700 dark:text-blue-400 mb-3">
                Pilih template standar untuk memulai. Anda dapat mengkustomisasi detailnya setelahnya.
            </p>
            
            <div class="template-grid" id="templateContainer">
                @foreach($availableRabBreakdowns as $template)
                <div class="template-card" 
                     data-code="{{ $template['code'] }}"
                     data-name="{{ $template['name'] }}"
                     onclick="selectTemplate(this)">
                    <div class="template-code">{{ $template['code'] }}</div>
                    <div class="template-name">{{ $template['name'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">Order: {{ $template['order'] }}</div>
                </div>
                @endforeach
            </div>
            
            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">Tips:</span> Template yang sudah digunakan tidak akan muncul di sini.
            </div>
        </div>
        @else
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <span class="font-medium text-yellow-800 dark:text-yellow-300">
                    Semua template standar sudah digunakan
                </span>
            </div>
            <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-400">
                Anda telah membuat semua template RAB Breakdown standar untuk project ini. Anda masih dapat membuat breakdown kustom.
            </p>
        </div>
        @endif
        
        <form action="{{ route('dev.rab-breakdown.store', $project->id) }}" method="POST" id="rabBreakdownForm">
            @csrf
            
            <!-- Hidden fields for template selection -->
            <input type="hidden" name="rab_breakdown_code" id="rab_breakdown_code" required>
            
            <div class="space-y-6">
                <!-- RAB Breakdown Code Display -->
                <div class="form-group">
                    <label class="form-label">
                        Kode RAB Breakdown <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="text" 
                               id="rab_breakdown_code_display"
                               class="form-input font-mono font-bold text-blue-600 bg-gray-50"
                               placeholder="Pilih template atau masukkan kode kustom"
                               readonly
                               required>
                        <button type="button"
                                onclick="enableCustomCode()"
                                class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg">
                            Kustom
                        </button>
                    </div>
                    <div id="customCodeContainer" class="hidden mt-2">
                        <input type="text" 
                               name="custom_rab_breakdown_code"
                               id="custom_rab_breakdown_code"
                               class="form-input"
                               placeholder="Contoh: RAB-012, RAB-CUSTOM"
                               oninput="updateCustomCode()">
                    </div>
                </div>
                
                <!-- RAB Breakdown Name -->
                <div class="form-group">
                    <label class="form-label">
                        Nama RAB Breakdown <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="rab_breakdown_name"
                           class="form-input"
                           placeholder="Nama deskriptif untuk RAB Breakdown"
                           required
                           maxlength="255">
                </div>
                
                <!-- Description -->
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" 
                              id="rab_breakdown_description"
                              class="form-input"
                              rows="3"
                              placeholder="Deskripsi detail tentang RAB Breakdown ini"></textarea>
                </div>
                
                <!-- Dates -->
                <div class="date-grid">
                    <div class="form-group">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" 
                               name="start_date" 
                               class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" 
                               name="end_date" 
                               class="form-input">
                    </div>
                </div>
                
                <!-- Budget Allocation -->
                <div class="form-group">
                    <label class="form-label">Alokasi Budget Awal</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500">Rp</span>
                        </div>
                        <input type="number" 
                               name="budget_amount" 
                               class="form-input pl-12"
                               placeholder="0"
                               min="0"
                               step="1000"
                               value="0">
                    </div>
                    <div class="form-hint text-sm text-gray-500 mt-1">
                        Budget dapat diupdate nanti setelah menambahkan items
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('dev.rab-breakdown.index', $project->id) }}"
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg font-medium">
                    Batal
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                    Simpan RAB Breakdown
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let selectedTemplate = null;
    let customCodeEnabled = false;
    
    function selectTemplate(element) {
        // Remove selection from all templates
        document.querySelectorAll('.template-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Add selection to clicked template
        element.classList.add('selected');
        selectedTemplate = {
            code: element.dataset.code,
            name: element.dataset.name
        };
        
        // Update form fields
        document.getElementById('rab_breakdown_code').value = selectedTemplate.code;
        document.getElementById('rab_breakdown_code_display').value = selectedTemplate.code;
        document.getElementById('rab_breakdown_name').value = selectedTemplate.name;
        
        // Disable custom code input if enabled
        if (customCodeEnabled) {
            document.getElementById('customCodeContainer').classList.add('hidden');
            document.getElementById('rab_breakdown_code_display').classList.remove('bg-white');
            document.getElementById('rab_breakdown_code_display').classList.add('bg-gray-50');
            document.getElementById('rab_breakdown_code_display').readOnly = true;
            customCodeEnabled = false;
        }
    }
    
    function enableCustomCode() {
        // Clear template selection
        document.querySelectorAll('.template-card').forEach(card => {
            card.classList.remove('selected');
        });
        selectedTemplate = null;
        
        // Enable custom code input
        document.getElementById('customCodeContainer').classList.remove('hidden');
        document.getElementById('rab_breakdown_code_display').classList.remove('bg-gray-50');
        document.getElementById('rab_breakdown_code_display').classList.add('bg-white');
        document.getElementById('rab_breakdown_code_display').readOnly = false;
        document.getElementById('rab_breakdown_code_display').value = '';
        document.getElementById('rab_breakdown_code').value = '';
        customCodeEnabled = true;
    }
    
    function updateCustomCode() {
        const customCode = document.getElementById('custom_rab_breakdown_code').value;
        document.getElementById('rab_breakdown_code_display').value = customCode;
        document.getElementById('rab_breakdown_code').value = customCode;
    }
    
    // Form validation
    document.getElementById('rabBreakdownForm').addEventListener('submit', function(e) {
        const rabBreakdownCode = document.getElementById('rab_breakdown_code').value;
        const rabBreakdownName = document.getElementById('rab_breakdown_name').value;
        
        if (!rabBreakdownCode) {
            e.preventDefault();
            alert('Kode RAB Breakdown wajib diisi. Pilih template atau masukkan kode kustom.');
            return;
        }
        
        if (!rabBreakdownName) {
            e.preventDefault();
            alert('Nama RAB Breakdown wajib diisi.');
            return;
        }
        
        // Show loading
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<svg class="w-4 h-4 animate-spin mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...';
        submitBtn.disabled = true;
    });
    
    // Auto-fill end date based on start date (optional)
    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.querySelector('input[name="start_date"]');
        const endDateInput = document.querySelector('input[name="end_date"]');
        
        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function() {
                if (this.value && !endDateInput.value) {
                    const startDate = new Date(this.value);
                    const endDate = new Date(startDate);
                    endDate.setDate(startDate.getDate() + 30);
                    endDateInput.value = endDate.toISOString().split('T')[0];
                }
            });
        }
        
        // Auto-select first template if available
        const firstTemplate = document.querySelector('.template-card');
        if (firstTemplate && !customCodeEnabled) {
            selectTemplate(firstTemplate);
        }
    });
</script>
@endsection



