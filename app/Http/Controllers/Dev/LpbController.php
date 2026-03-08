<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Lpb;
use App\Models\Bpg;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Rab;
use App\Models\RabItem;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Models\LpbItem;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Dev\Concerns\ValidatesRabScope;
use App\Http\Controllers\Dev\Concerns\LocksDocumentStatus;
use App\Support\AuditLogger;
use App\Support\NotificationService;

class LpbController extends Controller
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
        $lpbs = Lpb::with(['vendor', 'purchaseOrder'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->orderByDesc('id')
            ->get();

        return view('dev.lpbs.index', compact('project', 'rab', 'lpbs'));
    }

    public function create($projectId, $rabId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $rabItems = RabItem::with('data')->where('rab_id', $rabId)->get();
        $vendors = Vendor::forProject($project->id)->orderBy('nama')->get();
        $purchaseOrders = PurchaseOrder::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->get();

        return view('dev.lpbs.create', compact('project', 'rab', 'rabItems', 'vendors', 'purchaseOrders'));
    }

    public function store(Request $request, $projectId, $rabId, DocumentNumberService $numberService)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);

        $validated = $request->validate([
            'lpb_no' => 'nullable|string|max:100',
            'lpb_date' => 'nullable|date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'delivered_by' => 'nullable|string|max:100',
            'received_by' => 'nullable|string|max:100',
            'known_by' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rab_item_id' => ['required', Rule::exists('rab_items', 'id')->where('rab_id', $rabId)],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.arrival_date' => 'nullable|date',
            'items.*.doc_reference' => 'nullable|string|max:100',
            'items.*.work_notes' => 'nullable|string',
        ]);

        $po = PurchaseOrder::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->where('id', $validated['purchase_order_id'])
            ->first();
        if (!$po || ($po->status ?? '') !== 'approved') {
            return back()
                ->withErrors(['purchase_order_id' => 'PO harus berasal dari proyek ini dan berstatus approved.'])
                ->withInput();
        }
        if ($po->vendor_id) {
            if (!empty($validated['vendor_id']) && (int) $validated['vendor_id'] !== (int) $po->vendor_id) {
                return back()
                    ->withErrors(['vendor_id' => 'Vendor LPB harus sama dengan vendor PO.'])
                    ->withInput();
            }
            $validated['vendor_id'] = $po->vendor_id;
        } else {
            return back()
                ->withErrors(['purchase_order_id' => 'Vendor pada PO belum diisi. Lengkapi PO terlebih dahulu.'])
                ->withInput();
        }
        $this->assertVendorsInProject([$validated['vendor_id'] ?? null], $projectId, 'vendor_id');

        $items = $validated['items'] ?? [];
        $rabItems = $this->loadRabItemsOrFail($rabId, $items);
        $this->assertQtyWithinRabBudget(
            $items,
            $rabItems,
            LpbItem::class,
            'lpb_id',
            null,
            'lpb',
            $projectId,
            $rabId
        );
        $this->assertQtyWithinPo($po->id, $items, null);

        $lpbDate = !empty($validated['lpb_date']) ? Carbon::parse($validated['lpb_date']) : null;
        $lpbNo = $validated['lpb_no'] ?: $numberService->nextNumber($projectId, 'LPB', $project->code ?? null, $lpbDate);

        $lpb = null;
        DB::transaction(function () use (&$lpb, $validated, $projectId, $rabId, $lpbNo) {
            $lpb = Lpb::create([
                'project_id' => $projectId,
                'rab_id' => $rabId,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'lpb_no' => $lpbNo,
                'lpb_date' => $validated['lpb_date'] ?? null,
                'status' => 'draft',
                'delivered_by' => $validated['delivered_by'] ?? null,
                'received_by' => $validated['received_by'] ?? null,
                'known_by' => $validated['known_by'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $items = $validated['items'] ?? [];
            foreach ($items as $row) {
                $rabItem = RabItem::with('data')->find($row['rab_item_id']);
                if (!$rabItem) {
                    continue;
                }
                $item = LpbItem::create([
                    'lpb_id' => $lpb->id,
                    'rab_item_id' => $rabItem->id,
                    'item_code_snapshot' => $rabItem->data->kode ?? null,
                    'item_name_snapshot' => $rabItem->data->uraian ?? null,
                    'unit_snapshot' => $rabItem->satuan ?? ($rabItem->data->satuan ?? null),
                    'qty' => $row['qty'],
                    'unit' => $row['unit'] ?? ($rabItem->satuan ?? null),
                    'arrival_date' => $row['arrival_date'] ?? null,
                    'doc_reference' => $row['doc_reference'] ?? null,
                    'work_notes' => $row['work_notes'] ?? null,
                ]);

            }

            AuditLogger::log('lpb.created', $lpb, null, $lpb->toArray(), [
                'project_id' => $projectId,
                'rab_id' => $rabId,
            ]);
        });

        return redirect()->route('dev.rab-baseline.lpbs.index', [$projectId, $rabId])
            ->with('success', 'LPB berhasil dibuat.');
    }

    public function show($projectId, $rabId, $lpbId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $lpb = Lpb::with(['vendor', 'purchaseOrder', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($lpbId);

        return view('dev.lpbs.show', compact('project', 'rab', 'lpb'));
    }

    public function edit($projectId, $rabId, $lpbId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $lpb = Lpb::with(['items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($lpbId);
        $rabItems = RabItem::with('data')->where('rab_id', $rabId)->get();
        $vendors = Vendor::forProject($project->id)->orderBy('nama')->get();
        $purchaseOrders = PurchaseOrder::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->get();

        return view('dev.lpbs.edit', compact('project', 'rab', 'lpb', 'rabItems', 'vendors', 'purchaseOrders'));
    }

    public function update(Request $request, $projectId, $rabId, $lpbId)
    {
        $validated = $request->validate([
            'lpb_no' => 'nullable|string|max:100',
            'lpb_date' => 'nullable|date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'delivered_by' => 'nullable|string|max:100',
            'received_by' => 'nullable|string|max:100',
            'known_by' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rab_item_id' => ['required', Rule::exists('rab_items', 'id')->where('rab_id', $rabId)],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.arrival_date' => 'nullable|date',
            'items.*.doc_reference' => 'nullable|string|max:100',
            'items.*.work_notes' => 'nullable|string',
        ]);

        $lpb = Lpb::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($lpbId);
        $this->ensureEditableStatus($lpb, 'LPB');
        $before = $lpb->toArray();

        $po = PurchaseOrder::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->where('id', $validated['purchase_order_id'])
            ->first();
        if (!$po || ($po->status ?? '') !== 'approved') {
            return back()
                ->withErrors(['purchase_order_id' => 'PO harus berasal dari proyek ini dan berstatus approved.'])
                ->withInput();
        }
        if ($po->vendor_id) {
            if (!empty($validated['vendor_id']) && (int) $validated['vendor_id'] !== (int) $po->vendor_id) {
                return back()
                    ->withErrors(['vendor_id' => 'Vendor LPB harus sama dengan vendor PO.'])
                    ->withInput();
            }
            $validated['vendor_id'] = $po->vendor_id;
        } else {
            return back()
                ->withErrors(['purchase_order_id' => 'Vendor pada PO belum diisi. Lengkapi PO terlebih dahulu.'])
                ->withInput();
        }
        $this->assertVendorsInProject([$validated['vendor_id'] ?? null], $projectId, 'vendor_id');

        $items = $validated['items'] ?? [];
        $rabItems = $this->loadRabItemsOrFail($rabId, $items);
        $this->assertQtyWithinRabBudget(
            $items,
            $rabItems,
            LpbItem::class,
            'lpb_id',
            $lpb->id,
            'lpb',
            $projectId,
            $rabId
        );
        $this->assertQtyWithinPo($po->id, $items, $lpb->id);

        $isApproved = ($lpb->status ?? '') === 'approved';
        DB::transaction(function () use ($validated, $lpb, $isApproved) {
            $lpb->update([
                'vendor_id' => $validated['vendor_id'] ?? null,
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'lpb_no' => $validated['lpb_no'] ?? $lpb->lpb_no,
                'lpb_date' => $validated['lpb_date'] ?? null,
                'delivered_by' => $validated['delivered_by'] ?? null,
                'received_by' => $validated['received_by'] ?? null,
                'known_by' => $validated['known_by'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (array_key_exists('items', $validated)) {
                $lpb->items()->delete();
                StockMovement::where('doc_type', 'lpb')->where('doc_id', $lpb->id)->delete();
                foreach ($validated['items'] as $row) {
                    $rabItem = RabItem::with('data')->find($row['rab_item_id']);
                    if (!$rabItem) {
                        continue;
                    }
                    $item = LpbItem::create([
                        'lpb_id' => $lpb->id,
                        'rab_item_id' => $rabItem->id,
                        'item_code_snapshot' => $rabItem->data->kode ?? null,
                        'item_name_snapshot' => $rabItem->data->uraian ?? null,
                        'unit_snapshot' => $rabItem->satuan ?? ($rabItem->data->satuan ?? null),
                        'qty' => $row['qty'],
                        'unit' => $row['unit'] ?? ($rabItem->satuan ?? null),
                        'arrival_date' => $row['arrival_date'] ?? null,
                        'doc_reference' => $row['doc_reference'] ?? null,
                        'work_notes' => $row['work_notes'] ?? null,
                    ]);

                    if ($isApproved) {
                        StockMovement::create([
                            'project_id' => $lpb->project_id,
                            'rab_id' => $lpb->rab_id,
                            'rab_item_id' => $rabItem->id,
                            'movement_type' => 'in',
                            'doc_type' => 'lpb',
                            'doc_id' => $lpb->id,
                            'movement_date' => $item->arrival_date ?? $lpb->lpb_date,
                            'qty' => $item->qty,
                            'unit' => $item->unit,
                            'notes' => $item->doc_reference,
                        ]);
                    }
                }
            }
        });

        $lpb->refresh();
        AuditLogger::log('lpb.updated', $lpb, $before, $lpb->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return redirect()->route('dev.rabs.lpbs.show', [$projectId, $rabId, $lpbId])
            ->with('success', 'LPB berhasil diperbarui.');
    }

    public function destroy($projectId, $rabId, $lpbId)
    {
        $lpb = Lpb::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($lpbId);
        $this->ensureEditableStatus($lpb, 'LPB');
        $before = $lpb->toArray();
        StockMovement::where('doc_type', 'lpb')->where('doc_id', $lpb->id)->delete();
        $lpb->delete();
        AuditLogger::log('lpb.deleted', $lpb, $before, null, [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return redirect()->route('dev.rab-baseline.lpbs.index', [$projectId, $rabId])
            ->with('success', 'LPB berhasil dihapus.');
    }

    public function submit($projectId, $rabId, $lpbId)
    {
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $lpb = Lpb::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($lpbId);
        $po = $lpb->purchase_order_id
            ? PurchaseOrder::where('project_id', $projectId)->where('rab_id', $rabId)->find($lpb->purchase_order_id)
            : null;

        if (($rab->status ?? '') !== 'approved') {
            return back()->with('error', 'RAPP harus approved sebelum submit LPB.');
        }
        if (!$po || ($po->status ?? '') !== 'approved') {
            return back()->with('error', 'PO harus approved sebelum submit LPB.');
        }
        if (!in_array($lpb->status ?? 'draft', ['draft', 'rejected'], true)) {
            return back()->with('error', 'LPB hanya bisa disubmit dari status draft/rejected.');
        }

        $before = $lpb->toArray();
        $lpb->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => auth()->user()->name ?? null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        $lpb->refresh();
        AuditLogger::log('lpb.submitted', $lpb, $before, $lpb->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'LPB Diajukan',
            'LPB ' . ($lpb->lpb_no ?? $lpb->id) . ' diajukan' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.lpbs.show', [$projectId, $rabId, $lpbId]),
            'approval',
            [
                'doc_type' => 'lpb',
                'doc_id' => $lpb->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'LPB berhasil disubmit.');
    }

    public function approve($projectId, $rabId, $lpbId)
    {
        $this->ensureHOApproval();
        $lpb = Lpb::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($lpbId);

        if (($lpb->status ?? '') !== 'submitted') {
            return back()->with('error', 'LPB hanya bisa di-approve dari status submitted.');
        }

        $before = $lpb->toArray();
        $lpb->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->user()->name ?? null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        StockMovement::where('doc_type', 'lpb')->where('doc_id', $lpb->id)->delete();
        foreach ($lpb->items ?? [] as $item) {
            StockMovement::create([
                'project_id' => $lpb->project_id,
                'rab_id' => $lpb->rab_id,
                'rab_item_id' => $item->rab_item_id,
                'movement_type' => 'in',
                'doc_type' => 'lpb',
                'doc_id' => $lpb->id,
                'movement_date' => $item->arrival_date ?? $lpb->lpb_date,
                'qty' => $item->qty,
                'unit' => $item->unit,
                'notes' => $item->doc_reference,
            ]);
        }
        $lpb->refresh();
        AuditLogger::log('lpb.approved', $lpb, $before, $lpb->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);
        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'Stock Masuk (LPB)',
            'LPB ' . ($lpb->lpb_no ?? $lpb->id) . ' disetujui' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.lpbs.show', [$projectId, $rabId, $lpbId]),
            'stock',
            [
                'doc_type' => 'lpb',
                'doc_id' => $lpb->id,
                'project_id' => $projectId,
            ]
        );
        NotificationService::notifySubmitter(
            $lpb,
            'LPB Disetujui',
            'LPB ' . ($lpb->lpb_no ?? $lpb->id) . ' disetujui.',
            route('dev.rabs.lpbs.show', [$projectId, $rabId, $lpbId]),
            'approval',
            [
                'doc_type' => 'lpb',
                'doc_id' => $lpb->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'LPB berhasil di-approve.');
    }

    public function reject(Request $request, $projectId, $rabId, $lpbId)
    {
        $this->ensureHOApproval();
        $lpb = Lpb::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($lpbId);

        if (($lpb->status ?? '') !== 'submitted') {
            return back()->with('error', 'LPB hanya bisa di-reject dari status submitted.');
        }

        $before = $lpb->toArray();
        $reason = trim((string) $request->input('rejected_reason', ''));
        $lpb->update([
            'status' => 'rejected',
            'rejected_reason' => $reason !== '' ? $reason : null,
            'rejected_at' => now(),
            'rejected_by' => auth()->user()->name ?? null,
        ]);
        StockMovement::where('doc_type', 'lpb')->where('doc_id', $lpb->id)->delete();
        $bpgIds = Bpg::where('lpb_id', $lpb->id)->pluck('id');
        if ($bpgIds->isNotEmpty()) {
            Bpg::whereIn('id', $bpgIds)
                ->whereNotIn('status', ['rejected', 'cancelled'])
                ->update([
                    'status' => 'rejected',
                    'rejected_reason' => 'LPB ditolak',
                    'rejected_at' => now(),
                    'rejected_by' => auth()->user()->name ?? null,
                ]);
            StockMovement::where('doc_type', 'bpg')
                ->whereIn('doc_id', $bpgIds)
                ->delete();
        }
        $lpb->refresh();
        AuditLogger::log('lpb.rejected', $lpb, $before, $lpb->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);
        $message = 'LPB ' . ($lpb->lpb_no ?? $lpb->id) . ' ditolak.';
        if ($reason !== '') {
            $message .= ' Alasan: ' . $reason;
        }
        NotificationService::notifySubmitter(
            $lpb,
            'LPB Ditolak',
            $message,
            route('dev.rabs.lpbs.show', [$projectId, $rabId, $lpbId]),
            'approval',
            [
                'doc_type' => 'lpb',
                'doc_id' => $lpb->id,
                'project_id' => $projectId,
                'rejected_reason' => $reason,
            ]
        );

        return back()->with('success', 'LPB berhasil di-reject.');
    }

    public function print($projectId, $rabId, $lpbId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $lpb = Lpb::with(['vendor', 'purchaseOrder', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($lpbId);

        $logoSrc = asset('images/logo-dipo.png');
        return view('pdf.lpb', compact('project', 'rab', 'lpb', 'logoSrc'));
    }

    public function pdf($projectId, $rabId, $lpbId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $lpb = Lpb::with(['vendor', 'purchaseOrder', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($lpbId);

        $data = compact('project', 'rab', 'lpb');
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $filename = 'LPB-' . ($lpb->lpb_no ?? $lpb->id) . '.pdf';
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.lpb', $data)
                ->setPaper('a4', 'portrait')
                ->stream($filename);
        }

        return view('pdf.lpb', $data);
    }

    protected function assertQtyWithinPo(int $poId, array $items, ?int $currentLpbId): void
    {
        $poItems = PurchaseOrderItem::where('purchase_order_id', $poId)->get()->keyBy('rab_item_id');
        if ($poItems->isEmpty()) {
            return;
        }

        $requestedTotals = collect($items)
            ->filter(fn ($row) => !empty($row['rab_item_id']))
            ->groupBy(fn ($row) => (int) $row['rab_item_id'])
            ->map(fn ($rows) => $rows->sum(fn ($row) => (float) ($row['qty'] ?? 0)));

        foreach ($requestedTotals as $rabItemId => $requestedQty) {
            $rabItemId = (int) $rabItemId;
            $poItem = $poItems->get($rabItemId);
            if (!$poItem) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'Item LPB harus berasal dari PO yang dipilih.',
                ]);
            }

            $existingQty = LpbItem::whereHas('lpb', function ($q) use ($poId, $currentLpbId) {
                    $q->where('purchase_order_id', $poId)
                      ->whereNotIn('status', ['rejected', 'cancelled']);
                    if ($currentLpbId) {
                        $q->where('id', '!=', $currentLpbId);
                    }
                })
                ->where('rab_item_id', $rabItemId)
                ->sum('qty');

            $remaining = max(0, (float) ($poItem->qty ?? 0) - (float) $existingQty);
            if ($requestedQty > $remaining + 0.00001) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'Qty LPB melebihi sisa PO untuk item ' . ($poItem->item_code_snapshot ?? $rabItemId) . '. Sisa: ' . number_format($remaining, 2, ',', '.'),
                ]);
            }
        }
    }
}



