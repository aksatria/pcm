<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Rab;
use App\Models\RabItem;
use App\Models\Spp;
use App\Models\SppItem;
use App\Models\Vendor;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Dev\Concerns\ValidatesRabScope;
use App\Http\Controllers\Dev\Concerns\LocksDocumentStatus;
use App\Support\AuditLogger;
use App\Support\NotificationService;

class SppController extends Controller
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
        $spps = Spp::with('vendor')
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->orderByDesc('id')
            ->get();

        return view('dev.spps.index', compact('project', 'rab', 'spps'));
    }

    public function create($projectId, $rabId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $rabItems = RabItem::with('data')->where('rab_id', $rabId)->get();
        $vendors = Vendor::forProject($project->id)->orderBy('nama')->get();

        return view('dev.spps.create', compact('project', 'rab', 'rabItems', 'vendors'));
    }

    public function store(Request $request, $projectId, $rabId, DocumentNumberService $numberService)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);

        $validated = $request->validate([
            'spp_no' => 'nullable|string|max:100',
            'spp_date' => 'nullable|date',
            'schedule_date' => 'nullable|date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'status' => 'nullable|string|max:20',
            'requested_by' => 'nullable|string|max:100',
            'approved_by' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rab_item_id' => ['required', Rule::exists('rab_items', 'id')->where('rab_id', $rabId)],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.schedule_date' => 'nullable|date',
            'items.*.work_notes' => 'nullable|string',
            'items.*.vendor_id' => 'nullable|exists:vendors,id',
        ]);

        $items = $validated['items'] ?? [];
        $this->assertVendorsInProject([$validated['vendor_id'] ?? null], $projectId, 'vendor_id');
        $this->assertVendorsInProject(collect($items)->pluck('vendor_id')->all(), $projectId, 'items');

        $rabItems = $this->loadRabItemsOrFail($rabId, $items);
        $this->assertQtyWithinRabBudget(
            $items,
            $rabItems,
            SppItem::class,
            'spp_id',
            null,
            'spp',
            $projectId,
            $rabId
        );

        $sppDate = !empty($validated['spp_date']) ? Carbon::parse($validated['spp_date']) : null;
        $sppNo = $validated['spp_no'] ?: $numberService->nextNumber($projectId, 'SPP', $project->code ?? null, $sppDate);

        $spp = null;
        DB::transaction(function () use (&$spp, $validated, $projectId, $rabId, $sppNo) {
            $spp = Spp::create([
                'project_id' => $projectId,
                'rab_id' => $rabId,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'spp_no' => $sppNo,
                'spp_date' => $validated['spp_date'] ?? null,
                'schedule_date' => $validated['schedule_date'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'requested_by' => $validated['requested_by'] ?? null,
                'approved_by' => $validated['approved_by'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $items = $validated['items'] ?? [];
            foreach ($items as $row) {
                $rabItem = RabItem::with('data')->find($row['rab_item_id']);
                if (!$rabItem) {
                    continue;
                }

                SppItem::create([
                    'spp_id' => $spp->id,
                    'rab_item_id' => $rabItem->id,
                    'vendor_id' => $row['vendor_id'] ?? null,
                    'item_code_snapshot' => $rabItem->data->kode ?? null,
                    'item_name_snapshot' => $rabItem->data->uraian ?? null,
                    'unit_snapshot' => $rabItem->satuan ?? ($rabItem->data->satuan ?? null),
                    'qty' => $row['qty'],
                    'unit' => $row['unit'] ?? ($rabItem->satuan ?? null),
                    'schedule_date' => $row['schedule_date'] ?? null,
                    'work_notes' => $row['work_notes'] ?? null,
                ]);
            }

            AuditLogger::log('spp.created', $spp, null, $spp->toArray(), [
                'project_id' => $projectId,
                'rab_id' => $rabId,
            ]);
        });

        return redirect()->route('dev.rab-baseline.spps.index', [$projectId, $rabId])
            ->with('success', 'SPP berhasil dibuat.');
    }

    public function show($projectId, $rabId, $sppId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $spp = Spp::with(['vendor', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($sppId);

        return view('dev.spps.show', compact('project', 'rab', 'spp'));
    }

    public function edit($projectId, $rabId, $sppId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $spp = Spp::with(['items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($sppId);
        $rabItems = RabItem::with('data')->where('rab_id', $rabId)->get();
        $vendors = Vendor::forProject($project->id)->orderBy('nama')->get();

        return view('dev.spps.edit', compact('project', 'rab', 'spp', 'rabItems', 'vendors'));
    }

    public function update(Request $request, $projectId, $rabId, $sppId)
    {
        $validated = $request->validate([
            'spp_no' => 'nullable|string|max:100',
            'spp_date' => 'nullable|date',
            'schedule_date' => 'nullable|date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'status' => 'nullable|string|max:20',
            'requested_by' => 'nullable|string|max:100',
            'approved_by' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rab_item_id' => ['required', Rule::exists('rab_items', 'id')->where('rab_id', $rabId)],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.schedule_date' => 'nullable|date',
            'items.*.work_notes' => 'nullable|string',
            'items.*.vendor_id' => 'nullable|exists:vendors,id',
        ]);

        $spp = Spp::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($sppId);
        $this->ensureEditableStatus($spp, 'SPP');
        $before = $spp->toArray();

        $items = $validated['items'] ?? [];
        $this->assertVendorsInProject([$validated['vendor_id'] ?? null], $projectId, 'vendor_id');
        $this->assertVendorsInProject(collect($items)->pluck('vendor_id')->all(), $projectId, 'items');

        $rabItems = $this->loadRabItemsOrFail($rabId, $items);
        $this->assertQtyWithinRabBudget(
            $items,
            $rabItems,
            SppItem::class,
            'spp_id',
            $spp->id,
            'spp',
            $projectId,
            $rabId
        );

        DB::transaction(function () use ($validated, $spp) {
            $spp->update([
                'vendor_id' => $validated['vendor_id'] ?? null,
                'spp_no' => $validated['spp_no'] ?? $spp->spp_no,
                'spp_date' => $validated['spp_date'] ?? null,
                'schedule_date' => $validated['schedule_date'] ?? null,
                'status' => $validated['status'] ?? $spp->status,
                'requested_by' => $validated['requested_by'] ?? null,
                'approved_by' => $validated['approved_by'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (array_key_exists('items', $validated)) {
                $spp->items()->delete();
                foreach ($validated['items'] as $row) {
                    $rabItem = RabItem::with('data')->find($row['rab_item_id']);
                    if (!$rabItem) {
                        continue;
                    }
                    SppItem::create([
                        'spp_id' => $spp->id,
                        'rab_item_id' => $rabItem->id,
                        'vendor_id' => $row['vendor_id'] ?? null,
                        'item_code_snapshot' => $rabItem->data->kode ?? null,
                        'item_name_snapshot' => $rabItem->data->uraian ?? null,
                        'unit_snapshot' => $rabItem->satuan ?? ($rabItem->data->satuan ?? null),
                        'qty' => $row['qty'],
                        'unit' => $row['unit'] ?? ($rabItem->satuan ?? null),
                        'schedule_date' => $row['schedule_date'] ?? null,
                        'work_notes' => $row['work_notes'] ?? null,
                    ]);
                }
            }
        });

        $spp->refresh();
        AuditLogger::log('spp.updated', $spp, $before, $spp->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return redirect()->route('dev.rabs.spps.show', [$projectId, $rabId, $sppId])
            ->with('success', 'SPP berhasil diperbarui.');
    }

    public function destroy($projectId, $rabId, $sppId)
    {
        $spp = Spp::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($sppId);
        $this->ensureEditableStatus($spp, 'SPP');
        $before = $spp->toArray();
        $spp->delete();
        AuditLogger::log('spp.deleted', $spp, $before, null, [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return redirect()->route('dev.rab-baseline.spps.index', [$projectId, $rabId])
            ->with('success', 'SPP berhasil dihapus.');
    }

    public function submit($projectId, $rabId, $sppId)
    {
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $spp = Spp::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($sppId);

        if (($rab->status ?? '') !== 'approved') {
            return back()->with('error', 'RAPP harus approved sebelum submit SPP.');
        }
        if (!in_array($spp->status ?? 'draft', ['draft', 'rejected'], true)) {
            return back()->with('error', 'SPP hanya bisa disubmit dari status draft/rejected.');
        }

        $before = $spp->toArray();
        $spp->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => auth()->user()->name ?? null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        $spp->refresh();
        AuditLogger::log('spp.submitted', $spp, $before, $spp->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'SPP Diajukan',
            'SPP ' . ($spp->spp_no ?? $spp->id) . ' diajukan' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.spps.show', [$projectId, $rabId, $sppId]),
            'approval',
            [
                'doc_type' => 'spp',
                'doc_id' => $spp->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'SPP berhasil disubmit.');
    }

    public function approve($projectId, $rabId, $sppId)
    {
        $this->ensureHOApproval();
        $spp = Spp::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($sppId);

        if (($spp->status ?? '') !== 'submitted') {
            return back()->with('error', 'SPP hanya bisa di-approve dari status submitted.');
        }

        $before = $spp->toArray();
        $spp->update([
            'status' => 'approved',
            'approved_by' => auth()->user()->name ?? $spp->approved_by,
            'approved_at' => now(),
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        $spp->refresh();
        AuditLogger::log('spp.approved', $spp, $before, $spp->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);
        NotificationService::notifySubmitter(
            $spp,
            'SPP Disetujui',
            'SPP ' . ($spp->spp_no ?? $spp->id) . ' disetujui.',
            route('dev.rabs.spps.show', [$projectId, $rabId, $sppId]),
            'approval',
            [
                'doc_type' => 'spp',
                'doc_id' => $spp->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'SPP berhasil di-approve.');
    }

    public function reject(Request $request, $projectId, $rabId, $sppId)
    {
        $this->ensureHOApproval();
        $spp = Spp::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($sppId);

        if (($spp->status ?? '') !== 'submitted') {
            return back()->with('error', 'SPP hanya bisa di-reject dari status submitted.');
        }

        $before = $spp->toArray();
        $reason = trim((string) $request->input('rejected_reason', ''));
        $spp->update([
            'status' => 'rejected',
            'rejected_reason' => $reason !== '' ? $reason : null,
            'rejected_at' => now(),
            'rejected_by' => auth()->user()->name ?? null,
        ]);
        $spp->refresh();
        AuditLogger::log('spp.rejected', $spp, $before, $spp->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);
        $message = 'SPP ' . ($spp->spp_no ?? $spp->id) . ' ditolak.';
        if ($reason !== '') {
            $message .= ' Alasan: ' . $reason;
        }
        NotificationService::notifySubmitter(
            $spp,
            'SPP Ditolak',
            $message,
            route('dev.rabs.spps.show', [$projectId, $rabId, $sppId]),
            'approval',
            [
                'doc_type' => 'spp',
                'doc_id' => $spp->id,
                'project_id' => $projectId,
                'rejected_reason' => $reason,
            ]
        );

        return back()->with('success', 'SPP berhasil di-reject.');
    }

    public function print($projectId, $rabId, $sppId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $spp = Spp::with(['vendor', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($sppId);

        return view('pdf.spp', compact('project', 'rab', 'spp'));
    }

    public function pdf($projectId, $rabId, $sppId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $spp = Spp::with(['vendor', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($sppId);

        $data = compact('project', 'rab', 'spp');
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $filename = 'SPP-' . ($spp->spp_no ?? $spp->id) . '.pdf';
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.spp', $data)
                ->setPaper('a4', 'portrait')
                ->stream($filename);
        }

        return view('pdf.spp', $data);
    }
}



