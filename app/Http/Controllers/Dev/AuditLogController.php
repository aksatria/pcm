<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $q = AuditLog::query()->with('user');

        $action = trim((string) $request->get('action', ''));
        $model = trim((string) $request->get('model', ''));
        $userId = $request->get('user_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if ($action !== '') {
            $q->where('action', 'like', "%{$action}%");
        }
        if ($model !== '') {
            $q->where('model_type', 'like', "%{$model}%");
        }
        if (!empty($userId)) {
            $q->where('user_id', $userId);
        }
        if ($dateFrom) {
            $q->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $q->orderByDesc('id')->paginate(25)->appends($request->query());
        $users = User::orderBy('name')->get();

        return view('dev.audit_logs.index', compact('logs', 'users', 'action', 'model', 'userId', 'dateFrom', 'dateTo'));
    }
}
