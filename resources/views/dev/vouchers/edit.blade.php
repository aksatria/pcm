{{-- resources/views/dev/vouchers/edit.blade.php --}}
@extends('layouts.dev')

@section('title', 'Edit Voucher - ' . $voucher->voucher_number)

@section('styles')
<style>
  .is-invalid { border-color: #ef4444; }
</style>
@endsection

@section('content')
<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
    <div>
      <h1 class="text-lg font-semibold">Edit Voucher</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">Voucher: {{ $voucher->voucher_number }}</p>
    </div>
    <a href="{{ route('dev.vouchers.show', ['projectId' => $project->id, 'id' => $voucher->id]) }}"
       class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
      Kembali ke Detail
    </a>
  </div>

  <form action="{{ route('dev.vouchers.update', ['projectId' => $project->id, 'id' => $voucher->id]) }}" method="POST" id="voucherForm" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
        <h2 class="text-sm font-semibold mb-4">Informasi Voucher</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Nomor Voucher *</label>
            <input type="text" name="voucher_number" value="{{ old('voucher_number', $voucher->voucher_number) }}" required
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal *</label>
            <input type="date" name="tanggal" value="{{ old('tanggal', $voucher->tanggal->format('Y-m-d')) }}" required
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Metode Pembayaran *</label>
            <select class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800" name="pembayaran" required id="pembayaranSelect">
              <option value="cash" {{ old('pembayaran', $voucher->pembayaran) == 'cash' ? 'selected' : '' }}>Cash</option>
              <option value="transfer" {{ old('pembayaran', $voucher->pembayaran) == 'transfer' ? 'selected' : '' }}>Transfer</option>
              <option value="tempo" {{ old('pembayaran', $voucher->pembayaran) == 'tempo' ? 'selected' : '' }}>Tempo</option>
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Jatuh Tempo</label>
            <input type="date" name="jatuh_tempo" id="jatuhTempo"
                   value="{{ old('jatuh_tempo', $voucher->jatuh_tempo ? $voucher->jatuh_tempo->format('Y-m-d') : '') }}"
                   {{ $voucher->pembayaran == 'tempo' ? '' : 'disabled' }}
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800">
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
                      data-no-rekening="{{ $vendor->no_rekening }}"
                      {{ old('vendor_id', $voucher->vendor_id) == $vendor->id ? 'selected' : '' }}>
                {{ $vendor->project_id ? '[Proyek]' : '[Global]' }} {{ $vendor->nama }} - {{ $vendor->perusahaan }}
              </option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tujuan Transfer</label>
            <input type="text" name="tujuan_transfer" value="{{ old('tujuan_transfer', $voucher->tujuan_transfer) }}"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800"
                   placeholder="Keterangan tujuan transfer">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Bank</label>
            <input type="text" name="bank" id="bankInput" value="{{ old('bank', $voucher->bank) }}"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800"
                   placeholder="Nama bank">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Nama Rekening</label>
            <input type="text" name="nama_rekening" id="namaRekeningInput" value="{{ old('nama_rekening', $voucher->nama_rekening) }}"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800"
                   placeholder="Nama pemilik rekening">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No. Rekening</label>
            <input type="text" name="no_rekening" id="noRekeningInput" value="{{ old('no_rekening', $voucher->no_rekening) }}"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800"
                   placeholder="Nomor rekening">
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl">
      <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <div>
          <h2 class="text-sm font-semibold">Items Voucher</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $voucher->items->count() }} item</p>
        </div>
      </div>
      <div class="overflow-x-auto">
        @if($voucher->items->count() > 0)
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800/70">
              <tr class="text-gray-600 dark:text-gray-300">
                <th class="px-3 py-2 text-left w-10">#</th>
                <th class="px-3 py-2 text-left w-28">Kode</th>
                <th class="px-3 py-2 text-left">Uraian</th>
                <th class="px-3 py-2 text-left w-20">Sat</th>
                <th class="px-3 py-2 text-right w-24">Qty</th>
                <th class="px-3 py-2 text-right w-28">Harga</th>
                <th class="px-3 py-2 text-right w-28">Total</th>
                <th class="px-3 py-2 text-center w-16">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              @foreach($voucher->items as $item)
                <tr class="selected-item">
                  <td class="px-3 py-2">{{ $loop->iteration }}</td>
                  <td class="px-3 py-2"><div class="font-medium">{{ $item->kode }}</div></td>
                  <td class="px-3 py-2">{{ $item->uraian }}</td>
                  <td class="px-3 py-2">{{ $item->satuan }}</td>
                  <td class="px-3 py-2 text-right">{{ number_format($item->qty, 2, ',', '.') }}</td>
                  <td class="px-3 py-2 text-right">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                  <td class="px-3 py-2 text-right">Rp {{ number_format($item->total_harga, 0, ',', '.') }}</td>
                  <td class="px-3 py-2 text-center">
                    <button type="button" class="px-2 py-1 rounded border border-red-200 text-red-600 text-xs hover:bg-red-50"
                            onclick="deleteItem({{ $item->id }})">Hapus</button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <div class="p-4 text-sm text-amber-700 bg-amber-50 border border-amber-200">
            Tidak ada items dalam voucher.
          </div>
        @endif
      </div>

      <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-800">
        <h3 class="text-sm font-semibold mb-3">Tambah Item Baru</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Pilih Item</label>
            <select class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800" id="newItemSelect">
              <option value="">-- Pilih Item --</option>
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Qty</label>
            <input type="number" step="0.0001" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800" id="newItemQty" min="0.0001">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga Satuan</label>
            <input type="number" step="0.01" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800" id="newItemHarga" min="0">
          </div>
          <div class="flex items-end">
            <button type="button" class="w-full px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm" onclick="addNewItem()">Tambah</button>
          </div>
        </div>
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
                <option value="{{ $nama }}" {{ old('diajukan_oleh', $voucher->diajukan_oleh) == $nama ? 'selected' : '' }}>
                  {{ $nama }} ({{ $jabatan }})
                </option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Disetujui Oleh</label>
            <select class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800" name="disetujui_oleh">
              <option value="">-- Pilih --</option>
              @foreach($approvalPersons as $nama => $jabatan)
                <option value="{{ $nama }}" {{ old('disetujui_oleh', $voucher->disetujui_oleh) == $nama ? 'selected' : '' }}>
                  {{ $nama }} ({{ $jabatan }})
                </option>
              @endforeach
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Keterangan</label>
            <textarea class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800" name="keterangan" rows="3">{{ old('keterangan', $voucher->keterangan) }}</textarea>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
        <h2 class="text-sm font-semibold mb-4">Ringkasan</h2>
        <div class="space-y-3 text-sm">
          <div class="flex items-center justify-between">
            <span class="text-gray-600 dark:text-gray-300">PPN</span>
            <input type="number" step="0.01" class="w-32 px-2 py-1 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-right" name="ppn" value="{{ old('ppn', $voucher->ppn) }}">
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-600 dark:text-gray-300">Ongkir</span>
            <input type="number" step="0.01" class="w-32 px-2 py-1 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-right" name="ongkir" value="{{ old('ongkir', $voucher->ongkir) }}">
          </div>
          <div class="border-t border-gray-200 dark:border-gray-800"></div>
          <div class="flex items-center justify-between font-semibold text-emerald-600 dark:text-emerald-400">
            <span>Total Bayar</span>
            <span>Rp {{ number_format($voucher->total_bayar, 0, ',', '.') }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex items-center justify-end gap-2">
      <a href="{{ route('dev.vouchers.show', ['projectId' => $project->id, 'id' => $voucher->id]) }}"
         class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">Batal</a>
      <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">Simpan Perubahan</button>
    </div>
  </form>
</div>

@include('dev.vendors.quick-modal', ['project' => $project])
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize vendor select
        const vendorSelect = document.getElementById('vendorSelect');
        if (vendorSelect) {
            vendorSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    document.getElementById('bankInput').value = selectedOption.dataset.bank || '';
                    document.getElementById('namaRekeningInput').value = selectedOption.dataset.namaRekening || '';
                    document.getElementById('noRekeningInput').value = selectedOption.dataset.noRekening || '';
                }
            });

            // Trigger change on page load if vendor is selected
            if (vendorSelect.value) {
                vendorSelect.dispatchEvent(new Event('change'));
            }
        }

        // Toggle jatuh tempo based on pembayaran
        const pembayaranSelect = document.getElementById('pembayaranSelect');
        const jatuhTempoInput = document.getElementById('jatuhTempo');

        pembayaranSelect.addEventListener('change', function() {
            if (this.value === 'tempo') {
                jatuhTempoInput.disabled = false;
                jatuhTempoInput.required = true;
                if (!jatuhTempoInput.value) {
                    const today = new Date();
                    today.setDate(today.getDate() + 30);
                    jatuhTempoInput.value = today.toISOString().split('T')[0];
                }
            } else {
                jatuhTempoInput.disabled = true;
                jatuhTempoInput.required = false;
            }
        });

        // Trigger on page load
        pembayaranSelect.dispatchEvent(new Event('change'));

        // Budget Control removed
    });

    // Budget Control removed

    function addNewItem() {
        const select = document.getElementById('newItemSelect');
        if (!select.value) {
            alert('Pilih item terlebih dahulu');
            return;
        }

        const item = JSON.parse(select.options[select.selectedIndex].dataset.item);
        const qty = parseFloat(document.getElementById('newItemQty').value) || 0;
        const hargaSatuan = parseFloat(document.getElementById('newItemHarga').value) || 0;

        if (qty <= 0) {
            alert('Quantity harus lebih dari 0');
            return;
        }

        if (qty > item.available_qty) {
            alert(`Quantity tidak boleh melebihi ${item.available_qty}`);
            return;
        }

        const formData = new FormData();
        formData.append('budget_control_id', item.id);
        formData.append('qty', qty);
        formData.append('harga_satuan', hargaSatuan);

        fetch(`{{ route('dev.vouchers.items.store', ['projectId' => $project->id, 'id' => $voucher->id]) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal menambahkan item: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        });
    }

    function deleteItem(itemId) {
        if (confirm('Hapus item ini dari voucher?')) {
            fetch(`{{ route('dev.vouchers.items.destroy', ['projectId' => $project->id, 'id' => $voucher->id, 'itemId' => '']) }}/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Gagal menghapus item: ' + data.message);
                }
            });
        }
    }
</script>
@endsection
