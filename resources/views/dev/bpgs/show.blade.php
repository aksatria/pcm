@extends('layouts.dev')
@section('title', 'Detail BPG')

@section('content')
@php
  $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
  $status = $bpg->status ?? 'draft';
  $isHO = auth()->check() && method_exists(auth()->user(), 'isHO') && auth()->user()->isHO();
  $locked = in_array(strtolower($status), ['submitted', 'approved', 'closed', 'final', 'cancelled'], true);
  $canEdit = $isHO || !$locked;
  $lpbRef = $bpg->lpb ?? null;
  $lpbApproved = $lpbRef && (($lpbRef->status ?? '') === 'approved');
  $rabApproved = ($rab->status ?? '') === 'approved';
@endphp
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&display=swap');
  .bpg-title { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; letter-spacing: -0.01em; }
  .bpg-card-title { font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; }
</style>
<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <h1 class="bpg-title text-2xl font-semibold">Detail BPG</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">Proyek: {{ $project->name ?? '-' }} | RAPP: {{ $rab->name ?? ('RAPP #'.$rab->id) }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
      @if(in_array($status, ['draft', 'rejected']))
        <form method="POST" action="{{ route('dev.rabs.bpgs.submit', [$project->id, $rab->id, $bpg->id]) }}">
          @csrf
          <button type="submit"
                  @if(!$lpbApproved || !$rabApproved) disabled title="Pastikan RAPP dan LPB sudah approved" @endif
                  class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium @if(!$lpbApproved || !$rabApproved) opacity-60 cursor-not-allowed hover:bg-indigo-600 @endif">
            Submit
          </button>
        </form>
      @endif
      @if($status === 'submitted' && $isHO)
        <form method="POST" action="{{ route('dev.rabs.bpgs.approve', [$project->id, $rab->id, $bpg->id]) }}">
          @csrf
          <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">Approve</button>
        </form>
        <label for="rejectBpgModal" class="inline-flex items-center px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium cursor-pointer">Reject</label>
      @endif
      <a href="{{ route('dev.rab-baseline.bpgs.index', [$project->id, $rab->id]) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm font-medium">Kembali</a>
      @if($canEdit)
        <a href="{{ route('dev.rabs.bpgs.edit', [$project->id, $rab->id, $bpg->id]) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">Edit</a>
      @endif
      <a href="{{ route('dev.rabs.bpgs.print', [$project->id, $rab->id, $bpg->id]) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">Print</a>
      <a href="{{ route('dev.rabs.bpgs.pdf', [$project->id, $rab->id, $bpg->id]) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-900 hover:bg-black text-white text-sm font-medium">PDF</a>
    </div>
  </div>

  @if(!$rabApproved)
    <div class="rounded-xl border border-amber-200 dark:border-amber-900/40 bg-amber-50/80 dark:bg-amber-900/20 p-4 text-sm text-amber-800 dark:text-amber-200">
      RAPP belum approved. BPG hanya bisa disubmit setelah RAPP disetujui.
    </div>
  @endif

  @if(!$lpbRef)
    <div class="rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/80 dark:bg-rose-900/20 p-4 text-sm text-rose-800 dark:text-rose-200">
      BPG ini belum memiliki referensi LPB. Pilih LPB di halaman Edit.
    </div>
  @elseif(!$lpbApproved)
    <div class="rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/80 dark:bg-rose-900/20 p-4 text-sm text-rose-800 dark:text-rose-200">
      LPB referensi ({{ $lpbRef->lpb_no ?? ('LPB #'.$lpbRef->id) }}) belum approved.
    </div>
  @endif

  @if(!$canEdit)
    <div class="rounded-xl border border-blue-200 dark:border-blue-900/40 bg-blue-50/80 dark:bg-blue-900/20 p-4 text-sm text-blue-800 dark:text-blue-200">
      Dokumen sudah {{ $status }} dan tidak bisa diubah.
    </div>
  @endif

  @if($status === 'rejected' && ($bpg->rejected_reason ?? null))
    <div class="rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/80 dark:bg-rose-900/20 p-4 text-sm text-rose-800 dark:text-rose-200">
      <div class="font-semibold mb-1">Alasan Reject</div>
      <div class="text-xs leading-relaxed">{{ $bpg->rejected_reason }}</div>
    </div>
  @endif

  @if($status === 'submitted' && $isHO)
    <input id="rejectBpgModal" type="checkbox" class="peer hidden" />
    <div class="fixed inset-0 hidden peer-checked:flex items-center justify-center z-50">
      <label for="rejectBpgModal" class="absolute inset-0 bg-black/40"></label>
      <form method="POST" action="{{ route('dev.rabs.bpgs.reject', [$project->id, $rab->id, $bpg->id]) }}" class="relative bg-white dark:bg-gray-900 w-full max-w-md rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-xl">
        @csrf
        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Alasan Reject BPG</div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Isi alasan agar pengaju mengetahui perbaikan yang diperlukan.</p>
        <textarea name="rejected_reason" rows="3" class="mt-3 w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-200 px-3 py-2" placeholder="Tulis alasan reject..."></textarea>
        <div class="mt-4 flex items-center justify-end gap-2">
          <label for="rejectBpgModal" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 cursor-pointer">Batal</label>
          <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium">Reject</button>
        </div>
      </form>
    </div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="group relative min-h-[170px]">
      <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
      <div class="relative bg-gradient-to-br from-blue-500 via-blue-600 to-blue-700 text-white rounded-xl p-4 h-full transform transition-all duration-300 hover:scale-[1.01] hover:shadow-xl">
        <div class="bpg-card-title text-xs font-semibold text-blue-100 uppercase tracking-wide mb-3">Ringkasan</div>
        <div class="text-xs text-blue-100/90 mb-1">Nomor BPG</div>
        <div class="text-base font-semibold">{{ $bpg->bpg_no ?? '-' }}</div>
        <div class="mt-3 text-xs text-blue-100/90 mb-1">Tanggal</div>
        <div class="text-sm font-medium">{{ $fmtDate($bpg->bpg_date ?? null) }}</div>
        <div class="mt-3 text-xs text-blue-100/90 mb-1">Status</div>
        <div class="text-sm font-semibold">
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/20 text-white">{{ $status }}</span>
        </div>
      </div>
    </div>

    <div class="group relative min-h-[170px]">
      <div class="absolute -inset-0.5 bg-gradient-to-r from-amber-600 to-amber-700 rounded-xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
      <div class="relative bg-gradient-to-br from-amber-500 via-amber-600 to-amber-700 text-white rounded-xl p-4 h-full transform transition-all duration-300 hover:scale-[1.01] hover:shadow-xl">
        <div class="bpg-card-title text-xs font-semibold text-amber-100 uppercase tracking-wide mb-3">Approval</div>
        <div class="text-xs text-amber-100/90 mb-1">Proyek</div>
        <div class="text-sm font-semibold">{{ $project->name ?? '-' }}</div>
        <div class="mt-3 text-xs text-amber-100/90 mb-1">Referensi LPB</div>
        <div class="text-sm font-medium">
          {{ $lpbRef?->lpb_no ?? ($lpbRef?->id ? ('LPB #'.$lpbRef->id) : '-') }}
          @if($lpbRef)
            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white/20 text-white">
              {{ $lpbRef->status ?? '-' }}
            </span>
          @endif
        </div>
        <div class="mt-3 text-xs text-amber-100/90 mb-1">Diminta Oleh</div>
        <div class="text-sm font-medium">{{ $bpg->requested_by ?? '-' }}</div>
        <div class="mt-3 text-xs text-amber-100/90 mb-1">Disetujui Oleh</div>
        <div class="text-sm font-medium">{{ $bpg->approved_by ?? '-' }}</div>
      </div>
    </div>

    <div class="group relative min-h-[170px]">
      <div class="absolute -inset-0.5 bg-gradient-to-r from-emerald-600 to-emerald-700 rounded-xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
      <div class="relative bg-gradient-to-br from-emerald-500 via-emerald-600 to-emerald-700 text-white rounded-xl p-4 h-full transform transition-all duration-300 hover:scale-[1.01] hover:shadow-xl">
        <div class="bpg-card-title text-xs font-semibold text-emerald-100 uppercase tracking-wide mb-3">Catatan</div>
        <div class="text-xs text-emerald-100/90 mb-1">Diketahui Oleh</div>
        <div class="text-sm font-medium">{{ $bpg->known_by ?? '-' }}</div>
        <div class="mt-3 text-xs text-emerald-100/90 mb-1">Catatan</div>
        <div class="text-sm">{{ $bpg->notes ?? '-' }}</div>
      </div>
    </div>
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800">
      <h2 class="text-sm font-semibold">Item BPG</h2>
    </div>
    <div class="p-5 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800/70">
          <tr>
            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Item</th>
            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Qty</th>
            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Satuan</th>
            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Saldo Stok</th>
            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Tanggal Keluar</th>
            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Keterangan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @forelse($bpg->items ?? [] as $item)
            <tr>
              <td class="px-3 py-2">
                <div class="font-medium">{{ $item->item_name_snapshot ?? $item->rabItem?->data?->uraian ?? '-' }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->item_code_snapshot ?? $item->rabItem?->data?->kode ?? '-' }}</div>
              </td>
              <td class="px-3 py-2">{{ $item->qty ?? 0 }}</td>
              <td class="px-3 py-2">{{ $item->unit ?? $item->unit_snapshot ?? '-' }}</td>
              @php
                $balance = $stockBalances[$item->rab_item_id] ?? 0;
                $balanceClass = $balance < 0 ? 'text-rose-600 dark:text-rose-300' : 'text-gray-900 dark:text-gray-100';
                $unitLabel = $item->unit ?? $item->unit_snapshot ?? '';
                $volume = $item->rabItem?->volume ?? 0;
                $lowStockRatio = 0.15;
                $lowStockAbs = 1;
                $isLow = $balance <= $lowStockAbs || ($volume > 0 && $balance <= ($volume * $lowStockRatio));
              @endphp
              <td class="px-3 py-2 {{ $balanceClass }}">
                {{ number_format($balance, 2, ',', '.') }} {{ $unitLabel }}
                @if($isLow)
                  <span class="ml-2 inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                    Stok menipis
                  </span>
                @endif
              </td>
              <td class="px-3 py-2">{{ $fmtDate($item->issue_date ?? null) }}</td>
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





