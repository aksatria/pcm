@extends('layouts.dev')
@section('title', 'Detail SPP')

@section('content')
@php
  $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
  $status = $spp->status ?? 'draft';
  $isHO = auth()->check() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO();
  $locked = in_array(strtolower($status), ['submitted', 'approved', 'closed', 'final', 'cancelled'], true);
  $canEdit = $isHO || !$locked;
  $statusClass = match($status) {
    'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    'submitted' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
    'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    'paid', 'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
  };
@endphp
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&display=swap');
  .spp-title { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; letter-spacing: -0.01em; }
  .spp-kicker { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; }
  .spp-card-title { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; }
</style>

<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <h1 class="spp-title text-2xl font-semibold">Detail SPP</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">Proyek: {{ $project->name ?? '-' }} • RAPP: {{ $rab->name ?? ('RAPP #'.$rab->id) }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
      @if(in_array($status, ['draft', 'rejected']))
        <form method="POST" action="{{ route('dev.rabs.spps.submit', [$project->id, $rab->id, $spp->id]) }}">
          @csrf
          <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">Submit</button>
        </form>
      @endif
      @if($status === 'submitted' && $isHO)
        <form method="POST" action="{{ route('dev.rabs.spps.approve', [$project->id, $rab->id, $spp->id]) }}">
          @csrf
          <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">Approve</button>
        </form>
        <label for="rejectSppModal" class="inline-flex items-center px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium cursor-pointer">Reject</label>
      @endif
      <a href="{{ route('dev.rab-baseline.spps.index', [$project->id, $rab->id]) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm font-medium">Kembali</a>
      @if($canEdit)
        <a href="{{ route('dev.rabs.spps.edit', [$project->id, $rab->id, $spp->id]) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">Edit</a>
      @endif
      <a href="{{ route('dev.rabs.spps.print', [$project->id, $rab->id, $spp->id]) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">Print</a>
      <a href="{{ route('dev.rabs.spps.pdf', [$project->id, $rab->id, $spp->id]) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-900 hover:bg-black text-white text-sm font-medium">PDF</a>
    </div>
  </div>

  @if(!$canEdit)
    <div class="rounded-xl border border-blue-200 dark:border-blue-900/40 bg-blue-50/80 dark:bg-blue-900/20 p-4 text-sm text-blue-800 dark:text-blue-200">
      Dokumen sudah {{ $status }} dan tidak bisa diubah.
    </div>
  @endif

  @if($status === 'rejected' && ($spp->rejected_reason ?? null))
    <div class="rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/80 dark:bg-rose-900/20 p-4 text-sm text-rose-800 dark:text-rose-200">
      <div class="font-semibold mb-1">Alasan Reject</div>
      <div class="text-xs leading-relaxed">{{ $spp->rejected_reason }}</div>
    </div>
  @endif

  @if($status === 'submitted' && $isHO)
    <input id="rejectSppModal" type="checkbox" class="peer hidden" />
    <div class="fixed inset-0 hidden peer-checked:flex items-center justify-center z-50">
      <label for="rejectSppModal" class="absolute inset-0 bg-black/40"></label>
      <form method="POST" action="{{ route('dev.rabs.spps.reject', [$project->id, $rab->id, $spp->id]) }}" class="relative bg-white dark:bg-gray-900 w-full max-w-md rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-xl">
        @csrf
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Alasan Reject SPP</div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Isi alasan agar pengaju mengetahui perbaikan yang diperlukan.</p>
        <textarea name="rejected_reason" rows="3" class="mt-3 w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-200 px-3 py-2" placeholder="Tulis alasan reject..."></textarea>
        <div class="mt-4 flex items-center justify-end gap-2">
          <label for="rejectSppModal" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 cursor-pointer">Batal</label>
          <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium">Reject</button>
        </div>
      </form>
    </div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="group relative min-h-[170px]">
      <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
      <div class="relative bg-gradient-to-br from-blue-500 via-blue-600 to-blue-700 text-white rounded-xl p-4 h-full transform transition-all duration-300 hover:scale-[1.01] hover:shadow-xl">
        <div class="spp-card-title text-xs font-semibold text-blue-100 uppercase tracking-wide mb-3">Ringkasan</div>
        <div class="text-xs text-blue-100/90 mb-1">Nomor SPP</div>
        <div class="text-lg font-semibold">{{ $spp->spp_no ?? '-' }}</div>
        <div class="mt-3 text-xs text-blue-100/90 mb-1">Tanggal</div>
        <div class="text-sm font-medium">{{ $fmtDate($spp->spp_date ?? null) }}</div>
        <div class="mt-3 text-xs text-blue-100/90 mb-1">Status</div>
        <div class="text-sm font-semibold">
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/20 text-white">{{ $status }}</span>
        </div>
      </div>
    </div>

    <div class="group relative min-h-[170px]">
      <div class="absolute -inset-0.5 bg-gradient-to-r from-amber-600 to-amber-700 rounded-xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
      <div class="relative bg-gradient-to-br from-amber-500 via-amber-600 to-amber-700 text-white rounded-xl p-4 h-full transform transition-all duration-300 hover:scale-[1.01] hover:shadow-xl">
        <div class="spp-card-title text-xs font-semibold text-amber-100 uppercase tracking-wide mb-3">Proyek & Vendor</div>
        <div class="text-xs text-amber-100/90 mb-1">Proyek</div>
        <div class="text-sm font-semibold">{{ $project->name ?? '-' }}</div>
        <div class="mt-3 text-xs text-amber-100/90 mb-1">Vendor</div>
        <div class="text-sm font-medium">{{ optional($spp->vendor)->nama ?? '-' }}</div>
        <div class="mt-3 text-xs text-amber-100/90 mb-1">Tanggal Schedule</div>
        <div class="text-sm font-medium">{{ $fmtDate($spp->schedule_date ?? null) }}</div>
      </div>
    </div>

    <div class="group relative min-h-[170px]">
      <div class="absolute -inset-0.5 bg-gradient-to-r from-emerald-600 to-emerald-700 rounded-xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
      <div class="relative bg-gradient-to-br from-emerald-500 via-emerald-600 to-emerald-700 text-white rounded-xl p-4 h-full transform transition-all duration-300 hover:scale-[1.01] hover:shadow-xl">
        <div class="spp-card-title text-xs font-semibold text-emerald-100 uppercase tracking-wide mb-3">Approval</div>
        <div class="text-xs text-emerald-100/90 mb-1">Diminta Oleh</div>
        <div class="text-sm font-medium">{{ $spp->requested_by ?? '-' }}</div>
        <div class="mt-3 text-xs text-emerald-100/90 mb-1">Disetujui Oleh</div>
        <div class="text-sm font-medium">{{ $spp->approved_by ?? '-' }}</div>
        <div class="mt-3 text-xs text-emerald-100/90 mb-1">Catatan</div>
        <div class="text-sm">{{ $spp->notes ?? '-' }}</div>
      </div>
    </div>
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800">
      <h2 class="text-sm font-semibold">Item SPP</h2>
    </div>
    <div class="p-5 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800/70">
          <tr>
            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Item</th>
            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Qty</th>
            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Satuan</th>
            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Schedule</th>
            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Vendor</th>
            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Keterangan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @forelse($spp->items ?? [] as $item)
            <tr>
              <td class="px-3 py-2">
                <div class="font-medium">{{ $item->item_name_snapshot ?? $item->rabItem?->data?->uraian ?? '-' }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->item_code_snapshot ?? $item->rabItem?->data?->kode ?? '-' }}</div>
              </td>
              <td class="px-3 py-2">{{ $item->qty ?? 0 }}</td>
              <td class="px-3 py-2">{{ $item->unit ?? $item->unit_snapshot ?? '-' }}</td>
              <td class="px-3 py-2">{{ $fmtDate($item->schedule_date ?? null) }}</td>
              <td class="px-3 py-2">{{ optional($item->vendor)->nama ?? '-' }}</td>
              <td class="px-3 py-2">{{ $item->work_notes ?? '-' }}</td>
            </tr>
          @empty
            <tr><td class="px-3 py-3" colspan="6">Belum ada item.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection






