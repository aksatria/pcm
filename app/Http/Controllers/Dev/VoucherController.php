<?php
// app/Http\Controllers\Dev\VoucherController.php

namespace App\Http\Controllers\Dev;

use App\Models\Project;
use App\Models\Voucher;
use App\Models\VoucherItem;
use App\Models\BudgetControl;
use App\Models\Vendor;
use App\Models\Rab;
use App\Support\NotificationService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VoucherController extends BaseController
{
    /**
     * Display a listing of vouchers for the project.
     */
    public function index($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            $vouchers = Voucher::where('project_id', $projectId)
                ->with(['vendor', 'items.budgetControl'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // Statistics
            $stats = [
                'total' => Voucher::where('project_id', $projectId)->count(),
                'draft' => Voucher::where('project_id', $projectId)->where('status', 'draft')->count(),
                'submitted' => Voucher::where('project_id', $projectId)->where('status', 'submitted')->count(),
                'approved' => Voucher::where('project_id', $projectId)->where('status', 'approved')->count(),
                'paid' => Voucher::where('project_id', $projectId)->where('status', 'paid')->count(),
                'total_amount' => Voucher::where('project_id', $projectId)->sum('total_bayar')
            ];

            return view('dev.vouchers.index', compact('project', 'vouchers', 'stats'));

        } catch (\Exception $e) {
            Log::error('Voucher Index Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat data voucher: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new voucher.
     */
    public function create($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            // Get selected budget control items
            $selectedItems = BudgetControl::where('project_id', $projectId)
                ->selected()
                ->active()
                ->get()
                ->map(function($item) {
                    $deliveredQty = $item->voucherItems()
                        ->whereIn('status', ['delivered', 'completed'])
                        ->sum('qty');
                    
                    $availableQty = max(0, $item->volume_plan - $deliveredQty);
                    
                    return [
                        'id' => $item->id,
                        'kode' => $item->kode,
                        'uraian' => $item->uraian,
                        'satuan' => $item->satuan,
                        'volume_plan' => $item->volume_plan,
                        'harga_satuan_plan' => $item->harga_satuan_plan,
                        'available_qty' => $availableQty,
                        'is_over_budget' => $item->is_over_budget
                    ];
                });

            // Get vendors
            $vendors = Vendor::active()->forProject($project->id)->orderBy('nama')->get();
            
            // Get available approval persons
            $approvalPersons = [
                'Yachub Syahriar' => 'Direktur Utama',
                'Gunawan Wibisono' => 'Direktur Marketing',
                'Dicky Kusuma Purnomo P.' => 'Direktur Operasi',
                'Nur Islam Achmad' => 'Direktur Keuangan'
            ];

            // Get voucher number
            $voucherNumber = $this->generateVoucherNumber($project);

            return view('dev.vouchers.create', compact(
                'project',
                'selectedItems',
                'vendors',
                'approvalPersons',
                'voucherNumber'
            ));

        } catch (\Exception $e) {
            Log::error('Voucher Create Form Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat form voucher: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created voucher in storage.
     */
    public function store(Request $request, $projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            $validator = Validator::make($request->all(), [
                'voucher_number' => 'required|string|max:50|unique:vouchers,voucher_number',
                'tanggal' => 'required|date',
                'vendor_id' => 'nullable|exists:vendors,id',
                'tujuan_transfer' => 'nullable|string|max:255',
                'bank' => 'nullable|string|max:50',
                'nama_rekening' => 'nullable|string|max:255',
                'no_rekening' => 'nullable|string|max:50',
                'pembayaran' => 'required|in:cash,transfer,tempo',
                'jatuh_tempo' => 'nullable|date|after_or_equal:tanggal',
                'diajukan_oleh' => 'nullable|string|max:100',
                'disetujui_oleh' => 'nullable|string|max:100',
                'ppn' => 'nullable|numeric|min:0',
                'ongkir' => 'nullable|numeric|min:0',
                'keterangan' => 'nullable|string|max:1000',
                'items' => 'required|array|min:1',
                'items.*.budget_control_id' => 'required|exists:budget_controls,id',
                'items.*.qty' => 'required|numeric|min:0.0001',
                'items.*.harga_satuan' => 'required|numeric|min:0',
                'items.*.keterangan' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validasi gagal.',
                        'errors' => $validator->errors(),
                    ], 422);
                }

                return back()->withErrors($validator)->withInput();
            }

            DB::beginTransaction();

            // Create voucher
            $voucher = Voucher::create([
                'project_id' => $project->id,
                'vendor_id' => $request->vendor_id,
                'voucher_number' => $request->voucher_number,
                'tanggal' => $request->tanggal,
                'tujuan_transfer' => $request->tujuan_transfer,
                'bank' => $request->bank,
                'nama_rekening' => $request->nama_rekening,
                'no_rekening' => $request->no_rekening,
                'pembayaran' => $request->pembayaran,
                'jatuh_tempo' => $request->jatuh_tempo,
                'diajukan_oleh' => $request->diajukan_oleh,
                'disetujui_oleh' => $request->disetujui_oleh,
                'status' => 'draft',
                'ppn' => $request->ppn ?? 0,
                'ongkir' => $request->ongkir ?? 0,
                'keterangan' => $request->keterangan,
                'total_tagihan' => 0,
                'total_bayar' => 0
            ]);
            AuditLogger::log('voucher.created', $voucher, null, $voucher->toArray(), [
                'voucher_number' => $voucher->voucher_number,
                'project_id' => $project->id,
            ]);


            // Add items
            $totalTagihan = 0;
            $itemCounter = 1;

            foreach ($request->items as $itemData) {
                $budgetControl = BudgetControl::find($itemData['budget_control_id']);
                
                if (!$budgetControl) {
                    continue;
                }

                // Calculate total for this item
                $totalHarga = $itemData['qty'] * $itemData['harga_satuan'];
                $totalTagihan += $totalHarga;

                // Create voucher item
                VoucherItem::create([
                    'voucher_id' => $voucher->id,
                    'budget_control_id' => $budgetControl->id,
                    'kode' => $budgetControl->kode,
                    'uraian' => $budgetControl->uraian,
                    'qty' => $itemData['qty'],
                    'satuan' => $budgetControl->satuan,
                    'harga_satuan' => $itemData['harga_satuan'],
                    'total_harga' => $totalHarga,
                    'status' => 'pending',
                    'keterangan' => $itemData['keterangan'] ?? null,
                    'urutan' => $itemCounter++
                ]);

                // Deselect the budget control item
                $budgetControl->update(['is_selected' => false]);
            }

            // Update voucher totals
            $voucher->total_tagihan = $totalTagihan;
            $voucher->total_bayar = $totalTagihan + $voucher->ppn + $voucher->ongkir;
            $voucher->save();

            DB::commit();

            Log::info('Voucher Created:', [
                'voucher_id' => $voucher->id,
                'voucher_number' => $voucher->voucher_number,
                'project_id' => $project->id,
                'item_count' => count($request->items)
            ]);

            return redirect()->route('dev.vouchers.show', ['projectId' => $project->id, 'id' => $voucher->id])
                ->with('success', 'Voucher berhasil dibuat!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Voucher Store Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal membuat voucher: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified voucher.
     */
    public function show($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::with(['vendor', 'items.budgetControl', 'project'])->findOrFail($id);
            
            // Format additional properties
            $voucher->status_label = $this->getStatusLabel($voucher->status);
            $voucher->status_color = $this->getStatusBadgeColor($voucher->status);
            $voucher->status_alert = $this->getStatusAlert($voucher->status);

            // Get approval persons for dropdown
            $approvalPersons = [
                'Yachub Syahriar' => 'Direktur Utama',
                'Gunawan Wibisono' => 'Direktur Marketing',
                'Dicky Kusuma Purnomo P.' => 'Direktur Operasi',
                'Nur Islam Achmad' => 'Direktur Keuangan'
            ];

            // Check permissions
            $canEdit = $voucher->status === 'draft';
            $canSubmit = $voucher->status === 'draft';
            $canApprove = $voucher->status === 'submitted' && auth()->user()->isHO();
            $canReject = $voucher->status === 'submitted' && auth()->user()->isHO();
            $canMarkPaid = $voucher->status === 'approved';
            $canMarkCompleted = $voucher->status === 'paid';

            return view('dev.vouchers.show', compact(
                'project',
                'voucher',
                'approvalPersons',
                'canEdit',
                'canSubmit',
                'canApprove',
                'canReject',
                'canMarkPaid',
                'canMarkCompleted'
            ));

        } catch (\Exception $e) {
            Log::error('Voucher Show Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat detail voucher: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified voucher.
     */
    public function edit($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::with(['items.budgetControl'])->findOrFail($id);
            
            if (!auth()->user()->isHO() && $voucher->status !== 'draft') {
                return back()->with('error', 'Voucher hanya dapat diedit saat status draft.');
            }

            $vendors = Vendor::active()->forProject($project->id)->orderBy('nama')->get();
            
            $approvalPersons = [
                'Yachub Syahriar' => 'Direktur Utama',
                'Gunawan Wibisono' => 'Direktur Marketing',
                'Dicky Kusuma Purnomo P.' => 'Direktur Operasi',
                'Nur Islam Achmad' => 'Direktur Keuangan'
            ];

            return view('dev.vouchers.edit', compact(
                'project',
                'voucher',
                'vendors',
                'approvalPersons'
            ));

        } catch (\Exception $e) {
            Log::error('Voucher Edit Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat form edit voucher: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified voucher in storage.
     */
    public function update(Request $request, $projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::findOrFail($id);
            $before = $voucher->toArray();
            
            if (!auth()->user()->isHO() && $voucher->status !== 'draft') {
                return back()->with('error', 'Voucher hanya dapat diupdate saat status draft.');
            }

            $validator = Validator::make($request->all(), [
                'voucher_number' => 'required|string|max:50|unique:vouchers,voucher_number,' . $id,
                'tanggal' => 'required|date',
                'vendor_id' => 'nullable|exists:vendors,id',
                'tujuan_transfer' => 'nullable|string|max:255',
                'bank' => 'nullable|string|max:50',
                'nama_rekening' => 'nullable|string|max:255',
                'no_rekening' => 'nullable|string|max:50',
                'pembayaran' => 'required|in:cash,transfer,tempo',
                'jatuh_tempo' => 'nullable|date|after_or_equal:tanggal',
                'diajukan_oleh' => 'nullable|string|max:100',
                'disetujui_oleh' => 'nullable|string|max:100',
                'ppn' => 'nullable|numeric|min:0',
                'ongkir' => 'nullable|numeric|min:0',
                'keterangan' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            DB::beginTransaction();

            $voucher->update([
                'vendor_id' => $request->vendor_id,
                'voucher_number' => $request->voucher_number,
                'tanggal' => $request->tanggal,
                'tujuan_transfer' => $request->tujuan_transfer,
                'bank' => $request->bank,
                'nama_rekening' => $request->nama_rekening,
                'no_rekening' => $request->no_rekening,
                'pembayaran' => $request->pembayaran,
                'jatuh_tempo' => $request->jatuh_tempo,
                'diajukan_oleh' => $request->diajukan_oleh,
                'disetujui_oleh' => $request->disetujui_oleh,
                'ppn' => $request->ppn ?? 0,
                'ongkir' => $request->ongkir ?? 0,
                'keterangan' => $request->keterangan
            ]);
            AuditLogger::log('voucher.updated', $voucher, $before, $voucher->toArray(), [
                'voucher_number' => $voucher->voucher_number,
                'project_id' => $project->id,
            ]);


            $voucher->calculateTotals();

            DB::commit();

            Log::info('Voucher Updated:', ['voucher_id' => $voucher->id]);

            return redirect()->route('dev.vouchers.show', ['projectId' => $project->id, 'id' => $voucher->id])
                ->with('success', 'Voucher berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Voucher Update Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memperbarui voucher: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified voucher from storage.
     */
    public function destroy($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::with('items')->findOrFail($id);
            $before = $voucher->toArray();
            
            if (!in_array($voucher->status, ['draft', 'rejected'])) {
                return back()->with('error', 'Voucher hanya dapat dihapus saat status draft atau rejected.');
            }

            DB::beginTransaction();

            // Update budget control realisasi
            foreach ($voucher->items as $item) {
                if ($item->budgetControl) {
                    $item->budgetControl->updateRealisasi();
                }
            }

            // Delete voucher
            $voucherNumber = $voucher->voucher_number;
            $voucher->delete();
            AuditLogger::log('voucher.deleted', $voucher, $before, null, [
                'voucher_number' => $voucher->voucher_number,
                'project_id' => $project->id,
            ]);


            DB::commit();

            Log::info('Voucher Deleted:', [
                'voucher_number' => $voucherNumber,
                'project_id' => $project->id,
            ]);

            return redirect()->route('dev.vouchers.index', $project->id)
                ->with('success', 'Voucher "' . $voucherNumber . '" berhasil dihapus!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Voucher Destroy Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal menghapus voucher: ' . $e->getMessage());
        }
    }

    /**
     * Submit voucher for approval
     */
    public function submit($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::findOrFail($id);
            
            if (!auth()->user()->isHO() && $voucher->status !== 'draft') {
                return back()->with('error', 'Hanya voucher dengan status draft yang dapat disubmit.');
            }

            if ($voucher->items()->count() === 0) {
                return back()->with('error', 'Voucher harus memiliki minimal 1 item sebelum disubmit.');
            }

            $voucher->update([
                'status' => 'submitted',
                'tanggal_pengajuan' => now(),
                'tanggal_persetujuan' => null,
                'disetujui_oleh' => null,
                'catatan_reject' => null,
            ]);
            AuditLogger::log('voucher.submitted', $voucher, $before ?? $voucher->toArray(), $voucher->toArray(), [
                'voucher_number' => $voucher->voucher_number,
                'project_id' => $project->id,
            ]);


            $projectName = $project->name ?? null;
            NotificationService::notifyHO(
                'Voucher Diajukan',
                'Voucher ' . ($voucher->voucher_number ?? $voucher->id) . ' diajukan' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
                route('dev.vouchers.show', ['projectId' => $project->id, 'id' => $voucher->id]),
                'approval',
                [
                    'doc_type' => 'voucher',
                    'doc_id' => $voucher->id,
                    'project_id' => $project->id,
                ]
            );

            Log::info('Voucher Submitted:', ['voucher_id' => $voucher->id]);

            return back()->with('success', 'Voucher berhasil disubmit untuk persetujuan!');

        } catch (\Exception $e) {
            Log::error('Voucher Submit Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal submit voucher: ' . $e->getMessage());
        }
    }

    /**
     * Approve voucher
     */
    public function approve($projectId, $id)
    {
        abort_unless(auth()->user() && auth()->user()->isHO(), 403, 'Hanya HO yang diizinkan.');

        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::findOrFail($id);
            
            if ($voucher->status !== 'submitted') {
                return back()->with('error', 'Hanya voucher dengan status submitted yang dapat disetujui.');
            }

            $approvedBy = auth()->user()->name ?? 'System';
            $voucher->update([
                'status' => 'approved',
                'disetujui_oleh' => $approvedBy,
                'tanggal_persetujuan' => now(),
                'catatan_reject' => null
            ]);
            AuditLogger::log('voucher.approved', $voucher, $before ?? $voucher->toArray(), $voucher->toArray(), [
                'voucher_number' => $voucher->voucher_number,
                'project_id' => $project->id,
            ]);


            NotificationService::notifySubmitter(
                $voucher,
                'Voucher Disetujui',
                'Voucher ' . ($voucher->voucher_number ?? $voucher->id) . ' telah disetujui.',
                route('dev.vouchers.show', ['projectId' => $project->id, 'id' => $voucher->id]),
                'approval',
                [
                    'doc_type' => 'voucher',
                    'doc_id' => $voucher->id,
                    'project_id' => $project->id,
                ]
            );

            Log::info('Voucher Approved:', ['voucher_id' => $voucher->id, 'approved_by' => $approvedBy]);

            return back()->with('success', 'Voucher berhasil disetujui!');

        } catch (\Exception $e) {
            Log::error('Voucher Approve Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal menyetujui voucher: ' . $e->getMessage());
        }
    }

    /**
     * Reject voucher
     */
    public function reject(Request $request, $projectId, $id)
    {
        abort_unless(auth()->user() && auth()->user()->isHO(), 403, 'Hanya HO yang diizinkan.');

        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::findOrFail($id);
            
            if ($voucher->status !== 'submitted') {
                return back()->with('error', 'Hanya voucher dengan status submitted yang dapat ditolak.');
            }

            $reason = $request->input('reason', 'Tidak ada alasan spesifik');
            $rejectedBy = auth()->user()->name ?? 'System';
            
            $voucher->update([
                'status' => 'rejected',
                'disetujui_oleh' => $rejectedBy,
                'tanggal_persetujuan' => now(),
                'catatan_reject' => $reason
            ]);
            AuditLogger::log('voucher.rejected', $voucher, $before ?? $voucher->toArray(), $voucher->toArray(), [
                'voucher_number' => $voucher->voucher_number,
                'project_id' => $project->id,
                'reason' => $reason,
            ]);


            NotificationService::notifySubmitter(
                $voucher,
                'Voucher Ditolak',
                'Voucher ' . ($voucher->voucher_number ?? $voucher->id) . ' ditolak. Alasan: ' . $reason,
                route('dev.vouchers.show', ['projectId' => $project->id, 'id' => $voucher->id]),
                'approval',
                [
                    'doc_type' => 'voucher',
                    'doc_id' => $voucher->id,
                    'project_id' => $project->id,
                    'rejected_reason' => $reason,
                ]
            );

            Log::info('Voucher Rejected:', [
                'voucher_id' => $voucher->id,
                'reason' => $reason,
                'rejected_by' => $rejectedBy
            ]);

            return back()->with('success', 'Voucher berhasil ditolak!');

        } catch (\Exception $e) {
            Log::error('Voucher Reject Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal menolak voucher: ' . $e->getMessage());
        }
    }

    /**
     * Mark voucher as paid
     */
    public function markAsPaid($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::findOrFail($id);
            $before = $voucher->toArray();
            
            if ($voucher->status !== 'approved') {
                return back()->with('error', 'Hanya voucher dengan status approved yang dapat ditandai sebagai dibayar.');
            }

            $voucher->update([
                'status' => 'paid',
                'tanggal_pembayaran' => now()
            ]);
            AuditLogger::log('voucher.paid', $voucher, $before ?? $voucher->toArray(), $voucher->toArray(), [
                'voucher_number' => $voucher->voucher_number,
                'project_id' => $project->id,
            ]);


            Log::info('Voucher Marked as Paid:', ['voucher_id' => $voucher->id]);

            return back()->with('success', 'Voucher berhasil ditandai sebagai dibayar!');

        } catch (\Exception $e) {
            Log::error('Voucher Mark as Paid Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal menandai voucher sebagai dibayar: ' . $e->getMessage());
        }
    }

    /**
     * Mark voucher as completed
     */
    public function markAsCompleted($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::with('items')->findOrFail($id);
            $before = $voucher->toArray();
            
            if ($voucher->status !== 'paid') {
                return back()->with('error', 'Hanya voucher dengan status paid yang dapat ditandai sebagai selesai.');
            }

            DB::beginTransaction();

            $voucher->update(['status' => 'completed']);
            AuditLogger::log('voucher.completed', $voucher, $before ?? $voucher->toArray(), $voucher->toArray(), [
                'voucher_number' => $voucher->voucher_number,
                'project_id' => $project->id,
            ]);

            // Update all items to completed
            $voucher->items()->update(['status' => 'completed']);

            // Update budget control realisasi
            foreach ($voucher->items as $item) {
                if ($item->budgetControl) {
                    $item->budgetControl->updateRealisasi();
                }
            }

            DB::commit();

            Log::info('Voucher Marked as Completed:', ['voucher_id' => $voucher->id]);

            return back()->with('success', 'Voucher berhasil ditandai sebagai selesai!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Voucher Mark as Completed Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal menandai voucher sebagai selesai: ' . $e->getMessage());
        }
    }

    /**
     * Store voucher item
     */
    public function storeItem(Request $request, $projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::findOrFail($id);
            
            if (!auth()->user()->isHO() && $voucher->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Item hanya dapat ditambahkan ke voucher dengan status draft.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'budget_control_id' => 'required|exists:budget_controls,id',
                'qty' => 'required|numeric|min:0.0001',
                'harga_satuan' => 'required|numeric|min:0',
                'keterangan' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal: ' . implode(', ', $validator->errors()->all())
                ], 422);
            }

            $budgetControl = BudgetControl::find($request->budget_control_id);
            
            // Check if budget control belongs to the same project
            if ($budgetControl->project_id != $project->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak termasuk dalam project ini.'
                ], 403);
            }

            DB::beginTransaction();

            $totalHarga = $request->qty * $request->harga_satuan;
            
            $voucherItem = VoucherItem::create([
                'voucher_id' => $voucher->id,
                'budget_control_id' => $budgetControl->id,
                'kode' => $budgetControl->kode,
                'uraian' => $budgetControl->uraian,
                'qty' => $request->qty,
                'satuan' => $budgetControl->satuan,
                'harga_satuan' => $request->harga_satuan,
                'total_harga' => $totalHarga,
                'status' => 'pending',
                'keterangan' => $request->keterangan,
                'urutan' => $voucher->items()->max('urutan') + 1
            ]);

            // Update voucher totals
            $voucher->calculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil ditambahkan',
                'item' => $voucherItem->load('budgetControl')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Voucher Item Store Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update voucher item
     */
    public function updateItem(Request $request, $projectId, $id, $itemId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::findOrFail($id);
            $voucherItem = VoucherItem::findOrFail($itemId);
            
            if (!auth()->user()->isHO() && $voucher->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Item hanya dapat diupdate pada voucher dengan status draft.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'qty' => 'sometimes|required|numeric|min:0.0001',
                'harga_satuan' => 'sometimes|required|numeric|min:0',
                'keterangan' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal: ' . implode(', ', $validator->errors()->all())
                ], 422);
            }

            DB::beginTransaction();

            $updateData = [];
            
            if ($request->has('qty')) {
                $updateData['qty'] = $request->qty;
            }
            
            if ($request->has('harga_satuan')) {
                $updateData['harga_satuan'] = $request->harga_satuan;
            }
            
            if ($request->has('keterangan')) {
                $updateData['keterangan'] = $request->keterangan;
            }
            
            // Calculate new total if qty or harga_satuan changed
            if (isset($updateData['qty']) || isset($updateData['harga_satuan'])) {
                $qty = $updateData['qty'] ?? $voucherItem->qty;
                $hargaSatuan = $updateData['harga_satuan'] ?? $voucherItem->harga_satuan;
                $updateData['total_harga'] = $qty * $hargaSatuan;
            }
            
            $voucherItem->update($updateData);
            
            // Update voucher totals
            $voucher->calculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil diperbarui',
                'item' => $voucherItem->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Voucher Item Update Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Destroy voucher item
     */
    public function destroyItem($projectId, $id, $itemId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::findOrFail($id);
            $voucherItem = VoucherItem::findOrFail($itemId);
            
            if (!auth()->user()->isHO() && $voucher->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Item hanya dapat dihapus dari voucher dengan status draft.'
                ], 403);
            }

            DB::beginTransaction();

            $voucherItem->delete();
            
            // Update voucher totals
            $voucher->calculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Voucher Item Destroy Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update voucher item status
     */
    public function updateItemStatus(Request $request, $projectId, $id, $itemId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::findOrFail($id);
            $voucherItem = VoucherItem::findOrFail($itemId);
            
            if (!in_array($voucher->status, ['approved', 'paid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status item hanya dapat diupdate pada voucher dengan status approved atau paid.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,ordered,delivered,completed,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal'
                ], 422);
            }

            DB::beginTransaction();

            $oldStatus = $voucherItem->status;
            $voucherItem->update([
                'status' => $request->status,
                'tanggal_terima' => $request->status === 'delivered' ? now() : null
            ]);

            // If status changed to delivered or completed, update budget control
            if (($oldStatus !== 'delivered' && $request->status === 'delivered') ||
                ($oldStatus !== 'completed' && $request->status === 'completed')) {
                if ($voucherItem->budgetControl) {
                    $voucherItem->budgetControl->updateRealisasi();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status item berhasil diperbarui',
                'item' => $voucherItem->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Voucher Item Status Update Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export voucher to PDF
     */
    public function exportPdf($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::with(['vendor', 'items.budgetControl', 'project'])->findOrFail($id);
            $data = compact('project', 'voucher');

            $filename = 'VCH-' . ($voucher->voucher_number ?? $voucher->id) . '.pdf';
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.voucher', $data)
                ->setPaper('a4', 'portrait')
                ->download($filename);

        } catch (\Exception $e) {
            Log::error('Voucher PDF Export Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal export PDF: ' . $e->getMessage());
        }
    }

    /**
     * Print voucher
     */
    public function print($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::with(['vendor', 'items.budgetControl', 'project'])->findOrFail($id);
            
            // Add formatted properties for view
            $voucher->status_label = $this->getStatusLabel($voucher->status);
            
            $logoSrc = asset('images/logo-dipo.png');
            return view('pdf.voucher', compact('project', 'voucher', 'logoSrc'));

        } catch (\Exception $e) {
            Log::error('Voucher Print Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat halaman print: ' . $e->getMessage());
        }
    }

    /**
     * Bulk approve vouchers
     */
    public function bulkApprove(Request $request, $projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            $validator = Validator::make($request->all(), [
                'voucher_ids' => 'required|array',
                'voucher_ids.*' => 'exists:vouchers,id'
            ]);

            if ($validator->fails()) {
                return back()->with('error', 'Validasi gagal');
            }

            $count = 0;
            $approvedBy = auth()->user()->name ?? 'System';
            
            foreach ($request->voucher_ids as $voucherId) {
                $voucher = Voucher::find($voucherId);
                
                if ($voucher && $voucher->status === 'submitted' && $voucher->project_id == $project->id) {
                    $voucher->update([
                        'status' => 'approved',
                        'disetujui_oleh' => $approvedBy,
                        'tanggal_persetujuan' => now()
                    ]);
                    $count++;
                }
            }

            Log::info('Vouchers Bulk Approved:', [
                'count' => $count,
                'project_id' => $project->id,
                'approved_by' => $approvedBy
            ]);

            return back()->with('success', $count . ' voucher berhasil disetujui!');

        } catch (\Exception $e) {
            Log::error('Voucher Bulk Approve Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal approve bulk: ' . $e->getMessage());
        }
    }

    /**
     * Bulk reject vouchers
     */
    public function bulkReject(Request $request, $projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            $validator = Validator::make($request->all(), [
                'voucher_ids' => 'required|array',
                'voucher_ids.*' => 'exists:vouchers,id',
                'reason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return back()->with('error', 'Validasi gagal');
            }

            $count = 0;
            $rejectedBy = auth()->user()->name ?? 'System';
            
            foreach ($request->voucher_ids as $voucherId) {
                $voucher = Voucher::find($voucherId);
                
                if ($voucher && $voucher->status === 'submitted' && $voucher->project_id == $project->id) {
                    $voucher->update([
                        'status' => 'rejected',
                        'disetujui_oleh' => $rejectedBy,
                        'tanggal_persetujuan' => now(),
                        'catatan_reject' => $request->reason
                    ]);
                    $count++;
                }
            }

            Log::info('Vouchers Bulk Rejected:', [
                'count' => $count,
                'project_id' => $project->id,
                'reason' => $request->reason
            ]);

            return back()->with('success', $count . ' voucher berhasil ditolak!');

        } catch (\Exception $e) {
            Log::error('Voucher Bulk Reject Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal reject bulk: ' . $e->getMessage());
        }
    }

    /**
     * Vendor management - Index
     */
    public function vendorIndex(Request $request)
    {
        try {
            $scope     = $request->get('scope', 'all'); // all | global | project
            $projectId = $request->get('project_id');
            $status    = $request->get('status');
            $kategori  = $request->get('kategori');
            $search    = trim((string) $request->get('search', ''));

            $baseQuery = Vendor::query();

            if ($scope === 'global') {
                $baseQuery->whereNull('project_id');
            } elseif ($scope === 'project') {
                if ($projectId) {
                    $baseQuery->where('project_id', $projectId);
                } else {
                    $baseQuery->whereRaw('1 = 0');
                }
            }

            if ($status) {
                $baseQuery->where('status', $status);
            }

            if ($kategori) {
                $baseQuery->where('pekerjaan', $kategori);
            }

            if ($search !== '') {
                $baseQuery->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('perusahaan', 'like', "%{$search}%")
                        ->orWhere('telepon', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('kode_vendor', 'like', "%{$search}%")
                        ->orWhere('bank', 'like', "%{$search}%");
                });
            }

            $vendors = (clone $baseQuery)
                ->with(['project'])
                ->withCount('vouchers')
                ->orderBy('nama')
                ->paginate(20)
                ->appends($request->query());

            $stats = [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('status', 'active')->count(),
                'inactive' => (clone $baseQuery)->where('status', 'inactive')->count()
            ];

            $projects = Project::orderBy('name')->get();

            return view('dev.vendors.index', compact('vendors', 'stats', 'projects'));

        } catch (\Exception $e) {
            Log::error('Vendor Index Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat data vendor: ' . $e->getMessage());
        }
    }

    /**
     * Vendor management - Create form
     */
    public function vendorCreate()
    {
        $projects = Project::orderBy('name')->get();
        return view('dev.vendors.create', compact('projects'));
    }

    /**
     * Vendor management - Store
     */
    public function vendorStore(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:255',
                'project_id' => 'nullable|exists:projects,id',
                'perusahaan' => 'nullable|string|max:255',
                'pekerjaan' => 'nullable|string|max:100',
                'bank' => 'nullable|string|max:50',
                'no_rekening' => 'nullable|string|max:50',
                'nama_rekening' => 'nullable|string|max:255',
                'alamat' => 'nullable|string|max:500',
                'telepon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'status' => 'nullable|in:active,inactive'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // Generate vendor code
            $lastVendor = Vendor::orderBy('id', 'desc')->first();
            $nextNumber = $lastVendor ? (int) str_replace('V-', '', $lastVendor->kode_vendor) + 1 : 1;
            $kodeVendor = 'V-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $vendor = Vendor::create([
                'kode_vendor' => $kodeVendor,
                'project_id' => $request->project_id ?: null,
                'nama' => $request->nama,
                'perusahaan' => $request->perusahaan,
                'pekerjaan' => $request->pekerjaan,
                'bank' => $request->bank,
                'no_rekening' => $request->no_rekening,
                'nama_rekening' => $request->nama_rekening,
                'alamat' => $request->alamat,
                'telepon' => $request->telepon,
                'email' => $request->email,
                'status' => $request->status ?: 'active'
            ]);
            AuditLogger::log('vendor.created', $vendor, null, $vendor->toArray(), [
                'vendor_name' => $vendor->nama,
                'project_id' => $vendor->project_id,
            ]);


            Log::info('Vendor Created:', ['nama' => $request->nama, 'kode_vendor' => $kodeVendor]);
            $projectName = null;
            if ($vendor->project_id) {
                $projectName = Project::where('id', $vendor->project_id)->value('name');
            }
            NotificationService::notifyHO(
                'Vendor Baru',
                'Vendor ' . $vendor->nama . ' berhasil ditambahkan' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
                route('dev.vendors.index'),
                'vendor',
                [
                'vendor_id' => $vendor->id,
                'project_id' => $vendor->project_id,
                ]
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Vendor berhasil ditambahkan.',
                    'vendor' => [
                        'id' => $vendor->id,
                        'nama' => $vendor->nama,
                        'kode_vendor' => $vendor->kode_vendor,
                        'project_id' => $vendor->project_id,
                        'project_name' => $projectName,
                        'bank' => $vendor->bank,
                        'no_rekening' => $vendor->no_rekening,
                        'nama_rekening' => $vendor->nama_rekening,
                    ],
                ]);
            }

            return redirect()->route('dev.vendors.index')
                ->with('success', 'Vendor berhasil ditambahkan!');

        } catch (\Exception $e) {
            Log::error('Vendor Store Error:', ['error' => $e->getMessage()]);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gagal menambahkan vendor: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Gagal menambahkan vendor: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Vendor management - Edit form
     */
    public function vendorEdit($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            $projects = Project::orderBy('name')->get();
            return view('dev.vendors.edit', compact('vendor', 'projects'));

        } catch (\Exception $e) {
            Log::error('Vendor Edit Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat form edit vendor: ' . $e->getMessage());
        }
    }

    /**
     * Vendor management - Update
     */
    public function vendorUpdate(Request $request, $id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            $before = $vendor->toArray();

            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:255',
                'project_id' => 'nullable|exists:projects,id',
                'perusahaan' => 'nullable|string|max:255',
                'pekerjaan' => 'nullable|string|max:100',
                'bank' => 'nullable|string|max:50',
                'no_rekening' => 'nullable|string|max:50',
                'nama_rekening' => 'nullable|string|max:255',
                'alamat' => 'nullable|string|max:500',
                'telepon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'status' => 'required|in:active,inactive'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $vendor->update($validator->validated());
            AuditLogger::log('vendor.updated', $vendor, $before, $vendor->toArray(), [
                'vendor_name' => $vendor->nama,
                'project_id' => $vendor->project_id,
            ]);


            Log::info('Vendor Updated:', ['vendor_id' => $vendor->id, 'nama' => $vendor->nama]);
            $projectName = null;
            if ($vendor->project_id) {
                $projectName = Project::where('id', $vendor->project_id)->value('name');
            }
            NotificationService::notifyHO(
                'Vendor Diperbarui',
                'Vendor ' . $vendor->nama . ' diperbarui' . ($projectName ? ' (proyek ' . $projectName . ')' : '') . '.',
                route('dev.vendors.index'),
                'vendor',
                [
                    'vendor_id' => $vendor->id,
                    'project_id' => $vendor->project_id,
                ]
            );

            return redirect()->route('dev.vendors.index')
                ->with('success', 'Vendor berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error('Vendor Update Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memperbarui vendor: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Vendor management - Destroy
     */
    public function vendorDestroy($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            $before = $vendor->toArray();
            
            // Check if vendor has vouchers
            if ($vendor->vouchers()->count() > 0) {
                return back()->with('error', 'Tidak dapat menghapus vendor yang sudah memiliki transaksi voucher!');
            }

            $vendorName = $vendor->nama;
            $isHO = auth()->check() && auth()->user()->isHO();

            // Draft (inactive) bisa langsung dihapus
            if ($vendor->status === 'inactive' || $isHO) {
                $vendor->delete();
                AuditLogger::log('vendor.deleted', $vendor, $before, null, [
                    'vendor_name' => $vendorName,
                    'project_id' => $vendor->project_id,
                    'deleted_by' => auth()->user()->name ?? 'HO',
                ]);


                Log::info('Vendor Deleted:', ['vendor_id' => $id, 'nama' => $vendorName]);
                NotificationService::notifyHO(
                    'Vendor Dihapus',
                    'Vendor ' . $vendorName . ' dihapus dari sistem.',
                    route('dev.vendors.index'),
                    'vendor',
                    [
                        'vendor_id' => $id,
                    ]
                );

                return redirect()->route('dev.vendors.index')
                    ->with('success', 'Vendor "' . $vendorName . '" berhasil dihapus!');
            }

            if ($vendor->delete_status === 'pending') {
                return back()->with('error', 'Vendor ini sudah menunggu approval penghapusan.');
            }

            $vendor->update([
                'delete_status' => 'pending',
                'delete_requested_by' => auth()->check() ? auth()->user()->name : 'system',
                'delete_requested_at' => now(),
                'delete_reason' => request()->input('delete_reason'),
            ]);
            AuditLogger::log('vendor.delete_requested', $vendor, $before, $vendor->toArray(), [
                'vendor_name' => $vendorName,
                'project_id' => $vendor->project_id,
                'requested_by' => auth()->user()->name ?? 'system',
                'reason' => request()->input('delete_reason'),
            ]);


            return redirect()->route('dev.vendors.index')
                ->with('success', 'Permintaan hapus vendor "' . $vendorName . '" telah dikirim ke HO.');

        } catch (\Exception $e) {
            Log::error('Vendor Destroy Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal menghapus vendor: ' . $e->getMessage());
        }
    }

    public function vendorApproveDelete($id)
    {
        if (!auth()->check() || !auth()->user()->isHO()) {
            return back()->with('error', 'Hanya HO yang dapat menyetujui penghapusan.');
        }

        $vendor = Vendor::findOrFail($id);
        $before = $vendor->toArray();
        if ($vendor->delete_status !== 'pending') {
            return back()->with('error', 'Vendor tidak dalam status menunggu penghapusan.');
        }

        $vendor->update([
            'delete_status' => 'approved',
            'delete_reviewed_by' => auth()->user()->name ?? 'HO',
            'delete_reviewed_at' => now(),
        ]);
        AuditLogger::log('vendor.delete_approved', $vendor, $before, $vendor->toArray(), [
            'vendor_name' => $vendor->nama,
            'project_id' => $vendor->project_id,
            'reviewed_by' => auth()->user()->name ?? 'HO',
        ]);


        $vendorName = $vendor->nama;
        $vendor->delete();

        return redirect()->route('dev.vendors.index')->with('success', 'Penghapusan vendor "' . $vendorName . '" disetujui.');
    }

    public function vendorRejectDelete($id)
    {
        if (!auth()->check() || !auth()->user()->isHO()) {
            return back()->with('error', 'Hanya HO yang dapat menolak penghapusan.');
        }

        $vendor = Vendor::findOrFail($id);
        $before = $vendor->toArray();
        if ($vendor->delete_status !== 'pending') {
            return back()->with('error', 'Vendor tidak dalam status menunggu penghapusan.');
        }

        $vendor->update([
            'delete_status' => 'rejected',
            'delete_reviewed_by' => auth()->user()->name ?? 'HO',
            'delete_reviewed_at' => now(),
            'delete_review_note' => request()->input('reject_reason'),
        ]);
        AuditLogger::log('vendor.delete_rejected', $vendor, $before, $vendor->toArray(), [
            'vendor_name' => $vendor->nama,
            'project_id' => $vendor->project_id,
            'reviewed_by' => auth()->user()->name ?? 'HO',
            'reason' => request()->input('reject_reason'),
        ]);


        return redirect()->route('dev.vendors.index')->with('success', 'Penghapusan vendor ditolak.');
    }

    /**
     * Vendor management - Import
     */
    public function vendorImport(Request $request)
    {
        try {
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            // TODO: Implement Excel import logic
            // Use Laravel Excel package or PHPExcel

            return back()->with('info', 'Fitur import Excel akan segera tersedia.');

        } catch (\Exception $e) {
            Log::error('Vendor Import Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal import Excel: ' . $e->getMessage());
        }
    }

    /**
     * Vendor management - Export
     */
    public function vendorExport()
    {
        try {
            // TODO: Implement Excel export logic
            
            return back()->with('info', 'Fitur export Excel akan segera tersedia.');

        } catch (\Exception $e) {
            Log::error('Vendor Export Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Vendor categories settings
     */
    public function vendorCategories()
    {
        try {
            $categories = [
                'Toko Bangunan',
                'Supplier Material',
                'Jasa Kontraktor',
                'Jasa Arsitek',
                'Jasa Konsultan',
                'Logistik',
                'Lainnya'
            ];

            return view('dev.settings.vendor-categories', compact('categories'));

        } catch (\Exception $e) {
            Log::error('Vendor Categories Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat kategori vendor: ' . $e->getMessage());
        }
    }

    /**
     * Update vendor categories
     */
    public function updateVendorCategories(Request $request)
    {
        try {
            // TODO: Save vendor categories to database or config file
            
            return back()->with('success', 'Kategori vendor berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error('Update Vendor Categories Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memperbarui kategori vendor: ' . $e->getMessage());
        }
    }

    /**
     * Voucher settings
     */
    public function voucherSettings()
    {
        try {
            $settings = [
                'auto_generate_number' => true,
                'number_format' => 'VCH/{PROJECT}/{YEAR}/{SEQ}',
                'default_payment_method' => 'transfer',
                'default_ppn_percentage' => 11,
                'require_approval' => true,
                'approvers' => ['Direktur Utama', 'Direktur Keuangan'],
                'auto_close_days' => 30
            ];

            return view('dev.settings.voucher', compact('settings'));

        } catch (\Exception $e) {
            Log::error('Voucher Settings Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat pengaturan voucher: ' . $e->getMessage());
        }
    }

    /**
     * Update voucher settings
     */
    public function updateVoucherSettings(Request $request)
    {
        try {
            // TODO: Save voucher settings to database or config file
            
            return back()->with('success', 'Pengaturan voucher berhasil diperbarui!');

        } catch (\Exception $e) {
            Log::error('Update Voucher Settings Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memperbarui pengaturan voucher: ' . $e->getMessage());
        }
    }

    /**
     * Voucher reports
     */
    public function reports(Request $request)
    {
        try {
            $projectId = $request->get('project_id');
            $voucherQuery = Voucher::with(['project', 'vendor']);

            if ($projectId) {
                $voucherQuery->where('project_id', $projectId);
            }

            $vouchers = $voucherQuery
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $stats = [
                'total' => (clone $voucherQuery)->count(),
                'total_amount' => (clone $voucherQuery)->sum('total_bayar'),
                'by_status' => (clone $voucherQuery)->select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'by_month' => (clone $voucherQuery)->select(DB::raw('DATE_FORMAT(tanggal, "%Y-%m") as month'), DB::raw('SUM(total_bayar) as total'))
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
            ];

            $projects = Project::orderBy('name')->get();

            return view('dev.reports.vouchers', compact('vouchers', 'stats', 'projects', 'projectId'));

        } catch (\Exception $e) {
            Log::error('Voucher Reports Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat laporan voucher: ' . $e->getMessage());
        }
    }

    /**
     * Voucher summary report
     */
    public function voucherSummaryReport(Request $request)
    {
        try {
            $projectId = $request->get('project_id');

            // Get summary by project
            $projectSummaryQuery = Voucher::select('project_id', 
                    DB::raw('COUNT(*) as voucher_count'),
                    DB::raw('SUM(total_bayar) as total_amount')
                )
                ->with('project')
                ->groupBy('project_id');

            if ($projectId) {
                $projectSummaryQuery->where('project_id', $projectId);
            }

            $projectSummary = $projectSummaryQuery->orderBy('total_amount', 'desc')->get();

            // Get summary by vendor
            $vendorSummaryQuery = Voucher::select('vendor_id',
                    DB::raw('COUNT(*) as voucher_count'),
                    DB::raw('SUM(total_bayar) as total_amount')
                )
                ->with('vendor')
                ->groupBy('vendor_id');

            if ($projectId) {
                $vendorSummaryQuery->where('project_id', $projectId);
            }

            $vendorSummary = $vendorSummaryQuery->orderBy('total_amount', 'desc')->get();

            // Get monthly trend
            $monthlyTrendQuery = Voucher::select(
                    DB::raw('DATE_FORMAT(tanggal, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as voucher_count'),
                    DB::raw('SUM(total_bayar) as total_amount')
                );

            if ($projectId) {
                $monthlyTrendQuery->where('project_id', $projectId);
            }

            $monthlyTrend = $monthlyTrendQuery->groupBy('month')->orderBy('month')->get();

            $projects = Project::orderBy('name')->get();

            return view('dev.reports.voucher-summary', compact(
                'projectSummary',
                'vendorSummary',
                'monthlyTrend',
                'projects',
                'projectId'
            ));

        } catch (\Exception $e) {
            Log::error('Voucher Summary Report Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat summary voucher: ' . $e->getMessage());
        }
    }

    /**
     * Vendor performance report
     */
    public function vendorPerformanceReport(Request $request)
    {
        try {
            $projectId = $request->get('project_id');

            $vendorsQuery = Vendor::withCount(['vouchers as total_vouchers' => function ($query) use ($projectId) {
                    if ($projectId) {
                        $query->where('project_id', $projectId);
                    }
                }])
                ->withSum(['vouchers as total_amount' => function($query) use ($projectId) {
                    $query->where('status', '!=', 'rejected');
                    if ($projectId) {
                        $query->where('project_id', $projectId);
                    }
                }], 'total_bayar')
                ->orderBy('total_amount', 'desc');

            $vendors = $vendorsQuery->get();

            $projects = Project::orderBy('name')->get();

            return view('dev.reports.vendor-performance', compact('vendors', 'projects', 'projectId'));

        } catch (\Exception $e) {
            Log::error('Vendor Performance Report Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat laporan performa vendor: ' . $e->getMessage());
        }
    }

    /**
     * Test view for voucher
     */
    public function testView($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            // Create dummy data for testing
            $vouchers = Voucher::where('project_id', $project->id)->take(5)->get();

            $vendors = Vendor::take(3)->get();

            return view('dev.test.voucher', compact('project', 'vouchers', 'vendors'));

        } catch (\Exception $e) {
            Log::error('Voucher Test View Error:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat test view: ' . $e->getMessage());
        }
    }

    // ==================== PROTECTED HELPER METHODS ====================

    /**
     * Generate voucher number
     */
    protected function generateVoucherNumber($project)
    {
        $projectCode = str_replace(' ', '-', $project->code);
        $year = date('Y');
        
        $count = Voucher::where('project_id', $project->id)
            ->whereYear('created_at', $year)
            ->count() + 1;
        
        return 'VCH/' . $projectCode . '/' . $year . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Helper: Get status label
     */
    protected function getStatusLabel($status)
    {
        return match($status) {
            'draft' => 'Draft',
            'submitted' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'paid' => 'Dibayar',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
            default => 'Unknown'
        };
    }

    /**
     * Helper: Get status color for CSS
     */
    protected function getStatusColor($status)
    {
        return match($status) {
            'draft' => '#6c757d',
            'submitted' => '#0dcaf0',
            'approved' => '#198754',
            'paid' => '#ffc107',
            'completed' => '#198754',
            'rejected' => '#dc3545',
            default => '#6c757d'
        };
    }

    /**
     * Helper: Get status color for Bootstrap badge
     */
    protected function getStatusBadgeColor($status)
    {
        return match($status) {
            'draft' => 'secondary',
            'submitted' => 'info',
            'approved' => 'success',
            'paid' => 'warning',
            'completed' => 'success',
            'rejected' => 'danger',
            default => 'secondary'
        };
    }
    
    /**
     * Helper: Get status alert class
     */
    protected function getStatusAlert($status)
    {
        return match($status) {
            'draft' => 'secondary',
            'submitted' => 'info',
            'approved' => 'success',
            'paid' => 'warning',
            'completed' => 'success',
            'rejected' => 'danger',
            default => 'secondary'
        };
    }

    // ==================== API METHODS ====================

    public function apiIndex($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            $vouchers = Voucher::where('project_id', $projectId)
                ->with(['vendor', 'items.budgetControl'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'vouchers' => $vouchers
            ]);

        } catch (\Exception $e) {
            Log::error('API Voucher Index Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data voucher'
            ], 500);
        }
    }

    public function apiStore(Request $request, $projectId)
    {
        return $this->store($request, $projectId);
    }

    public function apiShow($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::with(['vendor', 'items.budgetControl', 'project'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'voucher' => $voucher
            ]);

        } catch (\Exception $e) {
            Log::error('API Voucher Show Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail voucher'
            ], 500);
        }
    }

    public function apiUpdate(Request $request, $projectId, $id)
    {
        return $this->update($request, $projectId, $id);
    }

    public function apiDestroy($projectId, $id)
    {
        return $this->destroy($projectId, $id);
    }

    public function apiSubmit($projectId, $id)
    {
        return $this->submit($projectId, $id);
    }

    public function apiApprove($projectId, $id)
    {
        return $this->approve($projectId, $id);
    }

    public function apiReject(Request $request, $projectId, $id)
    {
        return $this->reject($request, $projectId, $id);
    }

    public function apiMarkAsPaid($projectId, $id)
    {
        return $this->markAsPaid($projectId, $id);
    }

    public function apiMarkAsCompleted($projectId, $id)
    {
        return $this->markAsCompleted($projectId, $id);
    }

    public function apiGetItems($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::findOrFail($id);
            
            $items = $voucher->items()->with('budgetControl')->orderBy('urutan')->get();

            return response()->json([
                'success' => true,
                'items' => $items
            ]);

        } catch (\Exception $e) {
            Log::error('API Voucher Items Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat items voucher'
            ], 500);
        }
    }

    public function apiStoreItem(Request $request, $projectId, $id)
    {
        return $this->storeItem($request, $projectId, $id);
    }

    public function apiUpdateItem(Request $request, $projectId, $id, $itemId)
    {
        return $this->updateItem($request, $projectId, $id, $itemId);
    }

    public function apiDestroyItem($projectId, $id, $itemId)
    {
        return $this->destroyItem($projectId, $id, $itemId);
    }

    public function apiUpdateItemStatus(Request $request, $projectId, $id, $itemId)
    {
        return $this->updateItemStatus($request, $projectId, $id, $itemId);
    }

    public function apiCalculateTotals($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::findOrFail($id);
            
            $voucher->calculateTotals();

            return response()->json([
                'success' => true,
                'total_tagihan' => $voucher->total_tagihan,
                'total_bayar' => $voucher->total_bayar,
                'formatted' => [
                    'total_tagihan' => 'Rp ' . number_format($voucher->total_tagihan, 0, ',', '.'),
                    'total_bayar' => 'Rp ' . number_format($voucher->total_bayar, 0, ',', '.')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Calculate Totals Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghitung total'
            ], 500);
        }
    }

    public function apiPrintData($projectId, $id)
    {
        try {
            $project = Project::findOrFail($projectId);
            $voucher = Voucher::with(['vendor', 'items.budgetControl', 'project'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'voucher' => $voucher,
                'project' => $project
            ]);

        } catch (\Exception $e) {
            Log::error('API Print Data Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data print'
            ], 500);
        }
    }

    public function apiBulkApprove(Request $request, $projectId)
    {
        return $this->bulkApprove($request, $projectId);
    }

    public function apiBulkReject(Request $request, $projectId)
    {
        return $this->bulkReject($request, $projectId);
    }

    public function apiProjectVouchers($projectId)
    {
        return $this->apiIndex($projectId);
    }

    public function apiVoucherSummary($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            
            $summary = [
                'total' => Voucher::where('project_id', $projectId)->count(),
                'total_amount' => Voucher::where('project_id', $projectId)->sum('total_bayar'),
                'by_status' => Voucher::where('project_id', $projectId)
                    ->select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'by_month' => Voucher::where('project_id', $projectId)
                    ->select(DB::raw('DATE_FORMAT(tanggal, "%Y-%m") as month'), DB::raw('SUM(total_bayar) as total'))
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('API Voucher Summary Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat summary voucher'
            ], 500);
        }
    }

    public function apiVendorIndex()
    {
        try {
            $projectId = request()->get('project_id');
            $query = Vendor::orderBy('nama');
            if ($projectId) {
                $query->forProject($projectId);
            }
            $vendors = $query->paginate(10);

            return response()->json([
                'success' => true,
                'vendors' => $vendors
            ]);

        } catch (\Exception $e) {
            Log::error('API Vendor Index Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data vendor'
            ], 500);
        }
    }

    public function apiVendorStore(Request $request)
    {
        return $this->vendorStore($request);
    }

    public function apiVendorShow($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);

            return response()->json([
                'success' => true,
                'vendor' => $vendor
            ]);

        } catch (\Exception $e) {
            Log::error('API Vendor Show Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail vendor'
            ], 500);
        }
    }

    public function apiVendorUpdate(Request $request, $id)
    {
        return $this->vendorUpdate($request, $id);
    }

    public function apiVendorDestroy($id)
    {
        return $this->vendorDestroy($id);
    }

    public function apiVendorSearch(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $projectId = $request->get('project_id');

            $query = Vendor::query();
            if ($projectId) {
                $query->forProject($projectId);
            }

            $vendors = $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', '%' . $search . '%')
                        ->orWhere('perusahaan', 'like', '%' . $search . '%')
                        ->orWhere('kode_vendor', 'like', '%' . $search . '%');
                })
                ->orderBy('nama')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'vendors' => $vendors
            ]);

        } catch (\Exception $e) {
            Log::error('API Vendor Search Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari vendor'
            ], 500);
        }
    }

    public function apiVendorImport(Request $request)
    {
        return $this->vendorImport($request);
    }

    public function apiVendorExport()
    {
        return $this->vendorExport();
    }

    public function apiDashboardStats()
    {
        try {
            $stats = [
                'total_vouchers' => Voucher::count(),
                'total_amount' => Voucher::sum('total_bayar'),
                'pending_approval' => Voucher::where('status', 'submitted')->count(),
                'by_status' => Voucher::select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray()
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('API Voucher Dashboard Stats Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik dashboard'
            ], 500);
        }
    }

    public function apiStatusChart()
    {
        try {
            $statusData = Voucher::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->map(function($item) {
                    return [
                        'status' => $item->status,
                        'label' => $this->getStatusLabel($item->status),
                        'count' => $item->count,
                        'color' => $this->getStatusColor($item->status)
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $statusData
            ]);

        } catch (\Exception $e) {
            Log::error('API Voucher Status Chart Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat chart status'
            ], 500);
        }
    }

    public function apiVoucherSettings()
    {
        try {
            $settings = [
                'auto_generate_number' => true,
                'number_format' => 'VCH/{PROJECT}/{YEAR}/{SEQ}',
                'default_payment_method' => 'transfer',
                'default_ppn_percentage' => 11,
                'require_approval' => true,
                'approvers' => ['Direktur Utama', 'Direktur Keuangan'],
                'auto_close_days' => 30
            ];

            return response()->json([
                'success' => true,
                'settings' => $settings
            ]);

        } catch (\Exception $e) {
            Log::error('API Voucher Settings Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat pengaturan voucher'
            ], 500);
        }
    }

    public function apiUpdateVoucherSettings(Request $request)
    {
        return $this->updateVoucherSettings($request);
    }

    public function apiVendorCategories()
    {
        try {
            $categories = [
                'Toko Bangunan',
                'Supplier Material',
                'Jasa Kontraktor',
                'Jasa Arsitek',
                'Jasa Konsultan',
                'Logistik',
                'Lainnya'
            ];

            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);

        } catch (\Exception $e) {
            Log::error('API Vendor Categories Error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat kategori vendor'
            ], 500);
        }
    }

    public function apiUpdateVendorCategories(Request $request)
    {
        return $this->updateVendorCategories($request);
    }
}
