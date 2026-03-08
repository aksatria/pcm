<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Bpg;
use App\Models\BpgItem;
use App\Models\Lpb;
use App\Models\LpbItem;
use App\Models\Project;
use App\Models\Rab;
use App\Models\RabItem;
use App\Models\StockMovement;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Dev\Concerns\ValidatesRabScope;
use App\Http\Controllers\Dev\Concerns\LocksDocumentStatus;
use App\Support\AuditLogger;
use App\Support\NotificationService;

class BpgController extends Controller
{
    use ValidatesRabScope;
    use LocksDocumentStatus;

    protected function ensureHOApproval(): void
    {
        $user = auth()->user();
        if (!$user || !method_exists($user, 'isHO') || !$user->isHO()) {
            abort(403, 'Hanya HO yang boleh melakukan approval.');
        }
    }

    public function index($projectId, $rabId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $bpgs = Bpg::with(['items.rabItem.data'])->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->orderByDesc('id')
            ->get();

        $rabItemIds = $bpgs->flatMap(function ($bpg) {
            return $bpg->items?->pluck('rab_item_id') ?? collect();
        })->filter()->unique()->values()->all();
        $stockBalances = $this->getStockBalances($projectId, $rabId, $rabItemIds);

        return view('dev.bpgs.index', compact('project', 'rab', 'bpgs', 'stockBalances'));
    }

    public function create($projectId, $rabId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $rabItems = RabItem::with('data')->where('rab_id', $rabId)->get();
        $stockBalances = $this->getStockBalances($projectId, $rabId, $rabItems->pluck('id')->all());
        $lpbs = Lpb::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->get();

        return view('dev.bpgs.create', compact('project', 'rab', 'rabItems', 'lpbs', 'stockBalances'));
    }

    public function store(Request $request, $projectId, $rabId, DocumentNumberService $numberService)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);

        $validated = $request->validate([
            'bpg_no' => 'nullable|string|max:100',
            'bpg_date' => 'nullable|date',
            'lpb_id' => 'required|exists:lpbs,id',
            'requested_by' => 'nullable|string|max:100',
            'approved_by' => 'nullable|string|max:100',
            'known_by' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rab_item_id' => ['required', Rule::exists('rab_items', 'id')->where('rab_id', $rabId)],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.issue_date' => 'nullable|date',
            'items.*.work_notes' => 'nullable|string',
        ]);

        $lpb = Lpb::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->where('id', $validated['lpb_id'])
            ->first();
        if (!$lpb || ($lpb->status ?? '') !== 'approved') {
            return back()
                ->withErrors(['lpb_id' => 'LPB harus dari proyek ini dan berstatus approved.'])
                ->withInput();
        }

        $items = $validated['items'] ?? [];
        $rabItems = $this->loadRabItemsOrFail($rabId, $items);
        $this->assertQtyWithinRabBudget(
            $items,
            $rabItems,
            BpgItem::class,
            'bpg_id',
            null,
            'bpg',
            $projectId,
            $rabId
        );
        $this->assertQtyWithinLpb($lpb->id, $items, null);
        $this->assertQtyWithinStock($projectId, $rabId, $items, null, $rabItems);

        $bpgDate = !empty($validated['bpg_date']) ? Carbon::parse($validated['bpg_date']) : null;
        $bpgNo = $validated['bpg_no'] ?: $numberService->nextNumber($projectId, 'BPG', $project->code ?? null, $bpgDate);

        $bpg = null;
        DB::transaction(function () use (&$bpg, $validated, $projectId, $rabId, $bpgNo) {
            $bpg = Bpg::create([
                'project_id' => $projectId,
                'rab_id' => $rabId,
                'lpb_id' => $validated['lpb_id'],
                'bpg_no' => $bpgNo,
                'bpg_date' => $validated['bpg_date'] ?? null,
                'status' => 'draft',
                'requested_by' => $validated['requested_by'] ?? null,
                'approved_by' => $validated['approved_by'] ?? null,
                'known_by' => $validated['known_by'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $items = $validated['items'] ?? [];
            foreach ($items as $row) {
                $rabItem = RabItem::with('data')->find($row['rab_item_id']);
                if (!$rabItem) {
                    continue;
                }
                $item = BpgItem::create([
                    'bpg_id' => $bpg->id,
                    'rab_item_id' => $rabItem->id,
                    'item_code_snapshot' => $rabItem->data->kode ?? null,
                    'item_name_snapshot' => $rabItem->data->uraian ?? null,
                    'unit_snapshot' => $rabItem->satuan ?? ($rabItem->data->satuan ?? null),
                    'qty' => $row['qty'],
                    'unit' => $row['unit'] ?? ($rabItem->satuan ?? null),
                    'issue_date' => $row['issue_date'] ?? null,
                    'work_notes' => $row['work_notes'] ?? null,
                ]);

            }

            AuditLogger::log('bpg.created', $bpg, null, $bpg->toArray(), [
                'project_id' => $projectId,
                'rab_id' => $rabId,
            ]);
        });

        return redirect()->route('dev.rab-baseline.bpgs.index', [$projectId, $rabId])
            ->with('success', 'BPG berhasil dibuat.');
    }

    public function show($projectId, $rabId, $bpgId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $bpg = Bpg::with(['items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($bpgId);

        $rabItemIds = $bpg->items?->pluck('rab_item_id')->filter()->unique()->values()->all() ?? [];
        $stockBalances = $this->getStockBalances($projectId, $rabId, $rabItemIds);

        return view('dev.bpgs.show', compact('project', 'rab', 'bpg', 'stockBalances'));
    }

    public function edit($projectId, $rabId, $bpgId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $bpg = Bpg::with(['items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($bpgId);
        $rabItems = RabItem::with('data')->where('rab_id', $rabId)->get();
        $stockBalances = $this->getStockBalances($projectId, $rabId, $rabItems->pluck('id')->all());
        $lpbs = Lpb::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->get();
        if ($bpg->lpb_id && !$lpbs->pluck('id')->contains($bpg->lpb_id)) {
            $currentLpb = Lpb::where('project_id', $projectId)
                ->where('rab_id', $rabId)
                ->where('id', $bpg->lpb_id)
                ->first();
            if ($currentLpb) {
                $lpbs = $lpbs->prepend($currentLpb);
            }
        }

        return view('dev.bpgs.edit', compact('project', 'rab', 'bpg', 'rabItems', 'lpbs', 'stockBalances'));
    }

    public function update(Request $request, $projectId, $rabId, $bpgId)
    {
        $validated = $request->validate([
            'bpg_no' => 'nullable|string|max:100',
            'bpg_date' => 'nullable|date',
            'lpb_id' => 'required|exists:lpbs,id',
            'requested_by' => 'nullable|string|max:100',
            'approved_by' => 'nullable|string|max:100',
            'known_by' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rab_item_id' => ['required', Rule::exists('rab_items', 'id')->where('rab_id', $rabId)],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.issue_date' => 'nullable|date',
            'items.*.work_notes' => 'nullable|string',
        ]);

        $bpg = Bpg::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($bpgId);
        $this->ensureEditableStatus($bpg, 'BPG');
        $before = $bpg->toArray();

        $lpb = Lpb::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->where('id', $validated['lpb_id'])
            ->first();
        if (!$lpb || ($lpb->status ?? '') !== 'approved') {
            return back()
                ->withErrors(['lpb_id' => 'LPB harus dari proyek ini dan berstatus approved.'])
                ->withInput();
        }

        $items = $validated['items'] ?? [];
        $rabItems = $this->loadRabItemsOrFail($rabId, $items);
        $this->assertQtyWithinRabBudget(
            $items,
            $rabItems,
            BpgItem::class,
            'bpg_id',
            $bpg->id,
            'bpg',
            $projectId,
            $rabId
        );
        $this->assertQtyWithinLpb($lpb->id, $items, $bpg->id);
        $currentStockDocId = (($bpg->status ?? '') === 'approved') ? $bpg->id : null;
        $this->assertQtyWithinStock($projectId, $rabId, $items, $currentStockDocId, $rabItems);

        $isApproved = ($bpg->status ?? '') === 'approved';
        DB::transaction(function () use ($validated, $bpg, $isApproved) {
            $bpg->update([
                'lpb_id' => $validated['lpb_id'],
                'bpg_no' => $validated['bpg_no'] ?? $bpg->bpg_no,
                'bpg_date' => $validated['bpg_date'] ?? null,
                'requested_by' => $validated['requested_by'] ?? null,
                'approved_by' => $validated['approved_by'] ?? null,
                'known_by' => $validated['known_by'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (array_key_exists('items', $validated)) {
                $bpg->items()->delete();
                StockMovement::where('doc_type', 'bpg')->where('doc_id', $bpg->id)->delete();
                foreach ($validated['items'] as $row) {
                    $rabItem = RabItem::with('data')->find($row['rab_item_id']);
                    if (!$rabItem) {
                        continue;
                    }
                    $item = BpgItem::create([
                        'bpg_id' => $bpg->id,
                        'rab_item_id' => $rabItem->id,
                        'item_code_snapshot' => $rabItem->data->kode ?? null,
                        'item_name_snapshot' => $rabItem->data->uraian ?? null,
                        'unit_snapshot' => $rabItem->satuan ?? ($rabItem->data->satuan ?? null),
                        'qty' => $row['qty'],
                        'unit' => $row['unit'] ?? ($rabItem->satuan ?? null),
                        'issue_date' => $row['issue_date'] ?? null,
                        'work_notes' => $row['work_notes'] ?? null,
                    ]);

                    if ($isApproved) {
                        StockMovement::create([
                            'project_id' => $bpg->project_id,
                            'rab_id' => $bpg->rab_id,
                            'rab_item_id' => $rabItem->id,
                            'movement_type' => 'out',
                            'doc_type' => 'bpg',
                            'doc_id' => $bpg->id,
                            'movement_date' => $item->issue_date ?? $bpg->bpg_date,
                            'qty' => $item->qty,
                            'unit' => $item->unit,
                            'notes' => $item->work_notes,
                        ]);
                    }
                }
            }
        });

        $bpg->refresh();
        AuditLogger::log('bpg.updated', $bpg, $before, $bpg->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return redirect()->route('dev.rabs.bpgs.show', [$projectId, $rabId, $bpgId])
            ->with('success', 'BPG berhasil diperbarui.');
    }

    public function destroy($projectId, $rabId, $bpgId)
    {
        $bpg = Bpg::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($bpgId);
        $this->ensureEditableStatus($bpg, 'BPG');
        $before = $bpg->toArray();
        StockMovement::where('doc_type', 'bpg')->where('doc_id', $bpg->id)->delete();
        $bpg->delete();
        AuditLogger::log('bpg.deleted', $bpg, $before, null, [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return redirect()->route('dev.rab-baseline.bpgs.index', [$projectId, $rabId])
            ->with('success', 'BPG berhasil dihapus.');
    }

    public function submit($projectId, $rabId, $bpgId)
    {
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $bpg = Bpg::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($bpgId);
        $lpb = $bpg->lpb_id
            ? Lpb::where('project_id', $projectId)->where('rab_id', $rabId)->find($bpg->lpb_id)
            : null;

        if (($rab->status ?? '') !== 'approved') {
            return back()->with('error', 'RAPP harus approved sebelum submit BPG.');
        }
        if (!$lpb || ($lpb->status ?? '') !== 'approved') {
            return back()->with('error', 'LPB harus approved sebelum submit BPG.');
        }
        if (!in_array($bpg->status ?? 'draft', ['draft', 'rejected'], true)) {
            return back()->with('error', 'BPG hanya bisa disubmit dari status draft/rejected.');
        }

        $submitItems = $bpg->items->map(function ($item) {
            return [
                'rab_item_id' => $item->rab_item_id,
                'qty' => $item->qty,
            ];
        })->toArray();
        $this->assertQtyWithinStock($projectId, $rabId, $submitItems, null);

        $before = $bpg->toArray();
        $bpg->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => auth()->user()->name ?? null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        $bpg->refresh();
        AuditLogger::log('bpg.submitted', $bpg, $before, $bpg->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'BPG Diajukan',
            'BPG ' . ($bpg->bpg_no ?? $bpg->id) . ' diajukan' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.bpgs.show', [$projectId, $rabId, $bpgId]),
            'approval',
            [
                'doc_type' => 'bpg',
                'doc_id' => $bpg->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'BPG berhasil disubmit.');
    }

    public function approve($projectId, $rabId, $bpgId)
    {
        $this->ensureHOApproval();
        $bpg = Bpg::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($bpgId);

        if (($bpg->status ?? '') !== 'submitted') {
            return back()->with('error', 'BPG hanya bisa di-approve dari status submitted.');
        }

        $approveItems = $bpg->items->map(function ($item) {
            return [
                'rab_item_id' => $item->rab_item_id,
                'qty' => $item->qty,
            ];
        })->toArray();
        $this->assertQtyWithinStock($projectId, $rabId, $approveItems, null);

        $before = $bpg->toArray();
        $bpg->update([
            'status' => 'approved',
            'approved_by' => auth()->user()->name ?? $bpg->approved_by,
            'approved_at' => now(),
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        StockMovement::where('doc_type', 'bpg')->where('doc_id', $bpg->id)->delete();
        foreach ($bpg->items ?? [] as $item) {
            StockMovement::create([
                'project_id' => $bpg->project_id,
                'rab_id' => $bpg->rab_id,
                'rab_item_id' => $item->rab_item_id,
                'movement_type' => 'out',
                'doc_type' => 'bpg',
                'doc_id' => $bpg->id,
                'movement_date' => $item->issue_date ?? $bpg->bpg_date,
                'qty' => $item->qty,
                'unit' => $item->unit,
                'notes' => $item->work_notes,
            ]);
        }
        $bpg->refresh();
        AuditLogger::log('bpg.approved', $bpg, $before, $bpg->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);
        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'Stock Keluar (BPG)',
            'BPG ' . ($bpg->bpg_no ?? $bpg->id) . ' disetujui' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.bpgs.show', [$projectId, $rabId, $bpgId]),
            'stock',
            [
                'doc_type' => 'bpg',
                'doc_id' => $bpg->id,
                'project_id' => $projectId,
            ]
        );
        NotificationService::notifySubmitter(
            $bpg,
            'BPG Disetujui',
            'BPG ' . ($bpg->bpg_no ?? $bpg->id) . ' disetujui.',
            route('dev.rabs.bpgs.show', [$projectId, $rabId, $bpgId]),
            'approval',
            [
                'doc_type' => 'bpg',
                'doc_id' => $bpg->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'BPG berhasil di-approve.');
    }

    public function reject(Request $request, $projectId, $rabId, $bpgId)
    {
        $this->ensureHOApproval();
        $bpg = Bpg::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($bpgId);

        if (($bpg->status ?? '') !== 'submitted') {
            return back()->with('error', 'BPG hanya bisa di-reject dari status submitted.');
        }

        $before = $bpg->toArray();
        $reason = trim((string) $request->input('rejected_reason', ''));
        $bpg->update([
            'status' => 'rejected',
            'rejected_reason' => $reason !== '' ? $reason : null,
            'rejected_at' => now(),
            'rejected_by' => auth()->user()->name ?? null,
        ]);
        StockMovement::where('doc_type', 'bpg')->where('doc_id', $bpg->id)->delete();
        $bpg->refresh();
        AuditLogger::log('bpg.rejected', $bpg, $before, $bpg->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);
        $message = 'BPG ' . ($bpg->bpg_no ?? $bpg->id) . ' ditolak.';
        if ($reason !== '') {
            $message .= ' Alasan: ' . $reason;
        }
        NotificationService::notifySubmitter(
            $bpg,
            'BPG Ditolak',
            $message,
            route('dev.rabs.bpgs.show', [$projectId, $rabId, $bpgId]),
            'approval',
            [
                'doc_type' => 'bpg',
                'doc_id' => $bpg->id,
                'project_id' => $projectId,
                'rejected_reason' => $reason,
            ]
        );

        return back()->with('success', 'BPG berhasil di-reject.');
    }

    public function print($projectId, $rabId, $bpgId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $bpg = Bpg::with(['items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($bpgId);

        $logoSrc = asset('images/logo-dipo.png');
        return view('pdf.bpg', compact('project', 'rab', 'bpg', 'logoSrc'));
    }

    public function pdf($projectId, $rabId, $bpgId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $bpg = Bpg::with(['items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($bpgId);

        $data = compact('project', 'rab', 'bpg');
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $filename = 'BPG-' . ($bpg->bpg_no ?? $bpg->id) . '.pdf';
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.bpg', $data)
                ->setPaper('a4', 'portrait')
                ->stream($filename);
        }

        return view('pdf.bpg', $data);
    }

    protected function assertQtyWithinLpb(int $lpbId, array $items, ?int $currentBpgId): void
    {
        $lpbItems = LpbItem::where('lpb_id', $lpbId)->get()->keyBy('rab_item_id');
        if ($lpbItems->isEmpty()) {
            return;
        }

        $requestedTotals = collect($items)
            ->filter(fn ($row) => !empty($row['rab_item_id']))
            ->groupBy(fn ($row) => (int) $row['rab_item_id'])
            ->map(fn ($rows) => $rows->sum(fn ($row) => (float) ($row['qty'] ?? 0)));

        foreach ($requestedTotals as $rabItemId => $requestedQty) {
            $rabItemId = (int) $rabItemId;
            $lpbItem = $lpbItems->get($rabItemId);
            if (!$lpbItem) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'Item BPG harus berasal dari LPB yang dipilih.',
                ]);
            }

            $existingQty = BpgItem::whereHas('bpg', function ($q) use ($lpbId, $currentBpgId) {
                    $q->where('lpb_id', $lpbId)
                        ->whereNotIn('status', ['rejected', 'cancelled']);
                    if ($currentBpgId) {
                        $q->where('id', '!=', $currentBpgId);
                    }
                })
                ->where('rab_item_id', $rabItemId)
                ->sum('qty');

            $remaining = max(0, (float) ($lpbItem->qty ?? 0) - (float) $existingQty);
            if ($requestedQty > $remaining + 0.00001) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'Qty BPG melebihi sisa LPB untuk item ' . ($lpbItem->item_code_snapshot ?? $rabItemId) . '. Sisa: ' . number_format($remaining, 2, ',', '.'),
                ]);
            }
        }
    }

    protected function assertQtyWithinStock(int $projectId, int $rabId, array $items, ?int $currentBpgId = null, $rabItems = null): void
    {
        $requestedTotals = collect($items)
            ->filter(fn ($row) => !empty($row['rab_item_id']))
            ->groupBy(fn ($row) => (int) $row['rab_item_id'])
            ->map(fn ($rows) => $rows->sum(fn ($row) => (float) ($row['qty'] ?? 0)));

        if ($requestedTotals->isEmpty()) {
            return;
        }

        $rabItemIds = $requestedTotals->keys()->all();

        $stockRows = StockMovement::query()
            ->select('rab_item_id')
            ->selectRaw("SUM(CASE WHEN movement_type = 'in' THEN qty ELSE 0 END) as qty_in")
            ->selectRaw("SUM(CASE WHEN movement_type = 'out' THEN qty ELSE 0 END) as qty_out")
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->whereIn('rab_item_id', $rabItemIds)
            ->groupBy('rab_item_id')
            ->get()
            ->keyBy('rab_item_id');

        $currentOut = collect();
        if ($currentBpgId) {
            $currentOut = StockMovement::query()
                ->select('rab_item_id')
                ->selectRaw("SUM(qty) as qty_out")
                ->where('doc_type', 'bpg')
                ->where('doc_id', $currentBpgId)
                ->whereIn('rab_item_id', $rabItemIds)
                ->groupBy('rab_item_id')
                ->get()
                ->keyBy('rab_item_id');
        }

        if (!$rabItems) {
            $rabItems = RabItem::with('data')->whereIn('id', $rabItemIds)->get()->keyBy('id');
        }

        foreach ($requestedTotals as $rabItemId => $requestedQty) {
            $rabItemId = (int) $rabItemId;
            $row = $stockRows->get($rabItemId);
            $qtyIn = (float) ($row->qty_in ?? 0);
            $qtyOut = (float) ($row->qty_out ?? 0);
            $balance = $qtyIn - $qtyOut;

            $existingOut = (float) ($currentOut->get($rabItemId)->qty_out ?? 0);
            $available = $balance + $existingOut;

            if ($requestedQty > $available + 0.00001) {
                $rabItem = $rabItems instanceof \Illuminate\Support\Collection
                    ? $rabItems->get($rabItemId)
                    : ($rabItems[$rabItemId] ?? null);
                $itemCode = $rabItem?->data?->kode ?? $rabItem?->kode ?? $rabItemId;
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'Qty BPG melebihi stok tersedia untuk item ' . $itemCode . '. Sisa: ' . number_format($available, 2, ',', '.'),
                ]);
            }
        }
    }

    protected function getStockBalances(int $projectId, int $rabId, array $rabItemIds = []): array
    {
        $rows = StockMovement::query()
            ->select('rab_item_id')
            ->selectRaw("SUM(CASE WHEN movement_type = 'in' THEN qty ELSE 0 END) as qty_in")
            ->selectRaw("SUM(CASE WHEN movement_type = 'out' THEN qty ELSE 0 END) as qty_out")
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->when($rabItemIds, fn($q) => $q->whereIn('rab_item_id', $rabItemIds))
            ->groupBy('rab_item_id')
            ->get();

        $balances = [];
        foreach ($rows as $row) {
            $balances[(int) $row->rab_item_id] = (float) ($row->qty_in ?? 0) - (float) ($row->qty_out ?? 0);
        }
        return $balances;
    }
}



