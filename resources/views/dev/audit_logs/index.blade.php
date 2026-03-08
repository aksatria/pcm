@extends('layouts.dev')
@section('title', 'Audit Trail')
@section('subtitle', 'Riwayat perubahan sistem')

@section('content')
<div class="p-6 space-y-6 text-gray-900 dark:text-gray-100">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold">Audit Trail</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Catatan siapa melakukan perubahan, kapan, dan apa yang berubah.</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Action</label>
                <input type="text" name="action" value="{{ $action ?? '' }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white" placeholder="spp.updated">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Model</label>
                <input type="text" name="model" value="{{ $model ?? '' }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white" placeholder="App\\Models\\Spp">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">User</label>
                <select name="user_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <option value="">Semua</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((string) $userId === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Dari</label>
                <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-300 mb-1">Tanggal Sampai</label>
                <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
            </div>
            <div class="md:col-span-5 flex items-center justify-between">
                <p class="text-[11px] text-gray-500 dark:text-gray-400">Filter audit log berdasarkan action, model, user, dan tanggal.</p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('dev.audit-logs.index') }}" class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">Reset</a>
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-xs font-semibold text-white">Terapkan</button>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold">Log Terbaru</h2>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">Menampilkan {{ $logs->count() }} dari {{ $logs->total() }} log.</p>
        </div>
        <div class="p-0 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/70">
                    <tr>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Waktu</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">User</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Action</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Model</th>
                        <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wide">Meta</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-3 py-2">{{ $log->created_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="px-3 py-2 font-medium">{{ $log->action }}</td>
                            <td class="px-3 py-2">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->model_type }}</div>
                                <div class="text-sm">#{{ $log->model_id ?? '-' }}</div>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">{{ json_encode($log->meta) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada log.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">
                {{ $logs->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </div>
</div>
@endsection
