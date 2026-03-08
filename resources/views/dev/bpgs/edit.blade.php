@extends('layouts.dev')
@section('title', 'Edit BPG')
@section('content')
@php
  $projectName = $project->name ?? 'Proyek';
  $projectCode = $project->code ?? '';
  $rabName = $rab->name ?? ('RAPP #'.$rab->id);
@endphp
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&display=swap');
  .bpg-title { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; letter-spacing: -0.01em; }
  .bpg-kicker { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; }
</style>
<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div>
      <p class="bpg-kicker text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">BPG</p>
      <h1 class="bpg-title text-2xl font-semibold">Edit BPG</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Proyek: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $projectName }}</span>
        @if($projectCode)
          &bull; Kode: <span class="font-mono">{{ $projectCode }}</span>
        @endif
        &bull; RAPP: <span class="font-semibold">{{ $rabName }}</span>
      </p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="{{ route('dev.rab-baseline.bpgs.index', [$project->id, $rab->id]) }}" class="inline-flex items-center px-4 py-2 rounded-2xl border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
        Kembali ke Daftar
      </a>
      <button form="bpg-form" class="inline-flex items-center px-4 py-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white text-sm font-medium">
        Simpan Perubahan
      </button>
    </div>
  </div>

  <form id="bpg-form" method="post" action="{{ route('dev.rabs.bpgs.update', [$project->id, $rab->id, $bpg->id]) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 text-sm space-y-5">
      <div class="flex items-center gap-3 mb-1">
        <span class="h-8 w-8 rounded-full bg-blue-600 text-white text-xs font-semibold flex items-center justify-center">1</span>
        <div>
          <h2 class="bpg-title text-sm font-semibold text-blue-700 dark:text-blue-300">Informasi BPG</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">Perbarui data pengeluaran barang.</p>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No BPG</label>
          <input name="bpg_no" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ old('bpg_no', $bpg->bpg_no) }}" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal BPG</label>
          <input type="date" name="bpg_date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ old('bpg_date', optional($bpg->bpg_date)->format('Y-m-d')) }}" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">LPB (wajib, approved)</label>
          <select name="lpb_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" required>
            <option value="">Pilih LPB approved</option>
            @foreach($lpbs as $lpb)
              <option value="{{ $lpb->id }}" @selected(old('lpb_id', $bpg->lpb_id) == $lpb->id)>{{ $lpb->lpb_no ?? ('LPB #'.$lpb->id) }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Diajukan Oleh</label>
          <input name="requested_by" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ old('requested_by', $bpg->requested_by) }}" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Disetujui Oleh</label>
          <input name="approved_by" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ old('approved_by', $bpg->approved_by) }}" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Diketahui Oleh</label>
          <input name="known_by" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ old('known_by', $bpg->known_by) }}" />
        </div>
      </div>
      <div>
        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Catatan</label>
        <textarea name="notes" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" rows="3">{{ old('notes', $bpg->notes) }}</textarea>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 text-sm">
      <div class="flex items-center justify-between mb-3">
        <div>
          <h2 class="bpg-title text-sm font-semibold text-amber-700 dark:text-amber-300">Item</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">Catat pengeluaran barang.</p>
        </div>
        <button type="button" onclick="addRow('bpg-items','bpg-item-template')" class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-white text-xs hover:from-amber-600 hover:to-amber-700">Tambah</button>
      </div>
      <div id="bpg-items" class="space-y-3">
        @forelse($bpg->items as $idx => $it)
        <div class="item-row border border-gray-200 dark:border-gray-800 rounded-xl p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">RAPP Item</label>
            <select name="items[{{ $idx }}][rab_item_id]" class="rab-item-select w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
              @foreach($rabItems as $rit)
                @php
                  $unit = $rit->satuan ?? ($rit->data->satuan ?? '');
                  $price = $rit->harga_satuan ?? ($rit->data->harga_satuan ?? 0);
                  $volume = $rit->volume ?? 0;
                  $balance = $stockBalances[$rit->id] ?? 0;
                @endphp
                <option value="{{ $rit->id }}" data-unit="{{ $unit }}" data-rapp-price="{{ $price }}" data-rapp-volume="{{ $volume }}" data-stock-balance="{{ $balance }}" @selected($it->rab_item_id == $rit->id)>{{ $rit->data->kode ?? '' }} - {{ $rit->data->uraian ?? '' }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Qty</label>
            <input name="items[{{ $idx }}][qty]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ $it->qty }}" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga RAPP</label>
            <input class="rapp-price-display w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100" readonly />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Saldo Stok</label>
            <div class="flex items-center gap-2">
              <input class="stock-balance-display w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100" readonly />
              <span class="stock-warning hidden px-2 py-1 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">Stok menipis</span>
            </div>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Satuan</label>
            <input name="items[{{ $idx }}][unit]" class="unit-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ $it->unit }}" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Keluar</label>
            <input type="date" name="items[{{ $idx }}][issue_date]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ optional($it->issue_date)->format('Y-m-d') }}" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Keterangan</label>
            <input name="items[{{ $idx }}][work_notes]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" value="{{ $it->work_notes }}" />
          </div>
          <div class="flex items-end">
            <button type="button" class="remove-row px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Hapus</button>
          </div>
        </div>
        @empty
          <div class="text-sm text-gray-500 dark:text-gray-400">Tidak ada item.</div>
        @endforelse
      </div>

      <template id="bpg-item-template">
        <div class="item-row border border-gray-200 dark:border-gray-800 rounded-xl p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">RAPP Item</label>
            <select data-name="items[__INDEX__][rab_item_id]" class="rab-item-select w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
              @foreach($rabItems as $it)
                @php
                  $unit = $it->satuan ?? ($it->data->satuan ?? '');
                  $price = $it->harga_satuan ?? ($it->data->harga_satuan ?? 0);
                  $volume = $it->volume ?? 0;
                  $balance = $stockBalances[$it->id] ?? 0;
                @endphp
                <option value="{{ $it->id }}" data-unit="{{ $unit }}" data-rapp-price="{{ $price }}" data-rapp-volume="{{ $volume }}" data-stock-balance="{{ $balance }}">{{ $it->data->kode ?? '' }} - {{ $it->data->uraian ?? '' }}</option>
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
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Saldo Stok</label>
            <div class="flex items-center gap-2">
              <input class="stock-balance-display w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100" readonly />
              <span class="stock-warning hidden px-2 py-1 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">Stok menipis</span>
            </div>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Satuan</label>
            <input data-name="items[__INDEX__][unit]" class="unit-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Keluar</label>
            <input type="date" data-name="items[__INDEX__][issue_date]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Keterangan</label>
            <input data-name="items[__INDEX__][work_notes]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" />
          </div>
          <div class="flex items-end">
            <button type="button" class="remove-row px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Hapus</button>
          </div>
        </div>
      </template>
    </div>

    <div class="flex gap-2">
      <button class="inline-flex items-center px-4 py-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white text-sm font-medium">Simpan</button>
      <a href="{{ route('dev.rab-baseline.bpgs.index', [$project->id, $rab->id]) }}" class="inline-flex items-center px-4 py-2 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700">Batal</a>
    </div>
  </form>
</div>

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
  updateStockDisplay(row);
}

function updateStockDisplay(row) {
  const sel = row.querySelector('.rab-item-select');
  const opt = sel ? sel.options[sel.selectedIndex] : null;
  const balance = opt ? parseFloat(opt.getAttribute('data-stock-balance') || '0') : 0;
  const unit = opt ? (opt.getAttribute('data-unit') || '') : '';
  const volume = opt ? parseFloat(opt.getAttribute('data-rapp-volume') || '0') : 0;
  const display = row.querySelector('.stock-balance-display');
  const warning = row.querySelector('.stock-warning');
  const lowStockRatio = 0.15;
  const lowStockAbs = 1;
  const isLow = balance <= lowStockAbs || (volume > 0 && balance <= (volume * lowStockRatio));
  if (display) {
    const formatted = balance.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    display.value = unit ? `${formatted} ${unit}` : formatted;
  }
  if (warning) {
    warning.classList.toggle('hidden', !isLow);
  }
  row.dataset.lowStock = isLow ? '1' : '0';
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

function recalcPO() {}
function recalcSPK() {}
function recalcCMP() {}
function recalcAll() {}

document.addEventListener('click', function (e) {
  const btn = e.target.closest('.remove-row');
  if (!btn) return;
  const row = btn.closest('.item-row');
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
  const lowRows = Array.from(form.querySelectorAll('.item-row')).filter(row => row.dataset.lowStock === '1');
  if (lowRows.length > 0) {
    const proceed = confirm('Ada item dengan stok sangat kecil. Lanjutkan submit?');
    if (!proceed) {
      e.preventDefault();
      return;
    }
  }
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
  const firstRow = document.querySelector('#bpg-items .item-row');
  if (firstRow) {
    assignNamesInRow(firstRow, 0);
    applyRabDefaults(firstRow);
  }
  document.querySelectorAll('#bpg-items .item-row').forEach(row => applyRabDefaults(row));
  recalcAll();
});
</script>
@endsection



