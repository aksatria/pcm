<?php

use App\Http\Controllers\Dev\MasterDataController;
use App\Models\MasterData;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for Master Data Management
|--------------------------------------------------------------------------
|
| Semua route di sini menggunakan prefix /api secara otomatis
| dan middleware 'api'
|
*/

// Master Data Management Routes
$registerMasterDataRoutes = function (string $prefix, string $namePrefix): void {
    Route::prefix($prefix)->group(function () use ($namePrefix) {

        // =========================================================================
        // BASIC CRUD OPERATIONS
        // =========================================================================
        Route::get('/', [MasterDataController::class, 'index'])->name("{$namePrefix}.index");
        Route::post('/', [MasterDataController::class, 'store'])->name("{$namePrefix}.store");
        Route::get('/{id}', [MasterDataController::class, 'show'])->name("{$namePrefix}.show");
        Route::put('/{id}', [MasterDataController::class, 'update'])->name("{$namePrefix}.update");
        Route::patch('/{id}', [MasterDataController::class, 'update'])->name("{$namePrefix}.patch");
        Route::delete('/{id}', [MasterDataController::class, 'destroy'])->name("{$namePrefix}.delete");

        Route::post('/bulk/update', [MasterDataController::class, 'bulkUpdate'])->name("{$namePrefix}.bulk-update");

        // =========================================================================
        // UTILITY ENDPOINTS
        // =========================================================================
        Route::get('/generate/kode', [MasterDataController::class, 'generateKode'])->name("{$namePrefix}.generate-kode");

        // =========================================================================
        // IMPORT/EXPORT ROUTES
        // =========================================================================
        Route::post('/import', [MasterDataController::class, 'import'])->name("{$namePrefix}.import");
        Route::get('/export', [MasterDataController::class, 'export'])->name("{$namePrefix}.export");
        Route::get('/export/template', [MasterDataController::class, 'getTemplate'])->name("{$namePrefix}.export-template");

        // =========================================================================
        // ADVANCED (MINIMAL COMPAT)
        // =========================================================================
        Route::prefix('advanced')->group(function () use ($namePrefix) {
            Route::get('/search', function (\Illuminate\Http\Request $request) {
                $query = MasterData::query();
                if ($request->filled('q')) {
                    $term = $request->get('q');
                    $query->where(function ($q) use ($term) {
                        $q->where('name', 'like', "%{$term}%")
                            ->orWhere('code', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%");
                    });
                }

                return response()->json([
                    'success' => true,
                    'data' => $query->limit(50)->get(),
                ]);
            })->name("{$namePrefix}.advanced-search");

            Route::get('/price-analysis', function () {
                $items = MasterData::whereNotNull('price')->get();
                $categoryStats = $items->groupBy('category')->map(fn ($rows) => ['count' => $rows->count()]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'category_stats' => $categoryStats,
                        'price_changes' => [],
                        'summary' => [
                            'avg_price' => (float) ($items->avg('price') ?? 0),
                            'max_price' => (float) ($items->max('price') ?? 0),
                            'min_price' => (float) ($items->min('price') ?? 0),
                        ],
                    ],
                ]);
            })->name("{$namePrefix}.price-analysis");
        });
    });
};

// Primary namespace (new)
$registerMasterDataRoutes('data', 'api.data');

// Legacy alias (transitional)
$registerMasterDataRoutes('master-data', 'api.master-data');

// =========================================================================
// WEB ROUTES UNTUK BROWSER ACCESS (Optional)
// =========================================================================
// Route::get('/master-data/export', [MasterDataExportController::class, 'export'])->name('master-data.export');
// Route::get('/master-data/import/template', [MasterDataExportController::class, 'downloadTemplate'])->name('master-data.import.template');
