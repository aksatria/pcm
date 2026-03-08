<?php
// app/Http\Controllers\Dev\DataController.php

namespace App\Http\Controllers\Dev;

use App\Models\Data;
use App\Exports\DataExport;
use App\Exports\DataMultiExport;
use App\Exports\SimpleDataExport;
use App\Imports\DataImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataController extends BaseController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        try {
            parent::__construct();
        } catch (\Exception $e) {
            Log::warning('DataController constructor error: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of the resource with multiple tables - TANPA PAGINATION
     */
    public function index(Request $request)
    {
        try {
            Log::info('DataController index accessed', [
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'query_params' => $request->all()
            ]);

            // Cek apakah tabel data exists
            if (!Schema::hasTable('data')) {
                Log::warning('Table data does not exist');
                
                // Return empty array untuk semua categories
                $emptyData = [];
                foreach (Data::KATEGORI as $kode => $nama) {
                    $emptyData[$kode] = collect([]);
                }
                
                return view('dev.data.index-multi', [
                    'dataByKategori' => $emptyData,
                    'kategoriCounts' => [],
                    'kategoriList' => Data::KATEGORI,
                    'satuanList' => Data::SATUAN,
                    'statistics' => []
                ])->with('warning', 'Tabel data belum tersedia. Silakan jalankan migrasi database.');
            }

            $kategoriList = Data::KATEGORI;
            $satuanList = Data::SATUAN;
            $dataByKategori = [];
            $kategoriCounts = [];
            $statistics = [];

            // Get statistics for each category
            foreach ($kategoriList as $kode => $nama) {
                $statistics[$kode] = Data::getStatisticsByKategori($kode);
            }

            // Global search and filters
            $globalSearch = $request->get('search', '');
            $globalStatus = $request->get('status', '');
            $globalSort = $request->get('sort', 'kode_asc');

            // Process each category separately - TANPA PAGINATION
            foreach ($kategoriList as $kodeKategori => $namaKategori) {
                $query = Data::where('kode_kategori', $kodeKategori);

                // Apply global search
                if (!empty($globalSearch)) {
                    $query->where(function($q) use ($globalSearch) {
                        $q->where('kode', 'like', "%{$globalSearch}%")
                          ->orWhere('uraian', 'like', "%{$globalSearch}%")
                          ->orWhere('kategori', 'like', "%{$globalSearch}%");
                    });
                }

                // Apply global status filter
                if ($globalStatus !== '') {
                    $statusValue = $globalStatus == '1' ? true : false;
                    $query->where('status', $statusValue);
                }

                // Apply sorting
                $query = $this->applySorting($query, $globalSort);

                // Get ALL results for this category - TANPA PAGINATION
                $dataByKategori[$kodeKategori] = $query->get();
                
                // Get count for this category (with filters applied)
                $kategoriCounts[$kodeKategori] = $dataByKategori[$kodeKategori]->count();
            }

            Log::info('Multi-table data loaded successfully', [
                'global_search' => $globalSearch,
                'global_status' => $globalStatus,
                'global_sort' => $globalSort
            ]);

            return view('dev.data.index-multi', compact(
                'dataByKategori',
                'kategoriCounts',
                'kategoriList',
                'satuanList',
                'statistics'
            ));

        } catch (\Exception $e) {
            Log::error('Error in Data index (multi-table): ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return empty array untuk semua categories
            $emptyData = [];
            foreach (Data::KATEGORI as $kode => $nama) {
                $emptyData[$kode] = collect([]);
            }
            
            return view('dev.data.index-multi', [
                'dataByKategori' => $emptyData,
                'kategoriCounts' => [],
                'kategoriList' => Data::KATEGORI,
                'satuanList' => Data::SATUAN,
                'statistics' => []
            ])->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, $sort)
    {
        switch($sort) {
            case 'newest':
                return $query->orderBy('created_at', 'desc');
            case 'oldest':
                return $query->orderBy('created_at', 'asc');
            case 'kode_asc':
                return $query->orderBy('kode', 'asc');
            case 'kode_desc':
                return $query->orderBy('kode', 'desc');
            case 'harga_high':
                return $query->orderBy('harga', 'desc');
            case 'harga_low':
                return $query->orderBy('harga', 'asc');
            case 'uraian_asc':
                return $query->orderBy('uraian', 'asc');
            case 'uraian_desc':
                return $query->orderBy('uraian', 'desc');
            default:
                return $query->orderBy('kode', 'asc');
        }
    }

    /**
     * Display multi-table view
     */
    public function multiTable(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Get table data by kategori - TANPA PAGINATION
     */
    public function getTableByKategori(Request $request, $kategori)
    {
        try {
            if (!array_key_exists($kategori, Data::KATEGORI)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak valid'
                ], 400);
            }

            $query = Data::where('kode_kategori', $kategori);

            // Apply filters
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('kode', 'like', "%{$search}%")
                      ->orWhere('uraian', 'like', "%{$search}%");
                });
            }

            if ($request->has('status') && $request->status !== '') {
                $statusValue = $request->status == '1' ? true : false;
                $query->where('status', $statusValue);
            }

            // Apply sorting
            $sort = $request->get('sort', 'kode_asc');
            $query = $this->applySorting($query, $sort);

            // TANPA PAGINATION - get all data
            $data = $query->get();

            return response()->json([
                'success' => true,
                'data' => $data,
                'kategori' => Data::KATEGORI[$kategori],
                'statistics' => Data::getStatisticsByKategori($kategori)
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getTableByKategori: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Navigate to specific kategori
     */
    public function navigateToKategori(Request $request, $kategori)
    {
        if (!array_key_exists($kategori, Data::KATEGORI)) {
            return redirect()->route('dev.data.index')->with('error', 'Kategori tidak valid');
        }

        // Set session to expand the target table
        session(['expanded_table' => $kategori]);

        return redirect()->route('dev.data.index')->with('scroll_to', $kategori);
    }

    /**
     * Bulk Actions for specific category
     */
    public function bulkActionByKategori(Request $request, $kategori)
    {
        try {
            Log::info('Bulk action by kategori request', [
                'kategori' => $kategori,
                'request' => $request->all()
            ]);

            $request->validate([
                'action' => 'required|in:activate,deactivate,delete,move_category,update_harga',
                'ids' => 'required|array',
                'ids.*' => 'exists:data,id'
            ]);

            // Verify that all IDs belong to the specified category
            $invalidIds = Data::whereIn('id', $request->ids)
                ->where('kode_kategori', '!=', $kategori)
                ->pluck('id');

            if ($invalidIds->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Beberapa ID tidak termasuk dalam kategori ' . $kategori
                ], 400);
            }

            $ids = $request->ids;
            $action = $request->action;
            $message = '';

            switch ($action) {
                case 'activate':
                    Data::whereIn('id', $ids)->update(['status' => true]);
                    $message = count($ids) . ' data berhasil diaktifkan di kategori ' . $kategori;
                    break;
                    
                case 'deactivate':
                    Data::whereIn('id', $ids)->update(['status' => false]);
                    $message = count($ids) . ' data berhasil dinonaktifkan di kategori ' . $kategori;
                    break;
                    
                case 'delete':
                    Data::whereIn('id', $ids)->delete();
                    $message = count($ids) . ' data berhasil dihapus permanen dari kategori ' . $kategori;
                    break;
                    
                case 'move_category':
                    $request->validate([
                        'new_kategori' => 'required|in:MT,JS,AT,HO,SR,SB'
                    ]);
                    
                    $newKategori = $request->new_kategori;
                    $newKategoriName = Data::KATEGORI[$newKategori];
                    
                    // Update kategori untuk semua data yang dipilih
                    foreach ($ids as $id) {
                        $data = Data::find($id);
                        if ($data) {
                            $newKode = Data::generateKode($newKategori);
                            $data->update([
                                'kode_kategori' => $newKategori,
                                'kategori' => $newKategoriName,
                                'kode' => $newKode,
                            ]);
                        }
                    }
                    $message = count($ids) . ' data berhasil dipindahkan ke kategori ' . $newKategoriName;
                    break;
                    
                case 'update_harga':
                    $request->validate([
                        'new_harga' => 'required|numeric|min:0|max:999999999999.99'
                    ]);
                    
                    $newHarga = $request->new_harga;
                    Data::whereIn('id', $ids)->update(['harga' => $newHarga]);
                    $message = count($ids) . ' data berhasil diupdate harganya menjadi Rp ' . number_format($newHarga, 0, ',', '.');
                    break;
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Aksi tidak valid'
                    ], 400);
            }

            Log::info('Bulk action by kategori completed', [
                'kategori' => $kategori,
                'action' => $action,
                'count' => count($ids)
            ]);

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Error in bulkActionByKategori: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan aksi massal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export data by specific category - EXCEL VERSION
     */
    public function exportByKategori(Request $request, $kategori)
    {
        try {
            if (!array_key_exists($kategori, Data::KATEGORI)) {
                return redirect()->route('dev.data.index')
                    ->with('error', 'Kategori tidak valid');
            }

            $filters = $request->only(['status', 'search']);
            $filters['kategori'] = $kategori;
            
            $filename = 'data-' . strtolower($kategori) . '-' . date('Y-m-d') . '.xlsx';

            return Excel::download(new DataExport($filters), $filename);

        } catch (\Exception $e) {
            Log::error('Error in exportByKategori: ' . $e->getMessage());
            
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal mengexport data: ' . $e->getMessage());
        }
    }

    /**
     * Export all categories - EXCEL VERSION
     */
    public function exportAll(Request $request)
    {
        try {
            $filters = $request->only(['status', 'search']);
            
            return Excel::download(new DataMultiExport($filters), 'data-all-categories-' . date('Y-m-d') . '.xlsx');

        } catch (\Exception $e) {
            Log::error('Error in exportAll: ' . $e->getMessage());
            
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal mengexport data: ' . $e->getMessage());
        }
    }

    /**
     * Export to Excel - EXCEL VERSION  
     */
    public function export(Request $request)
    {
        try {
            $filters = $request->only(['kategori', 'status', 'search']);
            
            return Excel::download(new DataExport($filters), 'data-master-' . date('Y-m-d') . '.xlsx');

        } catch (\Exception $e) {
            Log::error('Error in export: ' . $e->getMessage());
            
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal mengexport data: ' . $e->getMessage());
        }
    }

    /**
     * Test Export
     */
    public function testExport()
    {
        try {
            return Excel::download(new \App\Exports\TestExport(), 'test-export.xlsx');
        } catch (\Exception $e) {
            Log::error('Test export error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Test Export Sederhana - tanpa filter
     */
    public function testExportSimple()
    {
        try {
            Log::info('Test export simple called');
            
            // Cek data di database
            $dataCount = Data::count();
            Log::info('Total data in database: ' . $dataCount);
            
            if ($dataCount === 0) {
                return response()->json([
                    'error' => 'Tidak ada data di database',
                    'count' => 0
                ], 400);
            }

            // Export sederhana tanpa filter
            return Excel::download(new DataExport(), 'test-export-simple-' . date('Y-m-d') . '.xlsx');
            
        } catch (\Exception $e) {
            Log::error('Test export simple error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Test Export All Data dengan filter
     */
    public function testExportAll()
    {
        try {
            Log::info('Test export all called');
            
            $filters = request()->all();
            Log::info('Filters: ', $filters);
            
            return Excel::download(new DataExport($filters), 'test-export-all-' . date('Y-m-d') . '.xlsx');
            
        } catch (\Exception $e) {
            Log::error('Test export all error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Debug Data
     */
    public function debugData()
    {
        try {
            $data = Data::all();
            
            return response()->json([
                'total_data' => $data->count(),
                'data' => $data->take(5), // Ambil 5 data pertama
                'filters_applied' => request()->all()
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export CSV Sederhana
     */
    public function exportCSV(Request $request)
    {
        try {
            $filters = $request->only(['status', 'search', 'kategori']);
            $query = Data::query();

            // Apply filters sama seperti di DataExport
            if (isset($filters['status']) && $filters['status'] !== '') {
                $statusValue = $filters['status'] == '1' ? true : false;
                $query->where('status', $statusValue);
            }

            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('kode', 'like', "%{$search}%")
                      ->orWhere('uraian', 'like', "%{$search}%")
                      ->orWhere('kategori', 'like', "%{$search}%");
                });
            }

            if (isset($filters['kategori']) && !empty($filters['kategori'])) {
                $query->where('kode_kategori', $filters['kategori']);
            }

            $data = $query->orderBy('kode_kategori')->orderBy('kode')->get();

            $fileName = 'data-export-' . date('Y-m-d') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ];

            $callback = function() use ($data) {
                $file = fopen('php://output', 'w');
                
                // Add BOM for UTF-8
                fwrite($file, "\xEF\xBB\xBF");
                
                // Header
                fputcsv($file, ['Kode', 'Kategori', 'Uraian', 'Satuan', 'Harga', 'Status']);

                // Data
                foreach ($data as $item) {
                    fputcsv($file, [
                        $item->kode,
                        $item->kategori,
                        $item->uraian,
                        $item->satuan,
                        $item->harga,
                        $item->status ? 'Aktif' : 'Nonaktif'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error in exportCSV: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal mengexport data: ' . $e->getMessage());
        }
    }

    /**
     * Get enhanced statistics
     */
    public function getEnhancedStatistics()
    {
        try {
            $stats = [];
            $grandTotal = 0;
            $grandAverage = 0;
            $totalItems = 0;

            foreach (Data::KATEGORI as $kode => $nama) {
                $categoryStats = Data::getStatisticsByKategori($kode);
                $stats[$kode] = $categoryStats;
                $grandTotal += $categoryStats['total_harga'];
                $totalItems += $categoryStats['total_items'];
            }

            $grandAverage = $totalItems > 0 ? $grandTotal / $totalItems : 0;

            $enhancedStats = [
                'by_kategori' => $stats,
                'grand_total' => $grandTotal,
                'grand_total_formatted' => 'Rp ' . number_format($grandTotal, 2, ',', '.'),
                'grand_average' => $grandAverage,
                'grand_average_formatted' => 'Rp ' . number_format($grandAverage, 2, ',', '.'),
                'total_items' => $totalItems,
                'total_categories' => count(Data::KATEGORI)
            ];

            Log::info('Enhanced statistics retrieved', $enhancedStats);

            return response()->json([
                'success' => true,
                'data' => $enhancedStats
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getEnhancedStatistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics by kategori
     */
    public function getStatisticsByKategori($kategori)
    {
        try {
            if (!array_key_exists($kategori, Data::KATEGORI)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak valid'
                ], 400);
            }

            $stats = Data::getStatisticsByKategori($kategori);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getStatisticsByKategori: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get table data for API
     */
    public function getTableData(Request $request, $kategori)
    {
        return $this->getTableByKategori($request, $kategori);
    }

    /**
     * Search data across all categories
     */
    public function searchData(Request $request)
    {
        try {
            $search = $request->get('q', '');
            $kategori = $request->get('kategori', '');

            $query = Data::query();

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('kode', 'like', "%{$search}%")
                      ->orWhere('uraian', 'like', "%{$search}%");
                });
            }

            if (!empty($kategori) && array_key_exists($kategori, Data::KATEGORI)) {
                $query->where('kode_kategori', $kategori);
            }

            $data = $query->limit(50)->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error in searchData: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Data master report
     */
    public function dataMasterReport()
    {
        try {
            $statistics = [];
            foreach (Data::KATEGORI as $kode => $nama) {
                $statistics[$kode] = Data::getStatisticsByKategori($kode);
            }

            return view('dev.reports.data-master', compact('statistics'));

        } catch (\Exception $e) {
            Log::error('Error in dataMasterReport: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal memuat laporan: ' . $e->getMessage());
        }
    }

    /**
     * Data usage report
     */
    public function dataUsageReport()
    {
        try {
            // Implement usage report logic here
            return view('dev.reports.data-usage');

        } catch (\Exception $e) {
            Log::error('Error in dataUsageReport: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal memuat laporan: ' . $e->getMessage());
        }
    }

    /**
     * Export summary report
     */
    public function exportSummaryReport()
    {
        try {
            $summary = [];
            foreach (Data::KATEGORI as $kode => $nama) {
                $summary[$kode] = [
                    'nama' => $nama,
                    'total_items' => Data::where('kode_kategori', $kode)->count(),
                    'total_value' => Data::where('kode_kategori', $kode)->sum('harga'),
                    'active_items' => Data::where('kode_kategori', $kode)->where('status', true)->count()
                ];
            }

            return view('dev.reports.export-summary', compact('summary'));

        } catch (\Exception $e) {
            Log::error('Error in exportSummaryReport: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal memuat laporan: ' . $e->getMessage());
        }
    }

    /**
     * Category summary report
     */
    public function categorySummaryReport()
    {
        try {
            $summary = [];
            foreach (Data::KATEGORI as $kode => $nama) {
                $summary[$kode] = [
                    'nama' => $nama,
                    'total_items' => Data::where('kode_kategori', $kode)->count(),
                    'total_value' => Data::where('kode_kategori', $kode)->sum('harga'),
                    'active_items' => Data::where('kode_kategori', $kode)->where('status', true)->count()
                ];
            }

            return view('dev.reports.category-summary', compact('summary'));

        } catch (\Exception $e) {
            Log::error('Error in categorySummaryReport: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal memuat laporan: ' . $e->getMessage());
        }
    }

    /**
     * Data categories settings
     */
    public function dataCategories()
    {
        try {
            $kategoriList = Data::KATEGORI;
            return view('dev.settings.data-categories', compact('kategoriList'));

        } catch (\Exception $e) {
            Log::error('Error in dataCategories: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal memuat pengaturan: ' . $e->getMessage());
        }
    }

    /**
     * Update categories
     */
    public function updateCategories(Request $request)
    {
        try {
            // Implement category update logic here
            return redirect()->route('dev.settings.dataCategories')
                ->with('success', 'Kategori berhasil diperbarui');

        } catch (\Exception $e) {
            Log::error('Error in updateCategories: ' . $e->getMessage());
            return redirect()->route('dev.settings.dataCategories')
                ->with('error', 'Gagal memperbarui kategori: ' . $e->getMessage());
        }
    }

    /**
     * Backup data
     */
    public function backupData()
    {
        try {
            // Implement backup logic here
            return response()->download(storage_path('app/backup/data-backup-' . date('Y-m-d') . '.sql'));

        } catch (\Exception $e) {
            Log::error('Error in backupData: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal membuat backup: ' . $e->getMessage());
        }
    }

    /**
     * Restore data
     */
    public function restoreData(Request $request)
    {
        try {
            // Implement restore logic here
            return redirect()->route('dev.data.index')
                ->with('success', 'Data berhasil direstore');

        } catch (\Exception $e) {
            Log::error('Error in restoreData: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal merestore data: ' . $e->getMessage());
        }
    }

    /**
     * Maintenance page
     */
    public function maintenance()
    {
        try {
            $counters = DB::table('data_counters')->get()->keyBy('kode_kategori');
            $availableNumbers = [];
            
            foreach (Data::KATEGORI as $kode => $nama) {
                $availableNumbers[$kode] = Data::getAvailableNumbers($kode);
            }

            return view('dev.data.maintenance', compact('counters', 'availableNumbers'));

        } catch (\Exception $e) {
            Log::error('Error loading maintenance page: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal memuat halaman maintenance: ' . $e->getMessage());
        }
    }

    /**
     * Audit log for admin
     */
    public function auditLog()
    {
        try {
            // Implement audit log logic here
            return view('dev.admin.audit-log');
        } catch (\Exception $e) {
            Log::error('Error in auditLog: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal memuat log audit: ' . $e->getMessage());
        }
    }

    /**
     * Data cleanup
     */
    public function dataCleanup()
    {
        try {
            return view('dev.admin.data-cleanup');
        } catch (\Exception $e) {
            Log::error('Error in dataCleanup: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal memuat halaman cleanup: ' . $e->getMessage());
        }
    }

    /**
     * Perform cleanup
     */
    public function performCleanup(Request $request)
    {
        try {
            // Implement cleanup logic here
            return redirect()->route('dev.admin.data.cleanup')
                ->with('success', 'Cleanup berhasil dilakukan');
        } catch (\Exception $e) {
            Log::error('Error in performCleanup: ' . $e->getMessage());
            return redirect()->route('dev.admin.data.cleanup')
                ->with('error', 'Gagal melakukan cleanup: ' . $e->getMessage());
        }
    }

    /**
     * Fix numbering gaps
     */
    public function fixNumberingGaps(Request $request)
    {
        try {
            Log::info('Fixing numbering gaps');
            
            Data::fixGaps();
            
            Log::info('Numbering gaps fixed successfully');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Penomoran berhasil diperbaiki'
                ]);
            }

            return redirect()->route('dev.data.index')
                ->with('success', 'Penomoran berhasil diperbaiki');

        } catch (\Exception $e) {
            Log::error('Error fixing numbering gaps: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbaiki penomoran: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal memperbaiki penomoran: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // KEEP ALL EXISTING METHODS FROM PREVIOUS IMPLEMENTATION
    // =========================================================================

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $kategoriList = Data::KATEGORI;
            $satuanList = Data::SATUAN;

            return view('dev.data.create', compact('kategoriList', 'satuanList'));
        } catch (\Exception $e) {
            Log::error('Error in Data create: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal memuat form: ' . $e->getMessage());
        }
    }

/**
 * Store a newly created resource in storage.
 */
public function store(Request $request)
{
    try {
        Log::info('Store data request', $request->all());

        // PERBAIKAN: Konversi status ke boolean
        $status = $request->status === '1' || $request->status === 'true' || $request->status === true || $request->status === 1;

        $validator = Validator::make($request->all(), [
            'kode_kategori' => 'required|in:MT,JS,AT,HO,SR,SB',
            'uraian' => [
                'required',
                'string',
                'max:1000',
                Rule::unique('data')->where(function ($query) use ($request) {
                    return $query->where('uraian', $request->uraian)
                                 ->where('kode_kategori', $request->kode_kategori);
                })
            ],
            'satuan' => 'required|string|max:50',
            'harga' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
                        $fail('Format harga tidak valid. Gunakan format: 100000 atau 100000.00');
                    }
                }
            ]
        ], [
            'uraian.unique' => 'Uraian sudah ada untuk kategori ini',
            'harga.max' => 'Harga terlalu besar. Maksimal 999.999.999.999,99',
            'satuan.*' => 'Satuan harus diisi'
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed', $validator->errors()->toArray());
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        // Generate kode otomatis
        $kode = Data::generateKode($validated['kode_kategori']);
        Log::info('Generated kode: ' . $kode);

        // Build data array hanya dengan kolom yang ada
        $dataArray = [
            'kode' => $kode,
            'kode_kategori' => $validated['kode_kategori'],
            'kategori' => Data::KATEGORI[$validated['kode_kategori']],
            'uraian' => $validated['uraian'],
            'satuan' => $validated['satuan'],
            'harga' => $validated['harga'],
            'status' => $status, // Gunakan status yang sudah dikonversi
        ];

        // Hanya tambahkan created_by dan updated_by jika kolomnya ada
        if (Schema::hasColumn('data', 'created_by')) {
            $dataArray['created_by'] = auth()->check() ? auth()->user()->name : 'system';
        }

        if (Schema::hasColumn('data', 'updated_by')) {
            $dataArray['updated_by'] = auth()->check() ? auth()->user()->name : 'system';
        }

        $data = Data::create($dataArray);
        AuditLogger::log('data.created', $data, null, $data->toArray(), [
            'kode' => $data->kode,
            'kategori' => $data->kategori,
        ]);

        return redirect()->route('dev.data.index')
            ->with('success', 'Data berhasil ditambahkan dengan kode: ' . $kode);

    } catch (\Exception $e) {
        Log::error('Error storing data: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return redirect()->back()
            ->with('error', 'Gagal menambahkan data: ' . $e->getMessage())
            ->withInput();
    }
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $item = Data::findOrFail($id);
            $kategoriList = Data::KATEGORI;
            $satuanList = Data::SATUAN;

            return view('dev.data.edit', compact('item', 'kategoriList', 'satuanList'));
        } catch (\Exception $e) {
            Log::error('Error editing data: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

/**
 * Update the specified resource in storage.
 */
public function update(Request $request, $id)
{
    try {
        $data = Data::findOrFail($id);
        $before = $data->toArray();
        Log::info('Update data request', ['id' => $id, 'request' => $request->all()]);

        // PERBAIKAN: Konversi status ke boolean
        $status = $request->status === '1' || $request->status === 'true' || $request->status === true || $request->status === 1;

        $validator = Validator::make($request->all(), [
            'uraian' => [
                'required',
                'string',
                'max:1000',
                Rule::unique('data')->where(function ($query) use ($id, $data, $request) {
                    return $query->where('uraian', $request->uraian)
                                 ->where('kode_kategori', $data->kode_kategori)
                                 ->where('id', '!=', $id);
                })
            ],
            'satuan' => 'required|string|max:50',
            'harga' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99'
            ]
        ], [
            'uraian.unique' => 'Uraian sudah ada untuk kategori ini',
            'harga.max' => 'Harga terlalu besar. Maksimal 999.999.999.999,99',
            'satuan.*' => 'Satuan harus diisi'
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => implode(', ', $validator->errors()->all())
            ], 422);
        }

        $validated = $validator->validated();

        $updateData = [
            'uraian' => $validated['uraian'],
            'satuan' => $validated['satuan'],
            'harga' => $validated['harga'],
            'status' => $status, // Gunakan status yang sudah dikonversi
        ];

        // Hanya tambahkan updated_by jika kolomnya ada
        if (Schema::hasColumn('data', 'updated_by')) {
            $updateData['updated_by'] = auth()->check() ? auth()->user()->name : 'system';
        }

        $data->update($updateData);
        AuditLogger::log('data.updated', $data, $before, $data->toArray(), [
            'kode' => $data->kode,
            'kategori' => $data->kategori,
        ]);


        Log::info('Data updated successfully', ['id' => $id]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui',
            'data' => $data->fresh()->toArray()
        ]);

    } catch (\Exception $e) {
        Log::error('Error updating data: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'Gagal memperbarui data: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $data = Data::findOrFail($id);
            $before = $data->toArray();
            $kode = $data->kode;

            Log::info('Deleting data', ['id' => $id, 'kode' => $kode]);
            $isHO = auth()->check() && auth()->user()->isHO();

            // Draft (nonaktif) bisa langsung dihapus
            if ($data->status === false || $isHO) {
                $data->delete();
                AuditLogger::log('data.deleted', $data, $before, null, [
                    'kode' => $data->kode,
                    'kategori' => $data->kategori,
                    'deleted_by' => auth()->user()->name ?? 'HO',
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Data berhasil dihapus permanen'
                    ]);
                }

                return redirect()->route('dev.data.index')
                    ->with('success', 'Data ' . $kode . ' berhasil dihapus permanen');
            }

            if ($data->delete_status === 'pending') {
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => 'Data ini sudah menunggu approval penghapusan.'], 422)
                    : redirect()->route('dev.data.index')->with('error', 'Data ini sudah menunggu approval penghapusan.');
            }

            // Ajukan penghapusan
            $data->update([
                'delete_status' => 'pending',
                'delete_requested_by' => auth()->check() ? auth()->user()->name : 'system',
                'delete_requested_at' => now(),
                'delete_reason' => $request->input('delete_reason'),
            ]);
            AuditLogger::log('data.delete_requested', $data, $before, $data->toArray(), [
                'kode' => $data->kode,
                'kategori' => $data->kategori,
                'requested_by' => auth()->user()->name ?? 'system',
                'reason' => $request->input('delete_reason'),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'pending' => true,
                    'message' => 'Permintaan hapus dikirim dan menunggu approval HO.'
                ]);
            }

            return redirect()->route('dev.data.index')
                ->with('success', 'Permintaan hapus data ' . $kode . ' telah dikirim ke HO.');

        } catch (\Exception $e) {
            Log::error('Error deleting data: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus data: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Approve delete (HO)
     */
    public function approveDelete(Request $request, $id)
    {
        if (!auth()->check() || !auth()->user()->isHO()) {
            return back()->with('error', 'Hanya HO yang dapat menyetujui penghapusan.');
        }

        $data = Data::findOrFail($id);
        $before = $data->toArray();
        if ($data->delete_status !== 'pending') {
            return back()->with('error', 'Data tidak dalam status menunggu penghapusan.');
        }

        $data->update([
            'delete_status' => 'approved',
            'delete_reviewed_by' => auth()->user()->name ?? 'HO',
            'delete_reviewed_at' => now(),
        ]);
        AuditLogger::log('data.delete_approved', $data, $before, $data->toArray(), [
            'kode' => $data->kode,
            'kategori' => $data->kategori,
            'reviewed_by' => auth()->user()->name ?? 'HO',
        ]);


        $data->delete();

        return redirect()->route('dev.data.index')->with('success', 'Penghapusan data disetujui.');
    }

    /**
     * Reject delete (HO)
     */
    public function rejectDelete(Request $request, $id)
    {
        if (!auth()->check() || !auth()->user()->isHO()) {
            return back()->with('error', 'Hanya HO yang dapat menolak penghapusan.');
        }

        $data = Data::findOrFail($id);
        $before = $data->toArray();
        if ($data->delete_status !== 'pending') {
            return back()->with('error', 'Data tidak dalam status menunggu penghapusan.');
        }

        $data->update([
            'delete_status' => 'rejected',
            'delete_reviewed_by' => auth()->user()->name ?? 'HO',
            'delete_reviewed_at' => now(),
            'delete_review_note' => $request->input('reject_reason'),
        ]);
        AuditLogger::log('data.delete_rejected', $data, $before, $data->toArray(), [
            'kode' => $data->kode,
            'kategori' => $data->kategori,
            'reviewed_by' => auth()->user()->name ?? 'HO',
            'reason' => $request->input('reject_reason'),
        ]);


        return redirect()->route('dev.data.index')->with('success', 'Penghapusan data ditolak.');
    }

    /**
     * Bulk Actions
     */
    public function bulkAction(Request $request)
    {
        try {
            Log::info('Bulk action request', $request->all());

            $request->validate([
                'action' => 'required|in:activate,deactivate,delete,move_category,update_harga',
                'ids' => 'required|array',
                'ids.*' => 'exists:data,id'
            ]);

            $ids = $request->ids;
            $action = $request->action;
            $message = '';

            switch ($action) {
                case 'activate':
                    Data::whereIn('id', $ids)->update(['status' => true]);
                    $message = count($ids) . ' data berhasil diaktifkan';
                    break;
                    
                case 'deactivate':
                    Data::whereIn('id', $ids)->update(['status' => false]);
                    $message = count($ids) . ' data berhasil dinonaktifkan';
                    break;
                    
                case 'delete':
                    $isHO = auth()->check() && auth()->user()->isHO();
                    $pendingCount = 0;
                    $deletedCount = 0;

                    foreach ($ids as $id) {
                        $item = Data::find($id);
                        if (!$item) continue;

                        if ($item->status === false || $isHO) {
                            $item->delete();
                            $deletedCount++;
                        } else {
                            if ($item->delete_status !== 'pending') {
                                $item->update([
                                    'delete_status' => 'pending',
                                    'delete_requested_by' => auth()->check() ? auth()->user()->name : 'system',
                                    'delete_requested_at' => now(),
                                ]);
                                $pendingCount++;
                            }
                        }
                    }

                    $message = $deletedCount . ' data dihapus, ' . $pendingCount . ' menunggu approval HO';
                    break;
                    
                case 'move_category':
                    $request->validate([
                        'new_kategori' => 'required|in:MT,JS,AT,HO,SR,SB'
                    ]);
                    
                    $newKategori = $request->new_kategori;
                    $newKategoriName = Data::KATEGORI[$newKategori];
                    
                    // Update kategori untuk semua data yang dipilih
                    foreach ($ids as $id) {
                        $data = Data::find($id);
                        if ($data) {
                            $newKode = Data::generateKode($newKategori);
                            $data->update([
                                'kode_kategori' => $newKategori,
                                'kategori' => $newKategoriName,
                                'kode' => $newKode,
                            ]);
                        }
                    }
                    $message = count($ids) . ' data berhasil dipindahkan ke kategori ' . $newKategoriName;
                    break;
                    
                case 'update_harga':
                    $request->validate([
                        'new_harga' => 'required|numeric|min:0|max:999999999999.99'
                    ]);
                    
                    $newHarga = $request->new_harga;
                    Data::whereIn('id', $ids)->update(['harga' => $newHarga]);
                    $message = count($ids) . ' data berhasil diupdate harganya menjadi Rp ' . number_format($newHarga, 0, ',', '.');
                    break;
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Aksi tidak valid'
                    ], 400);
            }

            Log::info('Bulk action completed', ['action' => $action, 'count' => count($ids)]);

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Error in bulkAction: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan aksi massal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk Update for selected data rows (compat with legacy master-data UI)
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:data,id',
                'field' => 'required|string|in:unit,price,is_active,category,satuan,harga,status,kode_kategori',
                'value' => 'required'
            ]);

            $ids = $request->input('ids', []);
            $field = $request->input('field');
            $value = $request->input('value');

            $updateData = [];

            // Normalize legacy field names to current data table columns
            switch ($field) {
                case 'unit':
                case 'satuan':
                    $updateData['satuan'] = (string) $value;
                    break;

                case 'price':
                case 'harga':
                    $cleanPrice = is_string($value) ? preg_replace('/[^\d.]/', '', $value) : $value;
                    $updateData['harga'] = $cleanPrice === '' ? 0 : (float) $cleanPrice;
                    break;

                case 'is_active':
                case 'status':
                    $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($boolValue === null) {
                        $boolValue = in_array((string) $value, ['1', 'true', 'yes'], true);
                    }
                    $updateData['status'] = $boolValue;
                    break;

                case 'category':
                case 'kode_kategori':
                    $categoryCode = strtoupper(trim((string) $value));
                    if (!array_key_exists($categoryCode, Data::KATEGORI)) {
                        // Support legacy input that sends category name instead of code
                        $reverseMap = array_change_key_case(array_flip(Data::KATEGORI), CASE_UPPER);
                        $categoryCode = $reverseMap[strtoupper($categoryCode)] ?? null;
                    }

                    if (!$categoryCode || !array_key_exists($categoryCode, Data::KATEGORI)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Kategori tidak valid'
                        ], 422);
                    }

                    $updateData['kode_kategori'] = $categoryCode;
                    $updateData['kategori'] = Data::KATEGORI[$categoryCode];
                    break;
            }

            $updateData['updated_by'] = auth()->check() ? auth()->user()->name : 'system';

            $updated = Data::whereIn('id', $ids)->update($updateData);

            return response()->json([
                'success' => true,
                'message' => $updated . ' data berhasil diperbarui'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in bulkUpdate: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan update massal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick Store - AJAX
     */
    public function quickStore(Request $request)
    {
        try {
            Log::info('Quick store request', $request->all());

            $validator = Validator::make($request->all(), [
                'kode_kategori' => 'required|in:MT,JS,AT,HO,SR,SB',
                'uraian' => [
                    'required',
                    'string',
                    'max:1000',
                    Rule::unique('data')->where(function ($query) use ($request) {
                        return $query->where('uraian', $request->uraian)
                                     ->where('kode_kategori', $request->kode_kategori);
                    })
                ],
                'satuan' => 'required|string|max:50',
                'harga' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:999999999999.99',
                    function ($attribute, $value, $fail) {
                        if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
                            $fail('Format harga tidak valid. Gunakan format: 100000 atau 100000.00');
                        }
                    }
                ],
                'status' => 'boolean'
            ], [
                'uraian.unique' => 'Uraian sudah ada untuk kategori ini',
                'harga.max' => 'Harga terlalu besar. Maksimal 999.999.999.999,99',
                'satuan.*' => 'Satuan harus diisi'
            ]);

            if ($validator->fails()) {
                Log::warning('Quick store validation failed', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => implode(', ', $validator->errors()->all())
                ], 422);
            }

            $validated = $validator->validated();

            // Generate kode otomatis
            $kode = Data::generateKode($validated['kode_kategori']);
            Log::info('Generated kode for quick store: ' . $kode);

            // Build data array hanya dengan kolom yang ada
            $dataArray = [
                'kode' => $kode,
                'kode_kategori' => $validated['kode_kategori'],
                'kategori' => Data::KATEGORI[$validated['kode_kategori']],
                'uraian' => $validated['uraian'],
                'satuan' => $validated['satuan'],
                'harga' => $validated['harga'],
                'status' => $validated['status'] ?? true,
            ];

            // Hanya tambahkan created_by dan updated_by jika kolomnya ada
            if (Schema::hasColumn('data', 'created_by')) {
                $dataArray['created_by'] = auth()->check() ? auth()->user()->name : 'system';
            }

            if (Schema::hasColumn('data', 'updated_by')) {
                $dataArray['updated_by'] = auth()->check() ? auth()->user()->name : 'system';
            }

            $data = Data::create($dataArray);

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil ditambahkan dengan kode: ' . $kode,
                'data' => $data->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Error in quickStore: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data: ' . $e->getMessage()
            ], 500);
        }
    }

/**
 * Quick Update - AJAX - FIXED VERSION dengan perbaikan status
 */
public function quickUpdate(Request $request, $id)
{
    try {
        $data = Data::findOrFail($id);
        
        Log::info('Quick update request', [
            'id' => $id, 
            'request_data' => $request->all(),
            'current_data' => [
                'uraian' => $data->uraian,
                'satuan' => $data->satuan,
                'harga' => $data->harga,
                'status' => $data->status
            ]
        ]);

        // PERBAIKAN: Konversi status ke boolean
        $status = $request->status === '1' || $request->status === 'true' || $request->status === true || $request->status === 1;

        $validator = Validator::make($request->all(), [
            'uraian' => [
                'required',
                'string',
                'max:1000',
                Rule::unique('data')->where(function ($query) use ($id, $data, $request) {
                    return $query->where('uraian', $request->uraian)
                                 ->where('kode_kategori', $data->kode_kategori)
                                 ->where('id', '!=', $id);
                })
            ],
            'satuan' => 'required|string|max:50',
            'harga' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99'
            ]
        ], [
            'uraian.unique' => 'Uraian sudah ada untuk kategori ini',
            'harga.max' => 'Harga terlalu besar. Maksimal 999.999.999.999,99',
            'satuan.required' => 'Satuan harus diisi'
        ]);

        if ($validator->fails()) {
            Log::warning('Quick update validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => implode(', ', $validator->errors()->all())
            ], 422);
        }

        $validated = $validator->validated();

        $updateData = [
            'uraian' => $validated['uraian'],
            'satuan' => $validated['satuan'],
            'harga' => $validated['harga'],
            'status' => $status, // Gunakan status yang sudah dikonversi
        ];

        // Hanya tambahkan updated_by jika kolomnya ada
        if (Schema::hasColumn('data', 'updated_by')) {
            $updateData['updated_by'] = auth()->check() ? auth()->user()->name : 'quick-edit';
        }

        $data->update($updateData);

        Log::info('Quick update data updated successfully', [
            'id' => $id,
            'updated_data' => $updateData
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui',
            'data' => $data->fresh()->toArray()
        ]);

    } catch (\Exception $e) {
        Log::error('Error in quickUpdate: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        Log::error('Request data: ' . json_encode($request->all()));
        
        return response()->json([
            'success' => false,
            'message' => 'Gagal memperbarui data: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Show data for AJAX request
     */
    public function showAjax($id)
    {
        try {
            $item = Data::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $item
            ]);
        } catch (\Exception $e) {
            Log::error('Error in showAjax: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Generate Kode - AJAX
     */
    public function generateKode(Request $request, $kategori = null)
    {
        try {
            // Backward compatibility: some legacy views still send `category`
            $kategori = $kategori ?: $request->get('kode_kategori') ?: $request->get('category');
            
            if (!$kategori || !array_key_exists($kategori, Data::KATEGORI)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode kategori tidak valid'
                ], 400);
            }

            $kode = Data::generateKode($kategori);

            return response()->json([
                'success' => true,
                'kode' => $kode,
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating kode: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate kode: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fix Gaps in Kode numbering
     */
    public function fixGaps(Request $request)
    {
        try {
            Log::info('Fixing gaps in kode numbering');
            
            $result = Data::fixGaps();
            
            Log::info('Gaps fixed successfully');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Penomoran kode berhasil diperbaiki'
                ]);
            }

            return redirect()->route('dev.data.index')
                ->with('success', 'Penomoran kode berhasil diperbaiki');

        } catch (\Exception $e) {
            Log::error('Error fixing gaps: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbaiki penomoran: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal memperbaiki penomoran: ' . $e->getMessage());
        }
    }

    /**
     * Reset counters to zero
     */
    public function resetCounters(Request $request)
    {
        try {
            Log::info('Resetting counters to zero');
            
            Data::resetCounters();
            
            Log::info('Counters reset successfully');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Counter berhasil direset ke 0'
                ]);
            }

            return redirect()->route('dev.data.index')
                ->with('success', 'Counter berhasil direset ke 0');

        } catch (\Exception $e) {
            Log::error('Error resetting counters: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal reset counter: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal reset counter: ' . $e->getMessage());
        }
    }

    /**
     * Get available numbers for kategori
     */
    public function getAvailableNumbers(Request $request)
    {
        try {
            $kodeKategori = $request->get('kode_kategori');
            
            if (!$kodeKategori || !array_key_exists($kodeKategori, Data::KATEGORI)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode kategori tidak valid'
                ], 400);
            }

            $availableNumbers = Data::getAvailableNumbers($kodeKategori);

            return response()->json([
                'success' => true,
                'available_numbers' => $availableNumbers
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting available numbers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan nomor yang tersedia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import from Excel - FIXED VERSION
     */
    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
            ]);

            $import = new DataImport();
            Excel::import($import, $request->file('file'));

            $importedCount = $import->getImportedCount();
            $skippedCount = $import->getSkippedCount();
            $errors = $import->getErrors();

            $message = "Import berhasil: {$importedCount} data ditambahkan";
            
            if (!empty($errors)) {
                $message .= ". Terdapat " . count($errors) . " error: " . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " dan " . (count($errors) - 5) . " error lainnya";
                }
            }

            if ($skippedCount > 0) {
                $message .= ". {$skippedCount} data dilewati";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'stats' => [
                    'imported' => $importedCount,
                    'skipped' => $skippedCount,
                    'errors' => count($errors),
                    'error_details' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in import: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download Template Import yang Sederhana
     */
    public function downloadTemplate()
    {
        try {
            $templateData = [
                // Header - hanya kolom yang diperlukan
                ['KATEGORI', 'URAIAN_ITEM', 'SATUAN', 'HARGA_SATUAN', 'STATUS'],
                // Contoh data
                ['MT', 'Pasir Halus Bangunan', 'kg', 50000, 'AKTIF'],
                ['JS', 'Jasa Tukang Bangunan', 'jam', 75000, 'AKTIF'],
                ['AT', 'Mesin Bor DeWalt', 'unit', 250000, 'AKTIF'],
            ];

            // Catatan penting untuk user
            $notes = [
                ['CATATAN PENTING:'],
                ['1. KATEGORI: Gunakan kode kategori (MT, JS, AT, HO, SR, SB)'],
                ['2. URAIAN_ITEM: Deskripsi item (wajib unik per kategori)'],
                ['3. SATUAN: Isi satuan sesuai kebutuhan (bebas)'],
                ['4. HARGA_SATUAN: Angka tanpa format (contoh: 50000)'],
                ['5. STATUS: AKTIF atau NONAKTIF (opsional, default: AKTIF)'],
                ['6. KODE akan digenerate OTOMATIS oleh sistem'],
            ];

            $exportData = array_merge($templateData, [['']], $notes);

            return Excel::download(new class($exportData) implements FromCollection {
                protected $data;
                
                public function __construct($data) {
                    $this->data = $data;
                }
                
                public function collection() {
                    return collect($this->data);
                }
            }, 'template-import-data-sederhana.xlsx');

        } catch (\Exception $e) {
            Log::error('Error downloading template: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal download template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics
     */
    public function getStatistics()
    {
        try {
            $stats = [
                'total_items' => Data::count(),
                'total_kategori' => Data::distinct('kode_kategori')->count('kode_kategori'),
                'updated_today' => Data::whereDate('updated_at', today())->count(),
                'total_value' => 'Rp ' . number_format(Data::sum('harga'), 2, ',', '.'),
                'active_items' => Data::where('status', true)->count()
            ];

            Log::info('Statistics retrieved', $stats);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getStatistics: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Global search
     */
    public function globalSearch(Request $request)
    {
        try {
            $search = $request->get('q', '');
            $results = [];

            if (!empty($search)) {
                foreach (Data::KATEGORI as $kode => $nama) {
                    $query = Data::where('kode_kategori', $kode)
                        ->where(function($q) use ($search) {
                            $q->where('kode', 'like', "%{$search}%")
                              ->orWhere('uraian', 'like', "%{$search}%");
                        })
                        ->limit(10)
                        ->get();

                    if ($query->count() > 0) {
                        $results[$kode] = [
                            'nama' => $nama,
                            'data' => $query
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Error in globalSearch: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pencarian: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search by kategori
     */
    public function searchByKategori(Request $request, $kategori)
    {
        try {
            if (!array_key_exists($kategori, Data::KATEGORI)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak valid'
                ], 400);
            }

            $search = $request->get('q', '');
            $results = [];

            if (!empty($search)) {
                $results = Data::where('kode_kategori', $kategori)
                    ->where(function($q) use ($search) {
                        $q->where('kode', 'like', "%{$search}%")
                          ->orWhere('uraian', 'like', "%{$search}%");
                    })
                    ->limit(20)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'results' => $results,
                'kategori' => Data::KATEGORI[$kategori],
            ]);

        } catch (\Exception $e) {
            Log::error('Error in searchByKategori: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pencarian: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filter data
     */
    public function filterData(Request $request)
    {
        try {
            $filters = $request->all();
            $query = Data::query();

            // Apply filters
            if (isset($filters['status']) && $filters['status'] !== '') {
                $statusValue = $filters['status'] == '1' ? true : false;
                $query->where('status', $statusValue);
            }

            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('kode', 'like', "%{$search}%")
                      ->orWhere('uraian', 'like', "%{$search}%");
                });
            }

            if (isset($filters['kategori']) && !empty($filters['kategori'])) {
                $query->where('kode_kategori', $filters['kategori']);
            }

            // Apply sorting
            $sort = $request->get('sort', 'kode_asc');
            $query = $this->applySorting($query, $sort);

            // TANPA PAGINATION - get all data
            $data = $query->get();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error in filterData: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memfilter data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Public categories
     */
    public function publicCategories()
    {
        try {
            $categories = [];
            foreach (Data::KATEGORI as $kode => $nama) {
                $categories[] = [
            'kode' => $data->kode,
                    'nama' => $nama,
                    'count' => Data::where('kode_kategori', $kode)->where('status', true)->count()
                ];
            }

            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);

        } catch (\Exception $e) {
            Log::error('Error in publicCategories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil kategori: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Public items
     */
    public function publicItems($kategori = null)
    {
        try {
            $query = Data::where('status', true);

            if ($kategori && array_key_exists($kategori, Data::KATEGORI)) {
                $query->where('kode_kategori', $kategori);
            }

            $items = $query->orderBy('kode')->get();

            return response()->json([
                'success' => true,
                'items' => $items
            ]);

        } catch (\Exception $e) {
            Log::error('Error in publicItems: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Public search
     */
    public function publicSearch(Request $request)
    {
        try {
            $search = $request->get('q', '');
            $results = [];

            if (!empty($search)) {
                $results = Data::where('status', true)
                    ->where(function($q) use ($search) {
                        $q->where('kode', 'like', "%{$search}%")
                          ->orWhere('uraian', 'like', "%{$search}%");
                    })
                    ->limit(20)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Error in publicSearch: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pencarian: ' . $e->getMessage()
            ], 500);
        }
    }
}
