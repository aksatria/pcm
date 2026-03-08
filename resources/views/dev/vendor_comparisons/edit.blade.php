@extends('layouts.dev')
@section('title', 'Edit Komparasi Vendor')
@section('content')
@php
  $projectName = $project->name ?? 'Proyek';
  $projectCode = $project->code ?? '';
  $rabName = $rab->name ?? ('RAPP #'.$rab->id);
  $isHO = auth()->check() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO();
@endphp
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&family=JetBrains+Mono:wght@500;600&display=swap');
  .cmp-title { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; letter-spacing: -0.01em; }
  .cmp-kicker { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; }
  .cmp-mono { font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace; font-variant-numeric: tabular-nums; }
  .money-input,
  #cmp-total-rapp,
  #cmp-total-v1,
  #cmp-total-v2,
  #cmp-total-v3 {
    font-size: 0.875rem;
    font-weight: 600;
  }
</style>
<div class="px-6 py-5 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="space-y-2">
    <nav class="text-xs text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-1">
      <a href="{{ route('dev.projects.show', $project->id) }}" class="hover:text-gray-700 dark:hover:text-gray-200">Proyek</a>
      <span>/</span>
      <a href="{{ route('dev.rab-baseline.show', ['projectId' => $project->id, 'rabId' => $rab->id]) }}" class="hover:text-gray-700 dark:hover:text-gray-200">RAPP</a>
      <span>/</span>
      <span class="text-gray-700 dark:text-gray-200">Komparasi Vendor</span>
      <span>/</span>
      <span class="text-gray-700 dark:text-gray-200">Edit</span>
    </nav>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="cmp-title text-2xl font-semibold">Edit Komparasi Vendor</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Proyek: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $projectName }}</span>
          @if($projectCode)
            &bull; Kode: <span class="font-mono">{{ $projectCode }}</span>
          @endif
          &bull; RAPP: <span class="font-semibold">{{ $rabName }}</span>
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="{{ route('dev.rab-baseline.vendor-comparisons.index', [$project->id, $rab->id]) }}"
           class="inline-flex items-center px-4 py-2 rounded-2xl border border-gray-200 dark:border-gray-800 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
          Kembali ke Daftar
        </a>
        <button form="cmp-form" class="inline-flex items-center px-4 py-2 rounded-2xl text-white text-sm font-medium bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700">
          <span class="relative">Simpan Perubahan</span>
        </button>
      </div>
    </div>
  </div>

  <form id="cmp-form" method="post" action="{{ route('dev.rabs.vendor-comparisons.update', [$project->id, $rab->id, $comparison->id]) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <section class="bg-white dark:bg-slate-900 rounded-2xl">
      <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-800">
        <h2 class="cmp-title text-sm font-semibold text-blue-700 dark:text-blue-300">Informasi Komparasi</h2>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">Perbarui nomor dan vendor keputusan.</p>
      </div>
      <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No Komparasi</label>
          <input name="comparison_no" value="{{ old('comparison_no', $comparison->comparison_no) }}"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal</label>
          <input type="date" name="comparison_date" value="{{ old('comparison_date', optional($comparison->comparison_date)->format('Y-m-d')) }}"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300">Vendor Keputusan</label>
            <button type="button" onclick="openVendorModal()" class="text-[10px] text-sky-600 dark:text-sky-400 hover:underline">Tambah Vendor</button>
          </div>
          <select name="decision_vendor_id" data-vendor-select="1"
                  class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
            <option value="">-</option>
            @foreach($vendors as $v)
              <option value="{{ $v->id }}" @selected(old('decision_vendor_id', $comparison->decision_vendor_id) == $v->id)>{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
            @endforeach
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Catatan</label>
          <textarea name="notes" rows="3"
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">{{ old('notes', $comparison->notes) }}</textarea>
        </div>
      </div>
    </section>

    <section class="bg-white dark:bg-slate-900 rounded-2xl">
      <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between">
        <div>
          <h2 class="cmp-title text-sm font-semibold text-amber-700 dark:text-amber-300">Item Komparasi</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">Perbarui harga dan vendor.</p>
        </div>
        <button type="button" onclick="addRow('cmp-items','cmp-item-template')" class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-white text-xs hover:from-amber-600 hover:to-amber-700 shadow">
          Tambah Item
        </button>
      </div>
      <div class="p-5 space-y-3" id="cmp-items">
        @forelse($comparison->items as $idx => $it)
        <div class="item-row border border-gray-200 dark:border-slate-800 rounded-xl p-4 grid grid-cols-1 md:grid-cols-2 gap-4" data-calc="cmp">
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">RAPP Item</label>
            <select name="items[{{ $idx }}][rab_item_id]" class="rab-item-select w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
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
            <input name="items[{{ $idx }}][qty]" value="{{ $it->qty }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Satuan</label>
            <input name="items[{{ $idx }}][unit]" value="{{ $it->unit }}" class="unit-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga RAPP</label>
            <input name="items[{{ $idx }}][rapp_unit_price]" value="{{ $it->rapp_unit_price }}" class="rapp-price money-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" @if(!$isHO) readonly @endif />
            @if(!$isHO)
              <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">Harga RAPP terkunci untuk staff.</p>
            @endif
          </div>
          <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Vendor 1</label>
              <select name="items[{{ $idx }}][vendor1_id]" data-vendor-select="1" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
                <option value="">-</option>
                @foreach($vendors as $v)
                  <option value="{{ $v->id }}" @selected($it->vendor1_id == $v->id)>{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga V1</label>
              <input name="items[{{ $idx }}][vendor1_unit_price]" value="{{ $it->vendor1_unit_price }}" class="money-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
            </div>
          </div>
          <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Vendor 2</label>
              <select name="items[{{ $idx }}][vendor2_id]" data-vendor-select="1" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
                <option value="">-</option>
                @foreach($vendors as $v)
                  <option value="{{ $v->id }}" @selected($it->vendor2_id == $v->id)>{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga V2</label>
              <input name="items[{{ $idx }}][vendor2_unit_price]" value="{{ $it->vendor2_unit_price }}" class="money-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
            </div>
          </div>
          <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Vendor 3</label>
              <select name="items[{{ $idx }}][vendor3_id]" data-vendor-select="1" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
                <option value="">-</option>
                @foreach($vendors as $v)
                  <option value="{{ $v->id }}" @selected($it->vendor3_id == $v->id)>{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga V3</label>
              <input name="items[{{ $idx }}][vendor3_unit_price]" value="{{ $it->vendor3_unit_price }}" class="money-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
            </div>
          </div>
          <div class="flex items-end">
            <button type="button" class="remove-row px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Hapus</button>
          </div>
        </div>
        @empty
          <div class="text-sm text-gray-500 dark:text-gray-400">Belum ada item.</div>
        @endforelse
      </div>
      <template id="cmp-item-template">
        <div class="item-row border border-gray-200 dark:border-slate-800 rounded-xl p-4 grid grid-cols-1 md:grid-cols-2 gap-4" data-calc="cmp">
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">RAPP Item</label>
            <select data-name="items[__INDEX__][rab_item_id]" class="rab-item-select w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
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
            <input data-name="items[__INDEX__][qty]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Satuan</label>
            <input data-name="items[__INDEX__][unit]" class="unit-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga RAPP</label>
            <input data-name="items[__INDEX__][rapp_unit_price]" class="rapp-price money-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" @if(!$isHO) readonly @endif />
            @if(!$isHO)
              <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">Harga RAPP terkunci untuk staff.</p>
            @endif
          </div>
          <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Vendor 1</label>
              <select data-name="items[__INDEX__][vendor1_id]" data-vendor-select="1" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
                <option value="">-</option>
                @foreach($vendors as $v)
                  <option value="{{ $v->id }}">{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga V1</label>
              <input data-name="items[__INDEX__][vendor1_unit_price]" class="money-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
            </div>
          </div>
          <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Vendor 2</label>
              <select data-name="items[__INDEX__][vendor2_id]" data-vendor-select="1" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
                <option value="">-</option>
                @foreach($vendors as $v)
                  <option value="{{ $v->id }}">{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga V2</label>
              <input data-name="items[__INDEX__][vendor2_unit_price]" class="money-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
            </div>
          </div>
          <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Vendor 3</label>
              <select data-name="items[__INDEX__][vendor3_id]" data-vendor-select="1" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
                <option value="">-</option>
                @foreach($vendors as $v)
                  <option value="{{ $v->id }}">{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Harga V3</label>
              <input data-name="items[__INDEX__][vendor3_unit_price]" class="money-input w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
            </div>
          </div>
          <div class="flex items-end">
            <button type="button" class="remove-row px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Hapus</button>
          </div>
        </div>
      </template>
    </section>

    <section class="bg-white dark:bg-slate-900 rounded-2xl">
      <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-800">
        <h2 class="cmp-title text-sm font-semibold text-emerald-700 dark:text-emerald-300">Ringkasan</h2>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">Total per vendor.</p>
      </div>
      <div class="p-5 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Total RAPP</label>
          <input id="cmp-total-rapp" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" readonly />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Total Vendor 1</label>
          <input id="cmp-total-v1" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" readonly />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Total Vendor 2</label>
          <input id="cmp-total-v2" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" readonly />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Total Vendor 3</label>
          <input id="cmp-total-v3" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" readonly />
        </div>
      </div>
    </section>

    <div class="flex items-center justify-between">
      <p class="text-[11px] text-gray-500 dark:text-gray-400">Isi minimal 1 item untuk menyimpan komparasi.</p>
      <div class="flex gap-2">
        <button class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">Simpan</button>
        <a href="{{ route('dev.rab-baseline.vendor-comparisons.index', [$project->id, $rab->id]) }}"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-slate-700">Batal</a>
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
  recalcCMP();
}

function parseNum(v) {
  if (v === null || v === undefined) return 0;
  const raw = String(v).replace(/[^0-9,.-]/g, '');
  const normalized = raw.replace(/\./g, '').replace(/,/g, '.');
  const n = parseFloat(normalized);
  return isNaN(n) ? 0 : n;
}

function formatRupiah(value) {
  const num = Number(value || 0);
  return 'Rp ' + num.toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

function formatRupiahInput(input) {
  if (!input) return;
  const value = parseNum(input.value);
  input.value = formatRupiah(value);
}

function getRowValue(row, key) {
  const byName = row.querySelector(`[name*="[${key}]"]`);
  if (byName) return byName.value;
  const byData = row.querySelector(`[data-name*="[${key}]"]`);
  return byData ? byData.value : '';
}

function setRowValue(row, key, value) {
  const byName = row.querySelector(`[name*="[${key}]"]`);
  if (byName) {
    byName.value = value;
    if (byName.classList.contains('money-input')) {
      formatRupiahInput(byName);
    }
    return;
  }
  const byData = row.querySelector(`[data-name*="[${key}]"]`);
  if (byData) {
    byData.value = value;
    if (byData.classList.contains('money-input')) {
      formatRupiahInput(byData);
    }
  }
}

function applyRabDefaults(row) {
  if (!row) return;
  const select = row.querySelector('.rab-item-select');
  if (!select) return;
  const option = select.options[select.selectedIndex];
  const unit = option ? option.getAttribute('data-unit') || '' : '';
  const price = option ? option.getAttribute('data-rapp-price') || '' : '';
  setRowValue(row, 'unit', unit);
  setRowValue(row, 'rapp_unit_price', price);
}

function recalcCMP() {
  const rows = document.querySelectorAll('#cmp-items .item-row');
  let totalRapp = 0;
  let totalV1 = 0;
  let totalV2 = 0;
  let totalV3 = 0;
  rows.forEach(row => {
    const qty = parseNum(getRowValue(row, 'qty'));
    const rapp = parseNum(getRowValue(row, 'rapp_unit_price'));
    const v1 = parseNum(getRowValue(row, 'vendor1_unit_price'));
    const v2 = parseNum(getRowValue(row, 'vendor2_unit_price'));
    const v3 = parseNum(getRowValue(row, 'vendor3_unit_price'));
    totalRapp += qty * rapp;
    totalV1 += qty * v1;
    totalV2 += qty * v2;
    totalV3 += qty * v3;
  });
  const rEl = document.getElementById('cmp-total-rapp');
  const v1El = document.getElementById('cmp-total-v1');
  const v2El = document.getElementById('cmp-total-v2');
  const v3El = document.getElementById('cmp-total-v3');
  if (rEl) rEl.value = formatRupiah(totalRapp);
  if (v1El) v1El.value = formatRupiah(totalV1);
  if (v2El) v2El.value = formatRupiah(totalV2);
  if (v3El) v3El.value = formatRupiah(totalV3);
}

document.addEventListener('click', function (e) {
  const btn = e.target.closest('.remove-row');
  if (!btn) return;
  const row = btn.closest('.item-row');
  if (row) row.remove();
  recalcCMP();
});

document.addEventListener('input', function (e) {
  if (e.target && e.target.classList.contains('money-input')) {
    recalcCMP();
    return;
  }
  recalcCMP();
});

document.addEventListener('blur', function (e) {
  if (e.target && e.target.classList.contains('money-input')) {
    formatRupiahInput(e.target);
    recalcCMP();
  }
}, true);

document.addEventListener('change', function (e) {
  const sel = e.target.closest('.rab-item-select');
  if (sel) {
    const row = sel.closest('.item-row');
    applyRabDefaults(row);
  }
  recalcCMP();
});

document.addEventListener('submit', function (e) {
  const form = e.target.closest('form');
  if (!form) return;
  form.querySelectorAll('.money-input').forEach(input => {
    const num = parseNum(input.value);
    input.value = num ? num : '';
  });
  const qtyInputs = form.querySelectorAll('input[name*="[qty]"], input[data-name*="[qty]"]');
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
  const firstRow = document.querySelector('#cmp-items .item-row');
  if (firstRow) {
    assignNamesInRow(firstRow, 0);
    applyRabDefaults(firstRow);
  }
  document.querySelectorAll('.money-input').forEach(input => formatRupiahInput(input));
  recalcCMP();
});
</script>
@endsection




