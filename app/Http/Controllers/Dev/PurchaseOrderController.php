<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseVoucher;
use App\Models\Rab;
use App\Models\RabItem;
use App\Models\Vendor;
use App\Models\Lpb;
use App\Models\Bpg;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Dev\Concerns\ValidatesRabScope;
use App\Http\Controllers\Dev\Concerns\LocksDocumentStatus;
use App\Support\AuditLogger;
use App\Support\NotificationService;

class PurchaseOrderController extends Controller
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
        $purchaseOrders = PurchaseOrder::with('vendor')
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->orderByDesc('id')
            ->get();

        return view('dev.purchase_orders.index', compact('project', 'rab', 'purchaseOrders'));
    }

    public function create($projectId, $rabId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $rabItems = RabItem::with('data')->where('rab_id', $rabId)->get();
        $remainingMap = $this->getRabRemainingMap($projectId, $rabId, null);
        $vendors = Vendor::forProject($project->id)->orderBy('nama')->get();

        return view('dev.purchase_orders.create', compact('project', 'rab', 'rabItems', 'vendors', 'remainingMap'));
    }

    public function store(Request $request, $projectId, $rabId, DocumentNumberService $numberService)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);

        $validated = $request->validate([
            'po_no' => 'nullable|string|max:100',
            'po_date' => 'nullable|date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:20',
            'tax_percent' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rab_item_id' => ['required', Rule::exists('rab_items', 'id')->where('rab_id', $rabId)],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.specification' => 'nullable|string|max:255',
        ]);

        $this->assertVendorsInProject([$validated['vendor_id'] ?? null], $projectId, 'vendor_id');
        $items = $validated['items'] ?? [];
        $rabItems = $this->loadRabItemsOrFail($rabId, $items);
        $this->assertQtyWithinRabBudget(
            $items,
            $rabItems,
            PurchaseOrderItem::class,
            'purchase_order_id',
            null,
            'purchaseOrder',
            $projectId,
            $rabId
        );
        $this->assertUnitPriceWithinThreshold($items, $rabItems, 15);

        $poDate = !empty($validated['po_date']) ? Carbon::parse($validated['po_date']) : null;
        $poNo = $validated['po_no'] ?: $numberService->nextNumber($projectId, 'PO', $project->code ?? null, $poDate);

        $po = null;
        DB::transaction(function () use (&$po, $validated, $projectId, $rabId, $poNo) {
            $po = PurchaseOrder::create([
                'project_id' => $projectId,
                'rab_id' => $rabId,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'po_no' => $poNo,
                'po_date' => $validated['po_date'] ?? null,
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'tax_percent' => $validated['tax_percent'] ?? 11,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            $subtotal = 0;
            $items = $validated['items'] ?? [];
            foreach ($items as $row) {
                $rabItem = RabItem::with('data')->find($row['rab_item_id']);
                if (!$rabItem) {
                    continue;
                }
                $totalPrice = (float) $row['qty'] * (float) $row['unit_price'];
                $subtotal += $totalPrice;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'rab_item_id' => $rabItem->id,
                    'item_code_snapshot' => $rabItem->data->kode ?? null,
                    'item_name_snapshot' => $rabItem->data->uraian ?? null,
                    'unit_snapshot' => $rabItem->satuan ?? ($rabItem->data->satuan ?? null),
                    'specification' => $row['specification'] ?? null,
                    'qty' => $row['qty'],
                    'unit' => $row['unit'] ?? ($rabItem->satuan ?? null),
                    'unit_price' => $row['unit_price'],
                    'total_price' => $totalPrice,
                ]);
            }

            $taxPercent = (float) ($po->tax_percent ?? 0);
            $taxAmount = ($subtotal * $taxPercent) / 100;
            $shipping = (float) ($po->shipping_cost ?? 0);
            $total = $subtotal + $taxAmount + $shipping;

            $po->update([
                'subtotal_amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
            ]);

            AuditLogger::log('po.created', $po, null, $po->toArray(), [
                'project_id' => $projectId,
                'rab_id' => $rabId,
            ]);
        });

        return redirect()->route('dev.rab-baseline.purchase-orders.index', [$projectId, $rabId])
            ->with('success', 'PO berhasil dibuat.');
    }

    public function show($projectId, $rabId, $poId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $purchaseOrder = PurchaseOrder::with(['vendor', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($poId);

        return view('dev.purchase_orders.show', compact('project', 'rab', 'purchaseOrder'));
    }

    public function edit($projectId, $rabId, $poId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $purchaseOrder = PurchaseOrder::with(['items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($poId);
        $rabItems = RabItem::with('data')->where('rab_id', $rabId)->get();
        $remainingMap = $this->getRabRemainingMap($projectId, $rabId, $purchaseOrder->id);
        $vendors = Vendor::forProject($project->id)->orderBy('nama')->get();

        return view('dev.purchase_orders.edit', compact('project', 'rab', 'purchaseOrder', 'rabItems', 'vendors', 'remainingMap'));
    }

    protected function getRabRemainingMap(int $projectId, int $rabId, ?int $currentPoId): array
    {
        $rabItems = RabItem::with('data')
            ->where('rab_id', $rabId)
            ->get()
            ->keyBy('id');

        if ($rabItems->isEmpty()) {
            return [];
        }

        $existingTotals = PurchaseOrderItem::query()
            ->selectRaw('rab_item_id, SUM(qty) as total_qty')
            ->whereIn('rab_item_id', $rabItems->keys())
            ->when($currentPoId, function ($query) use ($currentPoId) {
                $query->where('purchase_order_id', '!=', $currentPoId);
            })
            ->whereHas('purchaseOrder', function ($query) use ($projectId, $rabId) {
                $query->where('project_id', $projectId)
                    ->where('rab_id', $rabId)
                    ->whereNotIn('status', ['rejected', 'cancelled']);
            })
            ->groupBy('rab_item_id')
            ->pluck('total_qty', 'rab_item_id');

        $remaining = [];
        foreach ($rabItems as $rabItemId => $rabItem) {
            $budgetVol = (float) ($rabItem->volume ?? 0);
            $usedQty = (float) ($existingTotals[$rabItemId] ?? 0);
            $remaining[$rabItemId] = max(0, $budgetVol - $usedQty);
        }

        return $remaining;
    }

    public function update(Request $request, $projectId, $rabId, $poId)
    {
        $validated = $request->validate([
            'po_no' => 'nullable|string|max:100',
            'po_date' => 'nullable|date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:20',
            'tax_percent' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rab_item_id' => ['required', Rule::exists('rab_items', 'id')->where('rab_id', $rabId)],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.specification' => 'nullable|string|max:255',
        ]);

        $po = PurchaseOrder::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($poId);
        $this->ensureEditableStatus($po, 'PO');
        $before = $po->toArray();

        $this->assertVendorsInProject([$validated['vendor_id'] ?? null], $projectId, 'vendor_id');
        $items = $validated['items'] ?? [];
        $rabItems = $this->loadRabItemsOrFail($rabId, $items);
        $this->assertQtyWithinRabBudget(
            $items,
            $rabItems,
            PurchaseOrderItem::class,
            'purchase_order_id',
            $po->id,
            'purchaseOrder',
            $projectId,
            $rabId
        );
        $this->assertUnitPriceWithinThreshold($items, $rabItems, 15);

        DB::transaction(function () use ($validated, $po) {
            $po->update([
                'vendor_id' => $validated['vendor_id'] ?? null,
                'po_no' => $validated['po_no'] ?? $po->po_no,
                'po_date' => $validated['po_date'] ?? null,
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'status' => $validated['status'] ?? $po->status,
                'tax_percent' => $validated['tax_percent'] ?? $po->tax_percent,
                'shipping_cost' => $validated['shipping_cost'] ?? $po->shipping_cost,
                'notes' => $validated['notes'] ?? null,
            ]);

            $subtotal = 0;
            if (array_key_exists('items', $validated)) {
                $po->items()->delete();
                foreach ($validated['items'] as $row) {
                    $rabItem = RabItem::with('data')->find($row['rab_item_id']);
                    if (!$rabItem) {
                        continue;
                    }
                    $totalPrice = (float) $row['qty'] * (float) $row['unit_price'];
                    $subtotal += $totalPrice;

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'rab_item_id' => $rabItem->id,
                        'item_code_snapshot' => $rabItem->data->kode ?? null,
                        'item_name_snapshot' => $rabItem->data->uraian ?? null,
                        'unit_snapshot' => $rabItem->satuan ?? ($rabItem->data->satuan ?? null),
                        'specification' => $row['specification'] ?? null,
                        'qty' => $row['qty'],
                        'unit' => $row['unit'] ?? ($rabItem->satuan ?? null),
                        'unit_price' => $row['unit_price'],
                        'total_price' => $totalPrice,
                    ]);
                }
            }

            $taxPercent = (float) ($po->tax_percent ?? 0);
            $taxAmount = ($subtotal * $taxPercent) / 100;
            $shipping = (float) ($po->shipping_cost ?? 0);
            $total = $subtotal + $taxAmount + $shipping;

            $po->update([
                'subtotal_amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
            ]);
        });

        $po->refresh();
        AuditLogger::log('po.updated', $po, $before, $po->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return redirect()->route('dev.rabs.purchase-orders.show', [$projectId, $rabId, $poId])
            ->with('success', 'PO berhasil diperbarui.');
    }

    protected function assertUnitPriceWithinThreshold(array $items, $rabItems, float $thresholdPercent): void
    {
        foreach ($items as $idx => $row) {
            $rabItemId = (int) ($row['rab_item_id'] ?? 0);
            if ($rabItemId <= 0) {
                continue;
            }
            $rabItem = $rabItems->get($rabItemId);
            if (!$rabItem) {
                continue;
            }
            $rappPrice = (float) ($rabItem->harga_satuan ?? 0);
            $unitPrice = (float) ($row['unit_price'] ?? 0);
            if ($rappPrice <= 0) {
                continue;
            }
            $maxAllowed = $rappPrice * (1 + ($thresholdPercent / 100));
            if ($unitPrice > $maxAllowed + 0.00001) {
                $percentOver = (($unitPrice / $rappPrice) - 1) * 100;
                $itemCode = $rabItem->data?->kode ?? $rabItemId;
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'Harga satuan item ' . $itemCode . ' melebihi batas ' . number_format($thresholdPercent, 2, ',', '.') . '%. ' .
                        'Melebihi: ' . number_format($percentOver, 2, ',', '.') . '%.',
                ]);
            }
        }
    }

    public function destroy($projectId, $rabId, $poId)
    {
        $po = PurchaseOrder::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($poId);
        $this->ensureEditableStatus($po, 'PO');
        $before = $po->toArray();
        $po->delete();
        AuditLogger::log('po.deleted', $po, $before, null, [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return redirect()->route('dev.rab-baseline.purchase-orders.index', [$projectId, $rabId])
            ->with('success', 'PO berhasil dihapus.');
    }

    public function submit($projectId, $rabId, $poId)
    {
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $po = PurchaseOrder::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($poId);

        if (($rab->status ?? '') !== 'approved') {
            return back()->with('error', 'RAPP harus approved sebelum submit PO.');
        }
        if (!in_array($po->status ?? 'draft', ['draft', 'rejected'], true)) {
            return back()->with('error', 'PO hanya bisa disubmit dari status draft/rejected.');
        }

        $before = $po->toArray();
        $po->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => auth()->user()->name ?? null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        $po->refresh();
        AuditLogger::log('po.submitted', $po, $before, $po->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'PO Diajukan',
            'PO ' . ($po->po_no ?? $po->id) . ' diajukan' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.purchase-orders.show', [$projectId, $rabId, $poId]),
            'approval',
            [
                'doc_type' => 'po',
                'doc_id' => $po->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'PO berhasil disubmit.');
    }

    public function approve($projectId, $rabId, $poId)
    {
        $this->ensureHOApproval();
        $po = PurchaseOrder::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($poId);

        if (($po->status ?? '') !== 'submitted') {
            return back()->with('error', 'PO hanya bisa di-approve dari status submitted.');
        }

        $before = $po->toArray();
        $po->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->user()->name ?? null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        $po->refresh();
        AuditLogger::log('po.approved', $po, $before, $po->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);
        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'PO Disetujui',
            'PO ' . ($po->po_no ?? $po->id) . ' disetujui' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.purchase-orders.show', [$projectId, $rabId, $poId]),
            'approval',
            [
                'doc_type' => 'po',
                'doc_id' => $po->id,
                'project_id' => $projectId,
            ]
        );
        NotificationService::notifySubmitter(
            $po,
            'PO Disetujui',
            'PO ' . ($po->po_no ?? $po->id) . ' disetujui.',
            route('dev.rabs.purchase-orders.show', [$projectId, $rabId, $poId]),
            'approval',
            [
                'doc_type' => 'po',
                'doc_id' => $po->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'PO berhasil di-approve.');
    }

    public function reject(Request $request, $projectId, $rabId, $poId)
    {
        $this->ensureHOApproval();
        $po = PurchaseOrder::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($poId);

        if (($po->status ?? '') !== 'submitted') {
            return back()->with('error', 'PO hanya bisa di-reject dari status submitted.');
        }

        $before = $po->toArray();
        $reason = trim((string) $request->input('rejected_reason', ''));
        $po->update([
            'status' => 'rejected',
            'rejected_reason' => $reason !== '' ? $reason : null,
            'rejected_at' => now(),
            'rejected_by' => auth()->user()->name ?? null,
        ]);
        $po->refresh();
        AuditLogger::log('po.rejected', $po, $before, $po->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);
        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'PO Ditolak',
            'PO ' . ($po->po_no ?? $po->id) . ' ditolak' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.purchase-orders.show', [$projectId, $rabId, $poId]),
            'approval',
            [
                'doc_type' => 'po',
                'doc_id' => $po->id,
                'project_id' => $projectId,
            ]
        );
        $message = 'PO ' . ($po->po_no ?? $po->id) . ' ditolak.';
        if ($reason !== '') {
            $message .= ' Alasan: ' . $reason;
        }
        NotificationService::notifySubmitter(
            $po,
            'PO Ditolak',
            $message,
            route('dev.rabs.purchase-orders.show', [$projectId, $rabId, $poId]),
            'approval',
            [
                'doc_type' => 'po',
                'doc_id' => $po->id,
                'project_id' => $projectId,
                'rejected_reason' => $reason,
            ]
        );

        return back()->with('success', 'PO berhasil di-reject.');
    }

    public function cancel(Request $request, $projectId, $rabId, $poId)
    {
        $po = PurchaseOrder::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($poId);

        if (($po->status ?? '') === 'cancelled') {
            return back()->with('error', 'PO sudah dibatalkan.');
        }

        $before = $po->toArray();
        DB::transaction(function () use ($po) {
            $po->update(['status' => 'cancelled']);

            $now = now();
            $rejectBy = auth()->user()->name ?? null;

            $lpbIds = Lpb::where('purchase_order_id', $po->id)->pluck('id');
            if ($lpbIds->isNotEmpty()) {
                Lpb::whereIn('id', $lpbIds)
                    ->whereNotIn('status', ['rejected', 'cancelled'])
                    ->update([
                        'status' => 'rejected',
                        'rejected_reason' => 'PO dibatalkan',
                        'rejected_at' => $now,
                        'rejected_by' => $rejectBy,
                    ]);

                PurchaseVoucher::whereIn('lpb_id', $lpbIds)
                    ->whereNotIn('status', ['rejected', 'cancelled'])
                    ->update([
                        'status' => 'rejected',
                        'rejected_reason' => 'PO dibatalkan',
                        'rejected_at' => $now,
                        'rejected_by' => $rejectBy,
                    ]);

                // Hapus stock movement LPB yang terdampak
                \App\Models\StockMovement::where('doc_type', 'lpb')
                    ->whereIn('doc_id', $lpbIds)
                    ->delete();

                $bpgIds = Bpg::whereIn('lpb_id', $lpbIds)->pluck('id');
                if ($bpgIds->isNotEmpty()) {
                    Bpg::whereIn('id', $bpgIds)
                        ->whereNotIn('status', ['rejected', 'cancelled'])
                        ->update([
                            'status' => 'rejected',
                            'rejected_reason' => 'PO dibatalkan',
                            'rejected_at' => $now,
                            'rejected_by' => $rejectBy,
                        ]);
                    \App\Models\StockMovement::where('doc_type', 'bpg')
                        ->whereIn('doc_id', $bpgIds)
                        ->delete();
                }
            }

            PurchaseVoucher::where('purchase_order_id', $po->id)
                ->whereNotIn('status', ['rejected', 'cancelled'])
                ->update([
                    'status' => 'rejected',
                    'rejected_reason' => 'PO dibatalkan',
                    'rejected_at' => $now,
                    'rejected_by' => $rejectBy,
                ]);
        });

        $po->refresh();
        AuditLogger::log('po.cancelled', $po, $before, $po->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return back()->with('success', 'PO berhasil dibatalkan. LPB/Voucher terkait otomatis ditolak.');
    }

    public function print($projectId, $rabId, $poId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $purchaseOrder = PurchaseOrder::with(['vendor', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($poId);

        $logoSrc = asset('images/logo-dipo.png');
        return view('pdf.purchase_order', compact('project', 'rab', 'purchaseOrder', 'logoSrc'));
    }

    public function pdf($projectId, $rabId, $poId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $purchaseOrder = PurchaseOrder::with(['vendor', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($poId);

        $data = compact('project', 'rab', 'purchaseOrder');
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $rawNo = $purchaseOrder->po_no ?? $purchaseOrder->id;
            $safeNo = preg_replace('/[\\\\\\/]+/', '-', (string) $rawNo);
            $filename = 'PO-' . $safeNo . '.pdf';
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.purchase_order', $data)
                ->setPaper('a4', 'portrait')
                ->stream($filename);
        }

        return view('pdf.purchase_order', $data);
    }
}



