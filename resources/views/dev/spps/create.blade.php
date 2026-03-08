@extends('layouts.dev')
@section('title', 'Buat SPP')
@section('content')
@php
  $projectName = $project->name ?? 'Proyek';
  $projectCode = $project->code ?? '';
  $rabName = $rab->name ?? ('RAPP #'.$rab->id);
@endphp
<div class="px-6 py-5 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="space-y-2">
    <nav class="text-xs text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-1">
      <a href="{{ route('dev.projects.show', $project->id) }}" class="hover:text-gray-700 dark:hover:text-gray-200">Proyek</a>
      <span>/</span>
      <a href="{{ route('dev.rab-baseline.show', ['projectId' => $project->id, 'rabId' => $rab->id]) }}" class="hover:text-gray-700 dark:hover:text-gray-200">RAPP</a>
      <span>/</span>
      <span class="text-gray-700 dark:text-gray-200">SPP</span>
      <span>/</span>
      <span class="text-gray-700 dark:text-gray-200">Buat</span>
    </nav>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-2xl font-semibold">Buat SPP</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Proyek: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $projectName }}</span>
          @if($projectCode)
            &bull; Kode: <span class="font-mono">{{ $projectCode }}</span>
          @endif
          &bull; RAPP: <span class="font-semibold">{{ $rabName }}</span>
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="{{ route('dev.rab-baseline.spps.index', [$project->id, $rab->id]) }}"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-800 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
          Kembali ke Daftar
        </a>
        <button form="spp-form" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">
          Simpan SPP
        </button>
      </div>
    </div>
  </div>

  <form id="spp-form" method="post" action="{{ route('dev.rabs.spps.store', [$project->id, $rab->id]) }}" class="space-y-6">
    @csrf

    <section class="bg-white dark:bg-slate-900 ring-1 ring-gray-200 dark:ring-slate-800 rounded-2xl">
      <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-800">
        <h2 class="text-sm font-semibold">Informasi SPP</h2>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">Lengkapi data utama sebelum memilih item.</p>
      </div>
      <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">No SPP</label>
          <input name="spp_no" placeholder="Auto jika kosong"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal SPP</label>
          <input type="date" name="spp_date"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Schedule</label>
          <input type="date" name="schedule_date"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300">Vendor (opsional)</label>
            <button type="button" onclick="openVendorModal()" class="text-[10px] text-sky-600 dark:text-sky-400 hover:underline">Tambah Vendor</button>
          </div>
          <select name="vendor_id" data-vendor-select="1"
                  class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
            <option value="">-</option>
            @foreach($vendors as $v)
              <option value="{{ $v->id }}">{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Diajukan Oleh</label>
          <input name="requested_by" placeholder="Nama pengaju"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
        </div>
        <div>
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Disetujui Oleh</label>
          <input name="approved_by" placeholder="Nama penyetuju"
                 class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Catatan</label>
          <textarea name="notes" rows="3" placeholder="Catatan tambahan (opsional)"
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100"></textarea>
        </div>
      </div>
    </section>

    <section class="bg-white dark:bg-slate-900 ring-1 ring-gray-200 dark:ring-slate-800 rounded-2xl">
      <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between">
        <div>
          <h2 class="text-sm font-semibold">Item SPP</h2>
          <p class="text-[11px] text-gray-500 dark:text-gray-400">Pilih item RAPP lalu isi qty dan keterangan.</p>
        </div>
        <button type="button" onclick="addRow('spp-items','spp-item-template')" class="px-3 py-1.5 rounded-lg bg-gray-900 text-white text-xs hover:bg-gray-800">
          Tambah Item
        </button>
      </div>
      <div class="p-5 space-y-3" id="spp-items">
        <div class="item-row border border-gray-200 dark:border-slate-800 rounded-xl p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">RAPP Item</label>
            <select data-name="items[__INDEX__][rab_item_id]" class="rab-item-select w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
              @foreach($rabItems as $it)
                <option value="{{ $it->id }}" data-unit="{{ $it->satuan ?? ($it->data->satuan ?? '') }}">{{ $it->data->kode ?? '' }} - {{ $it->data->uraian ?? '' }}</option>
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
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Schedule</label>
            <input type="date" data-name="items[__INDEX__][schedule_date]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Keterangan</label>
            <input data-name="items[__INDEX__][work_notes]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Vendor</label>
            <select data-name="items[__INDEX__][vendor_id]" data-vendor-select="1" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
              <option value="">-</option>
              @foreach($vendors as $v)
                <option value="{{ $v->id }}">{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
              @endforeach
            </select>
          </div>
          <div class="flex items-end">
            <button type="button" class="remove-row px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Hapus</button>
          </div>
        </div>
      </div>
      <template id="spp-item-template">
        <div class="item-row border border-gray-200 dark:border-slate-800 rounded-xl p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">RAPP Item</label>
            <select data-name="items[__INDEX__][rab_item_id]" class="rab-item-select w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
              @foreach($rabItems as $it)
                <option value="{{ $it->id }}" data-unit="{{ $it->satuan ?? ($it->data->satuan ?? '') }}">{{ $it->data->kode ?? '' }} - {{ $it->data->uraian ?? '' }}</option>
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
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Schedule</label>
            <input type="date" data-name="items[__INDEX__][schedule_date]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Keterangan</label>
            <input data-name="items[__INDEX__][work_notes]" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Vendor</label>
            <select data-name="items[__INDEX__][vendor_id]" data-vendor-select="1" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-gray-100">
              <option value="">-</option>
              @foreach($vendors as $v)
                <option value="{{ $v->id }}">{{ $v->project_id ? '[Proyek]' : '[Global]' }} {{ $v->nama }}</option>
              @endforeach
            </select>
          </div>
          <div class="flex items-end">
            <button type="button" class="remove-row px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700">Hapus</button>
          </div>
        </div>
      </template>
    </section>

    <div class="flex items-center justify-between">
      <p class="text-[11px] text-gray-500 dark:text-gray-400">Pastikan item sesuai RAPP dan qty tidak melebihi sisa.</p>
      <div class="flex gap-2">
        <button class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">Simpan</button>
        <a href="{{ route('dev.rab-baseline.spps.index', [$project->id, $rab->id]) }}"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-slate-700">Batal</a>
      </div>
    </div>
  </form>
</div>

@include('dev.vendors.quick-modal', ['project' => $project])

<script>
function addRow(containerId, templateId) {
  const container = document.getElementById(containerId);
  const tpl = document.getElementById(templateId);
  if (!container || !tpl) return;
  const idx = container.querySelectorAll('.item-row').length;
  const clone = tpl.content.cloneNode(true);
  clone.querySelectorAll('[data-name]').forEach(el => {
    el.name = el.getAttribute('data-name').replace(/__INDEX__/g, idx);
  });
  container.appendChild(clone);
}

function parseNum(v) {
  if (v === null || v === undefined) return 0;
  const n = parseFloat(String(v).replace(/[^0-9.-]/g, ''));
  return isNaN(n) ? 0 : n;
}

document.addEventListener('click', function (e) {
  const btn = e.target.closest('.remove-row');
  if (!btn) return;
  const row = btn.closest('.item-row');
  if (row) row.remove();
});

document.addEventListener('change', function (e) {
  const sel = e.target.closest('.rab-item-select');
  if (sel) {
    const unit = sel.options[sel.selectedIndex]?.getAttribute('data-unit') || '';
    const row = sel.closest('.item-row');
    const unitInput = row ? row.querySelector('.unit-input') : null;
    if (unitInput) unitInput.value = unit;
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
</script>
@endsection





