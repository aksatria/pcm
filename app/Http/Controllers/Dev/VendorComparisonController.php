<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Rab;
use App\Models\RabItem;
use App\Models\Vendor;
use App\Models\VendorComparison;
use App\Models\VendorComparisonItem;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Dev\Concerns\ValidatesRabScope;
use App\Http\Controllers\Dev\Concerns\LocksDocumentStatus;
use App\Support\AuditLogger;
use App\Support\NotificationService;

class VendorComparisonController extends Controller
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
        $comparisons = VendorComparison::with('decisionVendor')
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->orderByDesc('id')
            ->get();

        return view('dev.vendor_comparisons.index', compact('project', 'rab', 'comparisons'));
    }

    public function create($projectId, $rabId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $rabItems = RabItem::with('data')->where('rab_id', $rabId)->get();
        $vendors = Vendor::forProject($project->id)->orderBy('nama')->get();

        return view('dev.vendor_comparisons.create', compact('project', 'rab', 'rabItems', 'vendors'));
    }

    public function store(Request $request, $projectId, $rabId, DocumentNumberService $numberService)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);

        $validated = $request->validate([
            'comparison_no' => 'nullable|string|max:100',
            'comparison_date' => 'nullable|date',
            'status' => 'nullable|string|max:20',
            'decision_vendor_id' => 'nullable|exists:vendors,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rab_item_id' => ['required', Rule::exists('rab_items', 'id')->where('rab_id', $rabId)],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.rapp_unit_price' => 'nullable|numeric|min:0',
            'items.*.vendor1_id' => 'nullable|exists:vendors,id',
            'items.*.vendor1_unit_price' => 'nullable|numeric|min:0',
            'items.*.vendor2_id' => 'nullable|exists:vendors,id',
            'items.*.vendor2_unit_price' => 'nullable|numeric|min:0',
            'items.*.vendor3_id' => 'nullable|exists:vendors,id',
            'items.*.vendor3_unit_price' => 'nullable|numeric|min:0',
        ]);

        $items = $validated['items'] ?? [];
        $vendorIds = collect($items)
            ->flatMap(function ($row) {
                return [
                    $row['vendor1_id'] ?? null,
                    $row['vendor2_id'] ?? null,
                    $row['vendor3_id'] ?? null,
                ];
            })
            ->merge([$validated['decision_vendor_id'] ?? null])
            ->all();
        $this->assertVendorsInProject($vendorIds, $projectId, 'items');

        $rabItems = $this->loadRabItemsOrFail($rabId, $items);
        $this->assertQtyWithinRabBudget(
            $items,
            $rabItems,
            VendorComparisonItem::class,
            'vendor_comparison_id',
            null,
            'comparison',
            $projectId,
            $rabId
        );

        $cmpDate = !empty($validated['comparison_date']) ? Carbon::parse($validated['comparison_date']) : null;
        $cmpNo = $validated['comparison_no'] ?: $numberService->nextNumber($projectId, 'KOM', $project->code ?? null, $cmpDate);

        $comparison = null;
        DB::transaction(function () use (&$comparison, $validated, $projectId, $rabId, $cmpNo) {
            $comparison = VendorComparison::create([
                'project_id' => $projectId,
                'rab_id' => $rabId,
                'comparison_no' => $cmpNo,
                'comparison_date' => $validated['comparison_date'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'decision_vendor_id' => $validated['decision_vendor_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $finalAmount = 0;
            $difference = 0;
            $items = $validated['items'] ?? [];
            foreach ($items as $row) {
                $rabItem = RabItem::with('data')->find($row['rab_item_id']);
                if (!$rabItem) {
                    continue;
                }

                $qty = (float) $row['qty'];
                $rappUnit = (float) ($row['rapp_unit_price'] ?? $rabItem->harga_satuan ?? 0);
                $rappTotal = $qty * $rappUnit;

                $v1Unit = (float) ($row['vendor1_unit_price'] ?? 0);
                $v2Unit = (float) ($row['vendor2_unit_price'] ?? 0);
                $v3Unit = (float) ($row['vendor3_unit_price'] ?? 0);

                VendorComparisonItem::create([
                    'vendor_comparison_id' => $comparison->id,
                    'rab_item_id' => $rabItem->id,
                    'item_code_snapshot' => $rabItem->data->kode ?? null,
                    'item_name_snapshot' => $rabItem->data->uraian ?? null,
                    'unit_snapshot' => $rabItem->satuan ?? ($rabItem->data->satuan ?? null),
                    'qty' => $qty,
                    'unit' => $row['unit'] ?? ($rabItem->satuan ?? null),
                    'rapp_unit_price' => $rappUnit,
                    'rapp_total' => $rappTotal,
                    'vendor1_id' => $row['vendor1_id'] ?? null,
                    'vendor1_unit_price' => $v1Unit,
                    'vendor1_total' => $qty * $v1Unit,
                    'vendor2_id' => $row['vendor2_id'] ?? null,
                    'vendor2_unit_price' => $v2Unit,
                    'vendor2_total' => $qty * $v2Unit,
                    'vendor3_id' => $row['vendor3_id'] ?? null,
                    'vendor3_unit_price' => $v3Unit,
                    'vendor3_total' => $qty * $v3Unit,
                ]);

                $finalAmount += $rappTotal;
            }

            $difference = $finalAmount;
            $comparison->update([
                'final_amount' => $finalAmount,
                'difference_amount' => $difference,
            ]);

            AuditLogger::log('vendor_comparison.created', $comparison, null, $comparison->toArray(), [
                'project_id' => $projectId,
                'rab_id' => $rabId,
            ]);
        });

        return redirect()->route('dev.rab-baseline.vendor-comparisons.index', [$projectId, $rabId])
            ->with('success', 'Komparasi vendor berhasil dibuat.');
    }

    public function show($projectId, $rabId, $comparisonId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $comparison = VendorComparison::with(['decisionVendor', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($comparisonId);

        return view('dev.vendor_comparisons.show', compact('project', 'rab', 'comparison'));
    }

    public function edit($projectId, $rabId, $comparisonId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $comparison = VendorComparison::with(['items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($comparisonId);
        $rabItems = RabItem::with('data')->where('rab_id', $rabId)->get();
        $vendors = Vendor::forProject($project->id)->orderBy('nama')->get();

        return view('dev.vendor_comparisons.edit', compact('project', 'rab', 'comparison', 'rabItems', 'vendors'));
    }

    public function update(Request $request, $projectId, $rabId, $comparisonId)
    {
        $validated = $request->validate([
            'comparison_no' => 'nullable|string|max:100',
            'comparison_date' => 'nullable|date',
            'status' => 'nullable|string|max:20',
            'decision_vendor_id' => 'nullable|exists:vendors,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rab_item_id' => ['required', Rule::exists('rab_items', 'id')->where('rab_id', $rabId)],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.rapp_unit_price' => 'nullable|numeric|min:0',
            'items.*.vendor1_id' => 'nullable|exists:vendors,id',
            'items.*.vendor1_unit_price' => 'nullable|numeric|min:0',
            'items.*.vendor2_id' => 'nullable|exists:vendors,id',
            'items.*.vendor2_unit_price' => 'nullable|numeric|min:0',
            'items.*.vendor3_id' => 'nullable|exists:vendors,id',
            'items.*.vendor3_unit_price' => 'nullable|numeric|min:0',
        ]);

        $comparison = VendorComparison::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($comparisonId);
        $this->ensureEditableStatus($comparison, 'Komparasi Vendor');
        $before = $comparison->toArray();

        $items = $validated['items'] ?? [];
        $vendorIds = collect($items)
            ->flatMap(function ($row) {
                return [
                    $row['vendor1_id'] ?? null,
                    $row['vendor2_id'] ?? null,
                    $row['vendor3_id'] ?? null,
                ];
            })
            ->merge([$validated['decision_vendor_id'] ?? null])
            ->all();
        $this->assertVendorsInProject($vendorIds, $projectId, 'items');

        $rabItems = $this->loadRabItemsOrFail($rabId, $items);
        $this->assertQtyWithinRabBudget(
            $items,
            $rabItems,
            VendorComparisonItem::class,
            'vendor_comparison_id',
            $comparison->id,
            'comparison',
            $projectId,
            $rabId
        );

        DB::transaction(function () use ($validated, $comparison) {
            $comparison->update([
                'comparison_no' => $validated['comparison_no'] ?? $comparison->comparison_no,
                'comparison_date' => $validated['comparison_date'] ?? null,
                'status' => $validated['status'] ?? $comparison->status,
                'decision_vendor_id' => $validated['decision_vendor_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $finalAmount = 0;
            if (array_key_exists('items', $validated)) {
                $comparison->items()->delete();
                foreach ($validated['items'] as $row) {
                    $rabItem = RabItem::with('data')->find($row['rab_item_id']);
                    if (!$rabItem) {
                        continue;
                    }

                    $qty = (float) $row['qty'];
                    $rappUnit = (float) ($row['rapp_unit_price'] ?? $rabItem->harga_satuan ?? 0);
                    $rappTotal = $qty * $rappUnit;

                    $v1Unit = (float) ($row['vendor1_unit_price'] ?? 0);
                    $v2Unit = (float) ($row['vendor2_unit_price'] ?? 0);
                    $v3Unit = (float) ($row['vendor3_unit_price'] ?? 0);

                    VendorComparisonItem::create([
                        'vendor_comparison_id' => $comparison->id,
                        'rab_item_id' => $rabItem->id,
                        'item_code_snapshot' => $rabItem->data->kode ?? null,
                        'item_name_snapshot' => $rabItem->data->uraian ?? null,
                        'unit_snapshot' => $rabItem->satuan ?? ($rabItem->data->satuan ?? null),
                        'qty' => $qty,
                        'unit' => $row['unit'] ?? ($rabItem->satuan ?? null),
                        'rapp_unit_price' => $rappUnit,
                        'rapp_total' => $rappTotal,
                        'vendor1_id' => $row['vendor1_id'] ?? null,
                        'vendor1_unit_price' => $v1Unit,
                        'vendor1_total' => $qty * $v1Unit,
                        'vendor2_id' => $row['vendor2_id'] ?? null,
                        'vendor2_unit_price' => $v2Unit,
                        'vendor2_total' => $qty * $v2Unit,
                        'vendor3_id' => $row['vendor3_id'] ?? null,
                        'vendor3_unit_price' => $v3Unit,
                        'vendor3_total' => $qty * $v3Unit,
                    ]);

                    $finalAmount += $rappTotal;
                }
            }

            $comparison->update([
                'final_amount' => $finalAmount,
                'difference_amount' => $finalAmount,
            ]);
        });

        $comparison->refresh();
        AuditLogger::log('vendor_comparison.updated', $comparison, $before, $comparison->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return redirect()->route('dev.rabs.vendor-comparisons.show', [$projectId, $rabId, $comparisonId])
            ->with('success', 'Komparasi vendor berhasil diperbarui.');
    }

    public function destroy($projectId, $rabId, $comparisonId)
    {
        $comparison = VendorComparison::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($comparisonId);
        $this->ensureEditableStatus($comparison, 'Komparasi Vendor');
        $before = $comparison->toArray();
        $comparison->delete();
        AuditLogger::log('vendor_comparison.deleted', $comparison, $before, null, [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return redirect()->route('dev.rab-baseline.vendor-comparisons.index', [$projectId, $rabId])
            ->with('success', 'Komparasi vendor berhasil dihapus.');
    }

    public function submit($projectId, $rabId, $comparisonId)
    {
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $comparison = VendorComparison::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($comparisonId);

        if (($rab->status ?? '') !== 'approved') {
            return back()->with('error', 'RAPP harus approved sebelum submit Komparasi.');
        }
        if (!in_array($comparison->status ?? 'draft', ['draft', 'rejected'], true)) {
            return back()->with('error', 'Komparasi hanya bisa disubmit dari status draft/rejected.');
        }

        $before = $comparison->toArray();
        $comparison->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => auth()->user()->name ?? null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        $comparison->refresh();
        AuditLogger::log('vendor_comparison.submitted', $comparison, $before, $comparison->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'Komparasi Diajukan',
            'Komparasi ' . ($comparison->comparison_no ?? $comparison->id) . ' diajukan' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.vendor-comparisons.show', [$projectId, $rabId, $comparisonId]),
            'approval',
            [
                'doc_type' => 'vendor_comparison',
                'doc_id' => $comparison->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'Komparasi berhasil disubmit.');
    }

    public function approve($projectId, $rabId, $comparisonId)
    {
        $this->ensureHOApproval();
        $comparison = VendorComparison::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($comparisonId);

        if (($comparison->status ?? '') !== 'submitted') {
            return back()->with('error', 'Komparasi hanya bisa di-approve dari status submitted.');
        }

        $before = $comparison->toArray();
        $comparison->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->user()->name ?? null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        $comparison->refresh();
        AuditLogger::log('vendor_comparison.approved', $comparison, $before, $comparison->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);
        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'Komparasi Disetujui',
            'Komparasi ' . ($comparison->comparison_no ?? $comparison->id) . ' disetujui' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.vendor-comparisons.show', [$projectId, $rabId, $comparisonId]),
            'approval',
            [
                'doc_type' => 'vendor_comparison',
                'doc_id' => $comparison->id,
                'project_id' => $projectId,
            ]
        );
        NotificationService::notifySubmitter(
            $comparison,
            'Komparasi Disetujui',
            'Komparasi ' . ($comparison->comparison_no ?? $comparison->id) . ' disetujui.',
            route('dev.rabs.vendor-comparisons.show', [$projectId, $rabId, $comparisonId]),
            'approval',
            [
                'doc_type' => 'vendor_comparison',
                'doc_id' => $comparison->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'Komparasi berhasil di-approve.');
    }

    public function reject(Request $request, $projectId, $rabId, $comparisonId)
    {
        $this->ensureHOApproval();
        $comparison = VendorComparison::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($comparisonId);

        if (($comparison->status ?? '') !== 'submitted') {
            return back()->with('error', 'Komparasi hanya bisa di-reject dari status submitted.');
        }

        $before = $comparison->toArray();
        $reason = trim((string) $request->input('rejected_reason', ''));
        $comparison->update([
            'status' => 'rejected',
            'rejected_reason' => $reason !== '' ? $reason : null,
            'rejected_at' => now(),
            'rejected_by' => auth()->user()->name ?? null,
        ]);
        $comparison->refresh();
        AuditLogger::log('vendor_comparison.rejected', $comparison, $before, $comparison->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);
        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'Komparasi Ditolak',
            'Komparasi ' . ($comparison->comparison_no ?? $comparison->id) . ' ditolak' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.vendor-comparisons.show', [$projectId, $rabId, $comparisonId]),
            'approval',
            [
                'doc_type' => 'vendor_comparison',
                'doc_id' => $comparison->id,
                'project_id' => $projectId,
            ]
        );
        $message = 'Komparasi ' . ($comparison->comparison_no ?? $comparison->id) . ' ditolak.';
        if ($reason !== '') {
            $message .= ' Alasan: ' . $reason;
        }
        NotificationService::notifySubmitter(
            $comparison,
            'Komparasi Ditolak',
            $message,
            route('dev.rabs.vendor-comparisons.show', [$projectId, $rabId, $comparisonId]),
            'approval',
            [
                'doc_type' => 'vendor_comparison',
                'doc_id' => $comparison->id,
                'project_id' => $projectId,
                'rejected_reason' => $reason,
            ]
        );

        return back()->with('success', 'Komparasi berhasil di-reject.');
    }

    public function print($projectId, $rabId, $comparisonId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $comparison = VendorComparison::with([
            'decisionVendor',
            'items.rabItem.data',
            'items.vendor1',
            'items.vendor2',
            'items.vendor3',
        ])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($comparisonId);

        return view('pdf.vendor_comparison', compact('project', 'rab', 'comparison'));
    }

    public function pdf($projectId, $rabId, $comparisonId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $comparison = VendorComparison::with([
            'decisionVendor',
            'items.rabItem.data',
            'items.vendor1',
            'items.vendor2',
            'items.vendor3',
        ])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($comparisonId);

        $data = compact('project', 'rab', 'comparison');
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $filename = 'KOM-' . ($comparison->comparison_no ?? $comparison->id) . '.pdf';
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.vendor_comparison', $data)
                ->setPaper('a4', 'landscape')
                ->stream($filename);
        }

        return view('pdf.vendor_comparison', $data);
    }
}



