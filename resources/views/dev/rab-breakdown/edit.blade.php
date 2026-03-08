@extends('layouts.dev')

@section('title', 'Edit RAB Breakdown - ' . $rabBreakdown->rab_breakdown_code . ' - ' . $project->name)

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
    
    .rab-breakdown-header {
        background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
        color: white;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .rab-breakdown-code-display {
        font-family: 'SF Mono', monospace;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .stat-card {
        background: #f8fafc;
        border-radius: 0.5rem;
        padding: 1rem;
        text-align: center;
    }
    
    .dark .stat-card {
        background: #374151;
    }
    
    .stat-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    
    .stat-value {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
    }
    
    .dark .stat-value {
        color: #f9fafb;
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
    
    .form-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.25em 1.25em;
        padding-right: 2.5rem;
    }
    
    .date-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .progress-display {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .progress-bar {
        flex: 1;
        height: 0.5rem;
        background: #e5e7eb;
        border-radius: 9999px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #60a5fa);
        border-radius: 9999px;
    }
</style>

<div class="form-container">
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
    
    <!-- RAB Breakdown Header -->
    <div class="rab-breakdown-header">
        <div class="rab-breakdown-code-display">{{ $rabBreakdown->rab_breakdown_code }}</div>
        <div class="text-lg font-semibold">{{ $rabBreakdown->name }}</div>
        <div class="text-sm opacity-90 mt-1">
            Project: {{ $project->name }} | 
            Created: {{ $rabBreakdown->created_at->format('d M Y') }} |
            Order: {{ $rabBreakdown->order_number }}
        </div>
    </div>
    
    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Items</div>
            <div class="stat-value">{{ $rabBreakdown->items_count ?? $rabBreakdown->items->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Budget Total</div>
            <div class="stat-value">Rp {{ number_format($rabBreakdown->budget_amount, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Actual Spent</div>
            <div class="stat-value">Rp {{ number_format($rabBreakdown->actual_amount, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Progress</div>
            <div class="progress-display">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $rabBreakdown->progress_percentage }}%"></div>
                </div>
                <span class="text-sm font-semibold">{{ number_format($rabBreakdown->progress_percentage, 1) }}%</span>
            </div>
        </div>
    </div>
    
    <div class="form-card">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Edit Work Breakdown Structure</h1>
        
        <form action="{{ route('dev.rab-breakdown.update', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}" 
              method="POST" id="editWbsForm">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <!-- RAB Breakdown Code (read-only) -->
                <div class="form-group">
                    <label class="form-label">Kode RAB Breakdown</label>
                    <input type="text" 
                           class="form-input bg-gray-50 dark:bg-gray-800 font-mono font-bold"
                           value="{{ $rabBreakdown->rab_breakdown_code }}"
                           readonly>
                    <div class="text-sm text-gray-500 mt-1">Kode RAB Breakdown tidak dapat diubah</div>
                </div>
                
                <!-- RAB Breakdown Name -->
                <div class="form-group">
                    <label class="form-label">
                        Nama RAB Breakdown <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           class="form-input"
                           value="{{ old('name', $rabBreakdown->name) }}"
                           required
                           maxlength="255">
                </div>
                
                <!-- Description -->
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" 
                              class="form-input"
                              rows="3">{{ old('description', $rabBreakdown->description) }}</textarea>
                </div>
                
                <!-- Dates -->
                <div class="date-grid">
                    <div class="form-group">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" 
                               name="start_date" 
                               class="form-input"
                               value="{{ old('start_date', $rabBreakdown->start_date ? $rabBreakdown->start_date->format('Y-m-d') : '') }}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" 
                               name="end_date" 
                               class="form-input"
                               value="{{ old('end_date', $rabBreakdown->end_date ? $rabBreakdown->end_date->format('Y-m-d') : '') }}">
                    </div>
                </div>
                
                <!-- Status -->
                <div class="form-group">
                    <label class="form-label">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select name="status" 
                            class="form-input form-select"
                            required>
                        <option value="not_started" {{ $rabBreakdown->status == 'not_started' ? 'selected' : '' }}>Belum Mulai</option>
                        <option value="in_progress" {{ $rabBreakdown->status == 'in_progress' ? 'selected' : '' }}>Dalam Progress</option>
                        <option value="completed" {{ $rabBreakdown->status == 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="delayed" {{ $rabBreakdown->status == 'delayed' ? 'selected' : '' }}>Terlambat</option>
                    </select>
                </div>
                
                <!-- Budget Allocation -->
                <div class="form-group">
                    <label class="form-label">Alokasi Budget</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500">Rp</span>
                        </div>
                        <input type="number" 
                               name="budget_amount" 
                               class="form-input pl-12"
                               value="{{ old('budget_amount', $rabBreakdown->budget_amount) }}"
                               min="0"
                               step="1000">
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        Budget otomatis dihitung dari total items. Kosongkan untuk menghitung ulang.
                    </div>
                </div>
                
                <!-- Actual Amount -->
                <div class="form-group">
                    <label class="form-label">Actual Amount Spent</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500">Rp</span>
                        </div>
                        <input type="number" 
                               name="actual_amount" 
                               class="form-input pl-12"
                               value="{{ old('actual_amount', $rabBreakdown->actual_amount) }}"
                               min="0"
                               step="1000"
                               oninput="calculateProgress()">
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        Progress akan dihitung ulang saat actual amount diubah
                    </div>
                </div>
                
                <!-- Calculated Progress Display -->
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-blue-800 dark:text-blue-300">
                            Progress Calculation
                        </span>
                        <span id="progressDisplay" class="text-lg font-bold text-blue-900 dark:text-blue-200">
                            {{ number_format($rabBreakdown->progress_percentage, 1) }}%
                        </span>
                    </div>
                    <div class="text-xs text-blue-700 dark:text-blue-400">
                        Progress = (Actual Amount / Budget Amount) × 100%
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-between gap-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <button type="button"
                            onclick="deleteRabBreakdown()"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
                        Hapus RAB Breakdown
                    </button>
                </div>
                
                <div class="flex gap-2">
                    <a href="{{ route('dev.rab-breakdown.show', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}"
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg font-medium">
                        Batal
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                        Update RAB Breakdown
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Delete Form (hidden) -->
        <form id="deleteForm" 
              action="{{ route('dev.rab-breakdown.destroy', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}" 
              method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

<script>
    function calculateProgress() {
        const actualAmount = parseFloat(document.querySelector('input[name="actual_amount"]').value) || 0;
        const budgetAmount = parseFloat(document.querySelector('input[name="budget_amount"]').value) || 0;
        
        if (budgetAmount > 0) {
            const progress = (actualAmount / budgetAmount) * 100;
            document.getElementById('progressDisplay').textContent = progress.toFixed(1) + '%';
        } else {
            document.getElementById('progressDisplay').textContent = '0.0%';
        }
    }
    
    function deleteRabBreakdown() {
        const breakdownName = @json((string) ($rabBreakdown->name ?? ''));
        const itemCount = @json((int) ($rabBreakdown->items_count ?? $rabBreakdown->items->count()));
        if (confirm(`Hapus RAB Breakdown "${breakdownName}"? Semua ${itemCount} item akan ikut terhapus. Tindakan ini tidak dapat dibatalkan.`)) {
            document.getElementById('deleteForm').submit();
        }
    }
    
    document.getElementById('editWbsForm').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<svg class="w-4 h-4 animate-spin mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Updating...';
        submitBtn.disabled = true;
    });
    
    // Initialize progress calculation
    document.addEventListener('DOMContentLoaded', function() {
        calculateProgress();
    });
</script>
@endsection





