<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Rab;
use App\Models\RabItem;
use App\Models\Spk;
use App\Models\SpkItem;
use App\Models\Vendor;
use App\Models\VendorComparison;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Dev\Concerns\ValidatesRabScope;
use App\Http\Controllers\Dev\Concerns\LocksDocumentStatus;
use App\Support\AuditLogger;
use App\Support\NotificationService;

class SpkController extends Controller
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
        $spks = Spk::with('vendor')
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->orderByDesc('id')
            ->get();

        return view('dev.spks.index', compact('project', 'rab', 'spks'));
    }

    public function create($projectId, $rabId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $rabItems = RabItem::with('data')->where('rab_id', $rabId)->get();
        $vendors = Vendor::forProject($project->id)->orderBy('nama')->get();
        $approvedComparisons = VendorComparison::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->get();

        return view('dev.spks.create', compact('project', 'rab', 'rabItems', 'vendors', 'approvedComparisons'));
    }

    public function store(Request $request, $projectId, $rabId, DocumentNumberService $numberService)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);

        $validated = $request->validate([
            'spk_no' => 'nullable|string|max:100',
            'spk_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_comparison_id' => 'nullable|exists:vendor_comparisons,id',
            'status' => 'nullable|string|max:20',
            'scope' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rab_item_id' => ['required', Rule::exists('rab_items', 'id')->where('rab_id', $rabId)],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string|max:255',
        ]);

        if (!empty($validated['vendor_comparison_id'])) {
            $comparison = VendorComparison::where('project_id', $projectId)
                ->where('rab_id', $rabId)
                ->where('status', 'approved')
                ->where('id', $validated['vendor_comparison_id'])
                ->first();
            if (!$comparison) {
                return back()
                    ->withErrors(['vendor_comparison_id' => 'Komparasi harus approved dan sesuai proyek ini.'])
                    ->withInput();
            }
            if ($comparison->decision_vendor_id) {
                if (!empty($validated['vendor_id']) && (int) $validated['vendor_id'] !== (int) $comparison->decision_vendor_id) {
                    return back()
                        ->withErrors(['vendor_id' => 'Vendor SPK harus mengikuti vendor keputusan komparasi.'])
                        ->withInput();
                }
                $validated['vendor_id'] = $comparison->decision_vendor_id;
            }
        }

        $this->assertVendorsInProject([$validated['vendor_id'] ?? null], $projectId, 'vendor_id');
        $items = $validated['items'] ?? [];
        $rabItems = $this->loadRabItemsOrFail($rabId, $items);
        $this->assertQtyWithinRabBudget(
            $items,
            $rabItems,
            SpkItem::class,
            'spk_id',
            null,
            'spk',
            $projectId,
            $rabId
        );
        $this->assertUnitPriceWithinThreshold($items, $rabItems, 15);

        $spkDate = !empty($validated['spk_date']) ? Carbon::parse($validated['spk_date']) : null;
        $spkNo = $validated['spk_no'] ?: $numberService->nextNumber($projectId, 'SPK', $project->code ?? null, $spkDate);

        $spk = null;
        DB::transaction(function () use (&$spk, $validated, $projectId, $rabId, $spkNo) {
            $spk = Spk::create([
                'project_id' => $projectId,
                'rab_id' => $rabId,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'vendor_comparison_id' => $validated['vendor_comparison_id'] ?? null,
                'spk_no' => $spkNo,
                'spk_date' => $validated['spk_date'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'scope' => $validated['scope'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $total = 0;
            $items = $validated['items'] ?? [];
            foreach ($items as $row) {
                $rabItem = RabItem::with('data')->find($row['rab_item_id']);
                if (!$rabItem) {
                    continue;
                }
                $totalPrice = (float) $row['qty'] * (float) $row['unit_price'];
                $total += $totalPrice;

                SpkItem::create([
                    'spk_id' => $spk->id,
                    'rab_item_id' => $rabItem->id,
                    'item_code_snapshot' => $rabItem->data->kode ?? null,
                    'item_name_snapshot' => $rabItem->data->uraian ?? null,
                    'unit_snapshot' => $rabItem->satuan ?? ($rabItem->data->satuan ?? null),
                    'description' => $row['description'] ?? null,
                    'qty' => $row['qty'],
                    'unit' => $row['unit'] ?? ($rabItem->satuan ?? null),
                    'unit_price' => $row['unit_price'],
                    'total_price' => $totalPrice,
                ]);
            }

            $spk->update(['total_amount' => $total]);

            AuditLogger::log('spk.created', $spk, null, $spk->toArray(), [
                'project_id' => $projectId,
                'rab_id' => $rabId,
            ]);
        });

        return redirect()->route('dev.rab-baseline.spks.index', [$projectId, $rabId])
            ->with('success', 'SPK berhasil dibuat.');
    }

    public function show($projectId, $rabId, $spkId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $spk = Spk::with(['vendor', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($spkId);

        return view('dev.spks.show', compact('project', 'rab', 'spk'));
    }

    public function edit($projectId, $rabId, $spkId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $spk = Spk::with(['items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($spkId);
        $rabItems = RabItem::with('data')->where('rab_id', $rabId)->get();
        $vendors = Vendor::forProject($project->id)->orderBy('nama')->get();
        $approvedComparisons = VendorComparison::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->get();
        if ($spk->vendor_comparison_id && !$approvedComparisons->pluck('id')->contains($spk->vendor_comparison_id)) {
            $currentComparison = VendorComparison::where('project_id', $projectId)
                ->where('rab_id', $rabId)
                ->where('id', $spk->vendor_comparison_id)
                ->first();
            if ($currentComparison) {
                $approvedComparisons = $approvedComparisons->prepend($currentComparison);
            }
        }

        return view('dev.spks.edit', compact('project', 'rab', 'spk', 'rabItems', 'vendors', 'approvedComparisons'));
    }

    public function update(Request $request, $projectId, $rabId, $spkId)
    {
        $validated = $request->validate([
            'spk_no' => 'nullable|string|max:100',
            'spk_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'vendor_id' => 'nullable|exists:vendors,id',
            'vendor_comparison_id' => 'nullable|exists:vendor_comparisons,id',
            'status' => 'nullable|string|max:20',
            'scope' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.rab_item_id' => ['required', Rule::exists('rab_items', 'id')->where('rab_id', $rabId)],
            'items.*.qty' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string|max:255',
        ]);

        $spk = Spk::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($spkId);
        $this->ensureEditableStatus($spk, 'SPK');
        $before = $spk->toArray();

        if (!empty($validated['vendor_comparison_id'])) {
            $comparison = VendorComparison::where('project_id', $projectId)
                ->where('rab_id', $rabId)
                ->where('status', 'approved')
                ->where('id', $validated['vendor_comparison_id'])
                ->first();
            if (!$comparison) {
                return back()
                    ->withErrors(['vendor_comparison_id' => 'Komparasi harus approved dan sesuai proyek ini.'])
                    ->withInput();
            }
            if ($comparison->decision_vendor_id) {
                if (!empty($validated['vendor_id']) && (int) $validated['vendor_id'] !== (int) $comparison->decision_vendor_id) {
                    return back()
                        ->withErrors(['vendor_id' => 'Vendor SPK harus mengikuti vendor keputusan komparasi.'])
                        ->withInput();
                }
                $validated['vendor_id'] = $comparison->decision_vendor_id;
            }
        }

        $this->assertVendorsInProject([$validated['vendor_id'] ?? null], $projectId, 'vendor_id');
        $items = $validated['items'] ?? [];
        $rabItems = $this->loadRabItemsOrFail($rabId, $items);
        $this->assertQtyWithinRabBudget(
            $items,
            $rabItems,
            SpkItem::class,
            'spk_id',
            $spk->id,
            'spk',
            $projectId,
            $rabId
        );
        $this->assertUnitPriceWithinThreshold($items, $rabItems, 15);

        DB::transaction(function () use ($validated, $spk) {
            $spk->update([
                'vendor_id' => $validated['vendor_id'] ?? null,
                'vendor_comparison_id' => $validated['vendor_comparison_id'] ?? null,
                'spk_no' => $validated['spk_no'] ?? $spk->spk_no,
                'spk_date' => $validated['spk_date'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'status' => $validated['status'] ?? $spk->status,
                'scope' => $validated['scope'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $total = 0;
            if (array_key_exists('items', $validated)) {
                $spk->items()->delete();
                foreach ($validated['items'] as $row) {
                    $rabItem = RabItem::with('data')->find($row['rab_item_id']);
                    if (!$rabItem) {
                        continue;
                    }
                    $totalPrice = (float) $row['qty'] * (float) $row['unit_price'];
                    $total += $totalPrice;

                    SpkItem::create([
                        'spk_id' => $spk->id,
                        'rab_item_id' => $rabItem->id,
                        'item_code_snapshot' => $rabItem->data->kode ?? null,
                        'item_name_snapshot' => $rabItem->data->uraian ?? null,
                        'unit_snapshot' => $rabItem->satuan ?? ($rabItem->data->satuan ?? null),
                        'description' => $row['description'] ?? null,
                        'qty' => $row['qty'],
                        'unit' => $row['unit'] ?? ($rabItem->satuan ?? null),
                        'unit_price' => $row['unit_price'],
                        'total_price' => $totalPrice,
                    ]);
                }
            }

            $spk->update(['total_amount' => $total]);
        });

        $spk->refresh();
        AuditLogger::log('spk.updated', $spk, $before, $spk->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return redirect()->route('dev.rabs.spks.show', [$projectId, $rabId, $spkId])
            ->with('success', 'SPK berhasil diperbarui.');
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

    public function destroy($projectId, $rabId, $spkId)
    {
        $spk = Spk::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($spkId);
        $this->ensureEditableStatus($spk, 'SPK');
        $before = $spk->toArray();
        $spk->delete();
        AuditLogger::log('spk.deleted', $spk, $before, null, [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        return redirect()->route('dev.rab-baseline.spks.index', [$projectId, $rabId])
            ->with('success', 'SPK berhasil dihapus.');
    }

    public function submit($projectId, $rabId, $spkId)
    {
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $spk = Spk::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($spkId);

        if (($rab->status ?? '') !== 'approved') {
            return back()->with('error', 'RAPP harus approved sebelum submit SPK.');
        }
        if (!in_array($spk->status ?? 'draft', ['draft', 'rejected'], true)) {
            return back()->with('error', 'SPK hanya bisa disubmit dari status draft/rejected.');
        }

        $before = $spk->toArray();
        $spk->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => auth()->user()->name ?? null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        $spk->refresh();
        AuditLogger::log('spk.submitted', $spk, $before, $spk->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);

        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'SPK Diajukan',
            'SPK ' . ($spk->spk_no ?? $spk->id) . ' diajukan' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.spks.show', [$projectId, $rabId, $spkId]),
            'approval',
            [
                'doc_type' => 'spk',
                'doc_id' => $spk->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'SPK berhasil disubmit.');
    }

    public function approve($projectId, $rabId, $spkId)
    {
        $this->ensureHOApproval();
        $spk = Spk::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($spkId);

        if (($spk->status ?? '') !== 'submitted') {
            return back()->with('error', 'SPK hanya bisa di-approve dari status submitted.');
        }

        $before = $spk->toArray();
        $spk->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->user()->name ?? null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        $spk->refresh();
        AuditLogger::log('spk.approved', $spk, $before, $spk->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);
        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'SPK Disetujui',
            'SPK ' . ($spk->spk_no ?? $spk->id) . ' disetujui' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.spks.show', [$projectId, $rabId, $spkId]),
            'approval',
            [
                'doc_type' => 'spk',
                'doc_id' => $spk->id,
                'project_id' => $projectId,
            ]
        );
        NotificationService::notifySubmitter(
            $spk,
            'SPK Disetujui',
            'SPK ' . ($spk->spk_no ?? $spk->id) . ' disetujui.',
            route('dev.rabs.spks.show', [$projectId, $rabId, $spkId]),
            'approval',
            [
                'doc_type' => 'spk',
                'doc_id' => $spk->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'SPK berhasil di-approve.');
    }

    public function reject(Request $request, $projectId, $rabId, $spkId)
    {
        $this->ensureHOApproval();
        $spk = Spk::where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($spkId);

        if (($spk->status ?? '') !== 'submitted') {
            return back()->with('error', 'SPK hanya bisa di-reject dari status submitted.');
        }

        $before = $spk->toArray();
        $reason = trim((string) $request->input('rejected_reason', ''));
        $spk->update([
            'status' => 'rejected',
            'rejected_reason' => $reason !== '' ? $reason : null,
            'rejected_at' => now(),
            'rejected_by' => auth()->user()->name ?? null,
        ]);
        $spk->refresh();
        AuditLogger::log('spk.rejected', $spk, $before, $spk->toArray(), [
            'project_id' => $projectId,
            'rab_id' => $rabId,
        ]);
        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'SPK Ditolak',
            'SPK ' . ($spk->spk_no ?? $spk->id) . ' ditolak' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rabs.spks.show', [$projectId, $rabId, $spkId]),
            'approval',
            [
                'doc_type' => 'spk',
                'doc_id' => $spk->id,
                'project_id' => $projectId,
            ]
        );
        $message = 'SPK ' . ($spk->spk_no ?? $spk->id) . ' ditolak.';
        if ($reason !== '') {
            $message .= ' Alasan: ' . $reason;
        }
        NotificationService::notifySubmitter(
            $spk,
            'SPK Ditolak',
            $message,
            route('dev.rabs.spks.show', [$projectId, $rabId, $spkId]),
            'approval',
            [
                'doc_type' => 'spk',
                'doc_id' => $spk->id,
                'project_id' => $projectId,
                'rejected_reason' => $reason,
            ]
        );

        return back()->with('success', 'SPK berhasil di-reject.');
    }

    public function print($projectId, $rabId, $spkId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $spk = Spk::with(['vendor', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($spkId);

        $logoSrc = asset('images/logo-dipo.png');
        return view('pdf.spk', compact('project', 'rab', 'spk', 'logoSrc'));
    }

    public function pdf($projectId, $rabId, $spkId)
    {
        $project = Project::findOrFail($projectId);
        $rab = Rab::where('project_id', $projectId)->findOrFail($rabId);
        $spk = Spk::with(['vendor', 'items.rabItem.data'])
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->findOrFail($spkId);

        $data = compact('project', 'rab', 'spk');
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $filename = 'SPK-' . ($spk->spk_no ?? $spk->id) . '.pdf';
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.spk', $data)
                ->setPaper('a4', 'portrait')
                ->stream($filename);
        }

        return view('pdf.spk', $data);
    }
}



