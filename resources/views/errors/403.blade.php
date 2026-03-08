@extends('layouts.dev')

@section('title', 'Akses Ditolak')

@section('content')
<div class="p-6">
  <div class="max-w-xl mx-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
    <div class="text-sm font-semibold text-rose-600 dark:text-rose-400">Akses Ditolak</div>
    <h1 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">Dokumen Terkunci</h1>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
      {{ $exception->getMessage() ?: 'Anda tidak memiliki akses untuk mengubah dokumen ini.' }}
    </p>
    <div class="mt-4 flex items-center gap-3">
      <a href="{{ url()->previous() }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-900 hover:bg-black text-white text-sm font-medium">
        Kembali
      </a>
      <a href="{{ route('dev.dashboard') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
        Dashboard
      </a>
    </div>
  </div>
</div>
@endsection
