<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Rab;
use App\Models\RabItem;
use App\Models\Data;
use App\Models\Client;
use App\Models\PurchaseOrder;
use App\Models\Lpb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use App\Support\AuditLogger;
use App\Support\NotificationService;

class RappController extends Controller
{
    protected function ensureHOApproval(): void
    {
        if (!auth()->check() || !auth()->user()->isHO()) {
            abort(403, 'Hanya HO yang dapat melakukan approval.');
        }
    }
    /**
     * Tampilkan daftar RAPP untuk 1 project
     */
    public function index($projectId)
    {
        try {
            $project = Project::with('client')->findOrFail($projectId);

            // Semua RAPP milik project ini (pakai paginate supaya cocok dengan $rabs->total() di blade)
            $rabs = Rab::where('project_id', $projectId)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return view('dev.rapps.index', compact('project', 'rabs'));
        } catch (\Exception $e) {
            Log::error('RAPP Index Error:', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memuat daftar RAPP: ' . $e->getMessage());
        }
    }

    /**
     * Form create RAPP
     */
    public function create($projectId)
    {
        try {
            $project = Project::with('client')->findOrFail($projectId);

            // Hanya izinkan 1 RAPP aktif (draft/submitted) per project
            $existingRab = $project->rabs()->latest()->first();
            if ($existingRab && in_array($existingRab->status, ['draft', 'submitted'])) {
                return redirect()
                    ->route('dev.rab-baseline.index', $projectId)
                    ->with('error', 'Hanya boleh ada 1 RAPP aktif (draft/submitted) per project.');
            }

            // BUAT DEFAULT NAME UNTUK RAPP
            $clientName  = $project->client ? $project->client->name : null;
            $baseName    = $project->name ?? $clientName ?? ('Proyek ' . $project->id);
            $defaultName = 'RAPP ' . $baseName;

            return view('dev.rapps.create', compact('project', 'defaultName'));
        } catch (\Exception $e) {
            Log::error('RAPP Create Error:', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal membuka form RAPP baru: ' . $e->getMessage());
        }
    }

    /**
     * Simpan RAPP baru
     */
    public function store(Request $request, $projectId)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string', // masih divalidasi, tapi tidak disimpan ke DB
            'version'     => 'nullable|string|max:20',
        ]);

        try {
            $project = Project::findOrFail($projectId);

            DB::beginTransaction();

            $rab = new Rab();
            $rab->project_id   = $project->id;
            $rab->name         = $request->name;
            // $rab->description = $request->description; // â›” tabel rabs tidak punya kolom description
            $rab->version      = $request->version ?: '1.0';
            $rab->status       = 'draft';
            $rab->total_budget = 0;
            $rab->created_by   = auth()->id();
            $rab->save();

            AuditLogger::log('rapp.created', $rab, null, $rab->toArray(), [
                'project_id' => $project->id,
            ]);

            DB::commit();

            return redirect()
                ->route('dev.rab-baseline.show', [$project->id, $rab->id])
                ->with('success', 'RAPP baru berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('RAPP Store Error:', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menyimpan RAPP: ' . $e->getMessage());
        }
    }

    /**
     * DETAIL RAPP
     * - Tabel per kategori
     * - Data master tersedia untuk modal tambah item
     */
    public function show($projectId, $rabId)
    {
        try {
            $project = Project::with('client')->findOrFail($projectId);

            $rab = Rab::with(['items.data', 'items.voucherItems'])
                ->where('project_id', $projectId)
                ->findOrFail($rabId);

            Log::info('RAPP Show Data Check', [
                'rab_id'       => $rab->id,
                'items_count'  => $rab->items->count(),
                'first_item'   => optional($rab->items->first())->id,
                'first_data'   => optional(optional($rab->items->first())->data)->id,
            ]);

            // PERBAIKAN: Pastikan total budget konsisten sebelum ditampilkan
            $this->ensureConsistentData($rab);

            // Group per kategori (helper di model Rab)
            $itemsByCategory = $rab->getItemsGroupedByCategory();

            // Breakdown per kategori (untuk summary / chart)
            $breakdown = $rab->getBreakdownByCategory();

            // PERBAIKAN: Validasi konsistensi breakdown dengan total
            $this->validateBreakdownConsistency($rab, $breakdown);

            // Perbandingan dengan budget project (helper di model Rab)
            $comparison = $rab->compareWithProjectBudget();

            // Data master yang belum dipakai di RAPP ini
            $addedItemIds = $rab->items->pluck('data_id')->filter()->all();

            $availableItems = Data::when(count($addedItemIds) > 0, function ($q) use ($addedItemIds) {
                    $q->whereNotIn('id', $addedItemIds);
                })
                ->orderBy('kategori')
                ->orderBy('nama')
                ->get();

            // Daftar kategori untuk filter di modal
            $categories = Data::distinct()->pluck('kategori')->toArray();

            // Flag boleh edit
            $editable = method_exists($rab, 'canEdit')
                ? $rab->canEdit()
                : in_array($rab->status, ['draft', 'rejected', 'approved']);

            $approvedPos = PurchaseOrder::with('vendor')
                ->where('project_id', $projectId)
                ->where('rab_id', $rabId)
                ->where('status', 'approved')
                ->orderByDesc('id')
                ->get();
            $approvedLpbs = Lpb::with('vendor', 'purchaseOrder')
                ->where('project_id', $projectId)
                ->where('rab_id', $rabId)
                ->where('status', 'approved')
                ->orderByDesc('id')
                ->get();

            return view('dev.rapps.show', compact(
                'project',
                'rab',
                'itemsByCategory',
                'breakdown',
                'comparison',
                'availableItems',
                'categories',
                'editable',
                'approvedPos',
                'approvedLpbs'
            ));
        } catch (\Exception $e) {
            Log::error('RAPP Show Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memuat detail RAPP: ' . $e->getMessage());
        }
    }

    /**
     * Tabel RAPP (halaman khusus tabel item + rekap).
     */
    public function table($projectId, $rabId)
    {
        try {
            $project = Project::with('client')->findOrFail($projectId);

            $rab = Rab::with(['items.data'])
                ->where('project_id', $projectId)
                ->findOrFail($rabId);

            $this->ensureConsistentData($rab);

            $items = $rab->items
                ->sort(function ($a, $b) {
                    $aKategori = strtoupper((string) ($a->data->kategori ?? ''));
                    $bKategori = strtoupper((string) ($b->data->kategori ?? ''));
                    if ($aKategori !== $bKategori) {
                        return $aKategori <=> $bKategori;
                    }

                    $aKode = strtoupper((string) ($a->data->kode ?? ''));
                    $bKode = strtoupper((string) ($b->data->kode ?? ''));
                    if ($aKode !== $bKode) {
                        return $aKode <=> $bKode;
                    }

                    return ((int) $a->id) <=> ((int) $b->id);
                })
                ->values();

            $categoryNameMap = [
                'MT' => 'Material',
                'JS' => 'Jasa',
                'AL' => 'Alat',
                'HO' => 'Head Office',
                'SR' => 'Sirkulasi',
                'SB' => 'Subkon',
            ];

            $categoryTotals = $items
                ->groupBy(function ($item) use ($categoryNameMap) {
                    $raw = strtoupper((string) ($item->data->kategori ?? ''));
                    if ($raw === '') {
                        return 'Lainnya';
                    }
                    return $categoryNameMap[$raw] ?? $raw;
                })
                ->map(function ($group) {
                    return (float) $group->sum(function ($item) {
                        return (float) ($item->volume ?? 0) * (float) ($item->harga_satuan ?? 0);
                    });
                })
                ->toArray();

            $grandTotal = (float) array_sum($categoryTotals);
            $projectBudget = (float) ($project->budget ?? 0);
            $budgetPercent = $projectBudget > 0 ? ($grandTotal / $projectBudget) * 100 : 0;

            return view('dev.rab-baseline.table', compact(
                'project',
                'rab',
                'items',
                'categoryTotals',
                'grandTotal',
                'projectBudget',
                'budgetPercent'
            ));
        } catch (\Exception $e) {
            Log::error('RAPP Table Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memuat tabel RAPP: ' . $e->getMessage());
        }
    }

    /**
     * Form edit info RAPP (nama, deskripsi, versi)
     */
    public function edit($projectId, $rabId)
    {
        try {
            $project = Project::with('client')->findOrFail($projectId);
            $rab = Rab::findOrFail($rabId);

            if (method_exists($rab, 'canEdit') && !$rab->canEdit()) {
                return back()->with('error', 'RAPP tidak dapat diedit karena status: ' . $rab->status);
            }

            return view('dev.rapps.edit', compact('project', 'rab'));
        } catch (\Exception $e) {
            Log::error('RAPP Edit Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal membuka form edit RAPP: ' . $e->getMessage());
        }
    }

    /**
     * Update meta RAPP
     */
    public function update(Request $request, $projectId, $rabId)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string', // masih boleh diisi, tapi tidak disimpan
            'version'     => 'nullable|string|max:20',
        ]);

        try {
            $project = Project::findOrFail($projectId);
            $rab     = Rab::findOrFail($rabId);
            $before = $rab->toArray();

            if (method_exists($rab, 'canEdit') && !$rab->canEdit()) {
                return back()->with('error', 'RAPP tidak dapat diedit karena status: ' . $rab->status);
            }

            $rab->name        = $request->name;
            // $rab->description = $request->description; // â›” kolom description tidak ada di tabel
            $rab->version     = $request->version ?: $rab->version;
            $rab->save();

            AuditLogger::log('rapp.updated', $rab, $before, $rab->toArray(), [
                'project_id' => $project->id,
            ]);

            return redirect()
                ->route('dev.rab-baseline.show', [$project->id, $rab->id])
                ->with('success', 'RAPP berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('RAPP Update Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal mengupdate RAPP: ' . $e->getMessage());
        }
    }

    /**
     * Hapus RAPP (hanya draft / rejected)
     */
    public function destroy($projectId, $rabId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $rab     = Rab::findOrFail($rabId);

            if (!in_array($rab->status, ['draft', 'rejected'])) {
                return back()->with('error', 'Hanya RAPP draft atau rejected yang dapat dihapus.');
            }

            $rab->delete();

            return redirect()
                ->route('dev.rab-baseline.index', $project->id)
                ->with('success', 'RAPP berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('RAPP Destroy Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus RAPP: ' . $e->getMessage());
        }
    }

    /**
     * Duplikasi RAPP beserta item
     */
    public function duplicate($projectId, $rabId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $rab     = Rab::with('items')->findOrFail($rabId);

            DB::beginTransaction();

            $newRab = $rab->replicate();
            $newRab->status       = 'draft';
            $newRab->version      = $this->incrementVersion($rab->version ?? '1.0');
            $newRab->created_by   = auth()->id();
            $newRab->created_at   = now();
            $newRab->updated_at   = now();
            $newRab->submitted_at = null;
            $newRab->approved_at  = null;
            $newRab->rejected_at  = null;
            $newRab->save();

            foreach ($rab->items as $item) {
                $newItem = $item->replicate();
                $newItem->rab_id     = $newRab->id;
                $newItem->created_at = now();
                $newItem->updated_at = now();
                $newItem->save();
            }

            DB::commit();

            return redirect()
                ->route('dev.rab-baseline.show', [$project->id, $newRab->id])
                ->with('success', 'RAPP berhasil diduplikasi.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('RAPP Duplicate Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menduplikasi RAPP: ' . $e->getMessage());
        }
    }

    /**
     * Submit ke Head Office
     */
    public function submit($projectId, $rabId)
    {
        try {
            $project = Project::findOrFail($projectId);

            DB::beginTransaction();

            // Lock RAPP agar tidak ada race condition saat submit
            $rab = Rab::where('id', $rabId)->lockForUpdate()->firstOrFail();

            if (method_exists($rab, 'canSubmit') && !$rab->canSubmit()) {
                DB::rollBack();
                return back()->with('error', 'RAPP tidak dapat disubmit. Pastikan status draft/rejected dan sudah ada items.');
            }

            if ($rab->items()->count() === 0) {
                DB::rollBack();
                return back()->with('error', 'RAPP harus memiliki minimal 1 item sebelum disubmit.');
            }

            // âœ… Wajib: semua item sudah diisi Volume RAPP dan Harga Satuan sebelum submit
            $invalidCount = $rab->items()
                ->where(function ($q) {
                    $q->whereNull('volume')->orWhere('volume', '<=', 0)
                      ->orWhereNull('harga_satuan')->orWhere('harga_satuan', '<=', 0);
                })
                ->count();

            if ($invalidCount > 0) {
                DB::rollBack();
                return back()->with('error', "Masih ada {$invalidCount} item yang belum diisi Volume RAPP / Harga Satuan. Lengkapi dulu sebelum Submit ke HO.");
            }

            // PERBAIKAN: Pastikan data konsisten sebelum submit
            $this->ensureConsistentData($rab);

            // Update total + breakdown terakhir sebelum submit (akurasi)
            $rab->updateTotals();

            $before = $rab->toArray();
            $rab->status       = 'submitted';
            $rab->submitted_at = now();
            $rab->submitted_by = auth()->id();
            $rab->save();

            AuditLogger::log('rapp.submitted', $rab, $before, $rab->toArray(), [
                'project_id' => $project->id,
            ]);

            $projectName = $project->name ?? null;
            NotificationService::notifyHO(
                'RAPP Diajukan',
                'RAPP ' . ($rab->name ?? $rab->id) . ' diajukan' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
                route('dev.rab-baseline.show', [$project->id, $rab->id]),
                'approval',
                [
                    'doc_type' => 'rapp',
                    'doc_id' => $rab->id,
                    'project_id' => $project->id,
                ]
            );

            DB::commit();

            return back()->with('success', 'RAPP berhasil disubmit ke Head Office.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('RAPP Submit Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal submit RAPP: ' . $e->getMessage());
        }
    }

    /**
     * Approve RAPP
     */
    public function approve($projectId, $rabId)
    {
        $this->ensureHOApproval();

        try {
            $project = Project::findOrFail($projectId);
            $rab     = Rab::findOrFail($rabId);

            if (method_exists($rab, 'canApprove') && !$rab->canApprove()) {
                return back()->with('error', 'Hanya RAPP dengan status submitted yang dapat di-approve.');
            }

            // PERBAIKAN: Pastikan data konsisten sebelum approve
            $this->ensureConsistentData($rab);

            $before = $rab->toArray();
            $rab->status      = 'approved';
            $rab->approved_at = now();
            $rab->approved_by = auth()->id();
            $rab->save();

            AuditLogger::log('rapp.approved', $rab, $before, $rab->toArray(), [
                'project_id' => $project->id,
            ]);

            NotificationService::notifySubmitter(
                $rab,
                'RAPP Disetujui',
                'RAPP ' . ($rab->name ?? $rab->id) . ' disetujui.',
                route('dev.rab-baseline.show', [$project->id, $rab->id]),
                'approval',
                [
                    'doc_type' => 'rapp',
                    'doc_id' => $rab->id,
                    'project_id' => $project->id,
                ]
            );

            return back()->with('success', 'RAPP berhasil di-approve.');
        } catch (\Exception $e) {
            Log::error('RAPP Approve Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal approve RAPP: ' . $e->getMessage());
        }
    }

    /**
     * Reject RAPP
     */
    public function reject($projectId, $rabId, Request $request)
    {
        $this->ensureHOApproval();

        try {
            $project = Project::findOrFail($projectId);
            $rab     = Rab::findOrFail($rabId);

            if (method_exists($rab, 'canReject') && !$rab->canReject()) {
                return back()->with('error', 'Hanya RAPP dengan status submitted yang dapat di-reject.');
            }

            $before = $rab->toArray();
            $rab->status          = 'rejected';
            $rab->rejected_at     = now();
            $rab->rejected_by     = auth()->id();
            $rab->rejected_reason = $request->input('rejected_reason');
            $rab->save();

            AuditLogger::log('rapp.rejected', $rab, $before, $rab->toArray(), [
                'project_id' => $project->id,
            ]);

            $reason = trim((string) $request->input('rejected_reason', ''));
            $message = 'RAPP ' . ($rab->name ?? $rab->id) . ' ditolak.';
            if ($reason !== '') {
                $message .= ' Alasan: ' . $reason;
            }
            NotificationService::notifySubmitter(
                $rab,
                'RAPP Ditolak',
                $message,
                route('dev.rab-baseline.show', [$project->id, $rab->id]),
                'approval',
                [
                    'doc_type' => 'rapp',
                    'doc_id' => $rab->id,
                    'project_id' => $project->id,
                    'rejected_reason' => $reason,
                ]
            );

            return back()->with('success', 'RAPP berhasil di-reject. Silakan lakukan revisi.');
        } catch (\Exception $e) {
            Log::error('RAPP Reject Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal reject RAPP: ' . $e->getMessage());
        }
    }

    /**
     * TAMBAH 1 item manual (bukan dari Data master)
     */
    public function storeItem(Request $request, $projectId, $rabId)
    {
        $request->validate([
            'uraian'       => 'required|string|max:255',
            'satuan'       => 'required|string|max:50',
            'volume'       => 'required|numeric|min:0.0001',
            'harga_satuan' => 'required|numeric|min:0',
        ]);

        try {
            $project = Project::findOrFail($projectId);
            $rab     = Rab::findOrFail($rabId);

            if (method_exists($rab, 'canEdit') && !$rab->canEdit()) {
                return back()->with('error', 'RAPP tidak dapat diedit karena status: ' . $rab->status);
            }

            DB::beginTransaction();

            $item = new RabItem();
            $item->rab_id        = $rab->id;
            $item->uraian_manual = $request->uraian;
            $item->satuan        = $request->satuan;
            $item->volume        = $request->volume;
            $item->harga_satuan  = $request->harga_satuan;
            $item->created_by    = auth()->id();
            $item->save();

            // update total dan pastikan konsisten
            $rab->updateTotals();
            $reapprovalRequired = $this->markForReapprovalIfApproved($rab);
            $rab->save();

            DB::commit();

            $message = 'Item berhasil ditambahkan.';
            if ($reapprovalRequired) {
                $message .= ' Status RAPP dikembalikan ke submitted, menunggu approval HO.';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('RAPP Store Item Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menambahkan item: ' . $e->getMessage());
        }
    }

    /**
     * BULK tambah items dari Data master (modal /dev/data)
     */
    public function bulkStoreItems(Request $request, $projectId, $rabId)
    {
        $request->validate([
            'item_ids'   => 'required|array',
            'item_ids.*' => 'exists:data,id',
        ]);

        try {
            $project = Project::findOrFail($projectId);
            $rab     = Rab::findOrFail($rabId);

            $this->guardEditable($rab);

            DB::beginTransaction();

            $addedItems = [];
            $skipped    = 0;

            foreach ($request->item_ids as $dataId) {
                $dataItem = Data::find($dataId);
                if (!$dataItem) {
                    $skipped++;
                    continue;
                }

                // Cegah duplikat (level aplikasi)
                $exists = RabItem::where('rab_id', $rab->id)
                    ->where('data_id', $dataItem->id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                try {
                    $item = new RabItem();
                    $item->rab_id       = $rab->id;
                    $item->data_id      = $dataItem->id;
                    $item->satuan       = $dataItem->satuan ?? $dataItem->satuan_default ?? null;

                    // âœ… wajib diisi user sebelum submit HO
                    $item->volume       = 0;
                    $item->harga_satuan = 0;

                    $item->created_by   = auth()->id();
                    $item->save();

                    $addedItems[] = $item->id;
                } catch (\Illuminate\Database\QueryException $qe) {
                    $skipped++;
                    continue;
                }
            }

            // PERBAIKAN: Pastikan update totals dipanggil
            $rab->updateTotals();
            $reapprovalRequired = $this->markForReapprovalIfApproved($rab);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menambahkan ' . count($addedItems) . ' item ke RAPP. (skip: ' . $skipped . ')' .
                    ($reapprovalRequired ? ' Status RAPP dikembalikan ke submitted, menunggu approval HO.' : ''),
                'data'    => [
                    'added_items' => $addedItems,
                    'skipped'     => $skipped,
                ],
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $he) {
            return response()->json([
                'success' => false,
                'message' => $he->getMessage(),
            ], $he->getStatusCode());
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('RAPP Bulk Store Items Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * âœ… BULK DELETE items (dipanggil route DELETE /items/bulk-delete)
     * Memperbaiki error: Call to undefined method bulkDestroyItems()
     */
    public function bulkDestroyItems(Request $request, $projectId, $rabId)
    {
        $request->validate([
            'item_ids'   => 'required|array',
            'item_ids.*' => 'integer',
        ]);

        try {
            $project = Project::findOrFail($projectId);
            $rab     = Rab::findOrFail($rabId);

            // Ikuti canEdit() agar konsisten untuk HO/staff.
            if (method_exists($rab, 'canEdit') && !$rab->canEdit()) {
                return back()->with('error', 'RAPP tidak dapat diedit karena status: ' . ($rab->status ?? '-'));
            }

            $ids = array_values(array_unique(array_filter($request->item_ids)));

            if (count($ids) === 0) {
                return back()->with('error', 'Tidak ada item yang dipilih.');
            }

            DB::beginTransaction();

            $deleted = RabItem::where('rab_id', $rab->id)
                ->whereIn('id', $ids)
                ->delete();

            // PERBAIKAN: Pastikan update totals dipanggil
            $rab->updateTotals();
            $reapprovalRequired = $this->markForReapprovalIfApproved($rab);

            DB::commit();

            $message = "Berhasil menghapus {$deleted} item terpilih.";
            if ($reapprovalRequired) {
                $message .= ' Status RAPP dikembalikan ke submitted, menunggu approval HO.';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('RAPP Bulk Destroy Items Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus item terpilih: ' . $e->getMessage());
        }
    }

    /**
     * Update 1 item (volume / harga_satuan) via AJAX
     */
    public function updateItem(Request $request, $projectId, $rabId, $itemId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $rab     = Rab::findOrFail($rabId);

            $this->guardEditable($rab);

            $item = RabItem::where('rab_id', $rab->id)->findOrFail($itemId);

            $field = $request->input('field');
            $raw   = $request->input('value');

            if (!in_array($field, ['volume', 'harga_satuan'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Field tidak valid.',
                ], 422);
            }

            // âœ… sanitize input (support "25.000.000" / "25,5" dll)
            $value = $this->normalizeNumber($raw);

            if ($field === 'volume') {
                if ($value <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Volume harus lebih besar dari 0.',
                    ], 422);
                }
                if ($value > 1000000) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Volume terlalu besar.',
                    ], 422);
                }
                $item->volume = $value;
            } else {
                if ($value <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Harga satuan harus lebih besar dari 0.',
                    ], 422);
                }
                if ($value > 999999999999) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Harga satuan terlalu besar.',
                    ], 422);
                }
                $item->harga_satuan = $value;
            }

            $item->save();

            // PERBAIKAN: Update total/breakdown dan validasi konsistensi
            $rab->updateTotals();
            $reapprovalRequired = $this->markForReapprovalIfApproved($rab);
            
            // Validasi konsistensi setelah update
            $this->validateConsistencyAfterUpdate($rab);

            return response()->json([
                'success'      => true,
                'message'      => 'Item berhasil diperbarui.' .
                    ($reapprovalRequired ? ' Status RAPP dikembalikan ke submitted, menunggu approval HO.' : ''),
                'item'         => [
                    'id'           => $item->id,
                    'volume'       => (float) $item->volume,
                    'harga_satuan' => (float) $item->harga_satuan,
                    'subtotal'     => (float) ($item->volume * $item->harga_satuan),
                ],
                'total_budget' => (float) $rab->total_budget,
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $he) {
            return response()->json([
                'success' => false,
                'message' => $he->getMessage(),
            ], $he->getStatusCode());
        } catch (\Exception $e) {
            Log::error('RAPP Update Item Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'item_id'    => $itemId,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus 1 item dari RAPP
     */
    public function destroyItem($projectId, $rabId, $itemId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $rab     = Rab::findOrFail($rabId);
            $item    = RabItem::where('rab_id', $rab->id)->findOrFail($itemId);

            if (method_exists($rab, 'canEdit') && !$rab->canEdit()) {
                return back()->with('error', 'RAPP tidak dapat diedit karena status: ' . $rab->status);
            }

            $item->delete();

            // PERBAIKAN: Pastikan update totals dipanggil
            $rab->updateTotals();
            $reapprovalRequired = $this->markForReapprovalIfApproved($rab);

            $message = 'Item berhasil dihapus.';
            if ($reapprovalRequired) {
                $message .= ' Status RAPP dikembalikan ke submitted, menunggu approval HO.';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('RAPP Destroy Item Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'item_id'    => $itemId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    /**
     * Quick add item dari Data master (bukan bulk)
     */
    public function quickAddItem(Request $request, $projectId, $rabId)
    {
        $request->validate([
            'data_id'      => 'required|exists:data,id',
            'volume'       => 'required|numeric|min:0.0001',
            'harga_satuan' => 'required|numeric|min:0',
        ]);

        try {
            $project = Project::findOrFail($projectId);
            $rab     = Rab::findOrFail($rabId);

            // Cek apakah RAPP bisa diedit
            if (method_exists($rab, 'canEdit') && !$rab->canEdit()) {
                return response()->json([
                    'success' => false,
                    'message' => 'RAPP tidak dapat diedit karena status: ' . $rab->status
                ], 403);
            }

            $dataItem = Data::findOrFail($request->data_id);

            DB::beginTransaction();

            // Cek duplikat
            $existing = RabItem::where('rab_id', $rab->id)
                ->where('data_id', $dataItem->id)
                ->first();

            if ($existing) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Item dengan kode ' . $dataItem->kode . ' sudah ada di RAPP ini.'
                ], 422);
            }

            // Urutan terakhir
            $maxUrutan = (int) RabItem::where('rab_id', $rab->id)->max('urutan');
            $urutan = $maxUrutan + 1;

            // Buat item baru
            $item = new RabItem();
            $item->rab_id         = $rab->id;
            $item->data_id        = $dataItem->id;
            $item->item_type      = $dataItem->kode_kategori ?? $dataItem->kategori ?? 'MT';
            $item->volume         = $request->volume;
            $item->satuan         = $dataItem->satuan ?? $dataItem->satuan_default;
            $item->harga_satuan   = $request->harga_satuan;
            $item->urutan         = $urutan;
            $item->keterangan     = null;
            $item->created_by     = auth()->id();
            $item->save();

            // Update total RAPP dan validasi konsistensi
            $rab->updateTotals();
            $reapprovalRequired = $this->markForReapprovalIfApproved($rab);
            $this->validateConsistencyAfterUpdate($rab);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil ditambahkan.' .
                    ($reapprovalRequired ? ' Status RAPP dikembalikan ke submitted, menunggu approval HO.' : ''),
                'item' => [
                    'id'           => $item->id,
                    'kode'         => $dataItem->kode,
                    'nama'         => $dataItem->uraian ?? $dataItem->nama,
                    'volume'       => (float) $item->volume,
                    'harga_satuan' => (float) $item->harga_satuan,
                    'subtotal'     => (float) ($item->volume * $item->harga_satuan),
                ],
                'total_budget' => (float) $rab->total_budget
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('RAPP Quick Add Item Error:', [
                'project_id' => $projectId,
                'rab_id'     => $rabId,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Breakdown per kategori (AJAX)
     */
    public function getBreakdown($projectId, $rabId)
    {
        try {
            $rab       = Rab::findOrFail($rabId);
            $breakdown = $rab->getBreakdownByCategory();

            return response()->json([
                'success'   => true,
                'breakdown' => $breakdown,
            ]);
        } catch (\Exception $e) {
            Log::error('RAPP Breakdown Error:', [
                'rab_id' => $rabId,
                'error'  => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat breakdown: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Perbandingan dengan budget project (AJAX)
     */
    public function getComparison($projectId, $rabId)
    {
        try {
            $rab        = Rab::findOrFail($rabId);
            $comparison = $rab->compareWithProjectBudget();

            return response()->json([
                'success'    => true,
                'comparison' => $comparison,
            ]);
        } catch (\Exception $e) {
            Log::error('RAPP Comparison Error:', [
                'rab_id' => $rabId,
                'error'  => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat perbandingan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GLOBAL INDEX /dev/rapp
     */
    public function globalIndex(Request $request)
    {
        try {
            $query = Project::with(['client', 'rabs.items'])
                ->has('rabs')
                ->withCount(['rabs as rab_items_count' => function ($q) {
                    $q->join('rab_items', 'rabs.id', '=', 'rab_items.rab_id')
                        ->select(DB::raw('COUNT(rab_items.id)'));
                }])
                ->withSum('rabs as rab_total', 'total_budget');

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Filter status
            if ($request->filled('status')) {
                $status = $request->status;
                $query->whereHas('rabs', function ($q) use ($status) {
                    $q->where('status', $status);
                });
            }

            // Filter client
            if ($request->filled('client_id')) {
                $clientId = $request->client_id;
                $query->where('client_id', $clientId);
            }

            // Sorting
            $sort = $request->get('sort', 'newest');
            switch ($sort) {
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'name_asc':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'rab_high':
                    $query->orderBy('rab_total', 'desc');
                    break;
                case 'rab_low':
                    $query->orderBy('rab_total', 'asc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $projects        = $query->paginate(12);
            $projectsWithRab = Project::has('rabs')->count();
            $totalRabItems   = RabItem::count();
            $globalRabTotal  = Rab::sum('total_budget');

            $statuses = ['draft', 'submitted', 'approved', 'rejected'];
            $clients  = Client::orderBy('name')->get();

            return view('dev.rapps.global-index', compact(
                'projects',
                'projectsWithRab',
                'totalRabItems',
                'globalRabTotal',
                'statuses',
                'clients'
            ));
        } catch (\Exception $e) {
            Log::error('Global RAPP Index Error:', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memuat data RAPP global: ' . $e->getMessage());
        }
    }

    /**
     * GLOBAL SUMMARY /dev/rapp/summary
     */
    public function globalSummary()
    {
        try {
            $projects = Project::with(['client', 'rabs.items'])->get();

            $totalProjects        = $projects->count();
            $projectsWithRapp     = $projects->filter(fn ($p) => $p->rabs->count() > 0)->count();
            $totalRab             = Rab::count();
            $totalRabItems        = RabItem::count();
            $totalRabBudget       = Rab::sum('total_budget');
            $averageRabPerProject = $projectsWithRapp > 0
                ? $totalRabBudget / $projectsWithRapp
                : 0;

            $statusStats = Rab::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $categoryStats = RabItem::with('data')
                ->get()
                ->groupBy(function ($item) {
                    return $item->data->kategori ?? 'Uncategorized';
                })
                ->map(function ($items, $category) {
                    return [
                        'category' => $category,
                        'total'    => $items->sum('total_harga'),
                        'count'    => $items->count(),
                    ];
                })
                ->values();

            return view('dev.rapps.global-summary', compact(
                'projects',
                'totalProjects',
                'projectsWithRapp',
                'totalRab',
                'totalRabItems',
                'totalRabBudget',
                'averageRabPerProject',
                'statusStats',
                'categoryStats'
            ));
        } catch (\Exception $e) {
            Log::error('Global RAPP Summary Error:', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memuat summary RAPP global: ' . $e->getMessage());
        }
    }

    /**
     * Laporan RAPP per project
     */
    public function projectReport($projectId)
    {
        try {
            $project = Project::with(['client', 'rabs.items.data'])
                ->findOrFail($projectId);

            $rabs = $project->rabs()
                ->with('items.data')
                ->orderBy('created_at', 'desc')
                ->get();

            return view('dev.rapps.project-report', compact('project', 'rabs'));
        } catch (\Exception $e) {
            Log::error('Project RAPP Report Error:', [
                'project_id' => $projectId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memuat laporan RAPP project: ' . $e->getMessage());
        }
    }

    // =========================
    // PERBAIKAN BARU: METHOD UNTUK KONSISTENSI DATA
    // =========================

    /**
     * Pastikan data RAPP konsisten sebelum ditampilkan
     */
    private function ensureConsistentData(Rab $rab): void
    {
        // Hitung total dari items secara real-time
        $calculatedTotal = $rab->items()->sum(
            DB::raw('COALESCE(volume, 0) * COALESCE(harga_satuan, 0)')
        );

        $calculatedTotal = (float) $calculatedTotal;
        $storedTotal = (float) ($rab->total_budget ?? 0);

        // Jika selisih > 1 (toleransi rounding), perbaiki
        if (abs($calculatedTotal - $storedTotal) > 1) {
            Log::warning('RAPP Data Inconsistency Detected', [
                'rab_id' => $rab->id,
                'rab_name' => $rab->name,
                'stored_total' => $storedTotal,
                'calculated_total' => $calculatedTotal,
                'difference' => $calculatedTotal - $storedTotal,
                'items_count' => $rab->items()->count()
            ]);

            // Perbaiki data
            $rab->total_budget = $calculatedTotal;
            $rab->breakdown = $rab->getBreakdownByCategory();
            $rab->save();

            Log::info('RAPP Data Repaired', [
                'rab_id' => $rab->id,
                'new_total' => $calculatedTotal
            ]);
        }
    }

    /**
     * Validasi konsistensi breakdown dengan total
     */
    private function validateBreakdownConsistency(Rab $rab, array $breakdown): void
    {
        $breakdownTotal = collect($breakdown)->sum('total_harga');
        $rabTotal = (float) $rab->total_budget;

        if (abs($breakdownTotal - $rabTotal) > 1) {
            Log::warning('RAPP Breakdown Inconsistency', [
                'rab_id' => $rab->id,
                'rab_total' => $rabTotal,
                'breakdown_total' => $breakdownTotal,
                'difference' => $breakdownTotal - $rabTotal
            ]);
        }
    }

    /**
     * Validasi konsistensi setelah update
     */
    private function validateConsistencyAfterUpdate(Rab $rab): void
    {
        // Hitung total dari items
        $calculatedTotal = $rab->items()->sum(
            DB::raw('COALESCE(volume, 0) * COALESCE(harga_satuan, 0)')
        );

        $calculatedTotal = (float) $calculatedTotal;
        $storedTotal = (float) $rab->total_budget;

        // Jika tidak konsisten, log warning
        if (abs($calculatedTotal - $storedTotal) > 1) {
            Log::warning('RAPP Inconsistency After Update', [
                'rab_id' => $rab->id,
                'stored_total' => $storedTotal,
                'calculated_total' => $calculatedTotal,
                'difference' => $calculatedTotal - $storedTotal
            ]);
        }
    }

    /**
     * Jika RAPP sudah approved lalu diubah, kembalikan ke submitted untuk approval ulang.
     */
    private function markForReapprovalIfApproved(Rab $rab): bool
    {
        if ($rab->status !== 'approved') {
            return false;
        }

        $rab->status = 'submitted';
        $rab->approved_at = null;
        $rab->approved_by = null;
        $rab->save();

        return true;
    }

    /**
     * Guard: pastikan RAPP masih editable sesuai aturan canEdit().
     */
    private function guardEditable(Rab $rab): void
    {
        if (method_exists($rab, 'canEdit') && !$rab->canEdit()) {
            abort(403, 'RAPP terkunci (status: ' . $rab->status . ')');
        }
    }

    /**
     * Helper naikkan versi (1.0 -> 1.1)
     */
    protected function incrementVersion($version)
    {
        try {
            [$major, $minor] = explode('.', $version);
            $minor = (int) $minor + 1;

            return $major . '.' . $minor;
        } catch (\Exception $e) {
            return '1.0';
        }
    }

    /**
     * HO check (role=ho atau is_admin)
     */
    private function isHO(): bool
    {
        $u = auth()->user();
        if (!$u) return false;
        return (($u->role ?? null) === 'ho') || (($u->is_admin ?? false) === true);
    }

    /**
     * Normalize angka dari input user / excel.
     * - Mendukung "25.000.000" => 25000000
     * - Mendukung "25,5" => 25.5
     */
    private function normalizeNumber($value): float
    {
        if (is_null($value)) {
            return 0.0;
        }

        // jika sudah numerik
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $s = (string) $value;
        $s = trim($s);

        if ($s === '') {
            return 0.0;
        }

        // buang "Rp", spasi, dan karakter non angka/koma/titik/minus
        $s = preg_replace('/[^0-9,\.\-]/', '', $s);

        // jika format Indonesia: 1.234.567,89
        if (substr_count($s, ',') > 0) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            // tidak ada koma: hapus titik ribuan bila pola 1.234.567 (lebih dari 1 titik)
            if (substr_count($s, '.') > 1) {
                $s = str_replace('.', '', $s);
            }
        }

        return (float) $s;
    }

    /**
     * Normalize angka KHUSUS import + koreksi ribuan otomatis + log koreksi
     * VERSION 2.0 - Lebih komprehensif untuk format Excel Indonesia
     */
    private function normalizeNumberForImport($raw, bool $autoFixThousand, array &$fixLogs, int $row, string $field): float
    {
        $before = $raw;
        
        // Jika null atau string kosong, return 0
        if (is_null($before) || (is_string($before) && trim($before) === '')) {
            return 0.0;
        }
        
        // Jika sudah numerik, langsung return
        if (is_numeric($before)) {
            return (float) $before;
        }
        
        $s = trim((string) $before);
        
        // Log raw untuk debugging
        Log::debug('Excel Import - Raw Value', [
            'row' => $row,
            'field' => $field,
            'raw' => $s,
            'type' => gettype($before),
        ]);
        
        // 1. Hapus semua karakter non-angka, kecuali titik, koma, dan minus
        // Termasuk hapus spasi, simbol mata uang, dll
        $s = preg_replace('/[^\d,\-\.]/u', '', $s);
        
        // 2. Jika setelah cleaning kosong, return 0
        if ($s === '' || $s === '-' || $s === '.') {
            return 0.0;
        }
        
        // 3. LOGIC UTAMA: Deteksi format
        // ============================================
        // Kasus 1: Angka murni tanpa pemisah
        if (preg_match('/^-?\d+$/', $s)) {
            return (float) $s;
        }
        
        // Kasus 2: Format Indonesia (titik ribuan, koma desimal)
        // Contoh: "1.234.567,89" atau "1.234,56"
        $lastDot = strrpos($s, '.');
        $lastComma = strrpos($s, ',');
        
        // Jika ada koma DAN koma setelah titik terakhir -> format Indonesia
        if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
            // Contoh: "1.234.567,89"
            $s = str_replace('.', '', $s); // Hapus semua titik (pemisah ribuan)
            $s = str_replace(',', '.', $s); // Ubah koma jadi titik desimal
            return (float) $s;
        }
        
        // Kasus 3: Hanya koma (mungkin desimal atau ribuan)
        if ($lastComma !== false && $lastDot === false) {
            $parts = explode(',', $s);

            // Jika hanya 2 bagian:
            // - Untuk harga_satuan: pola 44,000 (Excel/US ribuan) HARUS jadi 44000 (bukan 44)
            // - Untuk volume: pola 1,25 / 0,125 boleh dianggap desimal
            if (count($parts) === 2) {
                $left  = $parts[0];
                $right = $parts[1];

                // jika kedua sisi angka murni
                $isDigitsLeft  = $left !== '' && ctype_digit(ltrim($left, '-'));
                $isDigitsRight = $right !== '' && ctype_digit($right);

                if ($isDigitsLeft && $isDigitsRight) {
                    // âœ… 3 digit di belakang biasanya ribuan (US): 1,234 => 1234; 44,000 => 44000
                    if (strlen($right) === 3) {
                        // Untuk volume, 0,125 masih dianggap desimal (butuh 3 digit) -> exception
                        if ($field === 'volume') {
                            // Anggap desimal jika nilai kecil dan bukan pola ribuan besar
                            // contoh: 0,125 / 1,250 (lebih aman: jika left <= 2 digit dan left bukan ribuan besar)
                            if (strlen(ltrim($left, '-')) <= 2) {
                                $s = $left . '.' . $right;
                                return (float) $s;
                            }
                        }

                        // default: ribuan
                        $s = $left . $right;
                        return (float) $s;
                    }

                    // âœ… 1-2 digit di belakang biasanya desimal Indonesia
                    if (strlen($right) >= 1 && strlen($right) <= 2) {
                        $s = $left . '.' . $right;
                        return (float) $s;
                    }

                    // 4+ digit di belakang: jarang desimal, anggap gabungan angka
                    $s = $left . $right;
                    return (float) $s;
                }

                // fallback: coba desimal
                $s = $left . '.' . $right;
                return (float) $s;
            }

            // Jika lebih dari 1 koma, kemungkinan pemisah ribuan US
            if (substr_count($s, ',') > 1) {
                $s = str_replace(',', '', $s);
                return (float) $s;
            }
        }
        
        // Kasus 4: Hanya titik (mungkin ribuan atau desimal)
        if ($lastDot !== false && $lastComma === false) {
            // Jika lebih dari 1 titik -> pemisah ribuan Indonesia
            if (substr_count($s, '.') > 1) {
                $s = str_replace('.', '', $s);
                return (float) $s;
            }
            
            // Jika hanya 1 titik, cek apakah desimal
            $parts = explode('.', $s);
            if (count($parts) === 2) {
                $decimalPart = $parts[1];
                // Jika bagian setelah titik 1-3 digit, anggap sebagai desimal
                if (strlen($decimalPart) >= 1 && strlen($decimalPart) <= 3) {
                    return (float) $s; // Sudah format desimal benar
                }
            }
        }
        
        // Kasus 5: Auto-fix thousand untuk pola yang jelas
        if ($autoFixThousand) {
            // Pola: angka yang memiliki pemisah setiap 3 digit dari belakang
            $testStr = preg_replace('/[^\d]/', '', $s);
            if (strlen($testStr) > 3 && preg_match('/^\d+$/', $testStr)) {
                // Cek apakah angka terlalu kecil untuk menjadi ribuan
                $asFloat = (float) $testStr;
                $originalAsFloat = (float) str_replace([',', '.'], '', $s);
                
                // Jika perbedaan signifikan (misal: 1.234 vs 1234)
                if (abs($asFloat - $originalAsFloat) > 100) {
                    $fixLogs[] = [
                        'row'   => $row,
                        'field' => $field,
                        'from'  => $before,
                        'to'    => $asFloat,
                        'reason'=> 'Auto koreksi: dianggap sebagai angka tanpa pemisah',
                    ];
                    return $asFloat;
                }
            }
        }
        
        // 4. Final fallback: hapus semua karakter non-angka
        $s = preg_replace('/[^\d\-\.]/', '', $s);
        
        // Jika masih ada titik, handle sebagai desimal
        if (strpos($s, '.') !== false) {
            // Jika ada lebih dari 1 titik, hapus semua kecuali yang terakhir
            if (substr_count($s, '.') > 1) {
                $lastDotPos = strrpos($s, '.');
                $beforeDot = substr($s, 0, $lastDotPos);
                $afterDot = substr($s, $lastDotPos + 1);
                $beforeDot = str_replace('.', '', $beforeDot);
                $s = $beforeDot . '.' . $afterDot;
            }
        }
        
        $result = (float) $s;
        
        // Log hasil akhir
        Log::debug('Excel Import - Final Result', [
            'row' => $row,
            'field' => $field,
            'raw' => $before,
            'processed' => $s,
            'result' => $result,
        ]);
        
        return $result;
    }

    /**
     * Clean Excel value khusus untuk format Rupiah
     */
    private function cleanExcelCurrencyValue($value)
    {
        if (is_null($value)) {
            return null;
        }
        
        $value = trim((string)$value);
        
        // Hapus prefix "Rp", "IDR", dll (case insensitive)
        $value = preg_replace('/^(Rp|IDR|USD|â‚¬|Â£|\$)\s*/i', '', $value);
        
        // Hapus spasi
        $value = str_replace(' ', '', $value);
        
        // Hapus karakter non-angka yang tidak perlu (tapi biarkan titik, koma, minus)
        $value = preg_replace('/[^\d,\-\.]/', '', $value);
        
        return $value;
    }

    /**
     * Download template Excel untuk import item RAPP (berdasarkan Data Master).
     * Kolom yang diisi dari Excel: Kode, Nama Item, Sat, Volume RAPP, Harga Satuan
     */
    public function downloadExcelTemplate($projectId, $rabId)
    {
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $filename = 'template-import-rapp.xlsx';

        try {
            if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
                return back()->with('error', 'Library Excel belum terpasang. Jalankan: composer require phpoffice/phpspreadsheet');
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Import RAPP');

            $headers = ['Kode', 'Nama Item', 'Sat', 'Volume RAPP', 'Harga Satuan'];
            $sheet->fromArray($headers, null, 'A1');

            $example = [
                ['MT-001', 'Contoh Item Material', 'pcs', 10, 1280000],
                ['JS-001', 'Contoh Jasa', 'ls', 1, 5000000],
            ];
            $sheet->fromArray($example, null, 'A2');

            $sheet->getStyle('A1:E1')->getFont()->setBold(true);
            $sheet->getStyle('A1:E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF1F2937');
            $sheet->getStyle('A1:E1')->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getRowDimension(1)->setRowHeight(20);

            foreach (['A'=>16,'B'=>38,'C'=>10,'D'=>16,'E'=>16] as $col=>$w) {
                $sheet->getColumnDimension($col)->setWidth($w);
            }

            $sheet->getStyle('D2:D1000')->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E2:E1000')->getNumberFormat()->setFormatCode('#,##0');

            $sheet->setCellValue('G1', 'Catatan:');
            $sheet->setCellValue('G2', '1) Kolom yang wajib: Kode');
            $sheet->setCellValue('G3', '2) Kode harus ada di Data Master (/dev/data).');
            $sheet->setCellValue('G4', '3) Nama Item & Sat boleh kosong (akan diambil dari Data Master).');
            $sheet->setCellValue('G5', '4) Volume & Harga Satuan jika kosong dianggap 0.');
            $sheet->setCellValue('G6', '5) Format Harga: 1280000 atau 1.280.000 atau Rp 1.280.000');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } catch (\Exception $e) {
            Log::error('Download Excel template failed: '.$e->getMessage());
            return back()->with('error', 'Gagal membuat template Excel: '.$e->getMessage());
        }
    }

    /**
     * Import Excel ke RAPP (insert ke rab_items) berdasarkan kode di Data Master.
     * Fitur:
     * - preview sebelum import (?preview=1 atau input preview=1)
     * - validasi harga minimum
     * - log baris yang dikoreksi otomatis
     * - opsi auto_fix_thousand (checkbox)
     */
    public function importExcel(Request $request, $projectId, $rabId)
    {
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);

        // Guard edit konsisten via canEdit().
        if (method_exists($rab, 'canEdit') && !$rab->canEdit()) {
            return back()->with('error', 'RAPP tidak dapat diedit karena status: ' . ($rab->status ?? '-'));
        }

        $request->validate([
            'excel_file'       => ['required', 'file', 'max:5120'], // 5MB
            'import_mode'      => ['nullable', 'in:skip,update'],
            'preview'          => ['nullable', 'in:0,1'],
            'auto_fix_thousand'=> ['nullable', 'in:0,1'],
        ]);

        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return back()->with('error', 'Library Excel belum terpasang. Jalankan: composer require phpoffice/phpspreadsheet');
        }

        $mode            = $request->input('import_mode', 'skip'); // skip atau update
        $isPreview       = (string)$request->input('preview', '0') === '1';
        $autoFixThousand = (string)$request->input('auto_fix_thousand', '0') === '1';

        // âœ… validasi harga minimum (bisa kamu ubah sesuai aturanmu)
        $minHargaSatuan = (float) (config('rapp.min_harga_satuan') ?? 1); // default minimal 1

        $file = $request->file('excel_file');

        DB::beginTransaction();
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                DB::rollBack();
                return back()->with('error', 'File Excel kosong atau tidak memiliki data.');
            }

            // map header
            $headerRow = array_map(fn($v) => strtolower(trim((string)$v)), $rows[1]);

            $findCol = function(array $candidates) use ($headerRow) {
                foreach ($headerRow as $col => $name) {
                    $name = preg_replace('/\s+/', ' ', $name);
                    foreach ($candidates as $cand) {
                        if ($name === $cand) return $col;
                    }
                }
                return null;
            };

            $colKode  = $findCol(['kode', 'kode item', 'kode_item']);
            $colNama  = $findCol(['nama item', 'uraian', 'nama']);
            $colSat   = $findCol(['sat', 'satuan']);
            $colVol   = $findCol(['volume rapp', 'vol', 'volume']);
            $colHarga = $findCol(['harga satuan', 'harga', 'harga_satuan']);

            if (!$colKode) {
                DB::rollBack();
                return back()->with('error', 'Header kolom "Kode" tidak ditemukan. Gunakan template yang disediakan.');
            }

            // existing items by data_id
            $existingByDataId = RabItem::where('rab_id', $rab->id)
                ->get()
                ->keyBy('data_id');

            // preview data collector
            $previewRows = [];
            $fixLogs     = [];
            $errors      = [];
            $imported    = 0;
            $updated     = 0;
            $skipped     = 0;

            $maxUrutan  = (int) RabItem::where('rab_id', $rab->id)->max('urutan');
            $nextUrutan = $maxUrutan > 0 ? $maxUrutan + 1 : 1;

            for ($i = 2; $i <= count($rows); $i++) {
                $r = $rows[$i] ?? null;
                if (!$r) continue;

                $kode = trim((string)($r[$colKode] ?? ''));
                if ($kode === '') continue;

                $data = Data::where('kode', $kode)->first();
                if (!$data) {
                    $errors[] = "Baris {$i}: Kode '{$kode}' tidak ditemukan di Data Master.";
                    $skipped++;
                    continue;
                }

                // Bersihkan nilai dari Excel sebelum normalisasi
                $rawVol   = $this->cleanExcelCurrencyValue($r[$colVol] ?? 0);
                $rawHarga = $this->cleanExcelCurrencyValue($r[$colHarga] ?? 0);

                $vol   = $this->normalizeNumberForImport($rawVol, $autoFixThousand, $fixLogs, $i, 'volume');
                $harga = $this->normalizeNumberForImport($rawHarga, $autoFixThousand, $fixLogs, $i, 'harga_satuan');

                // validasi harga minimum (kalau ada data harga tapi terlalu kecil)
                if ($harga > 0 && $harga < $minHargaSatuan) {
                    $errors[] = "Baris {$i}: Harga satuan '{$kode}' terlalu kecil ({$harga}). Minimal {$minHargaSatuan}.";
                    // tetap lanjut preview/import? -> untuk safety: skip baris ini
                    $skipped++;
                    continue;
                }

                $sat = trim((string)($r[$colSat] ?? ''));
                if ($sat === '') $sat = (string)($data->satuan ?? '');

                $existing = $existingByDataId->get($data->id);

                if ($existing && $mode === 'skip') {
                    $skipped++;
                    if ($isPreview) {
                        $previewRows[] = [
                            'row'   => $i,
                            'kode'  => $kode,
                            'nama'  => $data->nama ?? $data->uraian ?? '',
                            'sat'   => $sat,
                            'vol'   => $vol,
                            'harga' => $harga,
                            'mode'  => 'skip (sudah ada)',
                            'status'=> 'skip',
                        ];
                    }
                    continue;
                }

                $subtotal = (float) ($vol * $harga);

                if ($isPreview) {
                    $previewRows[] = [
                        'row'   => $i,
                        'kode'  => $kode,
                        'nama'  => $data->nama ?? $data->uraian ?? '',
                        'sat'   => $sat,
                        'vol'   => $vol,
                        'harga' => $harga,
                        'mode'  => $existing ? 'update' : 'insert',
                        'status'=> $existing ? 'update' : 'insert',
                        'subtotal' => $subtotal,
                    ];
                    continue;
                }

                $payload = [
                    'rab_id'        => $rab->id,
                    'data_id'       => $data->id,
                    'item_type'     => $existing?->item_type ?? ($data->kode_kategori ?? $data->kategori ?? 'MT'),
                    'volume'        => $vol,
                    'satuan'        => $sat,
                    'harga_satuan'  => $harga,
                    'keterangan'    => $existing?->keterangan ?? null,
                    'created_by'    => auth()->id(),
                ];

                $realisasiVol = (float)($existing?->realisasi_volume ?? 0);
                $realisasiAmt = (float)($existing?->realisasi_amount ?? 0);

                $sisaAmt = max($subtotal - $realisasiAmt, 0);
                $sisaVol = max($vol - $realisasiVol, 0);

                $payload['realisasi_volume'] = $realisasiVol;
                $payload['realisasi_amount'] = $realisasiAmt;
                $payload['sisa_volume']      = $sisaVol;
                $payload['sisa_amount']      = $sisaAmt;

                if ($existing) {
                    $existing->fill($payload);
                    $existing->save();
                    $updated++;
                } else {
                    $payload['urutan'] = $nextUrutan++;
                    RabItem::create($payload);
                    $imported++;
                }
            }

            // Preview: jangan commit perubahan, tampilkan ringkasan
            if ($isPreview) {
                DB::rollBack();

                // Simpan log koreksi di session agar bisa ditampilkan
                session()->flash('import_fix_logs', $fixLogs);
                session()->flash('import_preview_rows', $previewRows);
                session()->flash('import_preview_errors', $errors);
                session()->flash('import_preview_meta', [
                    'mode'             => $mode,
                    'auto_fix_thousand'=> $autoFixThousand,
                    'min_harga_satuan' => $minHargaSatuan,
                    'rab_id'           => $rab->id,
                    'project_id'       => $projectId,
                ]);

                // Jika kamu punya view preview khusus, pakai ini:
                if (view()->exists('dev.rapps.import-preview')) {
                    return view('dev.rapps.import-preview', [
                        'project'     => Project::findOrFail($projectId),
                        'rab'         => $rab,
                        'previewRows' => $previewRows,
                        'fixLogs'     => $fixLogs,
                        'errors'      => $errors,
                        'meta'        => session('import_preview_meta'),
                    ]);
                }

                // Kalau belum ada view preview, minimal balik dengan flash data.
                return back()->with('success', 'Preview import siap. (Buat view dev.rapps.import-preview untuk tampilan preview).');
            }

            // PERBAIKAN: Pastikan update totals dipanggil setelah import
            $rab->updateTotals();
            $reapprovalRequired = $this->markForReapprovalIfApproved($rab);

            DB::commit();

            // simpan log koreksi (ringkas)
            if (!empty($fixLogs)) {
                Log::info('RAPP Import Excel - Auto Fix Logs', [
                    'project_id' => $projectId,
                    'rab_id'     => $rab->id,
                    'count'      => count($fixLogs),
                    'logs'       => array_slice($fixLogs, 0, 50),
                ]);
                session()->flash('import_fix_logs', $fixLogs);
            }

            $msg = "Import Excel selesai. Ditambahkan: {$imported}, Diupdate: {$updated}, Dilewati: {$skipped}.";
            if ($reapprovalRequired) {
                $msg .= ' Status RAPP dikembalikan ke submitted, menunggu approval HO.';
            }
            if (!empty($errors)) {
                $short = array_slice($errors, 0, 10);
                $msg .= " Beberapa error: " . implode(' | ', $short);
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Excel RAPP failed: '.$e->getMessage());
            return back()->with('error', 'Gagal import Excel: '.$e->getMessage());
        }
    }

    /**
     * PERBAIKAN BARU: Debug endpoint untuk memeriksa konsistensi data
     */
    public function debugConsistency($projectId, $rabId)
    {
        try {
            $project = Project::with('client')->findOrFail($projectId);
            $rab = Rab::with(['items.data'])->findOrFail($rabId);

            // Hitung manual total dari items
            $manualTotal = 0;
            $breakdown = [];
            
            foreach ($rab->items as $item) {
                $volume = $item->volume ?? 0;
                $harga = $item->harga_satuan ?? 0;
                $subtotal = $volume * $harga;
                $manualTotal += $subtotal;
                
                $category = $item->data->kategori ?? 'Uncategorized';
                if (!isset($breakdown[$category])) {
                    $breakdown[$category] = 0;
                }
                $breakdown[$category] += $subtotal;
            }

            // Hitung realisasi
            $realisasiTotal = $rab->items->sum(function($item) {
                return ($item->realisasi_volume ?? 0) * ($item->harga_satuan ?? 0);
            });

            return response()->json([
                'rab_id' => $rab->id,
                'rab_name' => $rab->name,
                'status' => $rab->status,
                'stored_total_budget' => (float) $rab->total_budget,
                'calculated_total' => (float) $manualTotal,
                'difference' => (float) $rab->total_budget - (float) $manualTotal,
                'realisasi_total' => (float) $realisasiTotal,
                'sisa_total' => (float) $manualTotal - (float) $realisasiTotal,
                'items_count' => $rab->items->count(),
                'breakdown' => $breakdown,
                'project_budget' => $project->budget ?? 0,
                'is_consistent' => abs($rab->total_budget - $manualTotal) < 1
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PERBAIKAN BARU: Repair data RAPP yang tidak konsisten
     */
    public function repairData($projectId, $rabId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $rab = Rab::with('items')->findOrFail($rabId);

            // Hanya admin/HO yang boleh repair
            if (!$this->isHO()) {
                return back()->with('error', 'Hanya Head Office yang dapat melakukan repair data.');
            }

            DB::beginTransaction();

            // Hitung ulang total dari items
            $calculatedTotal = $rab->items()->sum(
                DB::raw('COALESCE(volume, 0) * COALESCE(harga_satuan, 0)')
            );

            $oldTotal = $rab->total_budget;
            $rab->total_budget = $calculatedTotal;
            $rab->breakdown = $rab->getBreakdownByCategory();
            $rab->save();

            DB::commit();

            Log::info('RAPP Data Repaired Manually', [
                'rab_id' => $rab->id,
                'old_total' => $oldTotal,
                'new_total' => $calculatedTotal,
                'difference' => $calculatedTotal - $oldTotal,
                'repaired_by' => auth()->id()
            ]);

            return back()->with('success', "Data RAPP berhasil di-repair. Total: {$oldTotal} â†’ {$calculatedTotal}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RAPP Repair Error:', [
                'project_id' => $projectId,
                'rab_id' => $rabId,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Gagal repair data: ' . $e->getMessage());
        }
    }

    /**
     * Export RAPP ke PDF (layout resmi)
     */
    public function exportPdf($projectId, $rabId)
    {
        $project = Project::with('client')->findOrFail($projectId);
        $rab = Rab::with(['items.data'])
            ->where('project_id', $projectId)
            ->findOrFail($rabId);

        $data = compact('project', 'rab');
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $filename = 'RAB-Baseline-' . ($rab->id ?? 'export') . '.pdf';
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rapp', $data)
                ->setPaper('a4', 'portrait')
                ->stream($filename);
        }

        return view('pdf.rapp', $data);
    }

    /**
     * Export RAPP ke Excel
     */
    public function exportExcel($projectId, $rabId)
    {
        try {
            if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
                return back()->with('error', 'Library Excel belum terpasang. Jalankan: composer require phpoffice/phpspreadsheet');
            }

            $project = Project::with('client')->findOrFail($projectId);
            $rab = Rab::with(['items.data'])
                ->where('project_id', $projectId)
                ->findOrFail($rabId);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('RAPP');

            // Meta header
            $sheet->mergeCells('A1:J1');
            $sheet->setCellValue('A1', 'RAPP');
            $sheet->setCellValue('A2', 'Nama Proyek');
            $sheet->setCellValue('B2', $project->name ?? '-');
            $sheet->setCellValue('A3', 'Kode Proyek');
            $sheet->setCellValue('B3', $project->code ?? '-');
            $sheet->setCellValue('A4', 'Nama RAPP');
            $sheet->setCellValue('B4', $rab->name ?? '-');
            $sheet->setCellValue('A5', 'Status');
            $sheet->setCellValue('B5', $rab->status ?? 'draft');
            $sheet->setCellValue('A6', 'Tanggal Export');
            $sheet->setCellValue('B6', now()->format('d/m/Y H:i'));

            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE5E7EB');
            $sheet->getStyle('A2:A6')->getFont()->setBold(true);

            // Header tabel
            $headerRow = 8;
            $headers = [
                'Kode / Nama Item',
                'Volume RAPP',
                'Harga Satuan',
                'Total',
                'Realisasi (Vol | Rp)',
                'Sisa (Vol | Rp)',
                'Percent Used',
                'Keterangan',
                'Tanggal (Create / Update)',
            ];
            $sheet->fromArray($headers, null, 'A' . $headerRow);

            $lastHeaderCol = 'J';
            $sheet->getStyle('A' . $headerRow . ':' . $lastHeaderCol . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle('A' . $headerRow . ':' . $lastHeaderCol . $headerRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF1F2937');
            $sheet->getStyle('A' . $headerRow . ':' . $lastHeaderCol . $headerRow)->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('A' . $headerRow . ':' . $lastHeaderCol . $headerRow)->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

            $row = $headerRow + 1;
            foreach ($rab->items as $item) {
                $kode = $item->data->kode ?? '-';
                $namaItem = $item->data->uraian ?? '-';
                $vol = (float) ($item->volume ?? 0);
                $harga = (float) ($item->harga_satuan ?? 0);
                $total = $vol * $harga;
                $realisasiVol = (float) ($item->realisasi_volume ?? 0);
                $realisasiAmt = (float) ($item->realisasi_amount ?? 0);
                $sisaVol = (float) ($item->sisa_volume ?? 0);
                $sisaAmt = (float) ($item->sisa_amount ?? 0);
                $percentUsed = (float) ($item->percent_used ?? 0);
                $ket = $item->keterangan ?? '';
                $createdAt = optional($item->created_at)->format('Y-m-d H:i');
                $updatedAt = optional($item->updated_at)->format('Y-m-d H:i');
                $tanggalGabung = trim(($createdAt ?: '-') . ' / ' . ($updatedAt ?: '-'));

                $sheet->setCellValue('A' . $row, trim($kode . ' - ' . $namaItem));
                $sheet->setCellValue('B' . $row, $vol);
                $sheet->setCellValue('C' . $row, $harga);
                $sheet->setCellValue('D' . $row, $total);
                $sheet->setCellValue('E' . $row, trim(number_format($realisasiVol, 2, ',', '.') . ' | Rp ' . number_format($realisasiAmt, 0, ',', '.')));
                $sheet->setCellValue('F' . $row, trim(number_format($sisaVol, 2, ',', '.') . ' | Rp ' . number_format($sisaAmt, 0, ',', '.')));
                $sheet->setCellValue('G' . $row, $percentUsed / 100);
                $sheet->setCellValue('H' . $row, $ket);
                $sheet->setCellValue('I' . $row, $tanggalGabung);
                $row++;
            }

            $dataStart = $headerRow + 1;
            $dataEnd = max($row - 1, $dataStart);

            // Format angka
            $sheet->getStyle('B' . $dataStart . ':B' . $dataEnd)
                ->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('C' . $dataStart . ':D' . $dataEnd)
                ->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G' . $dataStart . ':G' . $dataEnd)
                ->getNumberFormat()->setFormatCode('0.00%');

            // Zebra rows untuk keterbacaan
            if ($dataEnd >= $dataStart) {
                for ($r = $dataStart; $r <= $dataEnd; $r++) {
                    if (($r - $dataStart) % 2 === 1) {
                        $sheet->getStyle('A' . $r . ':' . $lastHeaderCol . $r)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFF8FAFC');
                    }
                }
            }

            // Highlight kolom penting
            $sheet->getStyle('D' . $dataStart . ':D' . $dataEnd)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF0F9FF'); // Total
            $sheet->getStyle('E' . $dataStart . ':F' . $dataEnd)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF8FAFC'); // Realisasi & Sisa

            // Border tipis untuk seluruh tabel
            $sheet->getStyle('A' . $headerRow . ':' . $lastHeaderCol . $dataEnd)->getBorders()
                ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                ->getColor()->setARGB('FFE5E7EB');

            // Auto width
            foreach ([
                'A'=>22,'B'=>16,'C'=>16,'D'=>18,'E'=>22,'F'=>22,'G'=>12,'H'=>28,
                'I'=>24
            ] as $col => $w) {
                $sheet->getColumnDimension($col)->setWidth($w);
            }

            $filename = 'RAB-Baseline-' . ($rab->id ?? 'export') . '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $tmpFile = tempnam(sys_get_temp_dir(), 'rapp_');
            $writer->save($tmpFile);

            return response()->download($tmpFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Export Excel RAPP failed: ' . $e->getMessage(), [
                'project_id' => $projectId,
                'rab_id' => $rabId,
            ]);
            return back()->with('error', 'Gagal export Excel: ' . $e->getMessage());
        }
    }
}



