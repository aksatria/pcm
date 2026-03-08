{{-- resources/views/dev/RAPPs/partials/purchase-voucher-modal.blade.php --}}

{{-- BAR AKSI DI ATAS TABEL RAPP (muncul setelah ada item yang dicentang) --}}
<div id="voucherActionBar" class="hidden mb-4">
    <div class="flex items-center justify-between bg-blue-50 dark:bg-slate-900 border border-blue-200 dark:border-blue-800 rounded-xl px-4 py-3">
        <div class="text-xs md:text-sm text-blue-900 dark:text-blue-200">
            <span class="font-semibold" id="selectedCount">0</span> item dipilih untuk voucher pembelian.
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    onclick="openPurchaseVoucherModal()"
                    class="px-3 md:px-4 py-1.5 md:py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs md:text-sm font-semibold">
                Buat Voucher Pembelian
            </button>
            <button type="button"
                    onclick="clearAllSelections()"
                    class="px-2.5 md:px-3 py-1.5 border border-blue-200 dark:border-blue-700 rounded-lg text-[11px] md:text-xs text-blue-700 dark:text-blue-200 hover:bg-blue-50 dark:hover:bg-slate-800">
                Bersihkan
            </button>
        </div>
    </div>
</div>

{{-- MODAL VOUCHER PEMBELIAN --}}
<div id="purchaseVoucherModal" class="hidden fixed inset-0 z-40 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-2 md:px-4 py-6">
        <div class="fixed inset-0 bg-black/40 dark:bg-black/60" onclick="closePurchaseVoucherModal()"></div>

        <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-2xl w-full max-w-5xl border border-slate-200 dark:border-slate-700">
            {{-- HEADER --}}
            <div class="flex items-center justify-between px-4 md:px-6 py-3 border-b border-slate-200 dark:border-slate-700">
                <div>
                    <h2 class="text-sm md:text-base font-semibold text-slate-900 dark:text-slate-50">
                        Voucher Pembelian dari RAPP
                    </h2>
                    <p class="text-[11px] md:text-xs text-slate-500 dark:text-slate-400">
                        Pilih item pada tabel RAPP, lalu atur volume & harga untuk dibuatkan voucher.
                    </p>
                </div>
                <button type="button"
                        onclick="closePurchaseVoucherModal()"
                        class="inline-flex items-center justify-center w-7 h-7 rounded-full border border-slate-200 dark:border-slate-700 text-slate-400 hover:text-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- FORM --}}
            <form id="purchaseVoucherForm">
                @csrf

                {{-- BODY: TIDAK dibatasi tinggi, BOLEH PANJANG --}}
                <div class="px-4 md:px-6 pt-3 pb-4 space-y-4">

                    {{-- BLOK ATAS: info voucher --}}
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        {{-- Kolom 1 & 2: form utama --}}
                        <div class="lg:col-span-2 space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        No. Voucher
                                    </label>
                                    <input type="text" name="voucher_no" id="voucher_no"
                                           class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50"
                                           placeholder="Contoh: PV-001/AK/2025">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        Tanggal
                                    </label>
                                    <input type="date" name="voucher_date" id="voucher_date"
                                           class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        Referensi Dokumen
                                    </label>
                                    <select id="pv-source-type"
                                            class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50">
                                        <option value="po">PO (approved)</option>
                                        <option value="lpb">LPB (approved)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        Nomor Dokumen
                                    </label>
                                    <select name="purchase_order_id" id="pv-po-id"
                                            class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50">
                                        <option value="">Pilih PO approved</option>
                                        @foreach($approvedPos as $po)
                                            <option value="{{ $po->id }}" data-vendor="{{ $po->vendor?->nama ?? '' }}">
                                                {{ $po->po_no ?? ('PO #'.$po->id) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <select name="lpb_id" id="pv-lpb-id"
                                            class="hidden w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50">
                                        <option value="">Pilih LPB approved</option>
                                        @foreach($approvedLpbs as $lpb)
                                            <option value="{{ $lpb->id }}" data-vendor="{{ $lpb->vendor?->nama ?? '' }}">
                                                {{ $lpb->lpb_no ?? ('LPB #'.$lpb->id) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300">
                                            Vendor / Supplier
                                        </label>
                                        <button type="button"
                                                onclick="openVendorModal()"
                                                class="text-[10px] text-sky-600 dark:text-sky-400 hover:underline">
                                            Tambah Vendor
                                        </button>
                                    </div>
                                    <input type="text" name="vendor_name" id="vendor_name" data-vendor-input="1" readonly
                                           class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50"
                                           placeholder="Otomatis dari PO/LPB">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        No. Invoice / Dokumen
                                    </label>
                                    <input type="text" name="invoice_no" id="invoice_no"
                                           class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50"
                                           placeholder="Nomor invoice (jika ada)">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        Keperluan Pembayaran
                                    </label>
                                    <input type="text" name="payment_purpose" id="payment_purpose"
                                           class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50"
                                           placeholder="Misal: Pembelian material pondasi">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        Catatan
                                    </label>
                                    <input type="text" name="notes" id="notes"
                                           class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50"
                                           placeholder="Catatan (opsional)">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        Metode Pembayaran
                                    </label>
                                    <input type="text" name="payment_method" id="payment_method"
                                           class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50"
                                           placeholder="Transfer / Tunai / Giro">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        Jatuh Tempo
                                    </label>
                                    <input type="date" name="due_date" id="due_date"
                                           class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        Bank
                                    </label>
                                    <input type="text" name="bank" id="bank"
                                           class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50"
                                           placeholder="Nama bank">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        No Rekening
                                    </label>
                                    <input type="text" name="account_no" id="account_no"
                                           class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50"
                                           placeholder="Nomor rekening">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        Atas Nama
                                    </label>
                                    <input type="text" name="account_name" id="account_name"
                                           class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50"
                                           placeholder="Nama pemilik">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                                        Status
                                    </label>
                                    <select name="status" id="status"
                                            class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[12px] md:text-sm text-slate-900 dark:text-slate-50">
                                        <option value="draft">Draft</option>
                                        <option value="submitted">Submitted</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Kolom 3: ringkasan angka --}}
                        <div class="space-y-2">
                            <div class="bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2.5">
                                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 mb-1">
                                    <span>Subtotal</span>
                                    <span id="pv-subtotal-label" class="font-mono text-[11px] text-slate-900 dark:text-slate-50">Rp 0</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 mb-1">
                                    <span>PPN (%)</span>
                                    <div class="flex items-center gap-1.5">
                                        <input type="text" name="tax_percent" id="tax_percent"
                                               class="w-16 px-2 py-1 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 text-[11px] text-right text-slate-900 dark:text-slate-50"
                                               value="0">
                                        <span class="text-[11px]">%</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 mb-1">
                                    <span>PPN (Rp)</span>
                                    <span id="pv-tax-label" class="font-mono text-[11px] text-slate-900 dark:text-slate-50">Rp 0</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-slate-600 dark:text-slate-200 font-semibold border-t border-dashed border-slate-200 dark:border-slate-700 pt-2 mt-1">
                                    <span>Total Voucher</span>
                                    <span id="pv-total-label" class="font-mono text-[11px] text-emerald-600 dark:text-emerald-400">Rp 0</span>
                                </div>
                            </div>

                            <p class="text-[10px] text-slate-400 dark:text-slate-500">
                                Volume & harga diambil dari RAPP. Anda masih bisa menyesuaikan sesuai realisasi.
                            </p>
                        </div>
                    </div>

                    {{-- TABEL ITEM YANG AKAN MASUK VOUCHER --}}
                    <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                        <div class="px-3 py-2 bg-slate-50 dark:bg-slate-900/70 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <div class="text-[11px] font-medium text-slate-700 dark:text-slate-200">
                                Item RAPP untuk Voucher
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button"
                                        onclick="syncSelectedItemsToVoucher()"
                                        class="px-2.5 py-1 border border-slate-200 dark:border-slate-700 rounded-md text-[11px] text-slate-600 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                                    Refresh dari pilihan RAPP
                                </button>
                            </div>
                        </div>
                        <div class="max-h-[50vh] overflow-y-auto">
                            <table class="w-full text-[11px]">
                                <thead class="bg-slate-50 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-slate-500 dark:text-slate-300">Item</th>
                                        <th class="px-3 py-2 text-right font-medium text-slate-500 dark:text-slate-300">Sisa</th>
                                        <th class="px-3 py-2 text-right font-medium text-slate-500 dark:text-slate-300">Qty</th>
                                        <th class="px-3 py-2 text-right font-medium text-slate-500 dark:text-slate-300">Harga</th>
                                        <th class="px-3 py-2 text-right font-medium text-slate-500 dark:text-slate-300">Jumlah</th>
                                        <th class="px-3 py-2 text-center font-medium text-slate-500 dark:text-slate-300">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="pv-items-body" class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                {{-- Baris akan di-generate via JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- FOOTER --}}
                <div class="px-4 md:px-6 py-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-900/80 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="text-[10px] md:text-[11px] text-slate-500 dark:text-slate-400">
                        Pastikan volume voucher tidak melebihi sisa volume item di RAPP. Sistem akan menolak jika melebihi.
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button"
                                onclick="closePurchaseVoucherModal()"
                                class="px-3 py-1.5 border border-slate-200 dark:border-slate-700 rounded-lg text-[11px] text-slate-600 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                            Batal
                        </button>
                        <button type="submit"
                                id="btn-submit-pv"
                                class="px-3 md:px-4 py-1.5 md:py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] md:text-sm font-semibold">
                            Simpan Voucher
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@include('dev.vendors.quick-modal', ['project' => $project])

<script>
    function rupiah(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num || 0);
    }

    function formatDecimalID(value, decimals = 2) {
        if (value === null || value === undefined || value === '') {
            value = 0;
        }
        const num = Number(value) || 0;
        return num.toLocaleString('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }

    function clearAllSelections() {
        const checkboxes = document.querySelectorAll('.row-item-checkbox');
        checkboxes.forEach(cb => cb.checked = false);
        updateVoucherActionBar();
        syncSelectedItemsToVoucher();
    }

    function openPurchaseVoucherModal() {
        const modal = document.getElementById('purchaseVoucherModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
        updateVoucherSourceUI();
        syncSelectedItemsToVoucher();
    }

    function closePurchaseVoucherModal() {
        const modal = document.getElementById('purchaseVoucherModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function syncSelectedItemsToVoucher() {
        const body = document.getElementById('pv-items-body');
        if (!body) return;

        body.innerHTML = '';

        const selected = document.querySelectorAll('.row-item-checkbox:checked');
        selected.forEach((cb, index) => {
            const id         = cb.dataset.itemId;
            const name       = cb.dataset.itemName || '-';
            const satuan     = cb.dataset.itemSatuan || '-';
            const hargaRAPP  = Number(cb.dataset.itemHargaRAPP || 0);
            const sisaVol    = Number(cb.dataset.itemSisaVol || 0);
            const sisaAmount = Number(cb.dataset.itemSisaAmount || 0);

            const volDefault = sisaVol;
            const hargaVoucherDefault = hargaRAPP;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-3 py-2 align-top">
                    <div class="text-[11px] font-medium text-slate-900 dark:text-slate-50">
                        ${name}
                    </div>
                    <div class="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">
                        ${satuan} &bull; RAPP ${rupiah(hargaRAPP)}
                    </div>
                    <input type="hidden" name="items[${index}][rab_item_id]" value="${id}">
                </td>
                <td class="px-3 py-2 text-right align-top">
                    <div class="text-[11px] text-slate-800 dark:text-slate-100">
                        ${formatDecimalID(sisaVol, 2)}
                    </div>
                    <div class="text-[10px] text-slate-500 dark:text-slate-400">
                        ${rupiah(sisaAmount)}
                    </div>
                </td>
                <td class="px-3 py-2 text-right align-top">
                    <input type="text"
                           class="pv-vol w-20 text-right border border-slate-200 dark:border-slate-700 rounded-md px-1.5 py-1 text-[11px] bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-50"
                           data-item-id="${id}"
                           data-max="${sisaVol}"
                           value="${formatDecimalID(volDefault, 2)}"
                           name="items[${index}][qty]"
                           inputmode="decimal">
                </td>
                <td class="px-3 py-2 text-right align-top">
                    <input type="text"
                           class="pv-price w-24 text-right border border-slate-200 dark:border-slate-700 rounded-md px-1.5 py-1 text-[11px] bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-50"
                           data-item-id="${id}"
                           data-rapp-price="${hargaRAPP}"
                           value="${formatDecimalID(hargaVoucherDefault, 2)}"
                           name="items[${index}][price]">
                    <span class="pv-over-badge hidden ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200"
                          title="Harga vendor lebih tinggi dari harga RAPP">
                        !
                    </span>
                </td>
                <td class="px-3 py-2 text-right align-top">
                    <span class="pv-row-total text-[11px] font-medium text-slate-900 dark:text-slate-50">
                        ${rupiah(0)}
                    </span>
                </td>
                <td class="px-3 py-2 text-center align-top">
                    <button type="button"
                            class="btn-remove-pv-row text-[11px] text-red-500 hover:text-red-700">
                        Hapus
                    </button>
                </td>
            `;

            body.appendChild(tr);
        });

        bindVoucherInputEvents();
        updateVoucherTotals();
    }

    function bindVoucherInputEvents() {
        const volInputs   = document.querySelectorAll('.pv-vol');
        const priceInputs = document.querySelectorAll('.pv-price');
        const removeBtns  = document.querySelectorAll('.btn-remove-pv-row');

        volInputs.forEach(input => {
            input.addEventListener('input', () => {
                sanitizeVoucherNumberInput(input, 2);
                enforceMaxVolume(input);
                updateVoucherTotals();
            });
        });

        priceInputs.forEach(input => {
            input.addEventListener('input', () => {
                sanitizeVoucherNumberInput(input, 2);
                updateVoucherTotals();
            });
        });

        removeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tr = btn.closest('tr');
                if (tr) tr.remove();
                updateVoucherTotals();
            });
        });
    }

    function sanitizeVoucherNumberInput(input, decimals = 2) {
        let value = input.value || '';
        value = value.replace(/[^\d.,-]/g, '');
        const parts = value.split(',');
        if (parts.length > 2) {
            value = parts[0] + ',' + parts.slice(1).join('');
        }
        const decimalIndex = value.indexOf(',');
        if (decimalIndex !== -1) {
            const whole = value.slice(0, decimalIndex).replace(/[.,]/g, '');
            let fraction = value.slice(decimalIndex + 1).replace(/[.,]/g, '');
            fraction = fraction.slice(0, decimals);
            value = whole + ',' + fraction;
        } else {
            value = value.replace(/[.]/g, '');
        }
        input.value = value;
    }

    function parseIdNumber(value) {
        if (value === null || value === undefined || value === '') return 0;
        if (typeof value === 'number') return value;

        let s = String(value).trim();
        s = s.replace(/[^\d,.-]/g, '');
        s = s.replace(/\./g, '');
        s = s.replace(',', '.');

        const num = parseFloat(s);
        return isNaN(num) ? 0 : num;
    }

    function enforceMaxVolume(input) {
        const max = parseFloat(input.dataset.max || '0');
        let val = parseIdNumber(input.value);

        if (val > max) {
            alert('Volume voucher melebihi sisa volume (max: ' + max.toFixed(2) + ').');
            val = max;
        } else if (val < 0) {
            val = 0;
        }

        input.value = formatDecimalID(val, 2);
    }

    function updateVoucherTotals() {
        const rows = document.querySelectorAll('#pv-items-body tr');
        let subtotal = 0;

        rows.forEach(row => {
            const volInput   = row.querySelector('.pv-vol');
            const priceInput = row.querySelector('.pv-price');
            const totalEl    = row.querySelector('.pv-row-total');

            const qty   = parseIdNumber(volInput ? volInput.value : 0);
            const price = parseIdNumber(priceInput ? priceInput.value : 0);
            const amount = qty * price;

            subtotal += amount;

            if (totalEl) {
                totalEl.textContent = rupiah(amount);
            }

            // === Badge over-cost (harga vendor > harga RAPP) ===
            if (priceInput) {
                const rappPrice = parseFloat(priceInput.dataset.rappPrice || '0');
                const badge = row.querySelector('.pv-over-badge');
                if (badge && rappPrice > 0 && price > rappPrice) {
                    const diffPct = ((price - rappPrice) / rappPrice) * 100;

                    badge.classList.remove('hidden');
                    badge.classList.remove('bg-amber-100', 'text-amber-700', 'bg-red-100', 'text-red-700');

                    if (diffPct > 20) {
                        badge.classList.add('bg-red-100', 'text-red-700');
                    } else {
                        badge.classList.add('bg-amber-100', 'text-amber-700');
                    }
                } else if (badge) {
                    badge.classList.add('hidden');
                }
            }
        });

        const taxInput   = document.getElementById('tax_percent');
        const taxPercent = parseIdNumber(taxInput ? taxInput.value : 0);
        const taxAmount  = subtotal * (taxPercent / 100);
        const total      = subtotal + taxAmount;

        const subLabel = document.getElementById('pv-subtotal-label');
        const taxLabel = document.getElementById('pv-tax-label');
        const totLabel = document.getElementById('pv-total-label');

        if (subLabel) subLabel.textContent = rupiah(subtotal);
        if (taxLabel) taxLabel.textContent = rupiah(taxAmount);
        if (totLabel) totLabel.textContent = rupiah(total);
    }

    function updateVoucherSourceUI() {
        const typeSelect = document.getElementById('pv-source-type');
        const poSelect = document.getElementById('pv-po-id');
        const lpbSelect = document.getElementById('pv-lpb-id');
        const vendorInput = document.getElementById('vendor_name');
        const submitBtn = document.getElementById('btn-submit-pv');
        if (!typeSelect || !poSelect || !lpbSelect || !vendorInput) return;

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
        vendorInput.value = vendor;

        if (submitBtn) {
            submitBtn.disabled = !activeSelect.value;
            submitBtn.classList.toggle('opacity-50', submitBtn.disabled);
            submitBtn.classList.toggle('cursor-not-allowed', submitBtn.disabled);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateVoucherActionBar();
        updateVoucherSourceUI();

        const typeSelect = document.getElementById('pv-source-type');
        const poSelect = document.getElementById('pv-po-id');
        const lpbSelect = document.getElementById('pv-lpb-id');
        if (typeSelect) typeSelect.addEventListener('change', updateVoucherSourceUI);
        if (poSelect) poSelect.addEventListener('change', updateVoucherSourceUI);
        if (lpbSelect) lpbSelect.addEventListener('change', updateVoucherSourceUI);

        const form = document.getElementById('purchaseVoucherForm');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('btn-submit-pv');
            if (submitBtn) submitBtn.disabled = true;

            if (typeof window.showRAPPLoading === 'function') {
                window.showRAPPLoading();
            }

            try {
                const url   = @json(route('dev.rab-baseline.purchase-vouchers.store', ['projectId' => $project->id, 'rabId' => $rab->id]));
                const token = @json(csrf_token());
                const fd    = new FormData(form);

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: fd,
                });

                const result = await res.json().catch(() => null);

                if (!res.ok || !result || !result.status) {
                    throw new Error(result && result.message ? result.message : 'Gagal menyimpan voucher.');
                }

                if (typeof window.hideRAPPLoading === 'function') {
                    window.hideRAPPLoading();
                }

                window.location.reload();
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan: ' + (err && err.message ? err.message : err));
                const submitBtn = document.getElementById('btn-submit-pv');
                if (submitBtn) submitBtn.disabled = false;

                if (typeof window.hideRAPPLoading === 'function') {
                    window.hideRAPPLoading();
                }
            }
        });
    });
</script>






