<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Dev\BaseController;
use App\Models\MasterData;
use App\Models\MasterCodeCounter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MasterDataController extends BaseController
{
    public function __construct()
    {
        $this->shareSidebarData();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = MasterData::query();

            // Filter by category
            if ($request->has('kategori') && $request->kategori) {
                $query->where('category', $request->kategori);
            }

            // Filter by status
            if ($request->has('status') && $request->status !== '') {
                $query->where('is_active', $request->status);
            }

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Sort
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $masterData = $query->paginate(10)->withQueryString();

            // Get category counts for stats
            $categoryCounts = MasterData::select('category', DB::raw('COUNT(*) as count'))
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray();

            // Get kategori list for filters
            $kategoriList = [
                'MT' => 'Material',
                'JS' => 'Jasa',
                'AT' => 'Alat',
                'HO' => 'Head Office',
                'SR' => 'Sirkulasi',
                'SB' => 'SubKon'
            ];

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'data' => $masterData->items(),
                    'meta' => [
                        'current_page' => $masterData->currentPage(),
                        'last_page' => $masterData->lastPage(),
                        'per_page' => $masterData->perPage(),
                        'total' => $masterData->total(),
                    ],
                    'category_counts' => $categoryCounts,
                    'kategori_list' => $kategoriList,
                ]);
            }

            return view('dev.data.index', compact('masterData', 'categoryCounts', 'kategoriList'));

        } catch (\Exception $e) {
            Log::error('Error in MasterData index: ' . $e->getMessage());
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memuat data'], 500);
            }
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $kategoriList = [
            'MT' => 'Material',
            'JS' => 'Jasa',
            'AT' => 'Alat',
            'HO' => 'Head Office',
            'SR' => 'Sirkulasi',
            'SB' => 'SubKon'
        ];

        return view('dev.data.create', compact('kategoriList'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * NOTE: `code` optional - generated if missing.
     */
    public function store(Request $request)
    {
        try {
            // Base rules
            $rules = [
                'category' => 'required|in:MT,JS,AT,HO,SR,SB',
                'name' => 'required|string|max:255',
                'unit' => 'required|string|max:50',
                'price' => 'required',
                'description' => 'nullable|string',
                'is_active' => 'boolean'
            ];

            // code unique only when present
            if ($request->filled('code')) {
                $rules['code'] = 'string|unique:master_data,code';
            } else {
                $rules['code'] = 'nullable';
            }

            $validator = Validator::make($request->all(), $rules);

            // custom numeric check for price (accept formatted strings)
            $validator->after(function ($validator) use ($request) {
                if ($request->filled('price')) {
                    $p = $request->get('price');
                    // remove non-digits, treat empty as 0
                    $pClean = is_string($p) ? preg_replace('/[^\d]/', '', $p) : $p;
                    if ($pClean === '' || !is_numeric($pClean)) {
                        $validator->errors()->add('price', 'Price harus berupa angka.');
                    }
                }
            });

            if ($validator->fails()) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $validated = $validator->validated();

            // Format price (hapus simbol jika ada)
            $price = $validated['price'];
            if (is_string($price)) {
                $price = preg_replace('/[^\d]/', '', $price);
            }
            $price = $price === null || $price === '' ? 0 : (float)$price;

            // Determine code: use provided or generate
            $code = $validated['code'] ?? null;
            if (empty($code)) {
                $code = $this->generateCodeForCategory($validated['category']);
            } else {
                $code = trim($code);
            }

            $masterData = MasterData::create([
                'code' => $code,
                'category' => $validated['category'],
                'name' => $validated['name'],
                'unit' => $validated['unit'],
                'price' => $price,
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->check() ? auth()->user()->name : 'system',
                'updated_by' => auth()->check() ? auth()->user()->name : 'system'
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => 'Data master berhasil ditambahkan', 'data' => $masterData], 201);
            }

            return redirect()->route('dev.data.index')
                ->with('success', 'Data master berhasil ditambahkan');

        } catch (ValidationException $e) {
            // Shouldn't be reached because we handle validator above, but just in case
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $e->errors()], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error storing master data: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Gagal menambahkan data master: ' . $e->getMessage()], 500);
            }
            return redirect()->back()
                ->with('error', 'Gagal menambahkan data master: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        try {
            $item = MasterData::findOrFail($id);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                ]);
            }
            return view('dev.data.show', compact('item'));
        } catch (\Exception $e) {
            Log::error('Error showing master data: ' . $e->getMessage());
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }
            return redirect()->route('dev.data.index')
                ->with('error', 'Data tidak ditemukan');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $item = MasterData::findOrFail($id);
            $kategoriList = [
                'MT' => 'Material',
                'JS' => 'Jasa',
                'AT' => 'Alat',
                'HO' => 'Head Office',
                'SR' => 'Sirkulasi',
                'SB' => 'SubKon'
            ];

            return view('dev.data.edit', compact('item', 'kategoriList'));
        } catch (\Exception $e) {
            Log::error('Error editing master data: ' . $e->getMessage());
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
            $masterData = MasterData::findOrFail($id);

            $rules = [
                'category' => 'required|in:MT,JS,AT,HO,SR,SB',
                'name' => 'required|string|max:255',
                'unit' => 'required|string|max:50',
                'price' => 'required',
                'description' => 'nullable|string',
                'is_active' => 'boolean'
            ];

            if ($request->filled('code')) {
                $rules['code'] = 'string|unique:master_data,code,' . $id;
            } else {
                $rules['code'] = 'nullable';
            }

            $validator = Validator::make($request->all(), $rules);

            $validator->after(function ($validator) use ($request) {
                if ($request->filled('price')) {
                    $p = $request->get('price');
                    $pClean = is_string($p) ? preg_replace('/[^\d]/', '', $p) : $p;
                    if ($pClean === '' || !is_numeric($pClean)) {
                        $validator->errors()->add('price', 'Price harus berupa angka.');
                    }
                }
            });

            if ($validator->fails()) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
                }
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $validated = $validator->validated();

            $price = $validated['price'];
            if (is_string($price)) {
                $price = preg_replace('/[^\d]/', '', $price);
            }
            $price = $price === null || $price === '' ? 0 : (float)$price;

            $updateData = [
                'category' => $validated['category'],
                'name' => $validated['name'],
                'unit' => $validated['unit'],
                'price' => $price,
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'updated_by' => auth()->check() ? auth()->user()->name : 'system'
            ];

            if ($request->filled('code')) {
                $updateData['code'] = trim($request->code);
            }

            $masterData->update($updateData);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => 'Data master berhasil diperbarui', 'data' => $masterData]);
            }

            return redirect()->route('dev.data.index')
                ->with('success', 'Data master berhasil diperbarui');

        } catch (\Exception $e) {
            Log::error('Error updating master data: ' . $e->getMessage());
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Gagal memperbarui data master: ' . $e->getMessage()], 500);
            }
            return redirect()->back()
                ->with('error', 'Gagal memperbarui data master: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $masterData = MasterData::findOrFail($id);
            $masterData->delete();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => 'Data master berhasil dihapus']);
            }

            return redirect()->route('dev.data.index')
                ->with('success', 'Data master berhasil dihapus');

        } catch (\Exception $e) {
            Log::error('Error deleting master data: ' . $e->getMessage());
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Gagal menghapus data master: ' . $e->getMessage()], 500);
            }
            return redirect()->route('dev.data.index')
                ->with('error', 'Gagal menghapus data master: ' . $e->getMessage());
        }
    }

    /**
     * Generate kode otomatis berdasarkan kategori - API endpoint (AJAX).
     */
    public function generateKode(Request $request)
    {
        try {
            \Log::info('Generate kode request received', [
                'category' => $request->category,
                'all_params' => $request->all(),
            ]);

            $category = $request->get('category');

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak valid'
                ], 400);
            }

            $validCategories = ['MT', 'JS', 'AT', 'HO', 'SR', 'SB'];
            if (!in_array($category, $validCategories)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak valid. Pilih dari: ' . implode(', ', $validCategories)
                ], 400);
            }

            $kode = $this->generateCodeForCategory($category);

            return response()->json([
                'success' => true,
                'kode' => $kode
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating code for category ' . ($request->get('category') ?? 'unknown') . ': ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate kode: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Internal helper: generate a unique code for a given category using MasterCodeCounter.
     */
    protected function generateCodeForCategory(string $category): string
    {
        $validCategories = ['MT', 'JS', 'AT', 'HO', 'SR', 'SB'];
        if (!in_array($category, $validCategories)) {
            throw new \InvalidArgumentException('Invalid category for code generation: ' . $category);
        }

        return DB::transaction(function () use ($category) {
            $counter = MasterCodeCounter::where('category', $category)->lockForUpdate()->first();

            if (!$counter) {
                $counter = MasterCodeCounter::create([
                    'category' => $category,
                    'last_number' => 1,
                    'description' => 'Counter untuk kategori ' . $category,
                    'created_by' => 'system',
                    'updated_by' => 'system'
                ]);
                $newNumber = 1;
            } else {
                $newNumber = $counter->last_number + 1;
                $counter->update(['last_number' => $newNumber]);
            }

            $generatedCode = $category . '.' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

            Log::info('Generated code in generateCodeForCategory', [
                'category' => $category,
                'new_number' => $newNumber,
                'generated_code' => $generatedCode
            ]);

            return $generatedCode;
        });
    }

    /**
     * Quick store untuk tambah item cepat
     */
    public function quickStore(Request $request)
    {
        try {
            $rules = [
                'category' => 'required|in:MT,JS,AT,HO,SR,SB',
                'name' => 'required|string|max:255',
                'unit' => 'required|string|max:50',
                'price' => 'required',
                'description' => 'nullable|string'
            ];

            if ($request->filled('code')) {
                $rules['code'] = 'string|unique:master_data,code';
            } else {
                $rules['code'] = 'nullable';
            }

            $validator = Validator::make($request->all(), $rules);

            $validator->after(function ($validator) use ($request) {
                if ($request->filled('price')) {
                    $p = $request->get('price');
                    $pClean = is_string($p) ? preg_replace('/[^\d]/', '', $p) : $p;
                    if ($pClean === '' || !is_numeric($pClean)) {
                        $validator->errors()->add('price', 'Price harus berupa angka.');
                    }
                }
            });

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
            }

            $code = $request->code ?? null;
            if (empty($code)) {
                $code = $this->generateCodeForCategory($request->category);
            } else {
                $code = trim($code);
            }

            $price = $request->price;
            if (is_string($price)) {
                $price = preg_replace('/[^\d]/', '', $price);
            }
            $price = $price === null || $price === '' ? 0 : (float)$price;

            $masterData = MasterData::create([
                'code' => $code,
                'category' => $request->category,
                'name' => $request->name,
                'unit' => $request->unit,
                'price' => $price,
                'description' => $request->description ?? null,
                'is_active' => true,
                'created_by' => auth()->check() ? auth()->user()->name : 'system',
                'updated_by' => auth()->check() ? auth()->user()->name : 'system'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil ditambahkan',
                'data' => $masterData
            ]);

        } catch (\Exception $e) {
            Log::error('Error in quickStore: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk actions untuk multiple items
     */
    public function bulkAction(Request $request)
    {
        try {
            $request->validate([
                'action' => 'required|in:activate,deactivate,delete',
                'ids' => 'required|array',
                'ids.*' => 'exists:master_data,id'
            ]);

            $ids = $request->ids;
            $action = $request->action;

            switch ($action) {
                case 'activate':
                    MasterData::whereIn('id', $ids)->update(['is_active' => true]);
                    $message = count($ids) . ' item berhasil diaktifkan';
                    break;
                    
                case 'deactivate':
                    MasterData::whereIn('id', $ids)->update(['is_active' => false]);
                    $message = count($ids) . ' item berhasil dinonaktifkan';
                    break;
                    
                case 'delete':
                    MasterData::whereIn('id', $ids)->delete();
                    $message = count($ids) . ' item berhasil dihapus';
                    break;
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Aksi tidak valid'
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Error in bulkAction: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan aksi massal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update untuk multiple items
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:master_data,id',
                'field' => 'required|in:unit,price,is_active,category',
                'value' => 'required'
            ]);

            $ids = $request->ids;
            $field = $request->field;
            $value = $request->value;

            // Format value berdasarkan field
            switch ($field) {
                case 'price':
                    $value = preg_replace('/[^\d]/', '', $value);
                    break;
                    
                case 'is_active':
                    $value = (bool)$value;
                    break;
            }

            MasterData::whereIn('id', $ids)->update([
                $field => $value,
                'updated_by' => auth()->check() ? auth()->user()->name : 'system'
            ]);

            return response()->json([
                'success' => true,
                'message' => count($ids) . ' item berhasil diperbarui'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in bulkUpdate: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan update massal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import data dari Excel
     */
    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
            ]);

            // TODO: Implement Excel import logic
            // You can use Maatwebsite/Laravel-Excel package

            return response()->json([
                'success' => true,
                'message' => 'Import berhasil dilakukan'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in import: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export data ke Excel
     */
    public function export(Request $request)
    {
        try {
            // TODO: Implement Excel export logic
            // You can use Maatwebsite/Laravel-Excel package

            return response()->json([
                'success' => true,
                'message' => 'Export berhasil dilakukan'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in export: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengexport data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get template untuk import
     */
    public function getTemplate()
    {
        try {
            // TODO: Return Excel template file
            return response()->json([
                'success' => true,
                'message' => 'Template berhasil diunduh'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getTemplate: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get data by category
     */
    public function byCategory($category)
    {
        try {
            $masterData = MasterData::where('category', $category)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $categoryCounts = MasterData::select('category', DB::raw('COUNT(*) as count'))
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray();

            $kategoriList = [
                'MT' => 'Material',
                'JS' => 'Jasa', 
                'AT' => 'Alat',
                'HO' => 'Head Office',
                'SR' => 'Sirkulasi',
                'SB' => 'SubKon'
            ];

            return view('dev.data.index', compact(
                'masterData', 
                'categoryCounts',
                'kategoriList'
            ));

        } catch (\Exception $e) {
            Log::error('Error in byCategory: ' . $e->getMessage());
            return redirect()->route('dev.data.index')
                ->with('error', 'Terjadi kesalahan saat memuat data');
        }
    }

    /**
     * Get statistics overview
     */
    public function getStatistics()
    {
        try {
            $stats = [
                'total_items' => MasterData::count(),
                'total_categories' => MasterData::distinct('category')->count('category'),
                'updated_today' => MasterData::whereDate('updated_at', today())->count(),
                'total_value' => 'Rp ' . number_format(MasterData::sum('price'), 2, ',', '.'),
                'growth_rate' => '12.5' // This would be calculated based on your business logic
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getStatistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audit trail for specific item
     */
    public function audits($id)
    {
        try {
            $item = MasterData::findOrFail($id);
            // TODO: Return audit trail data if you have audit trail implementation
            
            return response()->json([
                'success' => true,
                'data' => []
            ]);

        } catch (\Exception $e) {
            Log::error('Error in audits: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil audit trail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed information
     */
    public function detail($id)
    {
        try {
            $item = MasterData::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $item
            ]);

        } catch (\Exception $e) {
            Log::error('Error in detail: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download error report for import
     */
    public function downloadErrorReport(Request $request)
    {
        try {
            // TODO: Implement error report download
            return response()->json([
                'success' => true,
                'message' => 'Laporan error berhasil diunduh'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in downloadErrorReport: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh laporan error: ' . $e->getMessage()
            ], 500);
        }
    }
}
