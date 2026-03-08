@extends('layouts.dev')

@section('title', 'Tambah Item RAB Breakdown - ' . $rabBreakdown->rab_breakdown_code)

@section('content')
@php
    $isHO = auth()->check() && auth()->user() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO();
    $canEdit = method_exists($rabBreakdown, 'canEdit') ? $rabBreakdown->canEdit() : $isHO;
    $approvalKey = method_exists($rabBreakdown, 'approvalKey') ? $rabBreakdown->approvalKey() : strtolower((string) ($rabBreakdown->approval_status ?? 'draft'));
@endphp
<style>
    .form-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .form-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        padding: 2rem;
    }
    
    .dark .form-card {
        background: #111827;
        border-color: #374151;
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
    
    .form-hint {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
    
    .master-data-search {
        position: relative;
    }
    
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        max-height: 200px;
        overflow-y: auto;
        z-index: 10;
        display: none;
    }
    
    .dark .search-results {
        background: #374151;
        border-color: #4b5563;
    }
    
    .search-result-item {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        border-bottom: 1px solid #e5e7eb;
        transition: background 0.2s ease;
    }
    
    .dark .search-result-item {
        border-color: #4b5563;
    }
    
    .search-result-item:hover {
        background: #f3f4f6;
    }
    
    .dark .search-result-item:hover {
        background: #4b5563;
    }
    
    .search-result-code {
        font-family: 'SF Mono', monospace;
        font-weight: 600;
        color: #3b82f6;
    }
    
    .search-result-desc {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.125rem;
    }
    
    .dark .search-result-desc {
        color: #9ca3af;
    }
    
    .budget-preview {
        background: #f8fafc;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-top: 1rem;
    }
    
    .dark .budget-preview {
        background: #374151;
    }
    
    .preview-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }
    
    .dark .preview-title {
        color: #d1d5db;
    }
    
    .preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .preview-item {
        text-align: center;
        padding: 0.75rem;
        background: white;
        border-radius: 0.375rem;
        border: 1px solid #e5e7eb;
    }
    
    .dark .preview-item {
        background: #1f2937;
        border-color: #374151;
    }
    
    .preview-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    
    .preview-value {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
    }
    
    .dark .preview-value {
        color: #f9fafb;
    }

    .allocation-guard {
        border-radius: 0.5rem;
        border: 1px solid #bae6fd;
        background: #f0f9ff;
        padding: 0.875rem 1rem;
        margin-top: 1rem;
    }

    .dark .allocation-guard {
        background: #0f172a;
        border-color: #1d4ed8;
    }

    .allocation-guard.warning {
        border-color: #fecaca;
        background: #fef2f2;
    }

    .dark .allocation-guard.warning {
        border-color: #b91c1c;
        background: #450a0a;
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
    
    <div class="form-card">
        <div class="mb-6 rounded-lg border border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/20 px-4 py-3">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Tambah Item RAB Breakdown</h1>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                <span class="font-semibold text-sky-700 dark:text-sky-300">{{ $rabBreakdown->rab_breakdown_code }}</span>
                {{ $rabBreakdown->name }}
            </p>
            @include('dev.rab-breakdown.partials.status-chips', ['approvalKey' => $approvalKey, 'canEdit' => $canEdit])
        </div>
        
        <form action="{{ route('dev.rab-breakdown.items.store', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}" 
              method="POST" id="itemForm">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div>
                    <!-- Item Code -->
                    <div class="form-group">
                        <label class="form-label">
                            Kode Item <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="item_code" 
                               id="item_code"
                               class="form-input"
                               placeholder="Contoh: A, A.1, B.1"
                               required
                               onblur="validateItemCode()">
                        <div class="form-hint">
                            Format: huruf atau huruf.angka (contoh: A atau A.1)
                        </div>
                        <div class="form-hint">
                            Kategori existing: {{ empty($categoryState['existing'] ?? []) ? '-' : implode(', ', $categoryState['existing']) }}.
                            Start kategori: {{ $categoryState['start'] ?? 'A' }}.
                            Next disarankan: {{ $categoryState['next'] ?? ($categoryState['start'] ?? 'A') }}.
                        </div>
                        <div id="itemCodeError" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Item RAPP <span class="text-red-500">*</span>
                        </label>
                        <select name="rab_item_id" id="rab_item_id" class="form-input form-select" required onchange="applyRabItem()">
                            <option value="">Pilih Item RAPP</option>
                            @foreach($rabItems as $rabItem)
                                <option value="{{ $rabItem->id }}"
                                        data-uraian="{{ $rabItem->uraian }}"
                                        data-volume="{{ $rabItem->volume }}"
                                        data-satuan="{{ $rabItem->satuan }}"
                                        data-price="{{ $rabItem->unit_price }}"
                                        data-master="{{ $rabItem->master_kode }}">
                                    {{ $rabItem->item_name }} - Vol {{ number_format($rabItem->volume, 2, ',', '.') }} {{ $rabItem->satuan }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-hint">Wajib pilih item dari RAPP approved proyek ini.</div>
                    </div>
                    
                    <!-- Uraian -->
                    <div class="form-group">
                        <label class="form-label">
                            Uraian / Deskripsi <span class="text-red-500">*</span>
                        </label>
                        <textarea name="uraian" 
                                  id="uraian"
                                  class="form-input"
                                  rows="3"
                                  placeholder="Deskripsi detail item pekerjaan"
                                  readonly
                                  required></textarea>
                    </div>
                    
                    <!-- Notes -->
                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" 
                                  class="form-input"
                                  rows="2"
                                  placeholder="Catatan tambahan (opsional)"></textarea>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <!-- Volume & Satuan -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">
                                Volume RAB <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="volume_rab" 
                                   id="volume_rab"
                                   class="form-input"
                                   step="0.01"
                                   min="0.01"
                                   readonly
                                   required
                                   oninput="calculateTotal()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Satuan <span class="text-red-500">*</span>
                            </label>
                            <select name="satuan" 
                                    id="satuan"
                                    class="form-input form-select"
                                    disabled
                                    required>
                                <option value="">Pilih Satuan</option>
                                <option value="OH">OH (Orang Hari)</option>
                                <option value="Rit">Rit</option>
                                <option value="m3">m3</option>
                                <option value="m2">m2</option>
                                <option value="unit">unit</option>
                                <option value="LS">LS (Lump Sum)</option>
                                <option value="kg">kg</option>
                                <option value="ton">ton</option>
                                <option value="lbr">lbr (lembar)</option>
                                <option value="btg">btg (batang)</option>
                                <option value="pcs">pcs</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Unit Price -->
                    <div class="form-group">
                        <label class="form-label">
                            Harga Satuan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500">Rp</span>
                            </div>
                            <input type="number" 
                                   name="unit_price" 
                                   id="unit_price"
                                   class="form-input pl-12"
                                   min="0"
                                   readonly
                                   required
                                   oninput="calculateTotal()">
                        </div>
                    </div>
                    
                    <!-- Master Data Mapping -->
                    <div class="form-group master-data-search">
                        <label class="form-label">
                            Kode Master Data (opsional)
                        </label>
                        <input type="text" 
                               name="master_kode"
                               id="master_kode"
                               class="form-input"
                               placeholder="Contoh: JS-001, MT-133"
                               readonly
                               autocomplete="off">
                        <div class="form-hint">
                            Auto-fill dari item RAPP.
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Dimensi RAPP Master (A:Q)</label>
                        <div class="grid grid-cols-2 gap-3">
                            <input type="number" name="p" id="p" class="form-input" step="0.0001" min="0" placeholder="P" value="{{ old('p') }}">
                            <input type="number" name="l" id="l" class="form-input" step="0.0001" min="0" placeholder="L" value="{{ old('l') }}">
                            <input type="number" name="t" id="t" class="form-input" step="0.0001" min="0" placeholder="T" value="{{ old('t') }}">
                            <input type="number" name="n" id="n" class="form-input" step="0.0001" min="0" placeholder="N" value="{{ old('n') }}">
                            <input type="number" name="n_tul_1" id="n_tul_1" class="form-input" step="0.0001" min="0" placeholder="N TUL 1" value="{{ old('n_tul_1') }}">
                            <input type="number" name="n_tul_2" id="n_tul_2" class="form-input" step="0.0001" min="0" placeholder="N TUL 2" value="{{ old('n_tul_2') }}">
                            <input type="number" name="jarak" id="jarak" class="form-input" step="0.0001" min="0" placeholder="JARAK" value="{{ old('jarak') }}">
                            <input type="number" name="dia_1" id="dia_1" class="form-input" step="0.0001" min="0" placeholder="DIA 1" value="{{ old('dia_1') }}">
                            <input type="number" name="dia_2" id="dia_2" class="form-input" step="0.0001" min="0" placeholder="DIA 2" value="{{ old('dia_2') }}">
                            <input type="number" name="dia_3" id="dia_3" class="form-input" step="0.0001" min="0" placeholder="DIA 3" value="{{ old('dia_3') }}">
                            <input type="number" name="berat_1" id="berat_1" class="form-input" step="0.0001" min="0" placeholder="BERAT 1" value="{{ old('berat_1') }}">
                            <input type="number" name="berat_2" id="berat_2" class="form-input" step="0.0001" min="0" placeholder="BERAT 2" value="{{ old('berat_2') }}">
                        </div>
                        <div class="form-hint">Opsional, isi sesuai kolom P/L/T/N/N TUL 1/N TUL 2/JARAK/DIA 1/DIA 2/DIA 3/BERAT 1/BERAT 2 pada Excel.</div>
                    </div>
                </div>
            </div>
            
            <!-- Budget Preview -->
            <div class="budget-preview">
                <div class="preview-title">Preview Budget</div>
                <div class="preview-grid">
                    <div class="preview-item">
                        <div class="preview-label">Volume × Harga</div>
                        <div id="volumeDisplay" class="preview-value">0</div>
                    </div>
                    <div class="preview-item">
                        <div class="preview-label">Harga Satuan</div>
                        <div id="unitPriceDisplay" class="preview-value">Rp 0</div>
                    </div>
                    <div class="preview-item">
                        <div class="preview-label">Total Harga</div>
                        <div id="totalPriceDisplay" class="preview-value font-bold text-blue-600">Rp 0</div>
                    </div>
                </div>
                <div id="allocationGuard" class="allocation-guard">
                    <div class="text-sm font-semibold text-sky-800 dark:text-sky-300">Guardrail Alokasi</div>
                    <div class="mt-1 text-xs text-gray-700 dark:text-gray-300">
                        Maks Qty Beli: <span id="maxQtyLabel" class="font-semibold">-</span> |
                        Maks Jumlah: <span id="maxJumlahLabel" class="font-semibold">-</span>
                    </div>
                    <div id="allocationGuardMessage" class="mt-1 text-xs text-sky-700 dark:text-sky-300">
                        Nilai saat ini masih dalam batas wajar.
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('dev.rab-breakdown.show', ['projectId' => $project->id, 'rabBreakdown' => $rabBreakdown->id]) }}"
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg font-medium">
                    Batal
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                    Simpan Item
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const categoryState = @json($categoryState ?? ['existing' => [], 'next' => 'A', 'start' => 'A']);

    function isLsUnit(rawUnit) {
        const unit = (rawUnit || '').toString().trim().toUpperCase();
        return unit === 'LS' || unit === 'LUMP SUM' || unit === 'LUMPSUM';
    }

    function computeAllocationLimits(satuan, volumeAcuan, amountAcuan) {
        const isLs = isLsUnit(satuan);
        const maxQty = isLs ? Math.max(volumeAcuan, 1) : (volumeAcuan * 1.2);
        const maxJumlah = amountAcuan * (isLs ? 1.05 : 1.25);
        return { maxQty, maxJumlah };
    }

    function formatRupiah(value) {
        return 'Rp ' + (Number(value) || 0).toLocaleString('id-ID');
    }

    function validateAllocationInline() {
        const satuan = document.getElementById('satuan').value;
        const volume = parseFloat(document.getElementById('volume_rab').value) || 0;
        const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
        const amountAcuan = volume * unitPrice;
        const qtyInput = document.getElementById('qty_beli');
        const jumlahInput = document.getElementById('jumlah');
        const qty = qtyInput ? (parseFloat(qtyInput.value) || 0) : volume;
        const jumlah = jumlahInput ? (parseFloat(jumlahInput.value) || 0) : amountAcuan;
        const limits = computeAllocationLimits(satuan, volume, amountAcuan);

        const guard = document.getElementById('allocationGuard');
        const msg = document.getElementById('allocationGuardMessage');
        document.getElementById('maxQtyLabel').textContent = `${limits.maxQty.toLocaleString('id-ID', { maximumFractionDigits: 4 })} ${satuan || ''}`.trim();
        document.getElementById('maxJumlahLabel').textContent = formatRupiah(limits.maxJumlah);

        if (qty > limits.maxQty || jumlah > limits.maxJumlah) {
            guard.classList.add('warning');
            msg.className = 'mt-1 text-xs text-red-700 dark:text-red-300';
            msg.textContent = 'Melebihi guardrail alokasi. Turunkan qty_beli/jumlah atau pilih item RAB yang benar.';
            return false;
        }

        guard.classList.remove('warning');
        msg.className = 'mt-1 text-xs text-sky-700 dark:text-sky-300';
        msg.textContent = 'Nilai saat ini masih dalam batas wajar.';
        return true;
    }

    // Calculate total price
    function calculateTotal() {
        const volume = parseFloat(document.getElementById('volume_rab').value) || 0;
        const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
        const total = volume * unitPrice;
        
        // Update displays
        document.getElementById('volumeDisplay').textContent = volume.toLocaleString('id-ID');
        document.getElementById('unitPriceDisplay').textContent = 'Rp ' + unitPrice.toLocaleString('id-ID');
        document.getElementById('totalPriceDisplay').textContent = 'Rp ' + total.toLocaleString('id-ID');
        validateAllocationInline();
    }
    
    // Validate item code format
    function validateItemCode() {
        const itemCode = document.getElementById('item_code').value.trim().toUpperCase();
        const errorDiv = document.getElementById('itemCodeError');
        
        if (!itemCode.match(/^[A-Z](?:\.\d+)?$/)) {
            errorDiv.textContent = 'Format kode item tidak valid. Gunakan format: A atau A.1';
            errorDiv.classList.remove('hidden');
            return false;
        }

        const match = itemCode.match(/^([A-Z])(?:\.\d+)?$/);
        const top = match ? match[1] : null;
        const existing = new Set((categoryState.existing || []).map(v => String(v).toUpperCase()));
        const start = String(categoryState.start || 'A').toUpperCase();
        if (top) {
            if (top.charCodeAt(0) < start.charCodeAt(0)) {
                errorDiv.textContent = `Kategori minimum untuk breakdown ini adalah ${start}.`;
                errorDiv.classList.remove('hidden');
                return false;
            }
            const targetIndex = top.charCodeAt(0) - 65 + 1;
            const startIndex = start.charCodeAt(0) - 65 + 1;
            for (let i = startIndex; i < targetIndex; i++) {
                const mustExist = String.fromCharCode(64 + i);
                if (!existing.has(mustExist)) {
                    errorDiv.textContent = `Urutan kategori tidak boleh loncat. Tambahkan kategori ${mustExist} sebelum ${top}.`;
                    errorDiv.classList.remove('hidden');
                    return false;
                }
            }
        }

        document.getElementById('item_code').value = itemCode;
        errorDiv.classList.add('hidden');
        return true;
    }
    
    function normalizeSatuanValue(value) {
        const raw = (value || '').toString().trim().toLowerCase();
        if (raw === 'm³' || raw === 'm3') {
            return 'm3';
        }
        if (raw === 'm²' || raw === 'm2') {
            return 'm2';
        }
        return value;
    }

    function applyRabItem() {
        const sel = document.getElementById('rab_item_id');
        if (!sel || !sel.value) return;
        const opt = sel.options[sel.selectedIndex];
        document.getElementById('uraian').value = opt.dataset.uraian || '';
        document.getElementById('volume_rab').value = opt.dataset.volume || 0;
        document.getElementById('satuan').value = normalizeSatuanValue(opt.dataset.satuan || '');
        document.getElementById('unit_price').value = opt.dataset.price || 0;
        document.getElementById('master_kode').value = opt.dataset.master || '';
        const pInput = document.getElementById('p');
        if (pInput && (!pInput.value || parseFloat(pInput.value) === 0)) {
            pInput.value = opt.dataset.volume || 0;
        }
        calculateTotal();
        validateAllocationInline();
    }
    
    // Form validation
    document.getElementById('itemForm').addEventListener('submit', function(e) {
        if (!validateItemCode()) {
            e.preventDefault();
            document.getElementById('item_code').focus();
            return;
        }
        if (!validateAllocationInline()) {
            e.preventDefault();
            return;
        }
        
        // Calculate total price and set in hidden field if needed
        const volume = parseFloat(document.getElementById('volume_rab').value) || 0;
        const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
        const totalPrice = volume * unitPrice;
        
        // You could add a hidden field for total_price if needed
        // Or let the backend calculate it
    });
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        applyRabItem();
        calculateTotal();
        validateAllocationInline();
    });
</script>
@endsection







