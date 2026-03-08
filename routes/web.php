<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Dev\DashboardController;
use App\Http\Controllers\Dev\ClientController;
use App\Http\Controllers\Dev\ProjectController;
use App\Http\Controllers\Dev\DataController;
use App\Http\Controllers\Dev\RappController;
use App\Http\Controllers\Dev\RabBaselineController;
use App\Http\Controllers\Dev\RabTestController;
use App\Http\Controllers\Dev\RabBreakdownController;
use App\Http\Controllers\Dev\BudgetControlController;
use App\Http\Controllers\Dev\VoucherController;
use App\Http\Controllers\Dev\PurchaseVoucherController;
use App\Http\Controllers\Dev\SppController;
use App\Http\Controllers\Dev\BpgController;
use App\Http\Controllers\Dev\LpbController;
use App\Http\Controllers\Dev\PurchaseOrderController;
use App\Http\Controllers\Dev\SpkController;
use App\Http\Controllers\Dev\VendorComparisonController;
use App\Http\Controllers\Dev\MasterItemPriceController;
use App\Http\Controllers\Dev\DocumentShortcutController;
use App\Http\Controllers\Dev\StockReportController;
use App\Http\Controllers\Dev\AuditLogController;
use App\Http\Controllers\Dev\NotificationController;
use App\Http\Controllers\Dev\ApprovalCenterController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ==================== PUBLIC ROUTES ====================
Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// ==================== AUTHENTICATION ROUTES (Laravel Breeze) ====================
if (file_exists(__DIR__.'/auth.php')) {
    require __DIR__.'/auth.php';
}

// ==================== MANUAL AUTH ROUTES (CUSTOM) ====================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ==================== AUTHENTICATED ROUTES ====================
Route::middleware(['auth'])->group(function () {

    // ==================== PROFILE ROUTES ====================
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ==================== MAIN DASHBOARD REDIRECT ====================
    Route::get('/dashboard', function () {
        return redirect()->route('dev.dashboard');
    })->name('dashboard');
    Route::get('/dev', function () {
        return redirect()->route('dev.dashboard');
    })->name('dev.root');

    // ==================== PCM APPLICATION ROUTES ====================
    Route::prefix('dev')->name('dev.')->group(function () {

        // ==================== DASHBOARD ROUTES ====================
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // ==================== NOTIFICATIONS ====================
        Route::get('notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
        Route::get('notifications', [NotificationController::class, 'inbox'])->name('notifications.inbox');
        Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.preferences');
        Route::post('notifications/test', [NotificationController::class, 'test'])->name('notifications.test');

        Route::get('approvals', [ApprovalCenterController::class, 'index'])
            ->middleware('role:ho')
            ->name('approvals.index');
        Route::get('approvals/urls', [ApprovalCenterController::class, 'urls'])
            ->middleware('role:ho')
            ->name('approvals.urls');

        // ==================== AUDIT LOGS ====================
        Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('role:ho')->name('audit-logs.index');

        // ==================== DOKUMEN SHORTCUT ====================
        Route::get('documents', [DocumentShortcutController::class, 'index'])->name('documents.index');
        Route::get('documents/{projectId}', [DocumentShortcutController::class, 'select'])->name('documents.select');
        Route::get('docs/roles-permissions', function () {
            return view('dev.docs.roles-permissions');
        })->name('docs.roles-permissions');

        // ==================== GLOBAL RAB BASELINE ROUTES ====================
        Route::prefix('rapp')->name('rab.')->group(function () {
            Route::get('/', [RappController::class, 'globalIndex'])->middleware('role:ho')->name('index');
            Route::get('/summary', [RappController::class, 'globalSummary'])->middleware('role:ho')->name('summary');
        });

        // ==================== DATA MASTER ROUTES ====================
        Route::prefix('data')->name('data.')->group(function () {
            // Basic CRUD Routes
            Route::get('/', [DataController::class, 'index'])->name('index');
            Route::get('/create', [DataController::class, 'create'])->name('create');
            Route::post('/', [DataController::class, 'store'])->name('store');
            Route::get('/{id}', [DataController::class, 'show'])->whereNumber('id')->name('show');
            Route::get('/{id}/edit', [DataController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [DataController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [DataController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::post('/{id}/approve-delete', [DataController::class, 'approveDelete'])->whereNumber('id')->middleware('role:ho')->name('approve-delete');
            Route::post('/{id}/reject-delete', [DataController::class, 'rejectDelete'])->whereNumber('id')->middleware('role:ho')->name('reject-delete');

            // AJAX & Quick Actions
            Route::get('/{id}/ajax', [DataController::class, 'showAjax'])->whereNumber('id')->name('show.ajax');
            Route::post('/quick-store', [DataController::class, 'quickStore'])->name('quickStore');
            Route::post('/{id}/quick-update', [DataController::class, 'quickUpdate'])->whereNumber('id')->name('quickUpdate');
            Route::post('/bulk-update', [DataController::class, 'bulkUpdate'])->middleware('role:ho')->name('bulkUpdate');
            Route::post('/generate-kode', [DataController::class, 'generateKode'])->middleware('role:ho')->name('generateKode');
            Route::get('/generate-kode/{kategori}', [DataController::class, 'generateKode'])->name('generateKode.kategori');

            // Bulk Actions
            Route::post('/bulk-action', [DataController::class, 'bulkAction'])->middleware('role:ho')->name('bulkAction');
            Route::post('/bulk-action-by-kategori/{kategori}', [DataController::class, 'bulkActionByKategori'])->middleware('role:ho')->name('bulkActionByKategori');

            // Multi-Table Features
            Route::get('/multi-table', [DataController::class, 'multiTable'])->name('multiTable');
            Route::get('/table/{kategori}', [DataController::class, 'getTableByKategori'])->name('table.byKategori');
            Route::get('/navigate/{kategori}', [DataController::class, 'navigateToKategori'])->name('navigate.kategori');

            // Search & Filter
            Route::get('/search/global', [DataController::class, 'globalSearch'])->name('search.global');
            Route::get('/search/{kategori}', [DataController::class, 'searchByKategori'])->name('search.byKategori');

            // Maintenance
            Route::get('/maintenance', [DataController::class, 'maintenance'])->name('maintenance');
            Route::post('/fix-gaps', [DataController::class, 'fixGaps'])->middleware('role:ho')->name('fixGaps');
            Route::post('/fix-numbering-gaps', [DataController::class, 'fixNumberingGaps'])->middleware('role:ho')->name('fixNumberingGaps');
            Route::post('/reset-counters', [DataController::class, 'resetCounters'])->middleware('role:ho')->name('resetCounters');
            Route::get('/available-numbers', [DataController::class, 'getAvailableNumbers'])->name('availableNumbers');

            // Import/Export
            Route::post('/import', [DataController::class, 'import'])->middleware('role:ho')->name('import');
            Route::get('/export', [DataController::class, 'export'])->middleware('role:ho')->name('export');
            Route::get('/export/{kategori}', [DataController::class, 'exportByKategori'])->middleware('role:ho')->name('export.by-kategori');
            Route::get('/export-all', [DataController::class, 'exportAll'])->middleware('role:ho')->name('export.all');
            Route::get('/download-template', [DataController::class, 'downloadTemplate'])->middleware('role:ho')->name('downloadTemplate');

            // Price Management
            Route::get('/{id}/prices', [MasterItemPriceController::class, 'index'])->whereNumber('id')->name('prices.index');
            Route::post('/{id}/prices', [MasterItemPriceController::class, 'store'])->whereNumber('id')->middleware('role:ho')->name('prices.store');
            Route::get('/prices/{priceId}/edit', [MasterItemPriceController::class, 'edit'])->whereNumber('priceId')->name('prices.edit');
            Route::match(['POST', 'PUT'], '/prices/{priceId}', [MasterItemPriceController::class, 'update'])->whereNumber('priceId')->middleware('role:ho')->name('prices.update');
            Route::delete('/prices/{priceId}', [MasterItemPriceController::class, 'destroy'])->whereNumber('priceId')->middleware('role:ho')->name('prices.destroy');
            Route::get('/{id}/prices/comparison', [MasterItemPriceController::class, 'priceComparison'])->whereNumber('id')->name('prices.comparison');

            // Statistics & Reports
            Route::get('/statistics/overview', [DataController::class, 'getStatistics'])->name('getStatistics');
            Route::get('/statistics/enhanced', [DataController::class, 'getEnhancedStatistics'])->name('getEnhancedStatistics');
            Route::get('/statistics/{kategori}', [DataController::class, 'getStatisticsByKategori'])->name('getStatisticsByKategori');

            // Test & Debug
            Route::get('/test-export', [DataController::class, 'testExport'])->middleware('role:ho')->name('testExport');
            Route::get('/test-export-simple', [DataController::class, 'testExportSimple'])->middleware('role:ho')->name('testExportSimple');
            Route::get('/test-export-all', [DataController::class, 'testExportAll'])->middleware('role:ho')->name('testExportAll');
            Route::get('/debug-data', [DataController::class, 'debugData'])->name('debugData');
            Route::get('/export-csv', [DataController::class, 'exportCSV'])->middleware('role:ho')->name('exportCSV');
        });

        // ==================== CLIENTS ROUTES ====================
        Route::prefix('clients')->name('clients.')->group(function () {
            Route::get('/', [ClientController::class, 'index'])->name('index');
            Route::get('/create', [ClientController::class, 'create'])->name('create');
            Route::post('/', [ClientController::class, 'store'])->name('store');
            Route::get('/{id}', [ClientController::class, 'show'])->whereNumber('id')->name('show');
            Route::get('/{id}/edit', [ClientController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [ClientController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [ClientController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::post('/{id}/approve-delete', [ClientController::class, 'approveDelete'])->whereNumber('id')->middleware('role:ho')->name('approve-delete');
            Route::post('/{id}/reject-delete', [ClientController::class, 'rejectDelete'])->whereNumber('id')->middleware('role:ho')->name('reject-delete');

            // Additional Client Routes
            Route::get('/{id}/projects', [ClientController::class, 'clientProjects'])->name('projects');
            Route::get('/{id}/reports', [ClientController::class, 'clientReports'])->middleware('role:ho')->name('reports');
        });

        // ==================== PROJECTS ROUTES ====================
        Route::prefix('projects')->name('projects.')->group(function () {
            // Basic CRUD Routes
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::get('/create', [ProjectController::class, 'create'])->middleware('role:ho')->name('create');
            Route::post('/', [ProjectController::class, 'store'])->middleware('role:ho')->name('store');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
            Route::get('/{project}/edit', [ProjectController::class, 'edit'])->middleware('role:ho')->name('edit');
            Route::put('/{project}', [ProjectController::class, 'update'])->middleware('role:ho')->name('update');
            Route::delete('/{project}', [ProjectController::class, 'destroy'])->middleware('role:ho')->name('destroy');

            // Project Management
            Route::delete('/files/{file}', [ProjectController::class, 'deleteFile'])->middleware('role:ho')->name('deleteFile');
            Route::get('/{project}/budget', [ProjectController::class, 'budget'])->name('budget');
            Route::put('/{project}/budget', [ProjectController::class, 'updateBudget'])->middleware('role:ho')->name('updateBudget');
            Route::get('/{project}/timeline', [ProjectController::class, 'timeline'])->name('timeline');
            Route::get('/{project}/reports', [ProjectController::class, 'projectReports'])->middleware('role:ho')->name('reports');

            // General Reports
            Route::get('/reports/general', [ProjectController::class, 'reports'])->middleware('role:ho')->name('general-reports');

            // Export
            Route::get('/export/excel', [ProjectController::class, 'exportProjects'])->middleware('role:ho')->name('export.excel');
        });

        // ==================== RAB BASELINE ROUTES PER PROJECT ====================
        Route::prefix('projects/{projectId}')->name('rabs.')->group(function () {
            Route::prefix('rapps')->group(function () {
                // Basic CRUD
                Route::get('/', [RappController::class, 'index'])->name('index');
                Route::get('/create', [RappController::class, 'create'])->name('create');
                Route::post('/', [RappController::class, 'store'])->name('store');
                Route::get('/{rabId}', [RappController::class, 'show'])->name('show');
                Route::get('/{rabId}/table', [RappController::class, 'table'])->name('table');
                Route::get('/{rabId}/edit', [RappController::class, 'edit'])->name('edit');
                Route::put('/{rabId}', [RappController::class, 'update'])->name('update');
                Route::delete('/{rabId}', [RappController::class, 'destroy'])->middleware('role:ho')->name('destroy');

                // Additional RAB Baseline Actions
                Route::post('/{rabId}/duplicate', [RappController::class, 'duplicate'])->middleware('role:ho')->name('duplicate');
                Route::post('/{rabId}/submit', [RappController::class, 'submit'])->name('submit');
                Route::post('/{rabId}/approve', [RappController::class, 'approve'])->middleware('role:ho')->name('approve');
                Route::post('/{rabId}/reject', [RappController::class, 'reject'])->middleware('role:ho')->name('reject');
                Route::get('/{rabId}/export-excel', [RappController::class, 'exportExcel'])->middleware('role:ho')->name('export.excel');
                Route::get('/{rabId}/export-pdf', [RappController::class, 'exportPdf'])->middleware('role:ho')->name('export.pdf');

                // Debug & Repair
                Route::get('/{rabId}/debug', [RappController::class, 'debugConsistency'])->middleware('role:ho')->name('debug');
                Route::post('/{rabId}/repair', [RappController::class, 'repairData'])->middleware('role:ho')->name('repair');

                // Import Excel (Item RAB Baseline)
                Route::get('/{rabId}/template-excel', [RappController::class, 'downloadExcelTemplate'])->middleware('role:ho')->name('template.excel');
                Route::post('/{rabId}/import-excel', [RappController::class, 'importExcel'])->middleware('role:ho')->name('import.excel');

                // Purchase Voucher dari RAB Baseline
                Route::post('/{rabId}/purchase-vouchers', [PurchaseVoucherController::class, 'store'])->name('purchase-vouchers.store');
                Route::get('/{rabId}/purchase-vouchers', [PurchaseVoucherController::class, 'index'])->name('purchase-vouchers.index');
                Route::get('/{rabId}/purchase-vouchers/print', [PurchaseVoucherController::class, 'printIndex'])->name('purchase-vouchers.print-index');
                Route::get('/{rabId}/purchase-vouchers/pdf', [PurchaseVoucherController::class, 'pdfIndex'])->name('purchase-vouchers.pdf-index');
                Route::get('/{rabId}/purchase-vouchers/{voucherId}', [PurchaseVoucherController::class, 'show'])->name('purchase-vouchers.show');
                Route::get('/{rabId}/purchase-vouchers/{voucherId}/print', [PurchaseVoucherController::class, 'print'])->name('purchase-vouchers.print');
                Route::get('/{rabId}/purchase-vouchers/{voucherId}/pdf', [PurchaseVoucherController::class, 'pdf'])->name('purchase-vouchers.pdf');
                // Tambahan: Edit & Delete (ADMIN only dicek di controller)
                Route::get('/{rabId}/purchase-vouchers/{voucherId}/edit', [PurchaseVoucherController::class, 'edit'])->name('purchase-vouchers.edit');
                Route::put('/{rabId}/purchase-vouchers/{voucherId}', [PurchaseVoucherController::class, 'update'])->name('purchase-vouchers.update');
                Route::delete('/{rabId}/purchase-vouchers/{voucherId}', [PurchaseVoucherController::class, 'destroy'])->name('purchase-vouchers.destroy');
                Route::post('/{rabId}/purchase-vouchers/{voucherId}/submit', [PurchaseVoucherController::class, 'submit'])->name('purchase-vouchers.submit');
                Route::post('/{rabId}/purchase-vouchers/{voucherId}/approve', [PurchaseVoucherController::class, 'approve'])->middleware('role:ho')->name('purchase-vouchers.approve');
                Route::post('/{rabId}/purchase-vouchers/{voucherId}/reject', [PurchaseVoucherController::class, 'reject'])->middleware('role:ho')->name('purchase-vouchers.reject');

                // SPP
                Route::get('/{rabId}/spps', [SppController::class, 'index'])->name('spps.index');
                Route::get('/{rabId}/spps/create', [SppController::class, 'create'])->name('spps.create');
                Route::post('/{rabId}/spps', [SppController::class, 'store'])->name('spps.store');
                Route::get('/{rabId}/spps/{sppId}', [SppController::class, 'show'])->name('spps.show');
                Route::get('/{rabId}/spps/{sppId}/edit', [SppController::class, 'edit'])->name('spps.edit');
                Route::put('/{rabId}/spps/{sppId}', [SppController::class, 'update'])->name('spps.update');
                Route::delete('/{rabId}/spps/{sppId}', [SppController::class, 'destroy'])->name('spps.destroy');
                Route::get('/{rabId}/spps/{sppId}/print', [SppController::class, 'print'])->name('spps.print');
                Route::get('/{rabId}/spps/{sppId}/pdf', [SppController::class, 'pdf'])->name('spps.pdf');
                Route::post('/{rabId}/spps/{sppId}/submit', [SppController::class, 'submit'])->name('spps.submit');
                Route::post('/{rabId}/spps/{sppId}/approve', [SppController::class, 'approve'])->middleware('role:ho')->name('spps.approve');
                Route::post('/{rabId}/spps/{sppId}/reject', [SppController::class, 'reject'])->middleware('role:ho')->name('spps.reject');

                // BPG
                Route::get('/{rabId}/bpgs', [BpgController::class, 'index'])->name('bpgs.index');
                Route::get('/{rabId}/bpgs/create', [BpgController::class, 'create'])->name('bpgs.create');
                Route::post('/{rabId}/bpgs', [BpgController::class, 'store'])->name('bpgs.store');
                Route::get('/{rabId}/bpgs/{bpgId}', [BpgController::class, 'show'])->name('bpgs.show');
                Route::get('/{rabId}/bpgs/{bpgId}/edit', [BpgController::class, 'edit'])->name('bpgs.edit');
                Route::put('/{rabId}/bpgs/{bpgId}', [BpgController::class, 'update'])->name('bpgs.update');
                Route::delete('/{rabId}/bpgs/{bpgId}', [BpgController::class, 'destroy'])->name('bpgs.destroy');
                Route::get('/{rabId}/bpgs/{bpgId}/print', [BpgController::class, 'print'])->name('bpgs.print');
                Route::get('/{rabId}/bpgs/{bpgId}/pdf', [BpgController::class, 'pdf'])->name('bpgs.pdf');
                Route::post('/{rabId}/bpgs/{bpgId}/submit', [BpgController::class, 'submit'])->name('bpgs.submit');
                Route::post('/{rabId}/bpgs/{bpgId}/approve', [BpgController::class, 'approve'])->middleware('role:ho')->name('bpgs.approve');
                Route::post('/{rabId}/bpgs/{bpgId}/reject', [BpgController::class, 'reject'])->middleware('role:ho')->name('bpgs.reject');

                // LPB
                Route::get('/{rabId}/lpbs', [LpbController::class, 'index'])->name('lpbs.index');
                Route::get('/{rabId}/lpbs/create', [LpbController::class, 'create'])->name('lpbs.create');
                Route::post('/{rabId}/lpbs', [LpbController::class, 'store'])->name('lpbs.store');
                Route::get('/{rabId}/lpbs/{lpbId}', [LpbController::class, 'show'])->name('lpbs.show');
                Route::get('/{rabId}/lpbs/{lpbId}/edit', [LpbController::class, 'edit'])->name('lpbs.edit');
                Route::put('/{rabId}/lpbs/{lpbId}', [LpbController::class, 'update'])->name('lpbs.update');
                Route::delete('/{rabId}/lpbs/{lpbId}', [LpbController::class, 'destroy'])->name('lpbs.destroy');
                Route::get('/{rabId}/lpbs/{lpbId}/print', [LpbController::class, 'print'])->name('lpbs.print');
                Route::get('/{rabId}/lpbs/{lpbId}/pdf', [LpbController::class, 'pdf'])->name('lpbs.pdf');
                Route::post('/{rabId}/lpbs/{lpbId}/submit', [LpbController::class, 'submit'])->name('lpbs.submit');
                Route::post('/{rabId}/lpbs/{lpbId}/approve', [LpbController::class, 'approve'])->middleware('role:ho')->name('lpbs.approve');
                Route::post('/{rabId}/lpbs/{lpbId}/reject', [LpbController::class, 'reject'])->middleware('role:ho')->name('lpbs.reject');

                // Purchase Order
                Route::get('/{rabId}/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
                Route::get('/{rabId}/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
                Route::post('/{rabId}/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
                Route::get('/{rabId}/purchase-orders/{poId}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
                Route::get('/{rabId}/purchase-orders/{poId}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
                Route::put('/{rabId}/purchase-orders/{poId}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
                Route::delete('/{rabId}/purchase-orders/{poId}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
                Route::get('/{rabId}/purchase-orders/{poId}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
                Route::get('/{rabId}/purchase-orders/{poId}/pdf', [PurchaseOrderController::class, 'pdf'])->name('purchase-orders.pdf');
                Route::post('/{rabId}/purchase-orders/{poId}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
                Route::post('/{rabId}/purchase-orders/{poId}/approve', [PurchaseOrderController::class, 'approve'])->middleware('role:ho')->name('purchase-orders.approve');
                Route::post('/{rabId}/purchase-orders/{poId}/reject', [PurchaseOrderController::class, 'reject'])->middleware('role:ho')->name('purchase-orders.reject');
                Route::post('/{rabId}/purchase-orders/{poId}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('role:ho')->name('purchase-orders.cancel');

                // SPK
                Route::get('/{rabId}/spks', [SpkController::class, 'index'])->name('spks.index');
                Route::get('/{rabId}/spks/create', [SpkController::class, 'create'])->name('spks.create');
                Route::post('/{rabId}/spks', [SpkController::class, 'store'])->name('spks.store');
                Route::get('/{rabId}/spks/{spkId}', [SpkController::class, 'show'])->name('spks.show');
                Route::get('/{rabId}/spks/{spkId}/edit', [SpkController::class, 'edit'])->name('spks.edit');
                Route::put('/{rabId}/spks/{spkId}', [SpkController::class, 'update'])->name('spks.update');
                Route::delete('/{rabId}/spks/{spkId}', [SpkController::class, 'destroy'])->name('spks.destroy');
                Route::get('/{rabId}/spks/{spkId}/print', [SpkController::class, 'print'])->name('spks.print');
                Route::get('/{rabId}/spks/{spkId}/pdf', [SpkController::class, 'pdf'])->name('spks.pdf');
                Route::post('/{rabId}/spks/{spkId}/submit', [SpkController::class, 'submit'])->name('spks.submit');
                Route::post('/{rabId}/spks/{spkId}/approve', [SpkController::class, 'approve'])->middleware('role:ho')->name('spks.approve');
                Route::post('/{rabId}/spks/{spkId}/reject', [SpkController::class, 'reject'])->middleware('role:ho')->name('spks.reject');

                // Komparasi Vendor
                Route::get('/{rabId}/vendor-comparisons', [VendorComparisonController::class, 'index'])->name('vendor-comparisons.index');
                Route::get('/{rabId}/vendor-comparisons/create', [VendorComparisonController::class, 'create'])->name('vendor-comparisons.create');
                Route::post('/{rabId}/vendor-comparisons', [VendorComparisonController::class, 'store'])->name('vendor-comparisons.store');
                Route::get('/{rabId}/vendor-comparisons/{comparisonId}', [VendorComparisonController::class, 'show'])->name('vendor-comparisons.show');
                Route::get('/{rabId}/vendor-comparisons/{comparisonId}/edit', [VendorComparisonController::class, 'edit'])->name('vendor-comparisons.edit');
                Route::put('/{rabId}/vendor-comparisons/{comparisonId}', [VendorComparisonController::class, 'update'])->name('vendor-comparisons.update');
                Route::delete('/{rabId}/vendor-comparisons/{comparisonId}', [VendorComparisonController::class, 'destroy'])->name('vendor-comparisons.destroy');
                Route::get('/{rabId}/vendor-comparisons/{comparisonId}/print', [VendorComparisonController::class, 'print'])->name('vendor-comparisons.print');
                Route::get('/{rabId}/vendor-comparisons/{comparisonId}/pdf', [VendorComparisonController::class, 'pdf'])->name('vendor-comparisons.pdf');
                Route::post('/{rabId}/vendor-comparisons/{comparisonId}/submit', [VendorComparisonController::class, 'submit'])->name('vendor-comparisons.submit');
                Route::post('/{rabId}/vendor-comparisons/{comparisonId}/approve', [VendorComparisonController::class, 'approve'])->middleware('role:ho')->name('vendor-comparisons.approve');
                Route::post('/{rabId}/vendor-comparisons/{comparisonId}/reject', [VendorComparisonController::class, 'reject'])->middleware('role:ho')->name('vendor-comparisons.reject');

                // Items
                Route::post('/{rabId}/items', [RappController::class, 'storeItem'])->name('items.store');
                Route::post('/{rabId}/items/bulk', [RappController::class, 'bulkStoreItems'])->name('items.bulk.store');
                Route::delete('/{rabId}/items/bulk-delete', [RappController::class, 'bulkDestroyItems'])->name('items.bulk.delete');
                Route::put('/{rabId}/items/{itemId}', [RappController::class, 'updateItem'])->name('items.update');
                Route::delete('/{rabId}/items/{itemId}', [RappController::class, 'destroyItem'])->name('items.destroy');

                // Quick Add Item
                Route::post('/{rabId}/quick-add', [RappController::class, 'quickAddItem'])->name('quick-add');

                // Quick Actions & AJAX
                Route::get('/{rabId}/breakdown', [RappController::class, 'getBreakdown'])->name('breakdown');
                Route::get('/{rabId}/comparison', [RappController::class, 'getComparison'])->name('comparison');
            });
        });

        // ==================== RAPP ROUTES (PRIMARY/CANONICAL PATH) ====================
        Route::prefix('projects/{projectId}')->name('rab-baseline.')->group(function () {
            Route::get('/rapps', [RabBaselineController::class, 'index'])->name('index');
            Route::get('/rapps/create', [RappController::class, 'create'])->name('create');
            Route::post('/rapps', [RappController::class, 'store'])->name('store');
            Route::get('/rapps/{rabId}', [RappController::class, 'show'])->name('show');
            Route::get('/rapps/{rabId}/table', [RappController::class, 'table'])->name('table');
            Route::get('/rapps/{rabId}/edit', [RappController::class, 'edit'])->name('edit');
            Route::put('/rapps/{rabId}', [RappController::class, 'update'])->name('update');
            Route::delete('/rapps/{rabId}', [RappController::class, 'destroy'])->middleware('role:ho')->name('destroy');
            Route::post('/rapps/{rabId}/submit', [RappController::class, 'submit'])->name('submit');
            Route::post('/rapps/{rabId}/approve', [RappController::class, 'approve'])->middleware('role:ho')->name('approve');
            Route::post('/rapps/{rabId}/reject', [RappController::class, 'reject'])->middleware('role:ho')->name('reject');
            Route::get('/rapps/{rabId}/export-excel', [RappController::class, 'exportExcel'])->middleware('role:ho')->name('export.excel');
            Route::get('/rapps/{rabId}/export-pdf', [RappController::class, 'exportPdf'])->middleware('role:ho')->name('export.pdf');
            Route::get('/rapps/{rabId}/debug', [RappController::class, 'debugConsistency'])->middleware('role:ho')->name('debug');
            Route::post('/rapps/{rabId}/repair', [RappController::class, 'repairData'])->middleware('role:ho')->name('repair');
            Route::get('/rapps/{rabId}/template-excel', [RappController::class, 'downloadExcelTemplate'])->middleware('role:ho')->name('template.excel');
            Route::post('/rapps/{rabId}/import-excel', [RappController::class, 'importExcel'])->middleware('role:ho')->name('import.excel');
            Route::post('/rapps/{rabId}/quick-add', [RappController::class, 'quickAddItem'])->name('quick-add');

            Route::post('/rapps/{rabId}/items', [RappController::class, 'storeItem'])->name('items.store');
            Route::post('/rapps/{rabId}/items/bulk', [RappController::class, 'bulkStoreItems'])->name('items.bulk.store');
            Route::delete('/rapps/{rabId}/items/bulk-delete', [RappController::class, 'bulkDestroyItems'])->name('items.bulk.delete');
            Route::put('/rapps/{rabId}/items/{itemId}', [RappController::class, 'updateItem'])->name('items.update');
            Route::delete('/rapps/{rabId}/items/{itemId}', [RappController::class, 'destroyItem'])->name('items.destroy');

            Route::get('/rapps/{rabId}/spps', [SppController::class, 'index'])->name('spps.index');
            Route::get('/rapps/{rabId}/bpgs', [BpgController::class, 'index'])->name('bpgs.index');
            Route::get('/rapps/{rabId}/lpbs', [LpbController::class, 'index'])->name('lpbs.index');
            Route::get('/rapps/{rabId}/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
            Route::get('/rapps/{rabId}/spks', [SpkController::class, 'index'])->name('spks.index');
            Route::get('/rapps/{rabId}/vendor-comparisons', [VendorComparisonController::class, 'index'])->name('vendor-comparisons.index');
            Route::get('/rapps/{rabId}/purchase-vouchers', [PurchaseVoucherController::class, 'index'])->name('purchase-vouchers.index');
            Route::post('/rapps/{rabId}/purchase-vouchers', [PurchaseVoucherController::class, 'store'])->name('purchase-vouchers.store');
        });

        // ==================== LEGACY RAB-BASELINE PATHS (AUTO REDIRECT) ====================
        Route::prefix('projects/{projectId}')->group(function () {
            Route::get('/rab-baseline', fn ($projectId) => redirect()->route('dev.rab-baseline.index', ['projectId' => $projectId], 301));
            Route::get('/rab-baseline/create', fn ($projectId) => redirect()->route('dev.rab-baseline.create', ['projectId' => $projectId], 301));
            Route::get('/rab-baseline/{rabId}', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.show', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/table', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.table', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/edit', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.edit', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/export-excel', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.export.excel', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/export-pdf', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.export.pdf', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/debug', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.debug', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/template-excel', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.template.excel', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/spps', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.spps.index', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/bpgs', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.bpgs.index', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/lpbs', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.lpbs.index', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/purchase-orders', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.purchase-orders.index', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/spks', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.spks.index', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/vendor-comparisons', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.vendor-comparisons.index', ['projectId' => $projectId, 'rabId' => $rabId], 301));
            Route::get('/rab-baseline/{rabId}/purchase-vouchers', fn ($projectId, $rabId) => redirect()->route('dev.rab-baseline.purchase-vouchers.index', ['projectId' => $projectId, 'rabId' => $rabId], 301));
        });

        // ==================== VOUCHER SYSTEM ROUTES ====================
        Route::prefix('projects/{projectId}/vouchers')->name('vouchers.')->group(function () {
            // Basic CRUD
            Route::get('/', [VoucherController::class, 'index'])->name('index');
            Route::get('/create', [VoucherController::class, 'create'])->name('create');
            Route::post('/', [VoucherController::class, 'store'])->name('store');
            Route::get('/{id}', [VoucherController::class, 'show'])->name('show');
            Route::get('/{id}/print', [VoucherController::class, 'print'])->name('print');
            Route::get('/{id}/pdf', [VoucherController::class, 'exportPdf'])->name('pdf');
            Route::get('/{id}/edit', [VoucherController::class, 'edit'])->name('edit');
            Route::put('/{id}', [VoucherController::class, 'update'])->name('update');
            Route::delete('/{id}', [VoucherController::class, 'destroy'])->name('destroy');

            // Approval Workflow
            Route::post('/{id}/submit', [VoucherController::class, 'submit'])->name('submit');
            Route::post('/{id}/approve', [VoucherController::class, 'approve'])->middleware('role:ho')->name('approve');
            Route::post('/{id}/reject', [VoucherController::class, 'reject'])->middleware('role:ho')->name('reject');
            Route::post('/{id}/mark-paid', [VoucherController::class, 'markAsPaid'])->middleware('role:ho')->name('mark-paid');
            Route::post('/{id}/mark-completed', [VoucherController::class, 'markAsCompleted'])->middleware('role:ho')->name('mark-completed');

            // Voucher Items
            Route::post('/{id}/items', [VoucherController::class, 'storeItem'])->name('items.store');
            Route::put('/{id}/items/{itemId}', [VoucherController::class, 'updateItem'])->name('items.update');
            Route::delete('/{id}/items/{itemId}', [VoucherController::class, 'destroyItem'])->name('items.destroy');
            Route::post('/{id}/items/{itemId}/update-status', [VoucherController::class, 'updateItemStatus'])->name('items.update-status');

            // Export & Print
            Route::get('/{id}/export-pdf', [VoucherController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/{id}/print', [VoucherController::class, 'print'])->name('print');

            // Bulk Actions
            Route::post('/bulk-approve', [VoucherController::class, 'bulkApprove'])->middleware('role:ho')->name('bulk-approve');
            Route::post('/bulk-reject', [VoucherController::class, 'bulkReject'])->middleware('role:ho')->name('bulk-reject');
        });

        // ==================== VENDOR MANAGEMENT ROUTES ====================
        Route::prefix('vendors')->name('vendors.')->group(function () {
            Route::get('/', [VoucherController::class, 'vendorIndex'])->name('index');
            Route::get('/create', [VoucherController::class, 'vendorCreate'])->name('create');
            Route::post('/', [VoucherController::class, 'vendorStore'])->name('store');
            Route::get('/{id}/edit', [VoucherController::class, 'vendorEdit'])->name('edit');
            Route::put('/{id}', [VoucherController::class, 'vendorUpdate'])->name('update');
            Route::delete('/{id}', [VoucherController::class, 'vendorDestroy'])->name('destroy');
            Route::post('/{id}/approve-delete', [VoucherController::class, 'vendorApproveDelete'])->middleware('role:ho')->name('approve-delete');
            Route::post('/{id}/reject-delete', [VoucherController::class, 'vendorRejectDelete'])->middleware('role:ho')->name('reject-delete');

            // Import/Export
            Route::post('/import', [VoucherController::class, 'vendorImport'])->middleware('role:ho')->name('import');
            Route::get('/export', [VoucherController::class, 'vendorExport'])->middleware('role:ho')->name('export');
        });

        // ==================== STOCK REPORTS ====================
        Route::get('stock/rekap', [StockReportController::class, 'rekap'])->middleware('role:ho')->name('stock.rekap');
        Route::get('stock/kartu', [StockReportController::class, 'kartu'])->middleware('role:ho')->name('stock.kartu');
        Route::get('stock/rekap-data', [StockReportController::class, 'rekapData'])->middleware('role:ho')->name('stock.rekap-data');
        Route::get('stock/kartu-data', [StockReportController::class, 'kartuData'])->middleware('role:ho')->name('stock.kartu-data');
        Route::get('stock/rekap/print', [StockReportController::class, 'rekapPrint'])->middleware('role:ho')->name('stock.rekap.print');
        Route::get('stock/rekap/pdf', [StockReportController::class, 'rekapPdf'])->middleware('role:ho')->name('stock.rekap.pdf');
        Route::get('stock/rekap/excel', [StockReportController::class, 'rekapExcel'])->middleware('role:ho')->name('stock.rekap.excel');
        Route::get('stock/kartu/print', [StockReportController::class, 'kartuPrint'])->middleware('role:ho')->name('stock.kartu.print');
        Route::get('stock/kartu/pdf', [StockReportController::class, 'kartuPdf'])->middleware('role:ho')->name('stock.kartu.pdf');
        Route::get('stock/kartu/excel', [StockReportController::class, 'kartuExcel'])->middleware('role:ho')->name('stock.kartu.excel');

        // ==================== RAB BREAKDOWN ROUTES ====================
        Route::prefix('projects/{projectId}')->name('rab-breakdown.')->group(function () {
            // RAB Breakdown Main Routes
            Route::prefix('rab-breakdown')->group(function () {
                // Basic CRUD
                Route::get('/', [RabBreakdownController::class, 'index'])->name('index');
                Route::get('/create', [RabBreakdownController::class, 'create'])->name('create');
                Route::post('/', [RabBreakdownController::class, 'store'])->name('store');
                Route::get('/{rabBreakdown}', [RabBreakdownController::class, 'show'])->name('show');
                Route::get('/{rabBreakdown}/edit', [RabBreakdownController::class, 'edit'])->name('edit');
                Route::put('/{rabBreakdown}', [RabBreakdownController::class, 'update'])->name('update');
                Route::delete('/{rabBreakdown}', [RabBreakdownController::class, 'destroy'])->name('destroy');
                Route::post('/{rabBreakdown}/submit', [RabBreakdownController::class, 'submit'])->name('submit');
                Route::post('/{rabBreakdown}/approve', [RabBreakdownController::class, 'approve'])->middleware('role:ho')->name('approve');
                Route::post('/{rabBreakdown}/reject', [RabBreakdownController::class, 'reject'])->middleware('role:ho')->name('reject');
                Route::post('/{rabBreakdown}/data-hygiene', [RabBreakdownController::class, 'runDataHygiene'])->name('data-hygiene');

                // RAB Breakdown Items Routes
                Route::prefix('{rabBreakdown}/items')->name('items.')->group(function () {
                    Route::get('/create', [RabBreakdownController::class, 'createItem'])->name('create');
                    Route::post('/', [RabBreakdownController::class, 'storeItem'])->name('store');
                    Route::get('/{item}/edit', [RabBreakdownController::class, 'editItem'])->name('edit');
                    Route::put('/{item}', [RabBreakdownController::class, 'updateItem'])->name('update');
                    Route::delete('/{item}', [RabBreakdownController::class, 'destroyItem'])->name('destroy');
                });

                // RAB Breakdown Progress Update
                Route::put('/{rabBreakdown}/update-progress', [RabBreakdownController::class, 'updateProgress'])->name('update-progress');

                // RAB Breakdown Import/Export Routes
                Route::get('/{rabBreakdown}/import', [RabBreakdownController::class, 'importForm'])->name('import.form');
                Route::post('/{rabBreakdown}/validate-import', [RabBreakdownController::class, 'validateImport'])->name('validate-import');
                Route::post('/{rabBreakdown}/import', [RabBreakdownController::class, 'processImport'])->name('import');
                Route::get('/template/download', [RabBreakdownController::class, 'downloadTemplate'])->name('download-template');
                Route::get('/export/{rabBreakdown?}', [RabBreakdownController::class, 'export'])->name('export');

                // RAB Breakdown Test Routes
                Route::get('/test/view', [RabBreakdownController::class, 'testView'])->name('test.view');
            });

            // RAB Breakdown API Routes (for AJAX calls)
            Route::prefix('api/rab-breakdown')->name('api.')->group(function () {
                Route::get('/', [RabBreakdownController::class, 'apiIndex'])->name('index');
                Route::get('/{rabBreakdown}', [RabBreakdownController::class, 'apiShow'])->name('show');
                Route::get('/{rabBreakdown}/items', [RabBreakdownController::class, 'apiGetItems'])->name('items');
                Route::put('/{rabBreakdown}/progress', [RabBreakdownController::class, 'apiUpdateProgress'])->middleware('role:ho')->name('update-progress');
                Route::put('/{rabBreakdown}/actual', [RabBreakdownController::class, 'apiUpdateActual'])->middleware('role:ho')->name('update-actual');
                Route::get('/{rabBreakdown}/calculate-budget', [RabBreakdownController::class, 'apiCalculateBudget'])->name('calculate-budget');
                Route::get('/{rabBreakdown}/calculate-progress', [RabBreakdownController::class, 'apiCalculateProgress'])->name('calculate-progress');
                Route::get('/{rabBreakdown}/summary', [RabBreakdownController::class, 'apiGetSummary'])->name('summary');

                // Partial Views Routes
                Route::get('/{rabBreakdown}/partials/items-table', [RabBreakdownController::class, 'getItemsTablePartial'])->name('partials.items-table');
                Route::get('/{rabBreakdown}/partials/budget-sources', [RabBreakdownController::class, 'getBudgetSourcesPartial'])->name('partials.budget-sources');
                Route::get('/{rabBreakdown}/partials/progress-form', [RabBreakdownController::class, 'getProgressFormPartial'])->name('partials.progress-form');
                Route::get('/{rabBreakdown}/partials/import-preview', [RabBreakdownController::class, 'getImportPreviewPartial'])->name('partials.import-preview');
            });

            // Legacy URL redirects from /wbs to /rab-breakdown
            Route::get('/wbs', fn ($projectId) => redirect()->route('dev.rab-breakdown.index', $projectId))->name('legacy.wbs.index');
            Route::get('/wbs/create', fn ($projectId) => redirect()->route('dev.rab-breakdown.create', $projectId))->name('legacy.wbs.create');
            Route::get('/wbs/{rabBreakdown}', fn ($projectId, $rabBreakdown) => redirect()->route('dev.rab-breakdown.show', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown]))->name('legacy.wbs.show');
            Route::get('/wbs/{rabBreakdown}/edit', fn ($projectId, $rabBreakdown) => redirect()->route('dev.rab-breakdown.edit', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown]))->name('legacy.wbs.edit');
            Route::get('/wbs/{rabBreakdown}/import', fn ($projectId, $rabBreakdown) => redirect()->route('dev.rab-breakdown.import.form', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown]))->name('legacy.wbs.import');
            Route::get('/wbs/template/download', fn ($projectId) => redirect()->route('dev.rab-breakdown.download-template', ['projectId' => $projectId]))->name('legacy.wbs.template');
            Route::get('/wbs/export/{rabBreakdown?}', fn ($projectId, $rabBreakdown = null) => redirect()->route('dev.rab-breakdown.export', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown]))->name('legacy.wbs.export');
        });

        // ==================== REPORTS ROUTES ====================    
        Route::prefix('reports')->name('reports.')->middleware('role:ho')->group(function () {
            // Data Master Reports
            Route::get('/data-master', [DataController::class, 'dataMasterReport'])->name('dataMaster');
            Route::get('/data-usage', [DataController::class, 'dataUsageReport'])->name('dataUsage');
            Route::get('/export-summary', [DataController::class, 'exportSummaryReport'])->name('exportSummary');
            Route::get('/category-summary', [DataController::class, 'categorySummaryReport'])->name('categorySummary');

            // Client & Project Reports
            Route::get('/clients', [ClientController::class, 'reports'])->name('clients');
            Route::get('/projects', [ProjectController::class, 'reports'])->name('projects');

            // RAB Baseline Reports (global)
            Route::get('/rabs', [RappController::class, 'reports'])->name('rabs');
            Route::get('/rabs/{projectId}', [RappController::class, 'projectReport'])->name('rabs.project');

            // Budget Control Reports
            // (budget control report routes removed)

            // Voucher Reports
            Route::get('/vouchers', [VoucherController::class, 'reports'])->name('vouchers');
            Route::get('/voucher-summary', [VoucherController::class, 'voucherSummaryReport'])->name('voucher-summary');
            Route::get('/vendor-performance', [VoucherController::class, 'vendorPerformanceReport'])->name('vendor-performance');

            // RAB Breakdown Reports
            Route::get('/rab-breakdown', [RabBreakdownController::class, 'globalReports'])->name('rab-breakdown');
            Route::get('/rab-breakdown/{projectId}', [RabBreakdownController::class, 'projectRabBreakdownReport'])->name('rab-breakdown.project');
            Route::get('/rab-breakdown-progress', [RabBreakdownController::class, 'progressReport'])->name('rab-breakdown.progress');
            // Legacy report URLs
            Route::get('/wbs', fn () => redirect()->route('dev.reports.rab-breakdown'))->name('legacy.wbs');
            Route::get('/wbs/{projectId}', fn ($projectId) => redirect()->route('dev.reports.rab-breakdown.project', ['projectId' => $projectId]))->name('legacy.wbs.project');
            Route::get('/wbs-progress', fn () => redirect()->route('dev.reports.rab-breakdown.progress'))->name('legacy.wbs.progress');
        });

        // ==================== SETTINGS ROUTES ====================
        Route::prefix('settings')->middleware('role:ho')->name('settings.')->group(function () {
            // Data Categories Settings
            Route::get('/data-categories', [DataController::class, 'dataCategories'])->name('dataCategories');
            Route::post('/update-categories', [DataController::class, 'updateCategories'])->name('updateCategories');

            // Vendor Settings
            Route::get('/vendor-categories', [VoucherController::class, 'vendorCategories'])->name('vendorCategories');
            Route::post('/vendor-categories/update', [VoucherController::class, 'updateVendorCategories'])->name('vendorCategories.update');

            // Budget Control Settings
            // (budget control settings routes removed)

            // Voucher Settings
            Route::get('/voucher-settings', [VoucherController::class, 'voucherSettings'])->name('voucher');
            Route::post('/voucher-settings/update', [VoucherController::class, 'updateVoucherSettings'])->name('voucher.update');

            // Backup & Restore
            Route::get('/backup-data', [DataController::class, 'backupData'])->name('backupData');
            Route::post('/restore-data', [DataController::class, 'restoreData'])->name('restoreData');

            // System Settings
            Route::get('/system', [DashboardController::class, 'systemSettings'])->name('system');
            Route::post('/system/update', [DashboardController::class, 'updateSystemSettings'])->name('system.update');

            // Application Settings
            Route::get('/application', [DashboardController::class, 'applicationSettings'])->name('application');
            Route::post('/application/update', [DashboardController::class, 'updateApplicationSettings'])->name('application.update');

            // RAB Breakdown Settings
            Route::get('/rab-breakdown-categories', [RabBreakdownController::class, 'categoriesSettings'])->name('rab-breakdown.categories');
            Route::post('/rab-breakdown-categories/update', [RabBreakdownController::class, 'updateCategoriesSettings'])->name('rab-breakdown.categories.update');
            // Legacy settings URLs
            Route::get('/wbs-categories', fn () => redirect()->route('dev.settings.rab-breakdown.categories'))->name('legacy.wbs.categories');
            Route::post('/wbs-categories/update', [RabBreakdownController::class, 'updateCategoriesSettings'])->name('legacy.wbs.categories.update');

            // System Actions
            Route::post('/cache/clear', [DashboardController::class, 'clearCache'])->middleware('role:ho')->name('cache.clear');
            Route::post('/views/clear', [DashboardController::class, 'clearViews'])->middleware('role:ho')->name('views.clear');
            Route::post('/cache/all', [DashboardController::class, 'clearAllCache'])->middleware('role:ho')->name('cache.all');
            Route::post('/maintenance/toggle', [DashboardController::class, 'toggleMaintenance'])->middleware('role:ho')->name('maintenance.toggle');
            Route::post('/backup/database', [DashboardController::class, 'backupDatabase'])->middleware('role:ho')->name('backup.database');
        });

        // ==================== TEST ROUTES ====================
        Route::prefix('test')->name('test.')->group(function () {
            Route::get('/rab', [RabTestController::class, 'testView'])->name('rab.view');
            Route::get('/rab-json', [RabTestController::class, 'testJson'])->name('rab.json');
            Route::get('/data-export', [DataController::class, 'testExport'])->middleware('role:ho')->name('data.export');

            // Budget Control Test
            // (budget control test view removed)

            // Voucher Test
            Route::get('/voucher/{projectId}', [VoucherController::class, 'testView'])->name('voucher.view');

            // RAB Breakdown Test Routes
            Route::get('/rab-breakdown/{projectId}', [RabBreakdownController::class, 'testView'])->name('rab-breakdown.view');
            // Legacy test URL
            Route::get('/wbs/{projectId}', fn ($projectId) => redirect()->route('dev.test.rab-breakdown.view', ['projectId' => $projectId]))->name('legacy.wbs.view');

            // Debug route for RAB Baseline
            Route::get('/rab-baseline-debug/{projectId}/{rabId}', [RappController::class, 'debugConsistency'])->name('rab-baseline.debug');
            // Legacy alias
            Route::get('/rapp-debug/{projectId}/{rabId}', [RappController::class, 'debugConsistency'])->name('legacy.rapp.debug');
        });
    });

    // ==================== API ROUTES ====================
    Route::prefix('api')->name('api.')->group(function () {
        Route::prefix('dev')->name('api.dev.')->group(function () {

            // Data API Routes
            Route::prefix('data')->name('data.')->group(function () {
                // Quick Actions API
                Route::post('/quick-store', [DataController::class, 'quickStore'])->name('quickStore');
                Route::post('/{id}/quick-update', [DataController::class, 'quickUpdate'])->whereNumber('id')->name('quickUpdate');

                // Bulk Actions API
            Route::post('/bulk-action', [DataController::class, 'bulkAction'])->middleware('role:ho')->name('bulkAction');
            Route::post('/bulk-action/{kategori}', [DataController::class, 'bulkActionByKategori'])->middleware('role:ho')->name('bulkActionByKategori');

                // Statistics API
                Route::get('/statistics', [DataController::class, 'getStatistics'])->name('statistics');
                Route::get('/statistics/{kategori}', [DataController::class, 'getStatisticsByKategori'])->name('statisticsByKategori');
                Route::get('/enhanced-statistics', [DataController::class, 'getEnhancedStatistics'])->name('enhancedStatistics');

                // Data Tables API
                Route::get('/table/{kategori}', [DataController::class, 'getTableData'])->name('tableData');
                Route::get('/search', [DataController::class, 'searchData'])->name('search');
                Route::get('/filter', [DataController::class, 'filterData'])->name('filter');

                // Maintenance API
            Route::post('/generate-kode', [DataController::class, 'generateKode'])->middleware('role:ho')->name('generateKode');
            Route::get('/generate-kode/{kategori}', [DataController::class, 'generateKode'])->middleware('role:ho')->name('generateKode.kategori');
            Route::post('/fix-gaps', [DataController::class, 'fixGaps'])->middleware('role:ho')->name('fixGaps');
            Route::post('/fix-numbering-gaps', [DataController::class, 'fixNumberingGaps'])->middleware('role:ho')->name('fixNumberingGaps');
            Route::post('/reset-counters', [DataController::class, 'resetCounters'])->middleware('role:ho')->name('resetCounters');
            Route::get('/available-numbers/{kategori}', [DataController::class, 'getAvailableNumbers'])->name('availableNumbers');

            // Import/Export API
            Route::post('/import', [DataController::class, 'import'])->middleware('role:ho')->name('import');
            Route::get('/export/{kategori?}', [DataController::class, 'export'])->middleware('role:ho')->name('export');

                // Test & Debug API
                Route::get('/debug', [DataController::class, 'debugData'])->name('debug');
                Route::get('/test-export', [DataController::class, 'testExportSimple'])->middleware('role:ho')->name('testExport');
            });

            // Clients API
            Route::prefix('clients')->name('clients.')->group(function () {
                Route::get('/', [ClientController::class, 'apiIndex'])->name('index');
                Route::get('/search', [ClientController::class, 'apiSearch'])->name('search');
                Route::get('/{id}/projects', [ClientController::class, 'apiClientProjects'])->whereNumber('id')->name('projects');
            });

            // Projects API
            Route::prefix('projects')->name('projects.')->group(function () {
                Route::get('/', [ProjectController::class, 'apiIndex'])->name('index');
                Route::get('/search', [ProjectController::class, 'apiSearch'])->name('search');
                Route::get('/statistics', [ProjectController::class, 'getProjectStatistics'])->name('statistics');
                Route::get('/{projectId}/rabs', [ProjectController::class, 'apiProjectRabs'])->name('rabs');

                // RAB Breakdown API for Projects
                Route::get('/{projectId}/rab-breakdown', [RabBreakdownController::class, 'apiProjectRabBreakdown'])->name('rab-breakdown');
                Route::get('/{projectId}/rab-breakdown-summary', [RabBreakdownController::class, 'apiProjectRabBreakdownSummary'])->name('rab-breakdown.summary');
                // Legacy API URLs
                Route::get('/{projectId}/wbs', [RabBreakdownController::class, 'apiProjectRabBreakdown'])->name('legacy.wbs');
                Route::get('/{projectId}/wbs-summary', [RabBreakdownController::class, 'apiProjectRabBreakdownSummary'])->name('legacy.wbs.summary');

                // Budget Control API for Projects
                // (budget control api summary removed)

                // Voucher API for Projects
                Route::get('/{projectId}/vouchers', [VoucherController::class, 'apiProjectVouchers'])->name('vouchers');
                Route::get('/{projectId}/voucher-summary', [VoucherController::class, 'apiVoucherSummary'])->name('voucher-summary');
            });

            // VOUCHER API Routes
            Route::prefix('projects/{projectId}/vouchers')->name('vouchers.')->group(function () {
                // Basic CRUD
                Route::get('/', [VoucherController::class, 'apiIndex'])->name('index');
                Route::post('/', [VoucherController::class, 'apiStore'])->name('store');
                Route::get('/{id}', [VoucherController::class, 'apiShow'])->name('show');
                Route::put('/{id}', [VoucherController::class, 'apiUpdate'])->name('update');
                Route::delete('/{id}', [VoucherController::class, 'apiDestroy'])->name('destroy');

                // Approval Workflow
                Route::post('/{id}/submit', [VoucherController::class, 'apiSubmit'])->name('submit');
                Route::post('/{id}/approve', [VoucherController::class, 'apiApprove'])->middleware('role:ho')->name('approve');
                Route::post('/{id}/reject', [VoucherController::class, 'apiReject'])->middleware('role:ho')->name('reject');
                Route::post('/{id}/mark-paid', [VoucherController::class, 'apiMarkAsPaid'])->middleware('role:ho')->name('mark-paid');
                Route::post('/{id}/mark-completed', [VoucherController::class, 'apiMarkAsCompleted'])->middleware('role:ho')->name('mark-completed');

                // Voucher Items
                Route::get('/{id}/items', [VoucherController::class, 'apiGetItems'])->name('items');
                Route::post('/{id}/items', [VoucherController::class, 'apiStoreItem'])->name('items.store');
                Route::put('/{id}/items/{itemId}', [VoucherController::class, 'apiUpdateItem'])->name('items.update');
                Route::delete('/{id}/items/{itemId}', [VoucherController::class, 'apiDestroyItem'])->name('items.destroy');
                Route::post('/{id}/items/{itemId}/update-status', [VoucherController::class, 'apiUpdateItemStatus'])->name('items.update-status');

                // Calculations
                Route::post('/{id}/calculate-totals', [VoucherController::class, 'apiCalculateTotals'])->name('calculate-totals');
                Route::get('/{id}/print-data', [VoucherController::class, 'apiPrintData'])->name('print-data');

                // Bulk Actions
                Route::post('/bulk-approve', [VoucherController::class, 'apiBulkApprove'])->middleware('role:ho')->name('bulk-approve');
                Route::post('/bulk-reject', [VoucherController::class, 'apiBulkReject'])->middleware('role:ho')->name('bulk-reject');
            });

            // VENDOR API Routes
            Route::prefix('vendors')->name('vendors.')->group(function () {
                Route::get('/', [VoucherController::class, 'apiVendorIndex'])->name('index');
                Route::post('/', [VoucherController::class, 'apiVendorStore'])->name('store');
                Route::get('/{id}', [VoucherController::class, 'apiVendorShow'])->name('show');
                Route::put('/{id}', [VoucherController::class, 'apiVendorUpdate'])->name('update');
                Route::delete('/{id}', [VoucherController::class, 'apiVendorDestroy'])->name('destroy');

                Route::get('/search', [VoucherController::class, 'apiVendorSearch'])->name('search');
            Route::post('/import', [VoucherController::class, 'apiVendorImport'])->middleware('role:ho')->name('import');
                Route::get('/export', [VoucherController::class, 'apiVendorExport'])->name('export');
            });

            // RAB Breakdown API Routes
            Route::prefix('projects/{projectId}')->name('rab-breakdown.')->group(function () {
                Route::prefix('rab-breakdown')->group(function () {
                    // RAB Breakdown Headers API
                    Route::get('/', [RabBreakdownController::class, 'apiIndex'])->name('index');
                Route::post('/', [RabBreakdownController::class, 'apiStore'])->middleware('role:ho')->name('store');
                Route::get('/{rabBreakdownId}', [RabBreakdownController::class, 'apiShow'])->name('show');
                Route::put('/{rabBreakdownId}', [RabBreakdownController::class, 'apiUpdate'])->middleware('role:ho')->name('update');
                Route::delete('/{rabBreakdownId}', [RabBreakdownController::class, 'apiDestroy'])->middleware('role:ho')->name('destroy');

                    // RAB Breakdown Items API
                    Route::prefix('{rabBreakdownId}/items')->group(function () {
                        Route::get('/', [RabBreakdownController::class, 'apiGetItems'])->name('items.index');
                    Route::post('/', [RabBreakdownController::class, 'apiStoreItem'])->middleware('role:ho')->name('items.store');
                });

                    // RAB Breakdown Progress & Status API
                Route::post('/{rabBreakdownId}/update-progress', [RabBreakdownController::class, 'apiUpdateProgress'])->middleware('role:ho')->name('updateProgress');
                Route::post('/{rabBreakdownId}/update-status', [RabBreakdownController::class, 'apiUpdateStatus'])->middleware('role:ho')->name('updateStatus');
                Route::post('/{rabBreakdownId}/update-actual', [RabBreakdownController::class, 'apiUpdateActual'])->middleware('role:ho')->name('updateActual');

                    // RAB Breakdown Calculations API
                    Route::get('/{rabBreakdownId}/calculate-budget', [RabBreakdownController::class, 'apiCalculateBudget'])->name('calculateBudget');
                    Route::get('/{rabBreakdownId}/calculate-progress', [RabBreakdownController::class, 'apiCalculateProgress'])->name('calculateProgress');
                    Route::get('/{rabBreakdownId}/summary', [RabBreakdownController::class, 'apiGetSummary'])->name('summary');

                    // Partial Views Routes
                    Route::get('/{rabBreakdown}/partials/items-table', [RabBreakdownController::class, 'getItemsTablePartial'])->name('partials.items-table');
                    Route::get('/{rabBreakdown}/partials/budget-sources', [RabBreakdownController::class, 'getBudgetSourcesPartial'])->name('partials.budget-sources');
                    Route::get('/{rabBreakdown}/partials/progress-form', [RabBreakdownController::class, 'getProgressFormPartial'])->name('partials.progress-form');
                    Route::get('/{rabBreakdown}/partials/import-preview', [RabBreakdownController::class, 'getImportPreviewPartial'])->name('partials.import-preview');
                });
            });

            // RAB Baseline API Routes (Quick Add & AJAX)
            Route::prefix('projects/{projectId}/rapps')->name('rabs.')->group(function () {
                // Quick Add Item API
                Route::post('/{rabId}/quick-add', [RappController::class, 'quickAddItem'])->name('quick-add');
                
                // Items API
                Route::post('/{rabId}/items', [RappController::class, 'storeItem'])->name('items.store');
                Route::post('/{rabId}/items/bulk', [RappController::class, 'bulkStoreItems'])->name('items.bulk.store');
                Route::put('/{rabId}/items/{itemId}', [RappController::class, 'updateItem'])->name('items.update');
                
                // Breakdown & Comparison API
                Route::get('/{rabId}/breakdown', [RappController::class, 'getBreakdown'])->name('breakdown');
                Route::get('/{rabId}/comparison', [RappController::class, 'getComparison'])->name('comparison');
                
                // Debug & Repair API
                Route::get('/{rabId}/debug', [RappController::class, 'debugConsistency'])->middleware('role:ho')->name('debug');
                Route::post('/{rabId}/repair', [RappController::class, 'repairData'])->middleware('role:ho')->name('repair');
            });

            // Dashboard API
            Route::prefix('dashboard')->name('dashboard.')->group(function () {
                Route::get('/stats', [DashboardController::class, 'apiGetStats'])->name('stats');
                Route::get('/recent-activities', [DashboardController::class, 'apiGetRecentActivities'])->name('recent-activities');
                Route::get('/chart-data', [DashboardController::class, 'apiGetChartData'])->name('chart-data');

                // Budget Control Dashboard Stats
                // (budget control dashboard stats removed)

                // Voucher Dashboard Stats
                Route::get('/voucher-stats', [VoucherController::class, 'apiDashboardStats'])->name('voucher.stats');
                Route::get('/voucher-status-chart', [VoucherController::class, 'apiStatusChart'])->name('voucher.status-chart');

                // RAB Breakdown Dashboard Stats
                Route::get('/rab-breakdown-stats', [RabBreakdownController::class, 'apiDashboardStats'])->name('rab-breakdown.stats');
                Route::get('/rab-breakdown-progress-chart', [RabBreakdownController::class, 'apiProgressChart'])->name('rab-breakdown.progressChart');
                // Legacy dashboard URLs
                Route::get('/wbs-stats', [RabBreakdownController::class, 'apiDashboardStats'])->name('legacy.wbs.stats');
                Route::get('/wbs-progress-chart', [RabBreakdownController::class, 'apiProgressChart'])->name('legacy.wbs.progressChart');
            });

            // Settings API
            Route::prefix('settings')->name('settings.')->group(function () {
                Route::post('/cache/clear', [DashboardController::class, 'clearCache'])->name('cache.clear');
                Route::post('/views/clear', [DashboardController::class, 'clearViews'])->name('views.clear');
                Route::post('/cache/all', [DashboardController::class, 'clearAllCache'])->name('cache.all');
                Route::post('/maintenance/toggle', [DashboardController::class, 'toggleMaintenance'])->name('maintenance.toggle');
                Route::post('/backup/database', [DashboardController::class, 'backupDatabase'])->name('backup.database');

                // Budget Control Settings API
                // (budget control settings api removed)

                // Voucher Settings API
            Route::get('/voucher', [VoucherController::class, 'apiVoucherSettings'])->middleware('role:ho')->name('voucher');
            Route::post('/voucher/update', [VoucherController::class, 'apiUpdateVoucherSettings'])->middleware('role:ho')->name('voucher.update');

                // Vendor Settings API
            Route::get('/vendor-categories', [VoucherController::class, 'apiVendorCategories'])->middleware('role:ho')->name('vendor.categories');
            Route::post('/vendor-categories/update', [VoucherController::class, 'apiUpdateVendorCategories'])->middleware('role:ho')->name('vendor.categories.update');

                // RAB Breakdown Settings API
            Route::get('/rab-breakdown-categories', [RabBreakdownController::class, 'apiGetCategories'])->middleware('role:ho')->name('rab-breakdown.categories');
            Route::post('/rab-breakdown-categories/update', [RabBreakdownController::class, 'apiUpdateCategories'])->middleware('role:ho')->name('rab-breakdown.categories.update');
            // Legacy settings API URLs
            Route::get('/wbs-categories', [RabBreakdownController::class, 'apiGetCategories'])->middleware('role:ho')->name('legacy.wbs.categories');
            Route::post('/wbs-categories/update', [RabBreakdownController::class, 'apiUpdateCategories'])->middleware('role:ho')->name('legacy.wbs.categories.update');
            });
        });
    });
});

// ==================== PUBLIC API ROUTES ====================
Route::prefix('public')->name('public.')->group(function () {
    // Data Master Public API
    Route::prefix('data')->name('data.')->group(function () {
        Route::get('/categories', [DataController::class, 'publicCategories'])->name('categories');
        Route::get('/items', [DataController::class, 'publicItems'])->name('items');
        Route::get('/items/{kategori}', [DataController::class, 'publicItemsByKategori'])->name('items.byKategori');
        Route::get('/search', [DataController::class, 'publicSearch'])->name('search');
    });

    // Public RAB Breakdown API
    Route::prefix('rab-breakdown')->name('rab-breakdown.')->group(function () {
        Route::get('/categories', [RabBreakdownController::class, 'publicCategories'])->name('categories');
        Route::get('/project/{projectId}/summary', [RabBreakdownController::class, 'publicProjectSummary'])->name('project.summary');
    });
    // Legacy public WBS API
    Route::prefix('wbs')->name('legacy.wbs.')->group(function () {
        Route::get('/categories', [RabBreakdownController::class, 'publicCategories'])->name('categories');
        Route::get('/project/{projectId}/summary', [RabBreakdownController::class, 'publicProjectSummary'])->name('project.summary');
    });

    // Public Projects API
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'publicIndex'])->name('index');
        Route::get('/{id}', [ProjectController::class, 'publicShow'])->name('show');
    });
});

// ==================== FALLBACK ROUTE ====================
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
})->name('fallback');


