@extends('layouts.dev')
@section('title', 'Rekap Stock Gudang')
@section('subtitle', 'Ringkasan stok per item')

@section('content')
<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <h1 class="text-lg font-semibold">Rekap Stock Gudang</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">Ringkasan stok per item (masuk, keluar, sisa).</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a id="printBtn" href="#" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">Print</a>
      <a id="pdfBtn" href="#" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-900 hover:bg-black text-white text-sm font-medium">PDF</a>
      <a id="excelBtn" href="#" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium">Excel</a>
    </div>
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
      <div>
        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Proyek</label>
        <select id="projectId" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg px-3 py-2 text-xs">
          <option value="">Pilih Project</option>
          @foreach($projects as $p)
            <option value="{{ $p->id }}">{{ $p->name ?? 'Project #'.$p->id }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Dari</label>
        <input id="dateFrom" type="date" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg px-3 py-2 text-xs">
      </div>
      <div>
        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Sampai</label>
        <input id="dateTo" type="date" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg px-3 py-2 text-xs">
      </div>
      <div>
        <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Filter</label>
        <select id="lowStockOnly" class="w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg px-3 py-2 text-xs">
          <option value="">Semua</option>
          <option value="1">Stok menipis</option>
        </select>
      </div>
      <div class="flex items-end">
        <button id="loadData" class="w-full px-3 py-2 text-xs rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">Muat Laporan</button>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Jumlah Item</p>
      <p id="totalItems" class="text-lg font-semibold">0</p>
    </div>
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Penerimaan</p>
      <p id="totalIn" class="text-lg font-semibold">0</p>
    </div>
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Pengeluaran</p>
      <p id="totalOut" class="text-lg font-semibold">0</p>
    </div>
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
      <h2 class="text-sm font-semibold">Detail Rekap</h2>
      <p id="summaryText" class="text-[11px] text-gray-500 dark:text-gray-400">Belum ada data.</p>
    </div>
    <div class="p-0 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800/70">
          <tr>
            <th class="text-left px-3 py-2 text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Kode</th>
            <th class="text-left px-3 py-2 text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Item</th>
            <th class="text-left px-3 py-2 text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Sat</th>
            <th class="text-right px-3 py-2 text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Penerimaan</th>
            <th class="text-right px-3 py-2 text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Pengeluaran</th>
            <th class="text-right px-3 py-2 text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">
              <span class="inline-flex items-center gap-1" title="Sisa stok = Penerimaan - Pengeluaran">
                Sisa
                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 18a6 6 0 100-12 6 6 0 000 12z"/>
                </svg>
              </span>
            </th>
          </tr>
        </thead>
        <tbody id="rekapBody" class="divide-y divide-gray-100 dark:divide-gray-800"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
const formatNumber = (value) => {
  const num = Number(value ?? 0);
  return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(num);
};

const buildQuery = () => {
  const params = new URLSearchParams();
  const pid = document.getElementById('projectId').value;
  const df = document.getElementById('dateFrom').value;
  const dt = document.getElementById('dateTo').value;
  if (pid) params.set('project_id', pid);
  if (df) params.set('date_from', df);
  if (dt) params.set('date_to', dt);
  return params.toString();
};

const updateLinks = () => {
  const query = buildQuery();
  const printBase = `{{ route('dev.stock.rekap.print') }}`;
  const pdfBase = `{{ route('dev.stock.rekap.pdf') }}`;
  const excelBase = `{{ route('dev.stock.rekap.excel') }}`;
  document.getElementById('printBtn').setAttribute('href', query ? `${printBase}?${query}` : printBase);
  document.getElementById('pdfBtn').setAttribute('href', query ? `${pdfBase}?${query}` : pdfBase);
  document.getElementById('excelBtn').setAttribute('href', query ? `${excelBase}?${query}` : excelBase);
};

document.getElementById('loadData').addEventListener('click', async () => {
  const query = buildQuery();
  const res = await fetch(`{{ route('dev.stock.rekap-data') }}?${query}`);
  const data = await res.json();
  const body = document.getElementById('rekapBody');
  body.innerHTML = '';

  let totalIn = 0;
  let totalOut = 0;
  const lowOnly = document.getElementById('lowStockOnly').value === '1';
  const lowStockRatio = 0.15;
  const lowStockAbs = 1;

  data.forEach(row => {
    const kode = row.rab_item?.data?.kode ?? '';
    const uraian = row.rab_item?.data?.uraian ?? '';
    const sat = row.rab_item?.satuan ?? row.rab_item?.data?.satuan ?? '';
    const inQty = Number(row.qty_in ?? 0);
    const outQty = Number(row.qty_out ?? 0);
    const sisa = inQty - outQty;
    const volume = Number(row.rab_item?.volume ?? 0);
    const isLow = sisa <= lowStockAbs || (volume > 0 && sisa <= (volume * lowStockRatio));
    if (lowOnly && !isLow) {
      return;
    }
    const unitLabel = sat ? ` ${sat}` : '';
    totalIn += inQty;
    totalOut += outQty;

    const badge = isLow ? `<span class="ml-2 inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">Stok menipis</span>` : '';
    body.innerHTML += `<tr>
      <td class="px-3 py-2">${kode}</td>
      <td class="px-3 py-2">${uraian}${badge}</td>
      <td class="px-3 py-2">${sat}</td>
      <td class="px-3 py-2 text-right">${formatNumber(inQty)}${unitLabel}</td>
      <td class="px-3 py-2 text-right">${formatNumber(outQty)}${unitLabel}</td>
      <td class="px-3 py-2 text-right ${sisa < 0 ? 'text-rose-600 dark:text-rose-300' : ''}">${formatNumber(sisa)}${unitLabel}</td>
    </tr>`;
  });

  const visibleRows = body.querySelectorAll('tr').length;
  document.getElementById('totalItems').textContent = formatNumber(visibleRows);
  document.getElementById('totalIn').textContent = formatNumber(totalIn);
  document.getElementById('totalOut').textContent = formatNumber(totalOut);
  document.getElementById('summaryText').textContent = visibleRows ? `Menampilkan ${visibleRows} item.` : 'Tidak ada data.';

  updateLinks();
});

['projectId', 'dateFrom', 'dateTo', 'lowStockOnly'].forEach((id) => {
  const el = document.getElementById(id);
  if (el) {
    el.addEventListener('change', updateLinks);
  }
});

updateLinks();
</script>
@endsection


