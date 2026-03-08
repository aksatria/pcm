<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AppNotification;
use App\Models\NotificationSetting;
use App\Models\Spp;
use App\Models\Bpg;
use App\Models\Lpb;
use App\Models\PurchaseOrder;
use App\Models\Spk;
use App\Models\VendorComparison;
use App\Models\PurchaseVoucher;
use App\Models\Voucher;
use App\Models\Rab;
use App\Models\RabBreakdown;
use Illuminate\Http\Request;
use App\Support\NotificationService;

class NotificationController extends Controller
{
    private function normalizeHrefForRequest(?string $href, Request $request): ?string
    {
        $href = is_string($href) ? trim($href) : null;
        if (!$href) {
            return null;
        }

        if (str_starts_with($href, '/')) {
            return $href;
        }

        $parts = parse_url($href);
        if (!is_array($parts)) {
            return $href;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!in_array($host, ['localhost', '127.0.0.1'], true)) {
            return $href;
        }

        $path = (string) ($parts['path'] ?? '/');
        $query = isset($parts['query']) ? ('?' . $parts['query']) : '';
        $fragment = isset($parts['fragment']) ? ('#' . $parts['fragment']) : '';

        return rtrim($request->getSchemeAndHttpHost(), '/') . $path . $query . $fragment;
    }

    public function feed(Request $request)
    {
        $user = $request->user();
        $isHO = $user && method_exists($user, 'isHO') && $user->isHO();
        $muted = NotificationSetting::query()
            ->where('user_id', $user?->id)
            ->value('muted_types') ?? [];
        if (!is_array($muted)) {
            $muted = [];
        }

        $items = [];
        $badge = 0;
        $pendingTotal = 0;

        if ($isHO && !in_array('approval', $muted, true)) {
            $pending = [
                'RAPP' => Rab::where('status', 'submitted')->count(),
                'RAB Breakdown' => RabBreakdown::where('approval_status', 'submitted')->count(),
                'SPP' => Spp::where('status', 'submitted')->count(),
                'BPG' => Bpg::where('status', 'submitted')->count(),
                'LPB' => Lpb::where('status', 'submitted')->count(),
                'PO' => PurchaseOrder::where('status', 'submitted')->count(),
                'SPK' => Spk::where('status', 'submitted')->count(),
                'Komparasi' => VendorComparison::where('status', 'submitted')->count(),
                'Voucher Pembelian' => PurchaseVoucher::where('status', 'submitted')->count(),
                'Voucher' => Voucher::where('status', 'submitted')->count(),
            ];

            $pendingTotal = array_sum($pending);
            $badge += $pendingTotal;

            foreach ($pending as $label => $count) {
                if ($count <= 0) {
                    continue;
                }
                $items[] = [
                    'id' => 'pending-' . strtolower(str_replace(' ', '-', $label)),
                    'type' => 'pending',
                    'title' => "Approval {$label}",
                    'message' => "{$count} dokumen menunggu approval.",
                    'time' => 'Baru',
                    'date' => now()->toDateString(),
                    'href' => route('dev.approvals.index'),
                ];
            }
        }

        $notifications = AppNotification::query()
            ->where('user_id', $user?->id)
            ->when($muted, function ($q) use ($muted) {
                $q->whereNotIn('type', $muted);
            })
            ->orderByRaw('read_at IS NULL DESC')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $badge += AppNotification::where('user_id', $user?->id)
            ->whereNull('read_at')
            ->when($muted, function ($q) use ($muted) {
                $q->whereNotIn('type', $muted);
            })
            ->count();

        foreach ($notifications as $notif) {
            $items[] = [
                'id' => (string) $notif->id,
                'type' => $notif->type,
                'title' => $notif->title,
                'message' => $notif->message,
                'time' => optional($notif->created_at)->diffForHumans(),
                'date' => optional($notif->created_at)->toDateString(),
                'href' => $this->normalizeHrefForRequest($notif->href, $request),
                'read_at' => $notif->read_at,
            ];
        }

        return response()->json([
            'badge' => $badge,
            'approvals_badge' => $pendingTotal,
            'items' => $items,
        ]);
    }

    public function markRead(Request $request, $id)
    {
        $user = $request->user();
        $notif = AppNotification::where('user_id', $user->id)->findOrFail($id);
        $notif->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notifikasi ditandai dibaca.');
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();
        AppNotification::where('user_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Semua notifikasi ditandai dibaca.');
    }

    public function inbox(Request $request)
    {
        $user = $request->user();
        if ($request->get('clear') === '1') {
            $request->session()->forget([
                'notifications.filter.type',
                'notifications.filter.status',
                'notifications.filter.date_from',
                'notifications.filter.date_to',
                'notifications.quick',
            ]);
            return redirect()->route('dev.notifications.inbox');
        }
        if ($request->get('clear_view') === '1') {
            $request->session()->forget('notifications.view');
            return redirect()->route('dev.notifications.inbox');
        }
        $setting = NotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['muted_types' => [], 'email_enabled' => true]
        );

        $q = AppNotification::query()->where('user_id', $user->id);
        $type = trim((string) $request->get('type', ''));
        $status = trim((string) $request->get('status', ''));
        $quick = trim((string) $request->get('quick', ''));
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $viewMode = $request->get('view', '');

        if ($request->has('type')) {
            $request->session()->put('notifications.filter.type', $type);
        } elseif ($type === '') {
            $type = (string) $request->session()->get('notifications.filter.type', '');
        }
        if ($request->has('status')) {
            $request->session()->put('notifications.filter.status', $status);
        } elseif ($status === '') {
            $status = (string) $request->session()->get('notifications.filter.status', '');
        }
        if ($request->has('date_from')) {
            $request->session()->put('notifications.filter.date_from', $dateFrom);
        } elseif (!$dateFrom) {
            $dateFrom = $request->session()->get('notifications.filter.date_from');
        }
        if ($request->has('date_to')) {
            $request->session()->put('notifications.filter.date_to', $dateTo);
        } elseif (!$dateTo) {
            $dateTo = $request->session()->get('notifications.filter.date_to');
        }

        $hasManualFilter = $request->has('type') || $request->has('status') || $request->has('date_from') || $request->has('date_to');
        if ($hasManualFilter && !$request->has('quick')) {
            $quick = '';
            $request->session()->put('notifications.quick', '');
        }

        if ($quick === 'unread') {
            $status = 'unread';
        } elseif ($quick === 'approval') {
            $type = 'approval';
        } elseif ($quick === 'pending') {
            $type = 'pending';
        } elseif ($quick === 'system') {
            $type = 'system';
        }

        if ($request->has('quick')) {
            $request->session()->put('notifications.quick', $quick);
        }
        if ($quick === '') {
            $quick = (string) $request->session()->get('notifications.quick', '');
            if ($quick === 'unread') {
                $status = 'unread';
            } elseif ($quick === 'approval') {
                $type = 'approval';
            } elseif ($quick === 'pending') {
                $type = 'pending';
            } elseif ($quick === 'system') {
                $type = 'system';
            }
        }

        if ($request->has('view')) {
            $request->session()->put('notifications.view', $viewMode);
        }
        if ($viewMode === '') {
            $viewMode = (string) $request->session()->get('notifications.view', '');
        }

        if ($type !== '') {
            $q->where('type', $type);
        }
        if ($status === 'unread') {
            $q->whereNull('read_at');
        } elseif ($status === 'read') {
            $q->whereNotNull('read_at');
        }
        if ($dateFrom) {
            $q->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $q->orderByDesc('id')->paginate(20)->appends($request->query());
        $muted = $setting->muted_types ?? [];
        if (!is_array($muted)) {
            $muted = [];
        }

        $counts = AppNotification::query()
            ->where('user_id', $user->id)
            ->when($muted, function ($q) use ($muted) {
                $q->whereNotIn('type', $muted);
            })
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();
        $counts['all'] = array_sum($counts);
        $counts['unread'] = AppNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->when($muted, function ($q) use ($muted) {
                $q->whereNotIn('type', $muted);
            })
            ->count();
        $types = AppNotification::query()
            ->where('user_id', $user->id)
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->all();
        $typeOptions = array_values(array_unique(array_merge(['approval', 'system'], $types)));
        $collection = $logs->getCollection();
        $grouped = $collection
            ->groupBy(function ($item) {
                return $item->type ?? 'system';
            })
            ->map(function ($group) {
                return $group->sortByDesc('created_at');
            })
            ->sortByDesc(function ($group) {
                return $group->max('created_at');
            });

        return view('dev.notifications.inbox', [
            'notifications' => $logs,
            'types' => $typeOptions,
            'grouped' => $grouped,
            'setting' => $setting,
            'counts' => $counts,
            'viewMode' => $viewMode,
            'filters' => [
                'type' => $type,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'quick' => $quick,
            ],
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();
        $action = $request->input('action');
        $muted = $request->input('muted_types', []);
        if (!is_array($muted)) {
            $muted = [];
        }
        $emailEnabled = $request->boolean('email_enabled', true);

        if ($action === 'mute_all') {
            $types = AppNotification::query()
                ->where('user_id', $user->id)
                ->select('type')
                ->distinct()
                ->pluck('type')
                ->all();
            $muted = array_values(array_unique(array_merge(['approval', 'system'], $types)));
        } elseif ($action === 'unmute_all') {
            $muted = [];
        }

        NotificationSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'muted_types' => array_values(array_unique($muted)),
                'email_enabled' => $emailEnabled,
            ]
        );

        return back()->with('success', 'Preferensi notifikasi disimpan.');
    }

    public function open(Request $request, $id)
    {
        $user = $request->user();
        $notif = AppNotification::where('user_id', $user->id)->findOrFail($id);
        $notif->update(['read_at' => now()]);

        $href = $this->normalizeHrefForRequest($notif->href ?: $request->input('redirect'), $request);
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'href' => $href]);
        }

        return $href ? redirect($href) : back();
    }

    public function test(Request $request)
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        $user = $request->user();
        NotificationService::notifyUsers([$user->id], 'Test Notifikasi', 'Realtime berjalan dengan baik.', null, 'system', [
            'source' => 'manual-test',
        ]);

        return back()->with('success', 'Notifikasi test dikirim.');
    }
}

