@extends('layouts.dev')

@section('title', 'Edit Voucher Pembelian - ' . ($voucher->voucher_no ?? ''))
@section('subtitle', 'Edit Voucher Pembelian')

@section('content')
@php
    $itemCount = $voucher->items?->count() ?? 0;
    $subtotal  = (float) ($voucher->subtotal_amount ?? 0);
    $tax       = (float) ($voucher->tax_amount ?? 0);
    $total     = (float) ($voucher->total_amount ?? 0);
    $fmtDate   = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
    $sourceType = $voucher->lpb_id ? 'lpb' : 'po';
@endphp

<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-lg font-semibold">Edit Voucher Pembelian</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Proyek: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $project->name }}</span>
                @if(!empty($project->code))
                    &bull; Kode: <span class="font-mono">{{ $project->code }}</span>
                @endif
                @if(!empty($rab->name))
                    &bull; RAPP: <span class="font-semibold">{{ $rab->name }}</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dev.rabs.purchase-vouchers.show', ['projectId' => $project->id, 'rabId' => $rab->id, 'voucherId' => $voucher->id]) }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm">
                Kembali ke Detail
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 text-xs rounded-lg px-4 py-3">
            <p class="font-semibold mb-1">Periksa kembali input:</p>
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('dev.rabs.purchase-vouchers.update', ['projectId' => $project->id, 'rabId' => $rab->id, 'voucherId' => $voucher->id]) }}"
          class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No Voucher *</label>
                        <input type="text" name="voucher_no" value="{{ old('voucher_no', $voucher->voucher_no) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                               required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Voucher *</label>
                        <input type="date" name="voucher_date" value="{{ old('voucher_date', optional($voucher->voucher_date)->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                               required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Referensi Dokumen</label>
                        <select id="ve-source-type"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            <option value="po" @selected($sourceType === 'po')>PO (approved)</option>
                            <option value="lpb" @selected($sourceType === 'lpb')>LPB (approved)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Nomor Dokumen</label>
                        <select name="purchase_order_id" id="ve-po-id"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            <option value="">Pilih PO approved</option>
                            @foreach($approvedPos as $po)
                                <option value="{{ $po->id }}" data-vendor="{{ $po->vendor?->nama ?? '' }}" @selected(old('purchase_order_id', $voucher->purchase_order_id) == $po->id)>
                                    {{ $po->po_no ?? ('PO #'.$po->id) }}
                                </option>
                            @endforeach
                        </select>
                        <select name="lpb_id" id="ve-lpb-id"
                                class="mt-2 hidden w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            <option value="">Pilih LPB approved</option>
                            @foreach($approvedLpbs as $lpb)
                                <option value="{{ $lpb->id }}" data-vendor="{{ $lpb->vendor?->nama ?? '' }}" @selected(old('lpb_id', $voucher->lpb_id) == $lpb->id)>
                                    {{ $lpb->lpb_no ?? ('LPB #'.$lpb->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">PPN (%)</label>
                        <input type="number" step="0.01" name="tax_percent" id="ve-tax-percent"
                               value="{{ old('tax_percent', $voucher->tax_percent) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-right">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Jatuh Tempo</label>
                        <input type="date" name="due_date" value="{{ old('due_date', optional($voucher->due_date)->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Vendor</label>
                        <input type="text" name="vendor_name" id="ve-vendor-name" value="{{ old('vendor_name', $voucher->vendor_name) }}" readonly
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No Invoice / Nota</label>
                        <input type="text" name="invoice_no" value="{{ old('invoice_no', $voucher->invoice_no) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Metode Pembayaran</label>
                        <input type="text" name="payment_method" value="{{ old('payment_method', $voucher->payment_method) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Status</label>
                        @php
                            $statusValue = strtolower($voucher->status ?? 'draft');
                            $statusBadge = [
                                'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700/40 dark:text-gray-200',
                                'submitted' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                                'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
                            ];
                            $badgeClass = $statusBadge[$statusValue] ?? $statusBadge['draft'];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                            {{ ucfirst($statusValue) }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tujuan Pembayaran</label>
                        <input type="text" name="payment_purpose" value="{{ old('payment_purpose', $voucher->payment_purpose) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Bank</label>
                        <input type="text" name="bank" value="{{ old('bank', $voucher->bank) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No Rekening</label>
                        <input type="text" name="account_no" value="{{ old('account_no', $voucher->account_no) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Atas Nama</label>
                        <input type="text" name="account_name" value="{{ old('account_name', $voucher->account_name) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Catatan</label>
                        <input type="text" name="notes" value="{{ old('notes', $voucher->notes) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                <div class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-2">Ringkasan Pembayaran</div>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-300">Subtotal</span>
                        <span id="ve-subtotal" class="font-semibold text-gray-900 dark:text-gray-50">
                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-300">PPN ({{ number_format($voucher->tax_percent ?? 0, 2, ',', '.') }}%)</span>
                        <span id="ve-tax" class="font-semibold text-gray-900 dark:text-gray-50">
                            Rp {{ number_format($tax, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-800"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-200 font-semibold">Total</span>
                        <span id="ve-total" class="font-bold text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format($total, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                        Realisasi RAPP mengikuti rumus: <span class="font-semibold">Qty &times; Harga Satuan RAPP</span>.
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Edit Item Voucher</h2>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $itemCount }} baris item</p>
            </div>

            <div class="p-0">
                <table class="w-full border-t border-gray-200 dark:border-gray-800 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/70">
                        <tr class="text-gray-600 dark:text-gray-300">
                            <th class="px-2 py-2 text-center w-10">No</th>
                            <th class="px-2 py-2 text-left">Item</th>
                            <th class="px-2 py-2 text-center">Qty</th>
                            <th class="px-2 py-2 text-right">Harga Nota</th>
                            <th class="px-2 py-2 text-right">Jumlah Nota</th>
                            <th class="px-2 py-2 text-right">Realisasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($voucher->items as $item)
                            @php
                                $rabItem   = $item->rabItem;
                                $data      = $rabItem?->data;
                                $kode      = $data->kode ?? $data->item_code ?? '';
                                $uraian    = $data->uraian ?? $data->item_name ?? '';
                                $satuan    = $data->satuan ?? $rabItem->satuan ?? $item->unit ?? '';

                                $qty       = (float) ($item->qty ?? 0);
                                $priceNota = (float) ($item->price ?? 0);
                                $amount    = (float) ($item->amount ?? ($qty * $priceNota));

                                $hsRapp    = (float) ($item->rab_harga_satuan_snapshot ?? $rabItem->harga_satuan ?? 0);
                                $realisasiExcel = $qty * $hsRapp;
                            @endphp
                            <tr class="even:bg-gray-50/70 dark:even:bg-gray-900/40 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                                <td class="px-2 py-2 text-center text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-2 py-2">
                                    <div class="font-medium text-gray-900 dark:text-gray-50">{{ $uraian ?: '-' }}</div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ $kode ?: '-' }} • {{ $satuan ?: '-' }} • RAPP: Rp {{ number_format($hsRapp, 0, ',', '.') }}
                                    </div>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <input type="hidden" name="items[{{ $item->id }}][rab_item_id]" value="{{ $item->rab_item_id }}">
                                    <input type="number" step="0.01"
                                           name="items[{{ $item->id }}][qty]"
                                           value="{{ old('items.'.$item->id.'.qty', $qty) }}"
                                           class="ve-qty w-24 px-2 py-1 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-right text-[12px] text-gray-900 dark:text-white"
                                           data-item-id="{{ $item->id }}">
                                </td>
                                <td class="px-2 py-2 text-right">
                                    <input type="number" step="1"
                                           name="items[{{ $item->id }}][price]"
                                           value="{{ old('items.'.$item->id.'.price', $priceNota) }}"
                                           class="ve-price w-28 px-2 py-1 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-right text-[12px] text-gray-900 dark:text-white"
                                           data-item-id="{{ $item->id }}">
                                </td>
                                <td class="px-2 py-2 text-right font-semibold text-gray-900 dark:text-gray-50">
                                    <span id="ve-amount-{{ $item->id }}">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-2 py-2 text-right font-semibold text-emerald-700 dark:text-emerald-300">
                                    <span id="ve-realisasi-{{ $item->id }}" data-hs="{{ $hsRapp }}">
                                        Rp {{ number_format($realisasiExcel, 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Tidak ada item pada voucher ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <p class="text-[11px] text-gray-500 dark:text-gray-400">Perubahan Qty mempengaruhi realisasi anggaran RAPP.</p>
            <div class="flex items-center gap-2">
                <a href="{{ route('dev.rabs.purchase-vouchers.show', ['projectId' => $project->id, 'rabId' => $rab->id, 'voucherId' => $voucher->id]) }}"
                   class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-xs font-semibold text-white">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function veRupiah(n) {
        const v = Number(n || 0);
        return 'Rp ' + v.toLocaleString('id-ID');
    }

    function updateEditSourceUI() {
        const typeSelect = document.getElementById('ve-source-type');
        const poSelect = document.getElementById('ve-po-id');
        const lpbSelect = document.getElementById('ve-lpb-id');
        const vendorInput = document.getElementById('ve-vendor-name');
        const submitBtn = document.querySelector('button[type="submit"]');
        if (!typeSelect || !poSelect || !lpbSelect) return;

        const type = typeSelect.value || 'po';
        if (type === 'lpb') {
            poSelect.classList.add('hidden');
            lpbSelect.classList.remove('hidden');
            poSelect.value = '';
        } else {
            lpbSelect.classList.add('hidden');
            poSelect.classList.remove('hidden');
            lpbSelect.value = '';
        }

        const activeSelect = type === 'lpb' ? lpbSelect : poSelect;
        const selected = activeSelect.options[activeSelect.selectedIndex];
        const vendor = selected ? (selected.getAttribute('data-vendor') || '') : '';
        if (vendorInput) vendorInput.value = vendor;

        if (submitBtn) {
            submitBtn.disabled = !activeSelect.value;
            submitBtn.classList.toggle('opacity-50', submitBtn.disabled);
            submitBtn.classList.toggle('cursor-not-allowed', submitBtn.disabled);
        }
    }

    function recalcEditVoucherTotals() {
        const qtyInputs = document.querySelectorAll('.ve-qty');
        let subtotal = 0;

        qtyInputs.forEach(input => {
            const id = input.getAttribute('data-item-id');
            const qty = parseFloat(input.value || '0');
            const priceInput = document.querySelector(`.ve-price[data-item-id="${id}"]`);
            const price = parseFloat(priceInput?.value || '0');

            const amount = qty * price;
            subtotal += amount;

            const amountSpan = document.getElementById(`ve-amount-${id}`);
            if (amountSpan) amountSpan.textContent = veRupiah(amount);

            const realSpan = document.getElementById(`ve-realisasi-${id}`);
            if (realSpan) {
                const hs = parseFloat(realSpan.getAttribute('data-hs') || '0');
                const realisasi = qty * hs;
                realSpan.textContent = veRupiah(realisasi);
            }
        });

        const taxInput = document.getElementById('ve-tax-percent');
        const taxPercent = parseFloat(taxInput?.value || '0');
        const taxAmount = subtotal * (taxPercent / 100);
        const total = subtotal + taxAmount;

        const subEl = document.getElementById('ve-subtotal');
        const taxEl = document.getElementById('ve-tax');
        const totalEl = document.getElementById('ve-total');

        if (subEl) subEl.textContent = veRupiah(subtotal);
        if (taxEl) taxEl.textContent = veRupiah(taxAmount);
        if (totalEl) totalEl.textContent = veRupiah(total);
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateEditSourceUI();
        const typeSelect = document.getElementById('ve-source-type');
        const poSelect = document.getElementById('ve-po-id');
        const lpbSelect = document.getElementById('ve-lpb-id');
        if (typeSelect) typeSelect.addEventListener('change', updateEditSourceUI);
        if (poSelect) poSelect.addEventListener('change', updateEditSourceUI);
        if (lpbSelect) lpbSelect.addEventListener('change', updateEditSourceUI);

        document.querySelectorAll('.ve-qty, .ve-price').forEach(el => {
            el.addEventListener('input', recalcEditVoucherTotals);
            el.addEventListener('change', recalcEditVoucherTotals);
        });
        const taxInput = document.getElementById('ve-tax-percent');
        if (taxInput) {
            taxInput.addEventListener('input', recalcEditVoucherTotals);
            taxInput.addEventListener('change', recalcEditVoucherTotals);
        }
        recalcEditVoucherTotals();
    });
</script>
@endsection





