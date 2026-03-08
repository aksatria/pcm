@extends('layouts.dev')

@section('title', 'Inbox Notifikasi')
@section('subtitle', 'Semua notifikasi untuk akun ini')

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-900/70 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 sm:p-6 space-y-4">
        <div class="flex items-start justify-between gap-4 flex-col sm:flex-row">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Inbox Notifikasi</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Filter notifikasi berdasarkan tipe, status, dan tanggal.</p>
            </div>
            <div class="flex items-center gap-2">
                @if(app()->environment('local'))
                    <form method="POST" action="{{ route('dev.notifications.test') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">DEV</span>
                            Send Test
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('dev.notifications.read-all') }}" onsubmit="return confirm('Tandai semua notifikasi sebagai dibaca?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Tandai Semua Dibaca
                    </button>
                </form>
            </div>
        </div>

        <form method="GET" class="bg-gray-50 dark:bg-gray-900/60 p-4 rounded-xl border border-gray-200 dark:border-gray-700 space-y-3">
            <div id="inboxQuickToolbar" class="flex items-center gap-2 sticky top-0 z-10 bg-gray-50 dark:bg-gray-900/60 py-2 transition-shadow duration-200 overflow-x-auto whitespace-nowrap rounded-md">
                <a href="{{ route('dev.notifications.inbox', ['clear' => 1]) }}" class="text-xs border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 {{ ($filters['quick'] ?? '') === '' ? 'bg-white dark:bg-gray-700 text-blue-600 shadow-sm' : 'bg-white/80 dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">
                    All ({{ $counts['all'] ?? 0 }})
                </a>
                <a href="{{ route('dev.notifications.inbox', ['quick' => 'unread']) }}" class="text-xs border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 {{ ($filters['quick'] ?? '') === 'unread' ? 'bg-white dark:bg-gray-700 text-blue-600 shadow-sm' : 'bg-white/80 dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">
                    Unread ({{ $counts['unread'] ?? 0 }})
                </a>
                <a href="{{ route('dev.notifications.inbox', ['quick' => 'approval']) }}" class="text-xs border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 {{ ($filters['quick'] ?? '') === 'approval' ? 'bg-white dark:bg-gray-700 text-blue-600 shadow-sm' : 'bg-white/80 dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">
                    Approval ({{ $counts['approval'] ?? 0 }})
                </a>
                <a href="{{ route('dev.notifications.inbox', ['quick' => 'pending']) }}" class="text-xs border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 {{ ($filters['quick'] ?? '') === 'pending' ? 'bg-white dark:bg-gray-700 text-blue-600 shadow-sm' : 'bg-white/80 dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">
                    Pending ({{ $counts['pending'] ?? 0 }})
                </a>
                <a href="{{ route('dev.notifications.inbox', ['quick' => 'system']) }}" class="text-xs border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 {{ ($filters['quick'] ?? '') === 'system' ? 'bg-white dark:bg-gray-700 text-blue-600 shadow-sm' : 'bg-white/80 dark:bg-gray-800 text-gray-600 dark:text-gray-300' }}">
                    System ({{ $counts['system'] ?? 0 }})
                </a>
            </div>
            <div class="md:hidden text-[11px] text-gray-500 dark:text-gray-400 -mt-2">Swipe &rarr;</div>

            <div class="flex items-center gap-2">
                <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                    <input type="checkbox" name="view" value="compact" class="rounded border-gray-300 dark:border-gray-700 text-blue-600"
                        @checked(($viewMode ?? '') === 'compact') />
                    Mode Compact
                </label>
                <a href="{{ route('dev.notifications.inbox', ['clear_view' => 1]) }}" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors">
                    Reset Compact
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toolbar = document.getElementById('inboxQuickToolbar');
            if (!toolbar) return;

            function updateToolbarShadow() {
                const rect = toolbar.getBoundingClientRect();
                if (rect.top <= 12) {
                    toolbar.classList.add('shadow-md');
                } else {
                    toolbar.classList.remove('shadow-md');
                }
            }

            updateToolbarShadow();
            window.addEventListener('scroll', updateToolbarShadow, { passive: true });
        });
    </script>
    @endpush

    <div class="bg-white dark:bg-gray-900/70 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 sm:p-6">
        <div class="flex items-start justify-between gap-4 flex-col lg:flex-row">
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Preferensi Notifikasi</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Matikan jenis notifikasi tertentu jika tidak ingin menerima.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('dev.notifications.preferences') }}" class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @csrf
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2">
                <input type="checkbox" name="email_enabled" value="1" class="rounded border-gray-300 dark:border-gray-700 text-blue-600"
                    @checked(($setting->email_enabled ?? true)) />
                <span>Email Notification</span>
            </label>
            @foreach($types as $typeOption)
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2">
                    <input type="checkbox" name="muted_types[]" value="{{ $typeOption }}" class="rounded border-gray-300 dark:border-gray-700 text-blue-600"
                        @checked(in_array($typeOption, $setting->muted_types ?? [], true)) />
                    <span>Mute: {{ ucfirst($typeOption) }}</span>
                </label>
            @endforeach
            <div class="sm:col-span-2 lg:col-span-3 flex flex-wrap gap-2 pt-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-gray-900 text-white dark:bg-white dark:text-gray-900 text-sm font-medium">
                    Simpan Preferensi
                </button>
                <button type="submit" name="action" value="mute_all" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300">
                    Mute Semua
                </button>
                <button type="submit" name="action" value="unmute_all" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300">
                    Unmute Semua
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-900/70 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Daftar Notifikasi</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Menampilkan {{ $notifications->firstItem() ?? 0 }} - {{ $notifications->lastItem() ?? 0 }} dari {{ $notifications->total() }}</p>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @php $compact = ($viewMode ?? '') === 'compact'; @endphp
            @php
                $typeLabels = [
                    'approval' => 'Approval',
                    'vendor' => 'Vendor',
                    'stock' => 'Stock',
                    'pending' => 'Pending',
                    'system' => 'System',
                ];
            @endphp
            @forelse($grouped as $typeGroup => $items)
                <div class="px-4 sm:px-6 py-3 bg-gray-50 dark:bg-gray-800/60 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">
                    {{ $typeLabels[$typeGroup] ?? ucfirst($typeGroup) }}
                </div>
                @foreach($items as $notification)
                    @php $isUnread = is_null($notification->read_at); @endphp
                    @php
                        $type = $notification->type ?? 'system';
                        if ($type === 'approval') {
                            $typeBadge = 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200';
                            $typeIcon = 'M9 12.75L11.25 15 15 9.75';
                        } elseif ($type === 'pending') {
                            $typeBadge = 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200';
                            $typeIcon = 'M12 6v6l4 2';
                        } elseif ($type === 'vendor') {
                            $typeBadge = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200';
                            $typeIcon = 'M16 7a4 4 0 11-8 0 4 4 0 018 0m6 13a7 7 0 00-14 0';
                        } elseif ($type === 'stock') {
                            $typeBadge = 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200';
                            $typeIcon = 'M20 13V7a2 2 0 00-2-2h-4M4 11v6a2 2 0 002 2h4';
                        } else {
                            $typeBadge = 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200';
                            $typeIcon = 'M12 8h.01M11 12h1v4h1';
                        }
                    @endphp
                    <div class="px-4 sm:px-6 {{ $compact ? 'py-2' : 'py-4' }} hover:bg-gray-50 dark:hover:bg-gray-800/50 transition {{ $isUnread ? 'bg-blue-50/60 dark:bg-blue-900/20' : '' }}">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-4 {{ $compact ? 'sm:items-center' : '' }}">
                            <div class="flex items-start gap-3 sm:gap-4 flex-1 {{ $compact ? 'sm:items-center' : '' }}">
                                <div class="mt-1">
                                    <span class="inline-flex items-center justify-center w-2.5 h-2.5 rounded-full {{ $isUnread ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $typeBadge }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $typeIcon }}"></path>
                                            </svg>
                                            {{ ucfirst($type) }}
                                        </span>
                                        <span class="text-xs text-gray-400">•</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $notification->created_at?->format('d M Y H:i') }}</span>
                                        @if($isUnread)
                                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">UNREAD</span>
                                        @endif
                                    </div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1 {{ $compact ? 'truncate' : '' }}">{{ $notification->title }}</div>
                                    @if(!$compact && $notification->message)
                                        <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ $notification->message }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 sm:justify-end sm:min-w-[140px]">
                                @if($notification->href)
                                    <form method="POST" action="{{ route('dev.notifications.open', $notification->id) }}">
                                        @csrf
                                        <input type="hidden" name="redirect" value="{{ $notification->href }}">
                                        <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-700">Buka</button>
                                    </form>
                                @endif
                                @if($isUnread)
                                    <form method="POST" action="{{ route('dev.notifications.read', $notification->id) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Tandai dibaca</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @empty
                <div class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada notifikasi.</div>
            @endforelse
        </div>
        <div class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection

