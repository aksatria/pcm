@extends('layouts.dev')
@section('title', 'Edit Purchase Order')
@section('content')
@php
  $projectName = $project->name ?? 'Proyek';
  $projectCode = $project->code ?? '';
  $rabName = $rab->name ?? ('RAPP #'.$rab->id);
@endphp
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&display=swap');
  .po-title { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; letter-spacing: -0.01em; }
  .po-kicker { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; }
  .price-over { border-color: #ef4444 !important; box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.5); }
  .price-hint { font-size: 11px; color: #ef4444; margin-top: 4px; }
  .po-card { border: 1px solid rgba(148, 163, 184, 0.25); background: #ffffff; }
  .dark .po-card { border-color: rgba(148, 163, 184, 0.18); background: rgba(15, 23, 42, 0.7); }
  .po-badge { border: 1px solid rgba(148, 163, 184, 0.3); }
  .dark .po-badge { border-color: rgba(148, 163, 184, 0.2); }
</style>
<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div>
      <p class="po-kicker text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">Purchase Order</p>
      <h1 class="po-title text-2xl font-semibold">Edit Purchase Order</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Proyek: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $projectName }}</span>
        @if($projectCode)
          &bull; Kode: <span class="font-mono">{{ $projectCode }}</span>
        @endif
        &bull; RAPP: <span class="font-semibold">{{ $rabName }}</span>
      </p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="{{ route('dev.rab-baseline.purchase-orders.index', [$project->id, $rab->id]) }}"
         class="inline-flex items-center px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-800 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
        Kembali ke Daftar
      </a>
      <button form="po-form" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium shadow-sm">
        Simpan Perubahan
      </button>
    </div>
  </div>
  @if($errors->any())
    <div class="rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/80 dark:bg-rose-900/20 p-4 text-sm text-rose-800 dark:text-rose-200">
      <div class="font-semibold mb-1">Periksa kembali input berikut:</div>
      <ul class="list-disc list-inside space-y-1 text-xs">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form id="po-form" method="post" action="{{ route('dev.rabs.purchase-orders.update', [$project->id, $rab->id, $purchaseOrder->id]) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <section class="po-card rounded-2xl p-5">
      <div class="flex items-center gap-3 mb-4">
        <span class="po-badge h-8 w-8 rounded-full bg-sky-600 text-white text-xs font-semibold flex items-center justify-center">1</span>
        <div>
          <h2 class="po-title text-sm font-semibold text-sky-700 dark:text-sky-300">Informasi PO</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">Perbarui data vendor & detail PO.</p>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No PO</label>
          <input name="po_no" value="{{ old('po_no', $purchaseOrder->po_no) }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal PO</label>
          <input type="date" name="po_date" value="{{ old('po_date', optional($purchaseOrder->po_date)->format('Y-m-d')) }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300">Vendor</label>
            <button type="button" onclick="openVendorModal()" class="text-[10px] text-sky-600 dark:text-sky-400 hover:underline">Tambah Vendor</button>
          </div>
          <select name="vendor_id" data-vendor-select="1" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
            <option value="">-</option>
            @foreach($vendors as $v)
              <option value="{{ $v->id }}" @selected(old('vendor_id', $purchaseOrder->vendor_id) == $v->id)>{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Contact Person</label>
          <input name="contact_person" value="{{ old('contact_person', $purchaseOrder->contact_person) }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Phone</label>
          <input name="phone" value="{{ old('phone', $purchaseOrder->phone) }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Alamat</label>
          <input name="address" value="{{ old('address', $purchaseOrder->address) }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">PPN %</label>
          <input name="tax_percent" value="{{ old('tax_percent', $purchaseOrder->tax_percent) }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Ongkir</label>
          <input name="shipping_cost" value="{{ old('shipping_cost', $purchaseOrder->shipping_cost) }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Catatan</label>
          <textarea name="notes" rows="3" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">{{ old('notes', $purchaseOrder->notes) }}</textarea>
        </div>
      </div>
    </section>

    <section class="po-card rounded-2xl p-5">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-3">
          <span class="po-badge h-8 w-8 rounded-full bg-amber-500 text-white text-xs font-semibold flex items-center justify-center">2</span>
          <div>
            <h2 class="po-title text-sm font-semibold text-amber-700 dark:text-amber-300">Item PO</h2>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">Pastikan item sesuai RAPP.</p>
          </div>
        </div>
        <button type="button" onclick="addRow('po-items','po-item-template')" class="px-3 py-1.5 rounded-xl bg-amber-500 text-white text-xs hover:bg-amber-600">Tambah Item</button>
      </div>
      <div id="po-items" class="space-y-3">
        @forelse($purchaseOrder->items as $idx => $it)
        <div class="item-row border border-gray-200 dark:border-gray-800 rounded-xl p-4 bg-gray-50/60 dark:bg-slate-900/40 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">RAPP Item</label>
            <select name="items[{{ $idx }}][rab_item_id]" class="rab-item-select w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
              @foreach($rabItems as $rit)
                @php
                  $unit = $rit->satuan ?? ($rit->data->satuan ?? '');
                  $price = $rit->harga_satuan ?? ($rit->data->harga_satuan ?? 0);
                  $remaining = $remainingMap[$rit->id] ?? 0;
                @endphp
                <option value="{{ $rit->id }}" data-unit="{{ $unit }}" data-rapp-price="{{ $price }}" data-remaining="{{ $remaining }}" @selected($it->rab_item_id == $rit->id)>{{ $rit->data->kode ?? '' }} - {{ $rit->data->uraian ?? '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Spesifikasi</label>
            <input name="items[{{ $idx }}][specification]" value="{{ $it->specification }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Qty</label>
            <input name="items[{{ $idx }}][qty]" value="{{ $it->qty }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
            <div class="remaining-hint mt-1 text-[11px] text-gray-500 dark:text-gray-400"></div>
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
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga Satuan</label>
            <input name="items[{{ $idx }}][unit_price]" value="{{ $it->unit_price }}" class="unit-price-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
            <div class="price-hint hidden">Harga melebihi RAPP.</div>
          </div>
          <div class="flex items-end">
            <button type="button" class="remove-row px-2 py-1 rounded bg-rose-600 text-white text-xs hover:bg-rose-700">Hapus</button>
          </div>
        </div>
        @empty
          <div class="text-sm text-gray-500 dark:text-gray-400">Tidak ada item.</div>
        @endforelse
      </div>

      <template id="po-item-template">
        <div class="item-row border border-gray-200 dark:border-gray-800 rounded-xl p-4 bg-gray-50/60 dark:bg-slate-900/40 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">RAPP Item</label>
            <select data-name="items[__INDEX__][rab_item_id]" class="rab-item-select w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
              @foreach($rabItems as $rit)
                @php
                  $unit = $rit->satuan ?? ($rit->data->satuan ?? '');
                  $price = $rit->harga_satuan ?? ($rit->data->harga_satuan ?? 0);
                  $remaining = $remainingMap[$rit->id] ?? 0;
                @endphp
                <option value="{{ $rit->id }}" data-unit="{{ $unit }}" data-rapp-price="{{ $price }}" data-remaining="{{ $remaining }}">{{ $rit->data->kode ?? '' }} - {{ $rit->data->uraian ?? '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Spesifikasi</label>
            <input data-name="items[__INDEX__][specification]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Qty</label>
            <input data-name="items[__INDEX__][qty]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
            <div class="remaining-hint mt-1 text-[11px] text-gray-500 dark:text-gray-400"></div>
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
            <button type="button" class="remove-row px-2 py-1 rounded bg-rose-600 text-white text-xs hover:bg-rose-700">Hapus</button>
          </div>
        </div>
      </template>
    </section>

    <section class="po-card rounded-2xl p-5">
      <div class="flex items-center gap-3 mb-4">
        <span class="po-badge h-8 w-8 rounded-full bg-emerald-600 text-white text-xs font-semibold flex items-center justify-center">3</span>
        <div>
          <h2 class="po-title text-sm font-semibold text-emerald-700 dark:text-emerald-300">Ringkasan</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">Subtotal, PPN, dan total akhir.</p>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Subtotal</label>
          <input id="po-subtotal" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" readonly />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">PPN</label>
          <input id="po-tax" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" readonly />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Total</label>
          <input id="po-total" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" readonly />
        </div>
      </div>
    </section>

    <div class="flex items-center justify-between">
      <p class="text-[11px] text-gray-500 dark:text-gray-400">Perubahan item akan mempengaruhi total PO.</p>
      <div class="flex gap-2">
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium shadow-sm">Simpan</button>
        <a href="{{ route('dev.rab-baseline.purchase-orders.index', [$project->id, $rab->id]) }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700">Batal</a>
      </div>
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
  const remaining = opt ? parseNum(opt.getAttribute('data-remaining') || 0) : 0;
  const unitInput = row.querySelector('.unit-input');
  const priceInput = row.querySelector('.rapp-price-display');
  const hint = row.querySelector('.remaining-hint');
  if (unitInput) unitInput.value = unit;
  if (priceInput) priceInput.value = price ? `Rp ${Number(price).toLocaleString('id-ID')}` : 'Rp 0';
  if (hint) {
    const formatted = remaining.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    hint.textContent = `Sisa RAPP: ${formatted}${unit ? ' ' + unit : ''}`;
  }
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
      hint.textContent = `Harga melebihi RAPP (lebih ${percentOver.toFixed(2)}%).`;
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

function formatRupiahValue(value) {
  const num = parseNum(value);
  return `Rp ${num.toLocaleString('id-ID')}`;
}

function recalcPO() {
  const rows = document.querySelectorAll('#po-items .item-row');
  if (!rows.length) return;
  let subtotal = 0;
  rows.forEach(row => {
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

function recalcSPK() {}
function recalcCMP() {}
function recalcAll() { recalcPO(); }

document.addEventListener('click', function (e) {
  const btn = e.target.closest('.remove-row');
  if (!btn) return;
  const row = btn.closest('.item-row');
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
  form.querySelectorAll('.unit-price-input').forEach(input => {
    unformatRupiahInput(input);
  });
  const taxInput = form.querySelector('input[name="tax_percent"]');
  const shippingInput = form.querySelector('input[name="shipping_cost"]');
  if (taxInput) taxInput.value = parseNum(taxInput.value);
  if (shippingInput) shippingInput.value = parseNum(shippingInput.value);
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
  const firstRow = document.querySelector('#po-items .item-row');
  if (firstRow) {
    assignNamesInRow(firstRow, 0);
    applyRabDefaults(firstRow);
    checkPriceThreshold(firstRow);
  }
  document.querySelectorAll('#po-items .item-row').forEach(row => {
    applyRabDefaults(row);
    checkPriceThreshold(row);
    const priceInput = row.querySelector('.unit-price-input');
    if (priceInput) {
      formatRupiahInput(priceInput);
    }
  });
  recalcAll();
});
</script>
@endsection



