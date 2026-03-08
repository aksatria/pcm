@extends('layouts.dev')
@section('title', 'Dokumen Proyek')
@section('subtitle', 'Pilih proyek & dokumen')

@section('content')
<div class="space-y-4">
  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
    <h2 class="text-sm font-semibold mb-2 text-gray-900 dark:text-white">Pilih Proyek</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      @foreach($projects as $project)
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-gray-50 dark:bg-gray-800/60">
          <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $project->name ?? 'Project #' . $project->id }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ $project->code ?? '-' }}</div>
          <div class="mt-3 flex flex-wrap gap-2 text-xs">
            <a class="px-2 py-1 rounded bg-gray-900 text-white hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600" href="{{ route('dev.documents.select', [$project->id, 'doc' => 'spp']) }}">SPP</a>
            <a class="px-2 py-1 rounded bg-gray-900 text-white hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600" href="{{ route('dev.documents.select', [$project->id, 'doc' => 'bpg']) }}">BPG</a>
            <a class="px-2 py-1 rounded bg-gray-900 text-white hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600" href="{{ route('dev.documents.select', [$project->id, 'doc' => 'lpb']) }}">LPB</a>
            <a class="px-2 py-1 rounded bg-gray-900 text-white hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600" href="{{ route('dev.documents.select', [$project->id, 'doc' => 'po']) }}">PO</a>
            <a class="px-2 py-1 rounded bg-gray-900 text-white hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600" href="{{ route('dev.documents.select', [$project->id, 'doc' => 'spk']) }}">SPK</a>
            <a class="px-2 py-1 rounded bg-gray-900 text-white hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600" href="{{ route('dev.documents.select', [$project->id, 'doc' => 'komparasi']) }}">Komparasi</a>
            <a class="px-2 py-1 rounded bg-gray-900 text-white hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600" href="{{ route('dev.documents.select', [$project->id, 'doc' => 'voucher']) }}">Voucher</a>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endsection


