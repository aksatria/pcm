@extends('layouts.dev')
@section('title', 'Purchase Order')
@section('content')
@php
  $statusClass = function($status) {
    return match($status) {
      'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
      'submitted' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
      'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
      'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-800/60 dark:text-gray-200',
      'paid', 'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
      default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    };
  };
  $rows = $purchaseOrders;
  $totalRows = $rows->count();
  $draftRows = $rows->where('status', 'draft')->count();
  $submittedRows = $rows->where('status', 'submitted')->count();
  $approvedRows = $rows->where('status', 'approved')->count();
  $rejectedRows = $rows->where('status', 'rejected')->count();
@endphp

<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
      <h1 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-white">Purchase Order</h1>
      <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
        Proyek: <span class="font-semibold">{{ $project->name }}</span>
        @if(!empty($project->code))
          &bull; Kode: <span class="font-mono">{{ $project->code }}</span>
        @endif
        @if(!empty($rab->name))
          &bull; RAPP: <span class="font-semibold">{{ $rab->name }}</span>
        @endif
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="{{ route('dev.projects.show', $project->id) }}"
         class="inline-flex items-center px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm">
        Kembali ke Project
      </a>
      <a href="{{ route('dev.rab-baseline.index', $project->id) }}"
         class="inline-flex items-center px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm">
        Kembali ke RAPP
      </a>
      <a href="{{ route('dev.rabs.purchase-orders.create', [$project->id, $rab->id]) }}"
         class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">
        + Buat PO
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
    @foreach([
      ['title' => 'Total', 'value' => $totalRows, 'description' => 'Semua PO', 'color' => 'blue', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
      ['title' => 'Draft', 'value' => $draftRows, 'description' => 'Dalam pengerjaan', 'color' => 'gray', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
      ['title' => 'Submitted', 'value' => $submittedRows, 'description' => 'Menunggu persetujuan', 'color' => 'yellow', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
      ['title' => 'Approved', 'value' => $approvedRows, 'description' => 'Disetujui', 'color' => 'green', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
      ['title' => 'Rejected', 'value' => $rejectedRows, 'description' => 'Ditolak', 'color' => 'rose', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ] as $stat)
    <div class="group relative min-h-[110px]">
      <div class="absolute -inset-0.5 bg-gradient-to-r from-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 rounded-xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
      <div class="relative bg-gradient-to-br from-{{ $stat['color'] }}-500 via-{{ $stat['color'] }}-600 to-{{ $stat['color'] }}-700 text-white rounded-xl p-4 h-full transform transition-all duration-300 hover:scale-[1.02] hover:shadow-xl flex flex-col">
        <div class="flex items-start justify-between mb-2">
          <div class="flex-1">
            <p class="text-{{ $stat['color'] }}-100 text-xs font-medium mb-1">{{ $stat['title'] }}</p>
            <p class="text-2xl font-bold">{{ $stat['value'] }}</p>
          </div>
          <div class="bg-{{ $stat['color'] }}-400/20 p-1.5 rounded-lg backdrop-blur-sm border border-{{ $stat['color'] }}-300/20 ml-2 flex-shrink-0">
            <svg class="w-5 h-5 transform group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"/>
            </svg>
          </div>
        </div>
        <div class="mt-auto">
          <p class="text-{{ $stat['color'] }}-100 text-xs">{{ $stat['description'] }}</p>
        </div>
      </div>
    </div>
    @endforeach
  </div>

  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
      <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Daftar Purchase Order</h2>
      <p class="text-[11px] text-gray-500 dark:text-gray-400">Total {{ $totalRows }} PO</p>
    </div>
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 dark:bg-gray-800/70">
        <tr>
          <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">No</th>
          <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Tanggal</th>
          <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Status</th>
          <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse($rows as $row)
          <tr>
            <td class="px-3 py-2">{{ $row->po_no ?? $row->id }}</td>
            <td class="px-3 py-2">{{ optional($row->po_date)->format('d M Y') ?? '-' }}</td>
            <td class="px-3 py-2">
              @php
                $st = $row->status ?? 'draft';
                $label = $st === 'cancelled' ? 'dibatalkan' : $st;
              @endphp
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $statusClass($st) }}">{{ $label }}</span>
              @if($st === 'rejected' && ($row->rejected_reason ?? null))
                <span class="ml-2 inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200" title="{{ $row->rejected_reason }}">Alasan</span>
              @endif
            </td>
            <td class="px-3 py-2 text-right">
              <a class="text-blue-600 dark:text-blue-400" href="{{ route('dev.rabs.purchase-orders.show', [$project->id, $rab->id, $row->id]) }}">Detail</a>
            </td>
          </tr>
        @empty
          <tr><td class="px-3 py-3" colspan="4">Belum ada data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection



