{{-- resources/views/dev/vouchers/create.blade.php --}}
@extends('layouts.dev')

@section('title', 'Buat Voucher Baru - ' . $project->name)

@section('styles')
<style>
  .is-invalid { border-color: #ef4444; }
</style>
@endsection

@section('content')
<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
    <div>
      <h1 class="text-lg font-semibold">Buat Voucher Baru</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">Proyek: {{ $project->name }}</p>
    </div>
    <a href="{{ route('dev.vouchers.index', $project->id) }}"
       class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
      Kembali
    </a>
  </div>

  <form action="{{ route('dev.vouchers.store', $project->id) }}" method="POST" id="voucherForm" class="space-y-6">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
        <h2 class="text-sm font-semibold mb-4">Informasi Voucher</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Nomor Voucher *</label>
            <input type="text" name="voucher_number" value="{{ $voucherNumber }}" required readonly
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Otomatis digenerate</p>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal *</label>
            <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Metode Pembayaran *</label>
            <select name="pembayaran" required
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
              <option value="cash">Cash</option>
              <option value="transfer" selected>Transfer</option>
              <option value="tempo">Tempo</option>
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Jatuh Tempo</label>
            <input type="date" name="jatuh_tempo" id="jatuhTempo" disabled
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
        <h2 class="text-sm font-semibold mb-4">Informasi Vendor</h2>
        <div class="space-y-4 text-sm">
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300">Pilih Vendor</label>
              <button type="button" onclick="openVendorModal()" class="text-[10px] text-sky-600 dark:text-sky-400 hover:underline">
                Tambah Vendor
              </button>
            </div>
            <select class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800" name="vendor_id" id="vendorSelect" data-vendor-select="1">
              <option value="">-- Pilih Vendor --</option>
              @foreach($vendors as $vendor)
                <option value="{{ $vendor->id }}"
                        data-bank="{{ $vendor->bank }}"
                        data-nama-rekening="{{ $vendor->nama_rekening }}"
                        data-no-rekening="{{ $vendor->no_rekening }}">
                  {{ $vendor->project_id ? '[Proyek]' : '[Global]' }} {{ $vendor->nama }} - {{ $vendor->perusahaan }}
                </option>
              @endforeach
            </select>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Atau isi manual di bawah</p>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tujuan Transfer</label>
            <input type="text" name="tujuan_transfer" placeholder="Keterangan tujuan transfer"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Bank</label>
            <input type="text" name="bank" id="bankInput" placeholder="Nama bank"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Nama Rekening</label>
            <input type="text" name="nama_rekening" id="namaRekeningInput" placeholder="Nama pemilik rekening"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No. Rekening</label>
            <input type="text" name="no_rekening" id="noRekeningInput" placeholder="Nomor rekening"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl">
      <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <div>
          <h2 class="text-sm font-semibold">Items Terpilih</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ count($selectedItems) }} item</p>
        </div>
      </div>
      <div class="overflow-x-auto">
        @if(count($selectedItems) > 0)
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800/70">
              <tr class="text-gray-600 dark:text-gray-300">
                <th class="px-3 py-2 text-left w-10">#</th>
                <th class="px-3 py-2 text-left w-28">Kode</th>
                <th class="px-3 py-2 text-left">Uraian</th>
                <th class="px-3 py-2 text-left w-24">Satuan</th>
                <th class="px-3 py-2 text-right w-28">Vol Rencana</th>
                <th class="px-3 py-2 text-right w-32">Harga Rencana</th>
                <th class="px-3 py-2 text-right w-28">Qty</th>
                <th class="px-3 py-2 text-right w-32">Harga</th>
                <th class="px-3 py-2 text-right w-32">Total</th>
                <th class="px-3 py-2 text-center w-16">Aksi</th>
              </tr>
            </thead>
            <tbody id="itemsContainer" class="divide-y divide-gray-100 dark:divide-gray-800">
              @foreach($selectedItems as $index => $item)
                <tr class="selected-item hover:bg-gray-50 dark:hover:bg-gray-800/60" data-item-id="{{ $item['id'] }}">
                  <td class="px-3 py-2">{{ $index + 1 }}</td>
                  <td class="px-3 py-2">
                    <div class="font-medium">{{ $item['kode'] }}</div>
                    <input type="hidden" name="items[{{ $index }}][budget_control_id]" value="{{ $item['id'] }}">
                  </td>
                  <td class="px-3 py-2">{{ $item['uraian'] }}</td>
                  <td class="px-3 py-2">{{ $item['satuan'] }}</td>
                  <td class="px-3 py-2 text-right">{{ number_format($item['volume_plan'], 2, ',', '.') }}</td>
                  <td class="px-3 py-2 text-right">Rp {{ number_format($item['harga_satuan_plan'], 0, ',', '.') }}</td>
                  <td class="px-3 py-2">
                    <input type="number" step="0.0001"
                           name="items[{{ $index }}][qty]"
                           class="qty-input w-24 px-2 py-1 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-right"
                           value="{{ min($item['available_qty'], 1) }}"
                           max="{{ $item['available_qty'] }}"
                           min="0.0001"
                           data-available="{{ $item['available_qty'] }}"
                           onchange="calculateItemTotal(this, {{ $index }})"
                           required>
                    <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
                      Max: {{ number_format($item['available_qty'], 2, ',', '.') }}
                      @if($item['is_over_budget'])
                        <span class="text-red-500">(Over Budget!)</span>
                      @endif
                    </div>
                  </td>
                  <td class="px-3 py-2">
                    <input type="number" step="0.01"
                           name="items[{{ $index }}][harga_satuan]"
                           class="price-input w-28 px-2 py-1 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-right"
                           value="{{ $item['harga_satuan_plan'] }}"
                           onchange="calculateItemTotal(this, {{ $index }})"
                           required>
                  </td>
                  <td class="px-3 py-2 text-right">
                    <input type="hidden" name="items[{{ $index }}][total_harga]" class="total-input" value="0">
                    <span class="item-total" id="itemTotal{{ $index }}">Rp 0</span>
                  </td>
                  <td class="px-3 py-2 text-center">
                    <button type="button" class="px-2 py-1 rounded border border-red-200 text-red-600 text-xs hover:bg-red-50"
                            onclick="removeItem(this)">Hapus</button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <div class="p-4 text-sm text-amber-700 bg-amber-50 border border-amber-200">
            Tidak ada items yang terpilih. Silakan pilih items terlebih dahulu di Control Budget.
          </div>
        @endif
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
        <h2 class="text-sm font-semibold mb-4">Informasi Tambahan</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Diajukan Oleh</label>
            <select class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800" name="diajukan_oleh">
              <option value="">-- Pilih --</option>
              @foreach($approvalPersons as $nama => $jabatan)
                <option value="{{ $nama }}">{{ $nama }} ({{ $jabatan }})</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Disetujui Oleh</label>
            <select class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800" name="disetujui_oleh">
              <option value="">-- Pilih --</option>
              @foreach($approvalPersons as $nama => $jabatan)
                <option value="{{ $nama }}">{{ $nama }} ({{ $jabatan }})</option>
              @endforeach
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Keterangan</label>
            <textarea class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800" name="keterangan" rows="3" placeholder="Catatan tambahan untuk voucher..."></textarea>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
        <h2 class="text-sm font-semibold mb-4">Ringkasan</h2>
        <div class="space-y-3 text-sm">
          <div class="flex items-center justify-between">
            <span class="text-gray-600 dark:text-gray-300">PPN</span>
            <input type="number" step="0.01" name="ppn" id="ppnInput" value="0"
                   class="w-32 px-2 py-1 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-right"
                   onchange="calculateTotals()">
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-600 dark:text-gray-300">Ongkir</span>
            <input type="number" step="0.01" name="ongkir" id="ongkirInput" value="0"
                   class="w-32 px-2 py-1 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-right"
                   onchange="calculateTotals()">
          </div>
          <div class="border-t border-gray-200 dark:border-gray-800"></div>
          <div class="flex items-center justify-between font-semibold">
            <span>Total Tagihan</span>
            <span id="totalTagihanDisplay">Rp 0</span>
            <input type="hidden" name="total_tagihan" id="totalTagihan" value="0">
          </div>
          <div class="flex items-center justify-between font-semibold text-emerald-600 dark:text-emerald-400">
            <span>Total Bayar</span>
            <span id="totalBayarDisplay">Rp 0</span>
            <input type="hidden" name="total_bayar" id="totalBayar" value="0">
          </div>
        </div>
      </div>
    </div>

    <div class="flex items-center justify-between">
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" id="saveAsDraft" name="save_as_draft" class="rounded border-gray-300 dark:border-gray-700">
        Simpan sebagai Draft
      </label>
      <div class="flex gap-2">
        <a href="{{ route('dev.vouchers.index', $project->id) }}"
           class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">Batal</a>
        <button type="submit"
                class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">Simpan Voucher</button>
      </div>
    </div>
  </form>
</div>

@include('dev.vendors.quick-modal', ['project' => $project])
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize vendor select
        document.getElementById('vendorSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                document.getElementById('bankInput').value = selectedOption.dataset.bank || '';
                document.getElementById('namaRekeningInput').value = selectedOption.dataset.namaRekening || '';
                document.getElementById('noRekeningInput').value = selectedOption.dataset.noRekening || '';
            }
        });

        // Toggle jatuh tempo based on pembayaran
        document.querySelector('select[name="pembayaran"]').addEventListener('change', function() {
            const jatuhTempoInput = document.getElementById('jatuhTempo');
            if (this.value === 'tempo') {
                jatuhTempoInput.disabled = false;
                jatuhTempoInput.required = true;
                // Set default jatuh tempo to 30 days from today
                const today = new Date();
                today.setDate(today.getDate() + 30);
                jatuhTempoInput.value = today.toISOString().split('T')[0];
            } else {
                jatuhTempoInput.disabled = true;
                jatuhTempoInput.required = false;
                jatuhTempoInput.value = '';
            }
        });

        // Calculate initial totals
        calculateAllItemTotals();
        calculateTotals();
    });

    function calculateItemTotal(input, index) {
        const row = input.closest('tr');
        const qtyInput = row.querySelector('.qty-input');
        const priceInput = row.querySelector('.price-input');
        const totalSpan = document.getElementById(`itemTotal${index}`);
        const totalInput = row.querySelector('.total-input');

        const qty = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const total = qty * price;

        // Update display
        totalSpan.textContent = 'Rp ' + formatNumber(total);
        totalInput.value = total;

        // Check if qty exceeds available
        const maxQty = parseFloat(qtyInput.dataset.available) || 0;
        if (qty > maxQty) {
            qtyInput.classList.add('is-invalid');
            qtyInput.setCustomValidity(`Quantity tidak boleh melebihi ${maxQty}`);
        } else {
            qtyInput.classList.remove('is-invalid');
            qtyInput.setCustomValidity('');
        }

        // Recalculate grand totals
        calculateTotals();
    }

    function calculateAllItemTotals() {
        const items = document.querySelectorAll('.selected-item');
        items.forEach((row, index) => {
            const qtyInput = row.querySelector('.qty-input');
            const priceInput = row.querySelector('.price-input');
            const totalSpan = document.getElementById(`itemTotal${index}`);
            const totalInput = row.querySelector('.total-input');

            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const total = qty * price;

            totalSpan.textContent = 'Rp ' + formatNumber(total);
            totalInput.value = total;
        });
    }

    function calculateTotals() {
        let totalTagihan = 0;

        // Sum all item totals
        document.querySelectorAll('.total-input').forEach(input => {
            totalTagihan += parseFloat(input.value) || 0;
        });

        // Add PPN and Ongkir
        const ppn = parseFloat(document.getElementById('ppnInput').value) || 0;
        const ongkir = parseFloat(document.getElementById('ongkirInput').value) || 0;
        const totalBayar = totalTagihan + ppn + ongkir;

        // Update displays
        document.getElementById('totalTagihan').value = totalTagihan;
        document.getElementById('totalTagihanDisplay').textContent = 'Rp ' + formatNumber(totalTagihan);

        document.getElementById('totalBayar').value = totalBayar;
        document.getElementById('totalBayarDisplay').textContent = 'Rp ' + formatNumber(totalBayar);
    }

    function removeItem(button) {
        const row = button.closest('tr');
        if (confirm('Hapus item ini dari voucher?')) {
            row.remove();
            renumberItems();
            calculateTotals();
        }
    }

    function renumberItems() {
        const rows = document.querySelectorAll('#itemsContainer tr');
        rows.forEach((row, index) => {
            // Update row number
            row.querySelector('td:first-child').textContent = index + 1;

            // Update input names
            const inputs = row.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                const name = input.name;
                if (name.includes('items[')) {
                    input.name = name.replace(/items\[\d+\]/, `items[${index}]`);
                }
            });

            // Update item total ID
            const totalSpan = row.querySelector('.item-total');
            if (totalSpan) {
                totalSpan.id = `itemTotal${index}`;
            }
        });
    }

    function formatNumber(num) {
        return num.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Form validation
    document.getElementById('voucherForm').addEventListener('submit', function(e) {
        const itemCount = document.querySelectorAll('.selected-item').length;
        if (itemCount === 0) {
            e.preventDefault();
            alert('Minimal 1 item harus ditambahkan ke voucher');
            return false;
        }

        // Validate quantities
        let isValid = true;
        document.querySelectorAll('.qty-input').forEach(input => {
            const maxQty = parseFloat(input.dataset.available) || 0;
            const qty = parseFloat(input.value) || 0;

            if (qty > maxQty) {
                input.classList.add('is-invalid');
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Beberapa quantity melebihi batas yang tersedia. Silakan periksa kembali.');
        }
    });
</script>
@endsection
