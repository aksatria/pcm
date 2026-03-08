@extends('layouts.dev')
@section('title', 'Buat SPK')
@section('content')
@php
  $projectName = $project->name ?? 'Proyek';
  $projectCode = $project->code ?? '';
  $rabName = $rab->name ?? ('RAPP #'.$rab->id);
@endphp
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&display=swap');
  .spk-title { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; letter-spacing: -0.01em; }
  .spk-kicker { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; }
  .price-over { border-color: #ef4444 !important; box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.5); }
  .price-hint { font-size: 11px; color: #ef4444; margin-top: 4px; }
</style>
<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div>
      <p class="spk-kicker text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">SPK</p>
      <h1 class="spk-title text-2xl font-semibold">Buat SPK</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Proyek: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $projectName }}</span>
        @if($projectCode)
          &bull; Kode: <span class="font-mono">{{ $projectCode }}</span>
        @endif
        &bull; RAPP: <span class="font-semibold">{{ $rabName }}</span>
      </p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="{{ route('dev.rab-baseline.spks.index', [$project->id, $rab->id]) }}"
         class="inline-flex items-center px-4 py-2 rounded-2xl border border-gray-200 dark:border-gray-800 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
        Kembali ke Daftar
      </a>
      <button form="spk-form" class="inline-flex items-center px-4 py-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white text-sm font-medium">
        Simpan SPK
      </button>
    </div>
  </div>
  <form id="spk-form" method="post" action="{{ route('dev.rabs.spks.store', [$project->id, $rab->id]) }}" class="space-y-6">
    @csrf
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 text-sm space-y-5">
      <div class="flex items-center gap-3 mb-1">
        <span class="h-8 w-8 rounded-full bg-blue-600 text-white text-xs font-semibold flex items-center justify-center">1</span>
        <div>
          <h2 class="spk-title text-sm font-semibold text-blue-700 dark:text-blue-300">Informasi SPK</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">Lengkapi data vendor & detail SPK.</p>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No SPK (auto jika kosong)</label>
          <input name="spk_no" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal SPK</label>
          <input type="date" name="spk_date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Mulai</label>
          <input type="date" name="start_date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Selesai</label>
          <input type="date" name="end_date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Komparasi Vendor (opsional)</label>
          <select id="comparisonSelect" name="vendor_comparison_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
            <option value="">-</option>
            @foreach($approvedComparisons as $cmp)
              <option value="{{ $cmp->id }}" data-decision-vendor="{{ $cmp->decision_vendor_id }}">
                {{ $cmp->comparison_no ?? ('KOM #'.$cmp->id) }}
              </option>
            @endforeach
          </select>
          <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">Jika dipilih, vendor akan mengikuti vendor keputusan komparasi.</p>
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
              <option value="{{ $v->id }}">{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Scope</label>
          <input name="scope" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
        </div>
      </div>
      <div>
        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Catatan</label>
        <textarea name="notes" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" rows="3"></textarea>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 text-sm">
      <div class="flex items-center justify-between mb-3">
        <div>
          <h2 class="spk-title text-sm font-semibold text-amber-700 dark:text-amber-300">Item</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">Pilih item RAPP dan isi qty & harga.</p>
        </div>
        <button type="button" onclick="addRow('spk-items','spk-item-template')" class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-white text-xs hover:from-amber-600 hover:to-amber-700">Tambah</button>
      </div>
      <div id="spk-items" class="space-y-3">
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
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Deskripsi</label>
            <input data-name="items[__INDEX__][description]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
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
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga Satuan</label>
            <input data-name="items[__INDEX__][unit_price]" class="unit-price-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
            <div class="price-hint hidden">Harga melebihi RAPP.</div>
          </div>
          <div class="flex items-end">
            <button type="button" class="remove-row px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Hapus</button>
          </div>
        </div>
      </div>

      <template id="spk-item-template">
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
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Deskripsi</label>
            <input data-name="items[__INDEX__][description]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
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
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga Satuan</label>
            <input data-name="items[__INDEX__][unit_price]" class="unit-price-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
            <div class="price-hint hidden">Harga melebihi RAPP.</div>
          </div>
          <div class="flex items-end">
            <button type="button" class="remove-row px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Hapus</button>
          </div>
        </div>
      </template>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 text-sm">
      <div class="flex items-center gap-3 mb-3">
        <span class="h-8 w-8 rounded-full bg-emerald-600 text-white text-xs font-semibold flex items-center justify-center">3</span>
        <div>
          <h2 class="spk-title text-sm font-semibold text-emerald-700 dark:text-emerald-300">Ringkasan</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">Subtotal dan total akhir.</p>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Subtotal</label>
          <input id="spk-subtotal" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" readonly />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Total</label>
          <input id="spk-total" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" readonly />
        </div>
      </div>
    </div>

    <div class="flex gap-2">
      <button class="inline-flex items-center px-4 py-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white text-sm font-medium">Simpan</button>
      <a href="{{ route('dev.rab-baseline.spks.index', [$project->id, $rab->id]) }}" class="inline-flex items-center px-4 py-2 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700">Batal</a>
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

function checkPriceThreshold(row) {
  if (!row) return;
  const sel = row.querySelector('.rab-item-select');
  const opt = sel ? sel.options[sel.selectedIndex] : null;
  const rappPrice = opt ? parseNum(opt.getAttribute('data-rapp-price') || 0) : 0;
  const maxAllowed = rappPrice + 0.1;
  const priceInput = row.querySelector('.unit-price-input');
  const hint = row.querySelector('.price-hint');
  if (!priceInput) return;
  const unitPrice = parseNum(priceInput.value);
  const over = rappPrice > 0 && unitPrice > maxAllowed;
  priceInput.classList.toggle('price-over', over);
  if (hint) {
    hint.classList.toggle('hidden', !over);
    if (over) {
      const percentOver = ((unitPrice / rappPrice) - 1) * 100;
      hint.textContent = `Harga melebihi harga RAPP (lebih ${percentOver.toFixed(2)}%).`;
    }
  }
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
  checkPriceThreshold(row);
  recalcAll();
}

function parseNum(v) {
  if (v === null || v === undefined) return 0;
  const cleaned = String(v).replace(/[^\d-]/g, '');
  if (cleaned === '' || cleaned === '-') return 0;
  const n = parseInt(cleaned, 10);
  return isNaN(n) ? 0 : n;
}

function formatRupiahInput(input) {
  if (!input) return;
  if (input.value === '') return;
  const raw = String(input.value).replace(/[^\d]/g, '');
  if (raw === '') return;
  const num = parseInt(raw, 10);
  if (isNaN(num)) return;
  input.value = `Rp ${num.toLocaleString('id-ID')}`;
}

function unformatRupiahInput(input) {
  if (!input) return;
  const raw = String(input.value).replace(/[^\d]/g, '');
  input.value = raw;
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
  if (subEl) subEl.value = subtotal.toFixed(2);
  if (taxEl) taxEl.value = tax.toFixed(2);
  if (totEl) totEl.value = total.toFixed(2);
}

function formatRupiahValue(value) {
  const num = parseNum(value);
  return `Rp ${num.toLocaleString('id-ID')}`;
}

function recalcSPK() {
  const rows = document.querySelectorAll('#spk-items .item-row');
  if (!rows.length) return;
  let subtotal = 0;
  rows.forEach(row => {
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
  if (rEl) rEl.value = totalRapp.toFixed(2);
  if (v1El) v1El.value = totalV1.toFixed(2);
  if (v2El) v2El.value = totalV2.toFixed(2);
  if (v3El) v3El.value = totalV3.toFixed(2);
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
  const priceInput = document.activeElement?.classList?.contains('unit-price-input') ? document.activeElement : null;
  if (priceInput) {
    const row = priceInput.closest('.item-row');
    checkPriceThreshold(row);
  }
  recalcAll();
});

document.addEventListener('change', function (e) {
  const sel = e.target.closest('.rab-item-select');
  if (sel) {
    const row = sel.closest('.item-row');
    applyRabDefaults(row);
    checkPriceThreshold(row);
  }
  const priceInput = e.target.closest('.unit-price-input');
  if (priceInput) {
    const row = priceInput.closest('.item-row');
    checkPriceThreshold(row);
  }
  recalcAll();
});

document.addEventListener('focusin', function (e) {
  const priceInput = e.target.closest('.unit-price-input');
  if (priceInput) {
    unformatRupiahInput(priceInput);
  }
});

document.addEventListener('focusout', function (e) {
  const priceInput = e.target.closest('.unit-price-input');
  if (priceInput) {
    formatRupiahInput(priceInput);
    const row = priceInput.closest('.item-row');
    checkPriceThreshold(row);
  }
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
  const firstRow = document.querySelector('#spk-items .item-row');
  if (firstRow) {
    assignNamesInRow(firstRow, 0);
    applyRabDefaults(firstRow);
    checkPriceThreshold(firstRow);
  }
  const comparisonSelect = document.getElementById('comparisonSelect');
  if (comparisonSelect) {
    comparisonSelect.addEventListener('change', function () {
      const opt = this.options[this.selectedIndex];
      const vendorId = opt ? opt.getAttribute('data-decision-vendor') : '';
      const vendorSelect = document.querySelector('[name="vendor_id"]');
      if (vendorSelect && vendorId) {
        vendorSelect.value = vendorId;
      }
    });
  }
  recalcAll();
});
</script>
@endsection


















