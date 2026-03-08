<?php

namespace App\Support;

use App\Events\NotificationCreated;
use App\Models\AuditLog;
use App\Models\AppNotification;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApprovalStatusMail;

class NotificationService
{
    private static function normalizeHref(?string $href): ?string
    {
        $href = is_string($href) ? trim($href) : null;
        if (!$href) {
            return null;
        }

        // Relative path is safest across env/port differences.
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

        $base = rtrim((string) config('app.url', ''), '/');
        if ($base === '') {
            return $path . $query . $fragment;
        }

        return $base . $path . $query . $fragment;
    }

    public static function notifyUsers(array $userIds, string $title, string $message, ?string $href = null, string $type = 'system', array $data = []): void
    {
        $userIds = collect($userIds)->filter()->unique()->values();
        if ($userIds->isEmpty()) {
            return;
        }

        $settings = NotificationSetting::query()
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        foreach ($userIds as $userId) {
            $muted = $settings->get($userId)?->muted_types ?? [];
            if (in_array($type, $muted, true)) {
                continue;
            }
            $notification = AppNotification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'href' => self::normalizeHref($href),
                'data' => $data,
            ]);

            broadcast(new NotificationCreated($notification))->toOthers();
        }
    }

    public static function notifyUser(int $userId, string $title, string $message, ?string $href = null, string $type = 'system', array $data = []): void
    {
        self::notifyUsers([$userId], $title, $message, $href, $type, $data);
    }

    public static function notifySubmitter(Model $model, string $title, string $message, ?string $href = null, string $type = 'approval', array $data = []): void
    {
        $submitterId = null;
        $submittedBy = $model->submitted_by ?? $model->created_by ?? null;
        if (is_numeric($submittedBy)) {
            $submitterId = (int) $submittedBy;
        }

        if (!$submitterId) {
            $log = AuditLog::query()
                ->where('model_type', get_class($model))
                ->where('model_id', $model->getKey())
                ->where('action', 'like', '%.submitted')
                ->orderByDesc('id')
                ->first();
            $submitterId = $log?->user_id;
        }

        if ($submitterId) {
            self::notifyUser($submitterId, $title, $message, $href, $type, $data);
            $user = User::find($submitterId);
            $emailEnabled = NotificationSetting::where('user_id', $submitterId)->value('email_enabled');
            if ($emailEnabled === null) {
                $emailEnabled = true;
            }
            if ($user && $user->email && $emailEnabled) {
                try {
                    Mail::to($user->email)->send(new ApprovalStatusMail(
                        $user->name ?? 'User',
                        $title,
                        $message,
                        $href,
                        $data['rejected_reason'] ?? null
                    ));
                } catch (\Throwable $e) {
                    // Fail silently for email errors
                }
            }
        }
    }

    public static function notifyHO(string $title, string $message, ?string $href = null, string $type = 'approval', array $data = []): void
    {
        $userIds = User::query()
            ->where(function ($q) {
                $q->where('role', 'ho')->orWhere('is_admin', true);
            })
            ->pluck('id')
            ->all();

        self::notifyUsers($userIds, $title, $message, $href, $type, $data);
    }
}
