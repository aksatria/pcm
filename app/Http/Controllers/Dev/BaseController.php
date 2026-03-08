<?php
// app/Http\Controllers/Dev/BaseController.php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class BaseController extends Controller
{
    /**
     * Constructor - dengan error handling
     */
    public function __construct()
    {
        // HAPUS BARIS INI: $this->middleware('auth');
        // Middleware auth sudah dihandle di routes
        
        // Panggil shareSidebarData tapi dengan error handling
        try {
            $this->shareSidebarData();
        } catch (\Exception $e) {
            Log::warning('BaseController constructor error: ' . $e->getMessage());
            // Tetap lanjutkan tanpa sidebar data
        }
    }

    /**
     * Share sidebar data to all views
     */
    protected function shareSidebarData()
    {
        try {
            // Cek tabel clients
            if (Schema::hasTable('clients')) {
                $clientsCount = \App\Models\Client::count();
            } else {
                $clientsCount = 0;
            }
            
            // Cek tabel projects
            if (Schema::hasTable('projects')) {
                $projectsCount = \App\Models\Project::count();
            } else {
                $projectsCount = 0;
            }
            
            // Share data to all views dengan default values
            View::share('sidebarData', [
                'clientsCount' => $clientsCount,
                'projectsCount' => $projectsCount,
                'notificationsUnread' => $this->getUnreadNotificationsCount(),
                'approvalsPending' => $this->getPendingApprovalsCount()
            ]);

            Log::debug('Sidebar data shared successfully', [
                'clientsCount' => $clientsCount,
                'projectsCount' => $projectsCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error sharing sidebar data: ' . $e->getMessage());
            
            // Fallback values
            View::share('sidebarData', [
                'clientsCount' => 0,
                'projectsCount' => 0,
                'notificationsUnread' => 0,
                'approvalsPending' => 0
            ]);
        }
    }

    protected function getUnreadNotificationsCount(): int
    {
        try {
            if (!auth()->check() || !Schema::hasTable('notifications')) {
                return 0;
            }

            $muted = [];
            if (Schema::hasTable('notification_settings')) {
                $muted = NotificationSetting::where('user_id', auth()->id())->value('muted_types') ?? [];
                if (!is_array($muted)) {
                    $muted = [];
                }
            }

            $query = AppNotification::where('user_id', auth()->id())->whereNull('read_at');
            if (!empty($muted)) {
                $query->whereNotIn('type', $muted);
            }

            return $query->count();
        } catch (\Exception $e) {
            Log::warning('Failed to count unread notifications: ' . $e->getMessage());
            return 0;
        }
    }

    protected function getPendingApprovalsCount(): int
    {
        try {
            if (!auth()->check() || !method_exists(auth()->user(), 'isHO') || !auth()->user()->isHO()) {
                return 0;
            }

            $total = 0;
            if (Schema::hasTable('spps')) {
                $total += Spp::where('status', 'submitted')->count();
            }
            if (Schema::hasTable('rabs')) {
                $total += Rab::where('status', 'submitted')->count();
            }
            if (Schema::hasTable('rab_breakdowns')) {
                $total += RabBreakdown::where('approval_status', 'submitted')->count();
            }
            if (Schema::hasTable('bpgs')) {
                $total += Bpg::where('status', 'submitted')->count();
            }
            if (Schema::hasTable('lpbs')) {
                $total += Lpb::where('status', 'submitted')->count();
            }
            if (Schema::hasTable('purchase_orders')) {
                $total += PurchaseOrder::where('status', 'submitted')->count();
            }
            if (Schema::hasTable('spks')) {
                $total += Spk::where('status', 'submitted')->count();
            }
            if (Schema::hasTable('vendor_comparisons')) {
                $total += VendorComparison::where('status', 'submitted')->count();
            }
            if (Schema::hasTable('purchase_vouchers')) {
                $total += PurchaseVoucher::where('status', 'submitted')->count();
            }
            if (Schema::hasTable('vouchers')) {
                $total += Voucher::where('status', 'submitted')->count();
            }

            return $total;
        } catch (\Exception $e) {
            Log::warning('Failed to count pending approvals: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generate success response
     */
    protected function successResponse($message = 'Operation completed successfully', $data = null, $redirect = null)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];

        if ($redirect) {
            $response['redirect'] = $redirect;
        }

        return response()->json($response);
    }

    /**
     * Generate error response
     */
    protected function errorResponse($message = 'An error occurred', $errors = null, $code = 400)
    {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ];

        return response()->json($response, $code);
    }

    /**
     * Format currency input to integer
     */
    protected function parseCurrency($value)
    {
        if (empty($value) || $value === '0') {
            return 0;
        }
        
        if (is_numeric($value)) {
            return (int) $value;
        }
        
        $cleanValue = preg_replace('/[^\d]/', '', (string) $value);
        return $cleanValue ? (int) $cleanValue : 0;
    }

    /**
     * Generate unique code with prefix
     */
    protected function generateUniqueCode($model, $prefix, $field = 'code', $length = 3)
    {
        $year = date('Y');
        $fullPrefix = "{$prefix}-{$year}-";
        
        $lastRecord = $model::where($field, 'like', $fullPrefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $start = $lastRecord ? ((int) last(explode('-', $lastRecord->{$field})) + 1) : 1;
        $attempt = $start;
        
        do {
            $candidate = $fullPrefix . str_pad($attempt, $length, '0', STR_PAD_LEFT);
            if (!$model::where($field, $candidate)->exists()) {
                return $candidate;
            }
            $attempt++;
        } while ($attempt < 1000);

        return $fullPrefix . uniqid();
    }

    /**
     * Validate file upload
     */
    protected function validateFile($file, $maxSize = 10240, $allowedMimes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'])
    {
        if (!$file->isValid()) {
            return false;
        }

        if ($file->getSize() > $maxSize * 1024) {
            return false;
        }

        $extension = $file->getClientOriginalExtension();
        if (!in_array(strtolower($extension), $allowedMimes)) {
            return false;
        }

        return true;
    }

    /**
     * Get file type based on filename or user selection
     */
    protected function getFileType($fileTypes, $index, $originalName)
    {
        if (isset($fileTypes[$index]) && !empty($fileTypes[$index])) {
            return $fileTypes[$index];
        }
        
        $name = strtolower($originalName);
        if (str_contains($name, 'surat') || str_contains($name, 'perintah')) {
            return 'surat_perintah';
        } elseif (str_contains($name, 'kontrak')) {
            return 'kontrak';
        } elseif (str_contains($name, 'proposal')) {
            return 'proposal';
        } elseif (str_contains($name, 'laporan')) {
            return 'laporan';
        }
        
        return 'lainnya';
    }

    /**
     * Calculate duration between two dates
     */
    protected function calculateDuration($startDate, $endDate)
    {
        if (!$startDate || !$endDate) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        return $start->diffInDays($end);
    }

    /**
     * Format number to Indonesian currency
     */
    protected function formatCurrency($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Get status color class
     */
    protected function getStatusColor($status)
    {
        $colors = [
            'Planning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
            'Active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'On Hold' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
            'Completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            'Cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        ];

        return $colors[$status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }

    /**
     * Get pagination parameters from request
     */
    protected function getPaginationParams($request, $defaultPerPage = 10)
    {
        return [
            'per_page' => $request->get('per_page', $defaultPerPage),
            'page' => $request->get('page', 1),
            'sort' => $request->get('sort', 'created_at'),
            'order' => $request->get('order', 'desc'),
        ];
    }

    /**
     * Apply search filter to query
     */
    protected function applySearch($query, $search, $searchableFields)
    {
        if ($search && !empty($searchableFields)) {
            $query->where(function($q) use ($search, $searchableFields) {
                foreach ($searchableFields as $field) {
                    if (str_contains($field, '.')) {
                        // Relationship field
                        [$relation, $column] = explode('.', $field);
                        $q->orWhereHas($relation, function($q) use ($column, $search) {
                            $q->where($column, 'like', "%{$search}%");
                        });
                    } else {
                        // Direct field
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Apply date range filter
     */
    protected function applyDateRange($query, $startDate, $endDate, $dateField = 'created_at')
    {
        if ($startDate) {
            $query->whereDate($dateField, '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate($dateField, '<=', $endDate);
        }

        return $query;
    }

    /**
     * Check if database table exists
     */
    protected function tableExists($tableName)
    {
        try {
            return Schema::hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get current user information
     */
    protected function getCurrentUser()
    {
        return auth()->user();
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated()
    {
        return auth()->check();
    }

    /**
     * Get user role
     */
    protected function getUserRole()
    {
        return auth()->check() ? auth()->user()->role : 'guest';
    }

    /**
     * Check if user has specific role
     */
    protected function hasRole($role)
    {
        return $this->getUserRole() === $role;
    }

    /**
     * Log activity
     */
    protected function logActivity($activity, $data = [])
    {
        $user = $this->getCurrentUser();
        
        Log::info('User Activity:', [
            'user_id' => $user ? $user->id : 'guest',
            'user_name' => $user ? $user->name : 'Unknown',
            'activity' => $activity,
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Validate and sanitize input
     */
    protected function sanitizeInput($input)
    {
        if (is_array($input)) {
            return array_map(function($item) {
                return is_string($item) ? trim(strip_tags($item)) : $item;
            }, $input);
        }
        
        return is_string($input) ? trim(strip_tags($input)) : $input;
    }

    /**
     * Generate random password
     */
    protected function generateRandomPassword($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }

    /**
     * Send success flash message
     */
    protected function sendSuccess($message)
    {
        return back()->with('success', $message);
    }

    /**
     * Send error flash message
     */
    protected function sendError($message)
    {
        return back()->with('error', $message);
    }

    /**
     * Send warning flash message
     */
    protected function sendWarning($message)
    {
        return back()->with('warning', $message);
    }

    /**
     * Send info flash message
     */
    protected function sendInfo($message)
    {
        return back()->with('info', $message);
    }
}
