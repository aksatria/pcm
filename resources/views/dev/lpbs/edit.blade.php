@extends('layouts.dev')
@section('title', 'Edit LPB')
@section('content')
@php
  $projectName = $project->name ?? 'Proyek';
  $projectCode = $project->code ?? '';
  $rabName = $rab->name ?? ('RAPP #'.$rab->id);
@endphp
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&display=swap');
  .lpb-title { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; letter-spacing: -0.01em; }
  .lpb-kicker { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; }
</style>
<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div>
      <p class="lpb-kicker text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">LPB</p>
      <h1 class="lpb-title text-2xl font-semibold">Edit LPB</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Proyek: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $projectName }}</span>
        @if($projectCode)
          &bull; Kode: <span class="font-mono">{{ $projectCode }}</span>
        @endif
        &bull; RAPP: <span class="font-semibold">{{ $rabName }}</span>
      </p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="{{ route('dev.rab-baseline.lpbs.index', [$project->id, $rab->id]) }}" class="inline-flex items-center px-4 py-2 rounded-2xl border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
        Kembali ke Daftar
      </a>
      <button form="lpb-form" class="inline-flex items-center px-4 py-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white text-sm font-medium">
        Simpan Perubahan
      </button>
    </div>
  </div>

  <form id="lpb-form" method="post" action="{{ route('dev.rabs.lpbs.update', [$project->id, $rab->id, $lpb->id]) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 text-sm space-y-5">
      <div class="flex items-center gap-3 mb-1">
        <span class="h-8 w-8 rounded-full bg-blue-600 text-white text-xs font-semibold flex items-center justify-center">1</span>
        <div>
          <h2 class="lpb-title text-sm font-semibold text-blue-700 dark:text-blue-300">Informasi LPB</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">Perbarui pengiriman dan penerimaan.</p>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No LPB</label>
          <input name="lpb_no" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ old('lpb_no', $lpb->lpb_no) }}" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal LPB</label>
          <input type="date" name="lpb_date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ old('lpb_date', optional($lpb->lpb_date)->format('Y-m-d')) }}" />
        </div>
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300">Vendor</label>
            <button type="button" onclick="openVendorModal()" class="text-[10px] text-sky-600 dark:text-sky-400 hover:underline">
              Tambah Vendor
            </button>
          </div>
          <select name="vendor_id" data-vendor-select="1" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
            <option value="">-</option>
            @foreach($vendors as $v)
              <option value="{{ $v->id }}" @selected(old('vendor_id', $lpb->vendor_id) == $v->id)>{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">PO (wajib, approved)</label>
          <select name="purchase_order_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" required>
            <option value="">Pilih PO approved</option>
            @foreach($purchaseOrders as $po)
              <option value="{{ $po->id }}" @selected(old('purchase_order_id', $lpb->purchase_order_id) == $po->id)>{{ $po->po_no }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Dikirim Oleh</label>
          <input name="delivered_by" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ old('delivered_by', $lpb->delivered_by) }}" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Diterima Oleh</label>
          <input name="received_by" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ old('received_by', $lpb->received_by) }}" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Diketahui Oleh</label>
          <input name="known_by" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ old('known_by', $lpb->known_by) }}" />
        </div>
      </div>
      <div>
        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Catatan</label>
        <textarea name="notes" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" rows="3">{{ old('notes', $lpb->notes) }}</textarea>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 text-sm">
      <div class="flex items-center justify-between mb-3">
        <div>
          <h2 class="lpb-title text-sm font-semibold text-amber-700 dark:text-amber-300">Item LPB</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">Catat kedatangan barang.</p>
        </div>
        <button type="button" onclick="addRow('lpb-items','lpb-item-template')" class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-white text-xs hover:from-amber-600 hover:to-amber-700">Tambah</button>
      </div>
      <div id="lpb-items" class="space-y-3">
        @forelse($lpb->items as $idx => $it)
        <div class="item-row border border-gray-200 dark:border-gray-800 rounded-xl p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">RAPP Item</label>
            <select name="items[{{ $idx }}][rab_item_id]" class="rab-item-select w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
              @foreach($rabItems as $rit)
                @php
                  $unit = $rit->satuan ?? ($rit->data->satuan ?? '');
                  $price = $rit->harga_satuan ?? ($rit->data->harga_satuan ?? 0);
                @endphp
                <option value="{{ $rit->id }}" data-unit="{{ $unit }}" data-rapp-price="{{ $price }}" @selected($it->rab_item_id == $rit->id)>{{ $rit->data->kode ?? '' }} - {{ $rit->data->uraian ?? '' }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Qty</label>
            <input name="items[{{ $idx }}][qty]" value="{{ $it->qty }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga RAPP</label>
            <input class="rapp-price-display w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100" readonly />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Satuan</label>
            <input name="items[{{ $idx }}][unit]" value="{{ $it->unit }}" class="unit-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Tiba</label>
            <input type="date" name="items[{{ $idx }}][arrival_date]" value="{{ $it->arrival_date ? $it->arrival_date->format('Y-m-d') : '' }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Dok Referensi</label>
            <input name="items[{{ $idx }}][doc_reference]" value="{{ $it->doc_reference }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div class="flex items-end">
            <button type="button" class="remove-row px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Hapus</button>
          </div>
        </div>
        @empty
          <div class="text-sm text-gray-500 dark:text-gray-400">Belum ada item.</div>
        @endforelse
      </div>

      <template id="lpb-item-template">
        <div class="item-row border border-gray-200 dark:border-gray-800 rounded-xl p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">RAPP Item</label>
            <select data-name="items[__INDEX__][rab_item_id]" class="rab-item-select w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
              @foreach($rabItems as $it)
                @php
                  $unit = $it->satuan ?? ($it->data->satuan ?? '');
                  $price = $it->harga_satuan ?? ($it->data->harga_satuan ?? 0);
                @endphp
                <option value="{{ $it->id }}" data-unit="{{ $unit }}" data-rapp-price="{{ $price }}">{{ $it->data->kode ?? '' }} - {{ $it->data->uraian ?? '' }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Qty</label>
            <input data-name="items[__INDEX__][qty]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga RAPP</label>
            <input class="rapp-price-display w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100" readonly />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Satuan</label>
            <input data-name="items[__INDEX__][unit]" class="unit-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Tiba</label>
            <input type="date" data-name="items[__INDEX__][arrival_date]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Dok Referensi</label>
            <input data-name="items[__INDEX__][doc_reference]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div class="flex items-end">
            <button type="button" class="remove-row px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Hapus</button>
          </div>
        </div>
      </template>
    </div>

    <div class="flex gap-2">
      <button class="inline-flex items-center px-4 py-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white text-sm font-medium">Simpan</button>
      <a href="{{ route('dev.rab-baseline.lpbs.index', [$project->id, $rab->id]) }}" class="inline-flex items-center px-4 py-2 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700">Batal</a>
    </div>
  </form>
</div>

@include('dev.vendors.quick-modal', ['project' => $project])

<script>
function assignNamesInRow(row, idx) {
  if (!row) return;
  row.querySelectorAll('[data-name]').forEach(el => {
    el.name = el.getAttribute('data-name').replace(/__INDEX__/g, idx);
  });
}

function applyRabDefaults(row) {
  if (!row) return;
  const sel = row.querySelector('.rab-item-select');
  if (!sel) return;
  const opt = sel.options[sel.selectedIndex];
  const unit = opt ? opt.getAttribute('data-unit') || '' : '';
  const price = opt ? opt.getAttribute('data-rapp-price') || '' : '';
  const unitInput = row.querySelector('.unit-input');
  const priceInput = row.querySelector('.rapp-price-display');
  if (unitInput) unitInput.value = unit;
  if (priceInput) priceInput.value = price ? `Rp ${Number(price).toLocaleString('id-ID')}` : 'Rp 0';
}

function addRow(containerId, templateId) {
  const container = document.getElementById(containerId);
  const tpl = document.getElementById(templateId);
  if (!container || !tpl) return;
  const idx = container.querySelectorAll('.item-row').length;
  const clone = tpl.content.cloneNode(true);
  const row = clone.querySelector('.item-row');
  assignNamesInRow(row, idx);
  container.appendChild(clone);
  applyRabDefaults(row);
  recalcAll();
}

function parseNum(v) {
  if (v === null || v === undefined) return 0;
  const n = parseFloat(String(v).replace(/[^0-9.-]/g, ''));
  return isNaN(n) ? 0 : n;
}

function formatRupiahValue(value) {
  const num = Number(value || 0);
  return `Rp ${num.toLocaleString('id-ID', { maximumFractionDigits: 0 })}`;
}

function recalcPO() {
  const table = document.querySelector('table[data-calc="po"]');
  if (!table) return;
  let subtotal = 0;
  table.querySelectorAll('tbody tr').forEach(row => {
    const qty = parseNum(row.querySelector('[name*="[qty]"]')?.value);
    const price = parseNum(row.querySelector('[name*="[unit_price]"]')?.value);
    subtotal += qty * price;
  });
  const taxPercent = parseNum(document.querySelector('input[name="tax_percent"]')?.value);
  const shipping = parseNum(document.querySelector('input[name="shipping_cost"]')?.value);
  const tax = subtotal * (taxPercent / 100);
  const total = subtotal + tax + shipping;

  const subEl = document.getElementById('po-subtotal');
  const taxEl = document.getElementById('po-tax');
  const totEl = document.getElementById('po-total');
  if (subEl) subEl.value = formatRupiahValue(subtotal);
  if (taxEl) taxEl.value = formatRupiahValue(tax);
  if (totEl) totEl.value = formatRupiahValue(total);
}

function recalcSPK() {
  const table = document.querySelector('table[data-calc="spk"]');
  if (!table) return;
  let subtotal = 0;
  table.querySelectorAll('tbody tr').forEach(row => {
    const qty = parseNum(row.querySelector('[name*="[qty]"]')?.value);
    const price = parseNum(row.querySelector('[name*="[unit_price]"]')?.value);
    subtotal += qty * price;
  });
  const subEl = document.getElementById('spk-subtotal');
  const totEl = document.getElementById('spk-total');
  if (subEl) subEl.value = formatRupiahValue(subtotal);
  if (totEl) totEl.value = formatRupiahValue(subtotal);
}

function recalcCMP() {
  const table = document.querySelector('table[data-calc="cmp"]');
  if (!table) return;
  let totalRapp = 0;
  let totalV1 = 0;
  let totalV2 = 0;
  let totalV3 = 0;
  table.querySelectorAll('tbody tr').forEach(row => {
    const qty = parseNum(row.querySelector('[name*="[qty]"]')?.value);
    const rapp = parseNum(row.querySelector('[name*="[rapp_unit_price]"]')?.value);
    const v1 = parseNum(row.querySelector('[name*="[vendor1_unit_price]"]')?.value);
    const v2 = parseNum(row.querySelector('[name*="[vendor2_unit_price]"]')?.value);
    const v3 = parseNum(row.querySelector('[name*="[vendor3_unit_price]"]')?.value);
    totalRapp += qty * rapp;
    totalV1 += qty * v1;
    totalV2 += qty * v2;
    totalV3 += qty * v3;
  });

  const rEl = document.getElementById('cmp-total-rapp');
  const v1El = document.getElementById('cmp-total-v1');
  const v2El = document.getElementById('cmp-total-v2');
  const v3El = document.getElementById('cmp-total-v3');
  if (rEl) rEl.value = formatRupiahValue(totalRapp);
  if (v1El) v1El.value = formatRupiahValue(totalV1);
  if (v2El) v2El.value = formatRupiahValue(totalV2);
  if (v3El) v3El.value = formatRupiahValue(totalV3);
}

function recalcAll() {
  recalcPO();
  recalcSPK();
  recalcCMP();
}

document.addEventListener('click', function (e) {
  const btn = e.target.closest('.remove-row');
  if (!btn) return;
  const row = btn.closest('tr');
  if (row) row.remove();
  recalcAll();
});

document.addEventListener('input', function () {
  recalcAll();
});

document.addEventListener('change', function (e) {
  const sel = e.target.closest('.rab-item-select');
  if (sel) {
    const row = sel.closest('.item-row');
    applyRabDefaults(row);
  }
  recalcAll();
});

document.addEventListener('submit', function (e) {
  const form = e.target.closest('form');
  if (!form) return;
  const qtyInputs = form.querySelectorAll('input[name*="[qty]"]');
  let hasQty = false;
  for (const input of qtyInputs) {
    const val = parseNum(input.value);
    if (val > 0) { hasQty = true; }
    if (input.value && isNaN(parseNum(input.value))) {
      alert('Qty harus angka.');
      e.preventDefault();
      return;
    }
  }
  if (!hasQty) {
    alert('Minimal 1 item dengan qty > 0.');
    e.preventDefault();
  }
});

document.addEventListener('DOMContentLoaded', function () {
  const firstRow = document.querySelector('#lpb-items .item-row');
  if (firstRow) {
    assignNamesInRow(firstRow, 0);
    applyRabDefaults(firstRow);
  }
  document.querySelectorAll('#lpb-items .item-row').forEach(row => applyRabDefaults(row));
  recalcAll();
});
</script>
@endsection



