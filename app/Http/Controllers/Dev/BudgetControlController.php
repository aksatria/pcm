<?php
// app/Http\Controllers\Dev\BudgetControlController.php

namespace App\Http\Controllers\Dev;

use App\Models\Project;
use App\Models\BudgetControl;
use App\Models\Rab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BudgetControlController extends BaseController
{
    /**
     * Display Control Budget for a project
     */
    public function index($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            // Predefined categories from Excel - TAMBAHKAN INI
            $categories = ['MT', 'JS', 'SB', 'AT', 'SR', 'HO', 'RN', 'OTHER'];
            
            // Get budget controls with pagination
            $budgetControls = BudgetControl::where('project_id', $projectId)
                ->when(request('search'), function($query, $search) {
                    return $query->where(function($q) use ($search) {
                        $q->where('kode', 'like', "%{$search}%")
                          ->orWhere('uraian', 'like', "%{$search}%");
                    });
                })
                ->when(request('kategori'), function($query, $kategori) {
                    return $query->where('kategori', $kategori);
                })
                ->when(request('status'), function($query, $status) {
                    if ($status === 'over') {
                        return $query->where('is_over_budget', true);
                    } elseif ($status === 'completed') {
                        return $query->where('percentage', '>=', 100);
                    } elseif ($status === 'warning') {
                        return $query->where('percentage', '>=', 80)->where('percentage', '<', 100);
                    } elseif ($status === 'active') {
                        return $query->where('percentage', '>', 0)->where('percentage', '<', 80);
                    } elseif ($status === 'pending') {
                        return $query->where('percentage', '=', 0);
                    }
                    return $query;
                })
                ->orderBy('kategori')
                ->orderBy('kode')
                ->paginate(50);

            // Get summary by category
            $categorySummary = BudgetControl::where('project_id', $projectId)
                ->select('kategori', 
                    DB::raw('SUM(jumlah_plan) as total_plan'),
                    DB::raw('SUM(jumlah_real) as total_real'),
                    DB::raw('SUM(jumlah_sisa) as total_sisa')
                )
                ->groupBy('kategori')
                ->get();

            // Get overall totals
            $totals = [
                'plan' => $categorySummary->sum('total_plan'),
                'real' => $categorySummary->sum('total_real'),
                'sisa' => $categorySummary->sum('total_sisa')
            ];

            return view('dev.budget-controls.index', compact(
                'project',
                'budgetControls',
                'categorySummary',
                'totals',
                'categories' // TAMBAHKAN INI
            ));

        } catch (\Exception $e) {
            Log::error('Budget Control Index Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat Control Budget: ' . $e->getMessage());
        }
    }

    /**
     * Show form to create new budget control item
     */
    public function create($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $rabs = $project->rabs()->active()->get();
            
            // Predefined categories from Excel
            $categories = ['MT', 'JS', 'SB', 'AT', 'SR', 'HO', 'RN', 'OTHER'];
            
            return view('dev.budget-controls.create', compact(
                'project',
                'rabs',
                'categories'
            ));

        } catch (\Exception $e) {
            Log::error('Budget Control Create Form Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat form: ' . $e->getMessage());
        }
    }

    /**
     * Store new budget control item
     */
    public function store(Request $request, $projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            $validator = Validator::make($request->all(), [
                'kode' => 'required|string|max:20',
                'uraian' => 'required|string|max:1000',
                'satuan' => 'required|string|max:20',
                'volume_plan' => 'required|numeric|min:0',
                'harga_satuan_plan' => 'required|numeric|min:0',
                'kategori' => 'required|in:MT,JS,SB,AT,SR,HO,RN,OTHER'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            DB::beginTransaction();

            $budgetControl = BudgetControl::create([
                'project_id' => $project->id,
                'rab_id' => $request->rab_id,
                'kode' => $request->kode,
                'uraian' => $request->uraian,
                'satuan' => $request->satuan,
                'volume_plan' => $request->volume_plan,
                'harga_satuan_plan' => $request->harga_satuan_plan,
                'jumlah_plan' => $request->volume_plan * $request->harga_satuan_plan,
                'kategori' => $request->kategori,
                'keterangan' => $request->keterangan,
                'status' => 'active'
            ]);

            DB::commit();

            Log::info('Budget Control Item Created:', [
                'id' => $budgetControl->id,
                'kode' => $budgetControl->kode,
                'project_id' => $project->id
            ]);

            return redirect()->route('dev.budget-controls.index', $project->id)
                ->with('success', 'Item Control Budget berhasil ditambahkan!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Budget Control Store Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal menambahkan item: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show edit form
     */
    public function edit($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $budgetControl = BudgetControl::findOrFail($id);
            $rabs = $project->rabs()->active()->get();
            $categories = ['MT', 'JS', 'SB', 'AT', 'SR', 'HO', 'RN', 'OTHER'];

            return view('dev.budget-controls.edit', compact(
                'project',
                'budgetControl',
                'rabs',
                'categories'
            ));

        } catch (\Exception $e) {
            Log::error('Budget Control Edit Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat form edit: ' . $e->getMessage());
        }
    }

    /**
     * Update budget control item
     */
    public function update(Request $request, $projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $budgetControl = BudgetControl::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'kode' => 'required|string|max:20',
                'uraian' => 'required|string|max:1000',
                'satuan' => 'required|string|max:20',
                'volume_plan' => 'required|numeric|min:0',
                'harga_satuan_plan' => 'required|numeric|min:0',
                'kategori' => 'required|in:MT,JS,SB,AT,SR,HO,RN,OTHER'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            DB::beginTransaction();

            $budgetControl->update([
                'rab_id' => $request->rab_id,
                'kode' => $request->kode,
                'uraian' => $request->uraian,
                'satuan' => $request->satuan,
                'volume_plan' => $request->volume_plan,
                'harga_satuan_plan' => $request->harga_satuan_plan,
                'jumlah_plan' => $request->volume_plan * $request->harga_satuan_plan,
                'kategori' => $request->kategori,
                'keterangan' => $request->keterangan,
                'status' => $request->status ?? 'active'
            ]);

            DB::commit();

            Log::info('Budget Control Item Updated:', ['id' => $budgetControl->id]);

            return redirect()->route('dev.budget-controls.index', $project->id)
                ->with('success', 'Item Control Budget berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Budget Control Update Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memperbarui item: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Delete budget control item
     */
    public function destroy($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $budgetControl = BudgetControl::findOrFail($id);

            // Check if item has voucher items
            if ($budgetControl->voucherItems()->count() > 0) {
                return back()->with('error', 'Tidak dapat menghapus item yang sudah memiliki transaksi voucher!');
            }

            $budgetControl->delete();

            Log::info('Budget Control Item Deleted:', ['id' => $id]);

            return redirect()->route('dev.budget-controls.index', $project->id)
                ->with('success', 'Item Control Budget berhasil dihapus!');

        } catch (\Exception $e) {
            Log::error('Budget Control Delete Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    /**
     * Toggle selection status for voucher creation
     */
    public function toggleSelection(Request $request, $projectId, $id)
    {
        try {
            $budgetControl = BudgetControl::where('project_id', $projectId)
                ->findOrFail($id);

            $budgetControl->update([
                'is_selected' => !$budgetControl->is_selected
            ]);

            return response()->json([
                'success' => true,
                'is_selected' => $budgetControl->is_selected
            ]);

        } catch (\Exception $e) {
            Log::error('Toggle Selection Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status selection'
            ], 500);
        }
    }

    /**
     * Bulk selection for voucher creation
     */
    public function bulkSelection(Request $request, $projectId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'item_ids' => 'required|array',
                'item_ids.*' => 'exists:budget_controls,id',
                'action' => 'required|in:select,deselect'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal'
                ], 422);
            }

            $isSelected = $request->action === 'select';
            
            BudgetControl::where('project_id', $projectId)
                ->whereIn('id', $request->item_ids)
                ->update(['is_selected' => $isSelected]);

            return response()->json([
                'success' => true,
                'message' => count($request->item_ids) . ' item berhasil di-' . ($isSelected ? 'select' : 'deselect')
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk Selection Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan bulk selection'
            ], 500);
        }
    }

    /**
     * Import from Excel (placeholder for now)
     */
    public function importExcel(Request $request, $projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            // TODO: Implement Excel import logic
            // Use Laravel Excel package or PHPExcel

            return back()->with('info', 'Fitur import Excel akan segera tersedia.');

        } catch (\Exception $e) {
            Log::error('Budget Control Import Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal import Excel: ' . $e->getMessage());
        }
    }

    /**
     * Export to Excel
     */
    public function exportExcel($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            // TODO: Implement Excel export logic
            
            return back()->with('info', 'Fitur export Excel akan segera tersedia.');

        } catch (\Exception $e) {
            Log::error('Budget Control Export Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Get selected items for voucher creation
     */
    public function getSelectedItems($projectId)
    {
        try {
            $selectedItems = BudgetControl::where('project_id', $projectId)
                ->selected()
                ->active()
                ->with(['rab', 'voucherItems' => function($query) {
                    $query->select('budget_control_id', DB::raw('SUM(qty) as total_qty'))
                        ->whereIn('status', ['delivered', 'completed'])
                        ->groupBy('budget_control_id');
                }])
                ->get()
                ->map(function($item) {
                    $deliveredQty = $item->voucherItems->sum('total_qty') ?? 0;
                    $availableQty = max(0, $item->volume_plan - $deliveredQty);
                    
                    return [
                        'id' => $item->id,
                        'kode' => $item->kode,
                        'uraian' => $item->uraian,
                        'satuan' => $item->satuan,
                        'volume_plan' => $item->volume_plan,
                        'harga_satuan_plan' => $item->harga_satuan_plan,
                        'jumlah_plan' => $item->jumlah_plan,
                        'volume_real' => $item->volume_real,
                        'available_qty' => $availableQty,
                        'is_over_budget' => $item->is_over_budget
                    ];
                });

            return response()->json([
                'success' => true,
                'items' => $selectedItems,
                'count' => $selectedItems->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Get Selected Items Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil selected items'
            ], 500);
        }
    }

    /**
     * Update realisasi manually (for admin)
     */
    public function updateRealisasi(Request $request, $projectId, $id)
    {
        try {
            $budgetControl = BudgetControl::where('project_id', $projectId)
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'volume_real' => 'required|numeric|min:0',
                'jumlah_real' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $budgetControl->update([
                'volume_real' => $request->volume_real,
                'jumlah_real' => $request->jumlah_real
            ]);

            Log::info('Realisasi Updated Manually:', [
                'id' => $budgetControl->id,
                'volume_real' => $request->volume_real,
                'jumlah_real' => $request->jumlah_real
            ]);

            return back()->with('success', 'Realisasi berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error('Update Realisasi Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memperbarui realisasi: ' . $e->getMessage());
        }
    }

    /**
     * Monitoring dashboard (fallback ke index)
     */
    public function monitoringDashboard($projectId)
    {
        return $this->index($projectId);
    }

    /**
     * Category summary (fallback ke index)
     */
    public function categorySummary($projectId)
    {
        return $this->index($projectId);
    }

    /**
     * Export budget controls
     */
    public function export(Request $request, $projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $format = $request->get('format', 'excel');
            
            // TODO: Implement export logic
            // For now, return message
            return back()->with('info', 'Fitur export ' . strtoupper($format) . ' akan segera tersedia.');

        } catch (\Exception $e) {
            Log::error('Budget Control Export Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal export: ' . $e->getMessage());
        }
    }

    /**
     * Load category data via AJAX
     */
    public function loadCategoryData($projectId, Request $request)
    {
        try {
            $category = $request->get('category');
            $ajax = $request->get('ajax');
            
            if (!$ajax) {
                return redirect()->route('dev.budget-controls.index', $projectId);
            }
            
            $budgetControls = BudgetControl::where('project_id', $projectId)
                ->where('kategori', $category)
                ->orderBy('kode')
                ->paginate(50);
            
            $project = Project::findOrFail($projectId);
            
            return view('dev.budget-controls.partials.category-table', compact(
                'budgetControls',
                'project',
                'category'
            ));

        } catch (\Exception $e) {
            Log::error('Load Category Data Error:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal memuat data'], 500);
        }
    }
}
