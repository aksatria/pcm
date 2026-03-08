<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\RabItem;
use App\Models\RabBreakdown;
use App\Models\RabBreakdownItem;
use App\Models\RabBreakdownBudgetSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RabBreakdownItemsImport;
use App\Support\NotificationService;

class RabBreakdownController extends Controller
{
    private const MAX_VOLUME_RATIO_NON_LS = 1.20; // toleransi waste/alokasi non LS (120% dari acuan)
    private const MAX_AMOUNT_RATIO_NON_LS = 1.25; // toleransi nilai non LS (125% dari acuan)
    private const MAX_AMOUNT_RATIO_LS = 1.05; // LS harus sangat ketat (maks 105%)

    protected function isHO(): bool
    {
        return auth()->check()
            && auth()->user()
            && method_exists(auth()->user(), 'isHO')
            && auth()->user()->isHO();
    }

    protected function ensureEditable(RabBreakdown $rabBreakdown): void
    {
        if ($this->isHO()) {
            return;
        }

        if (!$rabBreakdown->canEdit()) {
            abort(403, 'RAB Breakdown terkunci karena status approval: ' . ($rabBreakdown->approval_status ?? 'draft'));
        }
    }

    protected function markForReapprovalIfNeeded(RabBreakdown $rabBreakdown): bool
    {
        if ($this->isHO()) {
            return false;
        }

        if ($rabBreakdown->approvalKey() !== 'approved') {
            return false;
        }

        $rabBreakdown->approval_status = 'draft';
        $rabBreakdown->submitted_at = null;
        $rabBreakdown->approved_at = null;
        $rabBreakdown->rejected_at = null;
        $rabBreakdown->submitted_by = null;
        $rabBreakdown->approved_by = null;
        $rabBreakdown->rejected_by = null;
        $rabBreakdown->rejected_reason = null;
        $rabBreakdown->save();

        return true;
    }

    protected function getReferenceRab(Project $project)
    {
        return $project->latestApprovedRab()->first();
    }

    protected function ensureReferenceRab(Project $project)
    {
        $rab = $this->getReferenceRab($project);
        if (!$rab) {
            abort(422, 'RAB Breakdown harus mengacu pada RAB yang sudah approved.');
        }
        return $rab;
    }

    /**
     * Validasi bisnis agar alokasi tidak absurd dibanding acuan baseline.
     */
    private function assertAllocationIsReasonable(
        string $satuan,
        float $volumeAcuan,
        float $hargaAcuan,
        float $qtyBeli,
        float $jumlah,
        string $itemRef = '-'
    ): void {
        $errors = $this->getAllocationGuardrailErrors(
            $satuan,
            $volumeAcuan,
            $hargaAcuan,
            $qtyBeli,
            $jumlah
        );

        if (!empty($errors)) {
            $prefix = "Item {$itemRef}: ";
            $message = $prefix . implode(' ', $errors);
            throw ValidationException::withMessages([
                'qty_beli' => $message,
                'jumlah' => $message,
            ]);
        }
    }

    /**
     * Guardrail agregat lintas breakdown:
     * total alokasi item RAPP yang sama dalam 1 proyek tidak boleh melebihi batas.
     */
    private function assertProjectAggregateAllocationIsReasonable(
        int $projectId,
        RabItem $rabItem,
        float $incomingQty,
        float $incomingJumlah,
        ?int $ignoreItemId = null,
        string $itemRef = '-'
    ): void {
        $query = RabBreakdownItem::query()
            ->where('rab_item_id', $rabItem->id)
            ->whereHas('rabBreakdown', fn ($q) => $q->where('project_id', $projectId));

        if ($ignoreItemId !== null) {
            $query->where('id', '!=', $ignoreItemId);
        }

        $existingQty = (float) $query->sum('qty_beli');
        $existingJumlah = (float) $query->sum('jumlah');
        $aggregateQty = $existingQty + max(0.0, $incomingQty);
        $aggregateJumlah = $existingJumlah + max(0.0, $incomingJumlah);

        $errors = $this->getAllocationGuardrailErrors(
            (string) ($rabItem->satuan ?? ($rabItem->data->satuan ?? '')),
            (float) ($rabItem->volume ?? 0),
            (float) ($rabItem->harga_satuan ?? 0),
            $aggregateQty,
            $aggregateJumlah
        );

        if (!empty($errors)) {
            $prefix = "Item {$itemRef}: ";
            $message = $prefix . 'Total alokasi lintas breakdown melebihi batas acuan proyek. ' . implode(' ', $errors);
            throw ValidationException::withMessages([
                'qty_beli' => $message,
                'jumlah' => $message,
            ]);
        }
    }

    /**
     * Hitung daftar pelanggaran guardrail alokasi.
     */
    private function getAllocationGuardrailErrors(
        string $satuan,
        float $volumeAcuan,
        float $hargaAcuan,
        float $qtyBeli,
        float $jumlah
    ): array {
        $satuanKey = strtoupper(trim($satuan));
        $isLs = in_array($satuanKey, ['LS', 'LUMP SUM', 'LUMPSUM'], true);

        $acuanNilai = max(0, $volumeAcuan * $hargaAcuan);
        $errors = [];

        if ($volumeAcuan <= 0) {
            $errors[] = 'Volume acuan harus lebih besar dari 0 sebelum alokasi.';
        }

        if ($qtyBeli < 0 || $jumlah < 0) {
            $errors[] = 'Qty beli dan jumlah tidak boleh negatif.';
        }

        if ($isLs) {
            $maxQty = max(1.0, $volumeAcuan);
            if ($qtyBeli > ($maxQty + 0.000001)) {
                $errors[] = 'Untuk satuan LS, qty beli tidak boleh melebihi volume acuan.';
            }

            if ($acuanNilai > 0) {
                $maxJumlah = $acuanNilai * self::MAX_AMOUNT_RATIO_LS;
                if ($jumlah > ($maxJumlah + 1)) {
                    $errors[] = 'Untuk satuan LS, nilai alokasi melebihi batas toleransi terhadap nilai acuan.';
                }
            }
        } else {
            if ($volumeAcuan > 0) {
                $maxQty = $volumeAcuan * self::MAX_VOLUME_RATIO_NON_LS;
                if ($qtyBeli > ($maxQty + 0.000001)) {
                    $errors[] = 'Qty beli melebihi batas toleransi volume acuan (120%).';
                }
            }

            if ($acuanNilai > 0) {
                $maxJumlah = $acuanNilai * self::MAX_AMOUNT_RATIO_NON_LS;
                if ($jumlah > ($maxJumlah + 1)) {
                    $errors[] = 'Nilai alokasi melebihi batas toleransi nilai acuan (125%).';
                }
            }
        }

        return $errors;
    }

    /**
     * Audit hygiene per item untuk kebutuhan UI monitoring.
     */
    private function auditBreakdownItem(RabBreakdownItem $item): array
    {
        $issues = [];
        $severity = 'ok';

        $itemCode = $this->normalizeItemCode((string) ($item->item_code ?? ''));
        if (preg_match('/^[A-Z](?:\.\d+)?$/', $itemCode) !== 1) {
            $issues[] = 'Kode item tidak valid';
            $severity = 'danger';
        }

        $primarySource = $item->budgetSources->first();
        $masterKode = trim((string) (optional($primarySource)->master_kode ?? ''));
        if ($masterKode === '') {
            $issues[] = 'Master kode kosong';
            if ($severity !== 'danger') {
                $severity = 'warning';
            }
        }

        $qtyBeli = (float) ($item->qty_beli ?? optional($primarySource)->qty_beli ?? $item->volume_rab ?? 0);
        $jumlah = (float) ($item->jumlah ?? optional($primarySource)->jumlah ?? ($qtyBeli * (float) ($item->unit_price ?? 0)));
        $guardrailErrors = $this->getAllocationGuardrailErrors(
            (string) ($item->satuan ?? ''),
            (float) ($item->volume_rab ?? 0),
            (float) ($item->unit_price ?? 0),
            $qtyBeli,
            $jumlah
        );
        if (!empty($guardrailErrors)) {
            $issues[] = 'Melebihi guardrail alokasi';
            $severity = 'danger';
        }

        if (empty($issues)) {
            $issues[] = 'Data sehat';
        }

        return [
            'severity' => $severity,
            'issues' => $issues,
        ];
    }

    /**
     * Ubah item_code ke format konsisten uppercase.
     */
    private function normalizeItemCode(string $itemCode): string
    {
        return strtoupper(trim($itemCode));
    }

    /**
     * Ambil huruf kategori teratas dari item_code (A, B, C, dst).
     */
    private function topCategoryFromItemCode(string $itemCode): ?string
    {
        $code = $this->normalizeItemCode($itemCode);
        if (preg_match('/^([A-Z])(?:\.\d+)?$/', $code, $m) === 1) {
            return $m[1];
        }
        return null;
    }

    /**
     * Pastikan kategori item tidak loncat (A->B->C ...).
     * Contoh: tidak boleh input D jika A-C belum ada.
     */
    private function assertSequentialItemCode(
        RabBreakdown $rabBreakdown,
        string $newItemCode,
        ?int $ignoreItemId = null,
        ?string $currentItemCode = null
    ): void {
        $normalizedNew = $this->normalizeItemCode($newItemCode);
        $normalizedCurrent = $currentItemCode !== null ? $this->normalizeItemCode($currentItemCode) : null;

        // Jika update tidak mengubah kode, jangan blok data lama.
        if ($normalizedCurrent !== null && $normalizedCurrent === $normalizedNew) {
            return;
        }

        $top = $this->topCategoryFromItemCode($normalizedNew);
        if ($top === null) {
            throw ValidationException::withMessages([
                'item_code' => "Format item_code '{$newItemCode}' tidak valid. Gunakan A atau A.1.",
            ]);
        }

        $query = RabBreakdownItem::where('rab_breakdown_id', $rabBreakdown->id);
        if ($ignoreItemId !== null) {
            $query->where('id', '!=', $ignoreItemId);
        }

        $existingTops = $query->pluck('item_code')
            ->map(fn ($code) => $this->topCategoryFromItemCode((string) $code))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $existingSet = array_fill_keys($existingTops, true);
        $targetIdx = ord($top) - ord('A') + 1;
        $startTop = $this->breakdownStartCategory($rabBreakdown);
        $startIdx = ord($startTop) - ord('A') + 1;

        if ($targetIdx <= 0) {
            throw ValidationException::withMessages([
                'item_code' => "Kategori item_code '{$newItemCode}' tidak valid.",
            ]);
        }

        // Kategori minimum mengikuti header breakdown (mis. RAB-002 => B).
        if ($targetIdx < $startIdx) {
            throw ValidationException::withMessages([
                'item_code' => "Kategori item untuk {$rabBreakdown->rab_breakdown_code} minimal {$startTop}.",
            ]);
        }

        // Jika belum ada item sama sekali, harus mulai dari kategori awal header.
        if (empty($existingTops) && $top !== $startTop) {
            throw ValidationException::withMessages([
                'item_code' => "Urutan kategori tidak boleh loncat. Item pertama harus kategori {$startTop}.",
            ]);
        }

        for ($i = $startIdx; $i < $targetIdx; $i++) {
            $mustExist = chr(ord('A') + $i - 1);
            if (!isset($existingSet[$mustExist])) {
                throw ValidationException::withMessages([
                    'item_code' => "Urutan kategori tidak boleh loncat. Tambahkan kategori {$mustExist} sebelum {$top}.",
                ]);
            }
        }
    }

    /**
     * Kategori awal mengikuti kode header breakdown:
     * RAB-001=>A, RAB-002=>B, dst (maks 26=>Z). Fallback A.
     */
    private function breakdownStartCategory(RabBreakdown $rabBreakdown): string
    {
        $code = strtoupper(trim((string) ($rabBreakdown->rab_breakdown_code ?? '')));
        if (preg_match('/^RAB-(\d{3})$/', $code, $m) === 1) {
            $num = (int) $m[1];
            if ($num >= 1 && $num <= 26) {
                return chr(ord('A') + $num - 1);
            }
        }

        return 'A';
    }

    /**
     * Urutkan item_code secara natural: A, A.1, A.2, B, B.1, ...
     */
    private function compareItemCodeNatural(string $left, string $right): int
    {
        $ln = $this->normalizeItemCode($left);
        $rn = $this->normalizeItemCode($right);

        $lm = [];
        $rm = [];
        $lMatch = preg_match('/^([A-Z])(?:\.(\d+))?$/', $ln, $lm) === 1;
        $rMatch = preg_match('/^([A-Z])(?:\.(\d+))?$/', $rn, $rm) === 1;

        if (!$lMatch && !$rMatch) {
            return strcmp($ln, $rn);
        }
        if (!$lMatch) {
            return 1;
        }
        if (!$rMatch) {
            return -1;
        }

        $letterCmp = strcmp($lm[1], $rm[1]);
        if ($letterCmp !== 0) {
            return $letterCmp;
        }

        $lHasSub = array_key_exists(2, $lm);
        $rHasSub = array_key_exists(2, $rm);
        if ($lHasSub !== $rHasSub) {
            return $lHasSub ? 1 : -1; // A lebih dulu dari A.1
        }

        if ($lHasSub && $rHasSub) {
            $lNum = (int) $lm[2];
            $rNum = (int) $rm[2];
            if ($lNum !== $rNum) {
                return $lNum <=> $rNum;
            }
        }

        return strcmp($ln, $rn);
    }

    /**
     * Sort koleksi item breakdown dengan aturan natural item_code.
     */
    private function sortItemsNaturally($items)
    {
        return $items->sort(function ($a, $b) {
            return $this->compareItemCodeNatural((string) ($a->item_code ?? ''), (string) ($b->item_code ?? ''));
        })->values();
    }

    /**
     * Ringkasan urutan kategori existing untuk guidance di UI.
     */
    private function breakdownCategoryState(RabBreakdown $rabBreakdown, ?int $ignoreItemId = null): array
    {
        $query = RabBreakdownItem::where('rab_breakdown_id', $rabBreakdown->id);
        if ($ignoreItemId !== null) {
            $query->where('id', '!=', $ignoreItemId);
        }

        $existing = $query->pluck('item_code')
            ->map(fn ($code) => $this->topCategoryFromItemCode((string) $code))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $existingSet = array_fill_keys($existing, true);
        $start = $this->breakdownStartCategory($rabBreakdown);
        $startIdx = ord($start) - ord('A') + 1;
        $next = $start;
        for ($i = $startIdx; $i <= 26; $i++) {
            $letter = chr(ord('A') + $i - 1);
            if (!isset($existingSet[$letter])) {
                $next = $letter;
                break;
            }
        }

        return [
            'existing' => $existing,
            'next' => $next,
            'start' => $start,
        ];
    }

    // ==================== RAB BREAKDOWN DASHBOARD ====================
    
    /**
     * Display RAB Breakdown dashboard for a project
     */
    public function index($projectId)
    {
        $project = Project::findOrFail($projectId);
        $referenceRab = $this->getReferenceRab($project);
        if (!$referenceRab) {
            return redirect()
                ->route('dev.rab-baseline.index', ['projectId' => $projectId])
                ->with('error', 'RAB Breakdown hanya bisa dibuka setelah ada RAB berstatus approved.');
        }

        $rabBreakdownList = RabBreakdown::where('project_id', $projectId)
            ->orderBy('order_number')
            ->with(['items' => function ($query) {
                $query->with(['budgetSources', 'rabItem.data']);
            }])
            ->withCount('items')
            ->get();
        $rabBreakdownList->each(function ($rb) {
            $rb->setRelation('items', $this->sortItemsNaturally($rb->items));
        });

        // Calculate project summary
        $projectSummary = [
            'total_budget' => $rabBreakdownList->sum('budget_amount'),
            'total_actual' => $rabBreakdownList->sum('actual_amount'),
            'total_rab_breakdowns' => $rabBreakdownList->count(),
            'avg_progress' => $rabBreakdownList->avg('progress_percentage') ?? 0,
        ];

        return view('dev.rab-breakdown.index', [
            'project' => $project,
            'referenceRab' => $referenceRab,
            'rabBreakdownList' => $rabBreakdownList,
            'projectSummary' => $projectSummary,
        ]);
    }

    /**
     * Show RAB Breakdown detail
     */
    public function show($projectId, $rabBreakdownId)
    {
        $project = Project::findOrFail($projectId);
        $referenceRab = $this->getReferenceRab($project);
        if (!$referenceRab) {
            return redirect()
                ->route('dev.rab-baseline.index', ['projectId' => $projectId])
                ->with('error', 'RAB Breakdown hanya bisa dibuka setelah ada RAB berstatus approved.');
        }

        $rabBreakdown = RabBreakdown::where('project_id', $projectId)
            ->with(['items.budgetSources', 'items.rabItem'])
            ->findOrFail($rabBreakdownId);
        $rabBreakdown->setRelation('items', $this->sortItemsNaturally($rabBreakdown->items));
        $rabBreakdown->items->each(function ($item) {
            $item->setAttribute('hygiene_audit', $this->auditBreakdownItem($item));
        });

        // Group items by item_code prefix (A, B, C, etc.)
        $itemsByCategory = $rabBreakdown->items->groupBy(function ($item) {
            return explode('.', $item->item_code)[0] ?? 'A';
        });
        $hygieneSummary = [
            'ok' => 0,
            'warning' => 0,
            'danger' => 0,
        ];
        foreach ($rabBreakdown->items as $item) {
            $severity = (string) (data_get($item, 'hygiene_audit.severity', 'ok'));
            if (!array_key_exists($severity, $hygieneSummary)) {
                continue;
            }
            $hygieneSummary[$severity]++;
        }

        $quickRabItems = collect();
        if ($referenceRab) {
            $usedRabItemIds = RabBreakdownItem::query()
                ->whereHas('rabBreakdown', fn ($q) => $q->where('project_id', $projectId))
                ->whereNotNull('rab_item_id')
                ->pluck('rab_item_id')
                ->all();

            $quickRabItems = RabItem::with('data')
                ->where('rab_id', $referenceRab->id)
                ->orderBy('id')
                ->get()
                ->map(function ($item) use ($usedRabItemIds) {
                    $kode = $item->data->kode ?? null;
                    $uraian = $item->data->uraian ?? $item->keterangan ?? ('RAB Item #' . $item->id);
                    return (object) [
                        'id' => $item->id,
                        'kode' => $kode,
                        'uraian' => $uraian,
                        'satuan' => $item->satuan ?? ($item->data->satuan ?? ''),
                        'volume' => (float) ($item->volume ?? 0),
                        'unit_price' => (float) ($item->harga_satuan ?? 0),
                        'is_used' => in_array($item->id, $usedRabItemIds, true),
                    ];
                });
        }

        return view('dev.rab-breakdown.show', [
            'project' => $project,
            'rabBreakdown' => $rabBreakdown,
            'itemsByCategory' => $itemsByCategory,
            'quickRabItems' => $quickRabItems,
            'hygieneSummary' => $hygieneSummary,
        ]);
    }

    // ==================== RAB BREAKDOWN CRUD ====================

    /**
     * Show form to create new RAB Breakdown
     */
    public function create($projectId)
    {
        $project = Project::findOrFail($projectId);
        $referenceRab = $this->getReferenceRab($project);
        if (!$referenceRab) {
            return redirect()
                ->route('dev.rab-baseline.index', ['projectId' => $projectId])
                ->with('error', 'Buat/approve RAB terlebih dahulu sebelum membuat RAB Breakdown.');
        }
        
        // Get predefined RAB Breakdown templates that don't exist for this project yet
        $existingRabBreakdownCodes = RabBreakdown::where('project_id', $projectId)
            ->pluck('rab_breakdown_code')
            ->toArray();
            
        $predefinedRabBreakdowns = [
            ['code' => 'RAB-001', 'name' => 'PEKERJAAN PERSIAPAN', 'order' => 1],
            ['code' => 'RAB-002', 'name' => 'PEKERJAAN TANAH', 'order' => 2],
            ['code' => 'RAB-003', 'name' => 'PEKERJAAN PONDASI', 'order' => 3],
            ['code' => 'RAB-004', 'name' => 'PEKERJAAN STRUKTUR', 'order' => 4],
            ['code' => 'RAB-005', 'name' => 'PEKERJAAN ARSITEKTUR', 'order' => 5],
            ['code' => 'RAB-006', 'name' => 'PEKERJAAN MEKANIKAL', 'order' => 6],
            ['code' => 'RAB-007', 'name' => 'PEKERJAAN ELEKTRIKAL', 'order' => 7],
            ['code' => 'RAB-008', 'name' => 'PEKERJAAN SANITASI & PLAMBING', 'order' => 8],
            ['code' => 'RAB-009', 'name' => 'PEKERJAAN LANSEKAP', 'order' => 9],
            ['code' => 'RAB-010', 'name' => 'PEKERJAAN FINISHING', 'order' => 10],
            ['code' => 'RAB-011', 'name' => 'PENGADAAN PERABOT & PERALATAN', 'order' => 11],
        ];

        // Filter out templates that already exist for this project
        $availableRabBreakdowns = array_filter($predefinedRabBreakdowns, function ($rabBreakdown) use ($existingRabBreakdownCodes) {
            return !in_array($rabBreakdown['code'], $existingRabBreakdownCodes);
        });

        return view('dev.rab-breakdown.create', compact('project', 'availableRabBreakdowns'));
    }

    /**
     * Store new RAB Breakdown
     */
    public function store(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        $this->ensureReferenceRab($project);

        $request->merge([
            'rab_breakdown_code' => strtoupper(trim((string) $request->input('rab_breakdown_code'))),
        ]);

        $validated = $request->validate([
            'rab_breakdown_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^RAB-[A-Z0-9-]+$/',
                Rule::unique('rab_breakdowns', 'rab_breakdown_code')
                    ->where(fn ($q) => $q->where('project_id', $projectId)),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget_amount' => 'nullable|numeric|min:0',
        ]);

        // Get order number from predefined RAB Breakdown codes
        $predefinedRabBreakdowns = [
            'RAB-001' => 1, 'RAB-002' => 2, 'RAB-003' => 3, 'RAB-004' => 4, 'RAB-005' => 5,
            'RAB-006' => 6, 'RAB-007' => 7, 'RAB-008' => 8, 'RAB-009' => 9, 'RAB-010' => 10,
            'RAB-011' => 11,
        ];

        $validated['project_id'] = $projectId;
        $validated['order_number'] = $predefinedRabBreakdowns[$validated['rab_breakdown_code']] ?? 99;
        $validated['status'] = 'not_started';
        $validated['approval_status'] = 'draft';
        $validated['budget_amount'] = $validated['budget_amount'] ?? 0;

        $rabBreakdown = RabBreakdown::create($validated);

        return redirect()->route('dev.rab-breakdown.show', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown->id])
            ->with('success', 'RAB Breakdown berhasil dibuat!');
    }

    /**
     * Show form to edit RAB Breakdown
     */
    public function edit($projectId, $rabBreakdownId)
    {
        $project = Project::findOrFail($projectId);
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);

        return view('dev.rab-breakdown.edit', ['project' => $project, 'rabBreakdown' => $rabBreakdown]);
    }

    /**
     * Update RAB Breakdown
     */
    public function update(Request $request, $projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:not_started,in_progress,completed,delayed',
            'actual_amount' => 'nullable|numeric|min:0',
            'budget_amount' => 'nullable|numeric|min:0',
        ]);
        $validated['budget_amount'] = $validated['budget_amount']
            ?? ($rabBreakdown->budget_amount ?? 0);
        $validated['actual_amount'] = $validated['actual_amount']
            ?? ($rabBreakdown->actual_amount ?? 0);

        $rabBreakdown->update($validated);

        // Recalculate progress after budget/actual normalization.
        $rabBreakdown->calculateProgress();

        $needsResubmit = $this->markForReapprovalIfNeeded($rabBreakdown);

        return redirect()->route('dev.rab-breakdown.show', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown->id])
            ->with('success', $needsResubmit
                ? 'RAB Breakdown berhasil diperbarui. Karena diubah oleh Staff setelah approved, status kembali draft dan perlu submit ulang.'
                : 'RAB Breakdown berhasil diperbarui!');
    }

    /**
     * Delete RAB Breakdown
     */
    public function destroy($projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);
        $rabBreakdown->delete();

        return redirect()->route('dev.rab-breakdown.index', $projectId)
            ->with('success', 'RAB Breakdown berhasil dihapus!');
    }

    // ==================== RAB BREAKDOWN ITEMS MANAGEMENT ====================

    /**
     * Show form to add RAB Breakdown item
     */
    public function createItem($projectId, $rabBreakdownId)
    {
        $project = Project::findOrFail($projectId);
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);

        $rab = $this->ensureReferenceRab($project);

        $rabItems = RabItem::with('data')
            ->where('rab_id', $rab->id)
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                $kode = $item->data->kode ?? null;
                $uraian = $item->data->uraian ?? $item->keterangan ?? ('RAB Item #' . $item->id);
                $itemName = ($kode ? ($kode . ' - ') : '') . $uraian;
                return (object)[
                    'id' => $item->id,
                    'item_name' => $itemName,
                    'unit_price' => (float) ($item->harga_satuan ?? 0),
                    'satuan' => $item->satuan ?? ($item->data->satuan ?? ''),
                    'volume' => (float) ($item->volume ?? 0),
                    'master_kode' => $kode ?? '',
                    'uraian' => $uraian,
                ];
            });

        return view('dev.rab-breakdown.create-item', [
            'project' => $project,
            'rabBreakdown' => $rabBreakdown,
            'rabItems' => $rabItems,
            'categoryState' => $this->breakdownCategoryState($rabBreakdown),
        ]);
    }

    /**
     * Store new RAB Breakdown item
     */
    public function storeItem(Request $request, $projectId, $rabBreakdownId)
    {
        $project = Project::findOrFail($projectId);
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);
        $rab = $this->ensureReferenceRab($project);

        $validated = $request->validate([
            'item_code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z](?:\.\d+)?$/'],
            'rab_item_id' => 'required|exists:rab_items,id',
            'notes' => 'nullable|string',
            'p' => 'nullable|numeric|min:0',
            'l' => 'nullable|numeric|min:0',
            't' => 'nullable|numeric|min:0',
            'n' => 'nullable|numeric|min:0',
            'n_tul_1' => 'nullable|numeric|min:0',
            'n_tul_2' => 'nullable|numeric|min:0',
            'jarak' => 'nullable|numeric|min:0',
            'dia_1' => 'nullable|numeric|min:0',
            'dia_2' => 'nullable|numeric|min:0',
            'dia_3' => 'nullable|numeric|min:0',
            'berat_1' => 'nullable|numeric|min:0',
            'berat_2' => 'nullable|numeric|min:0',
            'm2_peng' => 'nullable|numeric|min:0',
            'qty' => 'nullable|numeric|min:0',
            'qty_beli' => 'nullable|numeric|min:0',
            'jumlah' => 'nullable|numeric|min:0',
        ]);
        $validated['item_code'] = $this->normalizeItemCode((string) $validated['item_code']);
        $this->assertSequentialItemCode($rabBreakdown, $validated['item_code']);

        $rabItem = RabItem::with('data')
            ->where('rab_id', $rab->id)
            ->find($validated['rab_item_id']);
        if (!$rabItem) {
            return back()->withErrors(['rab_item_id' => 'Item RAB tidak valid untuk proyek ini.'])->withInput();
        }

        $alreadyUsed = RabBreakdownItem::where('rab_breakdown_id', $rabBreakdown->id)
            ->where('rab_item_id', $rabItem->id)
            ->exists();
        if ($alreadyUsed) {
            return back()->withErrors(['rab_item_id' => 'Item RAPP ini sudah dipakai pada breakdown ini.'])->withInput();
        }

        $validated['rab_breakdown_id'] = $rabBreakdown->id;
        $validated['uraian'] = $rabItem->data->uraian ?? $rabItem->keterangan ?? ('RAB Item #' . $rabItem->id);
        $validated['volume_rab'] = (float) ($rabItem->volume ?? 0);
        $validated['satuan'] = $rabItem->satuan ?? ($rabItem->data->satuan ?? 'LS');
        $validated['unit_price'] = (float) ($rabItem->harga_satuan ?? 0);
        $validated['total_price'] = $validated['volume_rab'] * $validated['unit_price'];
        $validated['p'] = $this->nullableNumeric($request->input('p'), $validated['volume_rab']);
        $validated['l'] = $this->nullableNumeric($request->input('l'));
        $validated['t'] = $this->nullableNumeric($request->input('t'));
        $validated['n'] = $this->nullableNumeric($request->input('n'));
        $validated['n_tul_1'] = $this->nullableNumeric($request->input('n_tul_1'));
        $validated['n_tul_2'] = $this->nullableNumeric($request->input('n_tul_2'));
        $validated['jarak'] = $this->nullableNumeric($request->input('jarak'));
        $validated['dia_1'] = $this->nullableNumeric($request->input('dia_1'));
        $validated['dia_2'] = $this->nullableNumeric($request->input('dia_2'));
        $validated['dia_3'] = $this->nullableNumeric($request->input('dia_3'));
        $validated['berat_1'] = $this->nullableNumeric($request->input('berat_1'));
        $validated['berat_2'] = $this->nullableNumeric($request->input('berat_2'));
        $validated['m2_peng'] = $this->nullableNumeric($request->input('m2_peng'));
        $validated['qty'] = $this->nullableNumeric($request->input('qty'));
        $validated['qty_beli'] = $this->nullableNumeric($request->input('qty_beli'));
        $validated['jumlah'] = $this->nullableNumeric($request->input('jumlah'));

        $preQtyBeli = (float) ($validated['qty_beli'] ?? $validated['volume_rab']);
        $preJumlah = (float) ($validated['jumlah'] ?? ($preQtyBeli * (float) $validated['unit_price']));
        $itemRef = trim((string) ($validated['item_code'] ?? '')) !== ''
            ? (string) $validated['item_code']
            : ('RAB Item #' . (string) ($validated['rab_item_id'] ?? '-'));
        $this->assertAllocationIsReasonable(
            (string) ($validated['satuan'] ?? ''),
            (float) ($validated['volume_rab'] ?? 0),
            (float) ($validated['unit_price'] ?? 0),
            $preQtyBeli,
            $preJumlah,
            $itemRef
        );
        $this->assertProjectAggregateAllocationIsReasonable(
            (int) $projectId,
            $rabItem,
            $preQtyBeli,
            $preJumlah,
            null,
            $itemRef
        );

        $item = RabBreakdownItem::create($validated);

        $masterKode = $rabItem->data->kode ?? null;
        if (!empty($masterKode)) {
            $this->mapToMasterData($item, $masterKode, $this->dimensionPayloadFromItem($item));
        }

        // Update RAB Breakdown budget
        $rabBreakdown->updateBudget();
        $needsResubmit = $this->markForReapprovalIfNeeded($rabBreakdown);

        return redirect()->route('dev.rab-breakdown.show', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown->id])
            ->with('success', $needsResubmit
                ? 'Item berhasil ditambahkan. Status approval RAB Breakdown kembali draft, silakan submit ulang.'
                : 'Item berhasil ditambahkan!');
    }

    /**
     * Show form to edit RAB Breakdown item
     */
    public function editItem($projectId, $rabBreakdownId, $itemId)
    {
        $project = Project::findOrFail($projectId);
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);
        $item = RabBreakdownItem::where('rab_breakdown_id', $rabBreakdownId)->findOrFail($itemId);

        $rab = $this->ensureReferenceRab($project);

        $rabItems = RabItem::with('data')
            ->where('rab_id', $rab->id)
            ->orderBy('id')
            ->get()
            ->map(function ($rabItem) {
                $kode = $rabItem->data->kode ?? null;
                $uraian = $rabItem->data->uraian ?? $rabItem->keterangan ?? ('RAB Item #' . $rabItem->id);
                $itemName = ($kode ? ($kode . ' - ') : '') . $uraian;
                return (object)[
                    'id' => $rabItem->id,
                    'item_name' => $itemName,
                    'unit_price' => (float) ($rabItem->harga_satuan ?? 0),
                    'satuan' => $rabItem->satuan ?? ($rabItem->data->satuan ?? ''),
                    'volume' => (float) ($rabItem->volume ?? 0),
                    'master_kode' => $kode ?? '',
                    'uraian' => $uraian,
                ];
            });

        return view('dev.rab-breakdown.edit-item', [
            'project' => $project,
            'rabBreakdown' => $rabBreakdown,
            'item' => $item,
            'rabItems' => $rabItems,
            'categoryState' => $this->breakdownCategoryState($rabBreakdown, (int) $item->id),
        ]);
    }

    /**
     * Update RAB Breakdown item
     */
    public function updateItem(Request $request, $projectId, $rabBreakdownId, $itemId)
    {
        $project = Project::findOrFail($projectId);
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);
        $item = RabBreakdownItem::where('rab_breakdown_id', $rabBreakdownId)->findOrFail($itemId);
        $rab = $this->ensureReferenceRab($project);

        $validated = $request->validate([
            'item_code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z](?:\.\d+)?$/'],
            'rab_item_id' => 'required|exists:rab_items,id',
            'notes' => 'nullable|string',
            'p' => 'nullable|numeric|min:0',
            'l' => 'nullable|numeric|min:0',
            't' => 'nullable|numeric|min:0',
            'n' => 'nullable|numeric|min:0',
            'n_tul_1' => 'nullable|numeric|min:0',
            'n_tul_2' => 'nullable|numeric|min:0',
            'jarak' => 'nullable|numeric|min:0',
            'dia_1' => 'nullable|numeric|min:0',
            'dia_2' => 'nullable|numeric|min:0',
            'dia_3' => 'nullable|numeric|min:0',
            'berat_1' => 'nullable|numeric|min:0',
            'berat_2' => 'nullable|numeric|min:0',
            'm2_peng' => 'nullable|numeric|min:0',
            'qty' => 'nullable|numeric|min:0',
            'qty_beli' => 'nullable|numeric|min:0',
            'jumlah' => 'nullable|numeric|min:0',
        ]);
        $validated['item_code'] = $this->normalizeItemCode((string) $validated['item_code']);
        $this->assertSequentialItemCode(
            $rabBreakdown,
            $validated['item_code'],
            (int) $item->id,
            (string) ($item->item_code ?? '')
        );

        $rabItem = RabItem::with('data')
            ->where('rab_id', $rab->id)
            ->find($validated['rab_item_id']);
        if (!$rabItem) {
            return back()->withErrors(['rab_item_id' => 'Item RAB tidak valid untuk proyek ini.'])->withInput();
        }

        $alreadyUsed = RabBreakdownItem::where('rab_breakdown_id', $rabBreakdown->id)
            ->where('rab_item_id', $rabItem->id)
            ->where('id', '!=', $item->id)
            ->exists();
        if ($alreadyUsed) {
            return back()->withErrors(['rab_item_id' => 'Item RAPP ini sudah dipakai pada breakdown ini.'])->withInput();
        }

        $validated['uraian'] = $rabItem->data->uraian ?? $rabItem->keterangan ?? ('RAB Item #' . $rabItem->id);
        $validated['volume_rab'] = (float) ($rabItem->volume ?? 0);
        $validated['satuan'] = $rabItem->satuan ?? ($rabItem->data->satuan ?? 'LS');
        $validated['unit_price'] = (float) ($rabItem->harga_satuan ?? 0);
        $validated['total_price'] = $validated['volume_rab'] * $validated['unit_price'];
        $validated['p'] = $this->nullableNumeric($request->input('p'), $validated['volume_rab']);
        $validated['l'] = $this->nullableNumeric($request->input('l'));
        $validated['t'] = $this->nullableNumeric($request->input('t'));
        $validated['n'] = $this->nullableNumeric($request->input('n'));
        $validated['n_tul_1'] = $this->nullableNumeric($request->input('n_tul_1'));
        $validated['n_tul_2'] = $this->nullableNumeric($request->input('n_tul_2'));
        $validated['jarak'] = $this->nullableNumeric($request->input('jarak'));
        $validated['dia_1'] = $this->nullableNumeric($request->input('dia_1'));
        $validated['dia_2'] = $this->nullableNumeric($request->input('dia_2'));
        $validated['dia_3'] = $this->nullableNumeric($request->input('dia_3'));
        $validated['berat_1'] = $this->nullableNumeric($request->input('berat_1'));
        $validated['berat_2'] = $this->nullableNumeric($request->input('berat_2'));
        $validated['m2_peng'] = $this->nullableNumeric($request->input('m2_peng'));
        $validated['qty'] = $this->nullableNumeric($request->input('qty'));
        $validated['qty_beli'] = $this->nullableNumeric($request->input('qty_beli'));
        $validated['jumlah'] = $this->nullableNumeric($request->input('jumlah'));

        $preQtyBeli = (float) ($validated['qty_beli'] ?? $validated['volume_rab']);
        $preJumlah = (float) ($validated['jumlah'] ?? ($preQtyBeli * (float) $validated['unit_price']));
        $itemRef = trim((string) ($validated['item_code'] ?? '')) !== ''
            ? (string) $validated['item_code']
            : ('RAB Item #' . (string) ($validated['rab_item_id'] ?? '-'));
        $this->assertAllocationIsReasonable(
            (string) ($validated['satuan'] ?? ''),
            (float) ($validated['volume_rab'] ?? 0),
            (float) ($validated['unit_price'] ?? 0),
            $preQtyBeli,
            $preJumlah,
            $itemRef
        );
        $this->assertProjectAggregateAllocationIsReasonable(
            (int) $projectId,
            $rabItem,
            $preQtyBeli,
            $preJumlah,
            (int) $item->id,
            $itemRef
        );
        $item->update($validated);

        $masterKode = $rabItem->data->kode ?? null;
        if (!empty($masterKode)) {
            $item->budgetSources()->delete();
            $this->mapToMasterData($item, $masterKode, $this->dimensionPayloadFromItem($item));
        }

        // Update RAB Breakdown budget
        $rabBreakdown->updateBudget();
        $needsResubmit = $this->markForReapprovalIfNeeded($rabBreakdown);

        return redirect()->route('dev.rab-breakdown.show', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown->id])
            ->with('success', $needsResubmit
                ? 'Item berhasil diperbarui. Status approval RAB Breakdown kembali draft, silakan submit ulang.'
                : 'Item berhasil diperbarui!');
    }

    /**
     * Delete RAB Breakdown item
     */
    public function destroyItem($projectId, $rabBreakdownId, $itemId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);
        $item = RabBreakdownItem::where('rab_breakdown_id', $rabBreakdownId)->findOrFail($itemId);

        $item->delete();

        // Update RAB Breakdown budget
        $rabBreakdown->updateBudget();
        $needsResubmit = $this->markForReapprovalIfNeeded($rabBreakdown);

        return redirect()->route('dev.rab-breakdown.show', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown->id])
            ->with('success', $needsResubmit
                ? 'Item berhasil dihapus. Status approval RAB Breakdown kembali draft, silakan submit ulang.'
                : 'Item berhasil dihapus!');
    }

    /**
     * Jalankan data hygiene: normalisasi urutan item_code + audit anomali.
     */
    public function runDataHygiene($projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);

        $summary = DB::transaction(function () use ($rabBreakdown) {
            $items = RabBreakdownItem::with('budgetSources')
                ->where('rab_breakdown_id', $rabBreakdown->id)
                ->orderBy('id')
                ->get();

            $categoryMap = [];
            $nextCategoryOrd = ord('A');
            $subCounter = [];
            $resequenced = 0;

            $anomalyMalformedCode = 0;
            $anomalyMissingMaster = 0;
            $anomalyGuardrail = 0;

            foreach ($items as $item) {
                $originalCode = (string) ($item->item_code ?? '');
                $normalizedCode = $this->normalizeItemCode($originalCode);
                $top = $this->topCategoryFromItemCode($normalizedCode);
                $wasMalformedCode = false;
                if ($top === null) {
                    $wasMalformedCode = true;
                    $top = '__INVALID__';
                }

                if (!array_key_exists($top, $categoryMap)) {
                    $categoryMap[$top] = chr($nextCategoryOrd);
                    $nextCategoryOrd++;
                }
                $newTop = $categoryMap[$top];

                $subCounter[$newTop] = ($subCounter[$newTop] ?? 0) + 1;
                $newCode = $newTop . '.' . $subCounter[$newTop];

                if ($newCode !== $normalizedCode) {
                    $item->item_code = $newCode;
                    $item->save();
                    $resequenced++;
                }

                $audit = $this->auditBreakdownItem($item);
                if ($wasMalformedCode) {
                    $anomalyMalformedCode++;
                }
                if (in_array('Master kode kosong', $audit['issues'], true)) {
                    $anomalyMissingMaster++;
                }
                if (in_array('Melebihi guardrail alokasi', $audit['issues'], true)) {
                    $anomalyGuardrail++;
                }
            }

            $rabBreakdown->updateBudget();
            $needsResubmit = $this->markForReapprovalIfNeeded($rabBreakdown);

            return [
                'total' => $items->count(),
                'resequenced' => $resequenced,
                'anomaly_malformed_code' => $anomalyMalformedCode,
                'anomaly_missing_master' => $anomalyMissingMaster,
                'anomaly_guardrail' => $anomalyGuardrail,
                'needs_resubmit' => $needsResubmit,
            ];
        });

        $message = "Data hygiene selesai: {$summary['resequenced']} item diresequence dari {$summary['total']} item. "
            . "Anomali -> kode tidak valid: {$summary['anomaly_malformed_code']}, "
            . "master kosong: {$summary['anomaly_missing_master']}, "
            . "guardrail alokasi: {$summary['anomaly_guardrail']}.";
        if ($summary['needs_resubmit']) {
            $message .= ' Status approval kembali draft, silakan submit ulang.';
        }

        return redirect()->route('dev.rab-breakdown.show', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown->id])
            ->with('success', $message);
    }

    // ==================== IMPORT/EXPORT FUNCTIONALITY ====================

    /**
     * Show import form
     */
    public function importForm($projectId, $rabBreakdownId)
    {
        $project = Project::findOrFail($projectId);
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);
        
        return view('dev.rab-breakdown.import', ['project' => $project, 'rabBreakdown' => $rabBreakdown]);
    }

    /**
     * Validate Excel import data
     */
    public function validateImport(Request $request, $projectId, $rabBreakdownId)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);

        try {
            // Read Excel file
            $import = new RabBreakdownItemsImport($rabBreakdownId);
            $data = Excel::toArray($import, $request->file('file'))[0];
            
            $validations = [];
            $validData = [];
            $rowNumber = 0;
            
            foreach ($data as $row) {
                $rowNumber++;
                $rowValidations = $this->validateRow($row, $rowNumber);
                $validations = array_merge($validations, $rowValidations);
                
                // Check if row is valid (no errors)
                $hasErrors = collect($rowValidations)->contains(function ($v) {
                    return $v['type'] === 'error';
                });
                
                if (!$hasErrors) {
                    $dimensions = $this->extractDimensionsFromRow($row);
                    $validData[] = [
                        'item_code' => $row['item_code'] ?? '',
                        'uraian' => $row['uraian'] ?? '',
                        'volume_rab' => floatval($row['volume_rab'] ?? 0),
                        'satuan' => $row['satuan'] ?? '',
                        'unit_price' => floatval($row['unit_price'] ?? 0),
                        'master_kode' => $row['master_kode'] ?? null,
                        'notes' => $row['notes'] ?? null,
                        'validation_status' => $this->getRowStatus($rowValidations),
                        'total_price' => floatval($row['volume_rab'] ?? 0) * floatval($row['unit_price'] ?? 0)
                    ] + $dimensions;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Validasi selesai',
                'total_rows' => count($data),
                'valid_rows' => count($validData),
                'validations' => $validations,
                'data' => $validData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reading file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process import after validation
     */
    public function processImport(Request $request, $projectId, $rabBreakdownId)
    {
        $request->validate([
            'validation_data' => 'required|json',
            'preview_data' => 'required|json',
            'auto_map_budget' => 'nullable|boolean',
            'update_existing' => 'nullable|boolean'
        ]);

        $project = Project::findOrFail($projectId);
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);
        $rab = $this->ensureReferenceRab($project);
        $previewData = json_decode($request->preview_data, true);
        $autoMap = $request->boolean('auto_map_budget', true);
        $updateExisting = $request->boolean('update_existing', false);

        DB::beginTransaction();

        try {
            $importedCount = 0;
            $updatedCount = 0;
            $mappedCount = 0;

            foreach ($previewData as $idx => $itemData) {
                $rowNo = $idx + 1;
                $itemCodeRef = trim((string) ($itemData['item_code'] ?? '')) ?: '-';
                $masterKodeRef = trim((string) ($itemData['master_kode'] ?? '')) ?: '-';

                try {
                    $masterKode = trim((string) ($itemData['master_kode'] ?? ''));
                    if ($masterKode === '') {
                        throw new \RuntimeException('Import RAB Breakdown wajib menyertakan master_kode agar sinkron dengan RAB approved.');
                    }
                    $dimensions = $this->extractDimensionsFromRow($itemData);
                    $normalizedItemCode = $this->normalizeItemCode((string) ($itemData['item_code'] ?? ''));
                    if ($normalizedItemCode === '') {
                        throw new \RuntimeException('item_code wajib diisi.');
                    }
                    $itemData['item_code'] = $normalizedItemCode;

                    $rabItem = RabItem::with('data')
                        ->where('rab_id', $rab->id)
                        ->whereHas('data', fn ($q) => $q->where('kode', $masterKode))
                        ->first();
                    if (!$rabItem) {
                        throw new \RuntimeException("Kode {$masterKode} tidak ditemukan pada item RAB approved proyek ini.");
                    }

                    // Check if item already exists
                    $existingItem = null;
                    if ($updateExisting) {
                        $existingItem = RabBreakdownItem::where('rab_breakdown_id', $rabBreakdownId)
                            ->where('item_code', $itemData['item_code'])
                            ->first();
                    }

                    $this->assertSequentialItemCode(
                        $rabBreakdown,
                        (string) $itemData['item_code'],
                        $existingItem ? (int) $existingItem->id : null,
                        $existingItem ? (string) ($existingItem->item_code ?? '') : null
                    );

                    $satuanAcuan = (string) ($rabItem->satuan ?? ($rabItem->data->satuan ?? ''));
                    $volumeAcuan = (float) ($rabItem->volume ?? 0);
                    $hargaAcuan = (float) ($rabItem->harga_satuan ?? 0);
                    $qtyBeliImport = (float) ($dimensions['qty_beli'] ?? ($existingItem?->qty_beli ?? 0));
                    $jumlahImport = (float) ($dimensions['jumlah'] ?? ($existingItem?->jumlah ?? 0));

                    $this->assertAllocationIsReasonable(
                        $satuanAcuan,
                        $volumeAcuan,
                        $hargaAcuan,
                        $qtyBeliImport,
                        $jumlahImport,
                        $itemCodeRef
                    );
                    $this->assertProjectAggregateAllocationIsReasonable(
                        (int) $projectId,
                        $rabItem,
                        $qtyBeliImport,
                        $jumlahImport,
                        $existingItem ? (int) $existingItem->id : null,
                        $itemCodeRef
                    );

                    if ($existingItem) {
                        $duplicateUse = RabBreakdownItem::where('rab_breakdown_id', $rabBreakdownId)
                            ->where('rab_item_id', $rabItem->id)
                            ->where('id', '!=', $existingItem->id)
                            ->exists();
                        if ($duplicateUse) {
                            throw new \RuntimeException("Item RAPP {$masterKode} sudah digunakan pada breakdown ini.");
                        }

                        // Update existing item
                        $existingItem->update([
                            'uraian' => $rabItem->data->uraian ?? $rabItem->keterangan ?? $itemData['uraian'],
                            'volume_rab' => (float) ($rabItem->volume ?? 0),
                            'satuan' => $rabItem->satuan ?? ($rabItem->data->satuan ?? $itemData['satuan']),
                            'unit_price' => (float) ($rabItem->harga_satuan ?? 0),
                            'total_price' => ((float) ($rabItem->volume ?? 0)) * ((float) ($rabItem->harga_satuan ?? 0)),
                            'rab_item_id' => $rabItem->id,
                            'notes' => $itemData['notes'] ?? $existingItem->notes,
                            'p' => $dimensions['p'] ?? null,
                            'l' => $dimensions['l'] ?? null,
                            't' => $dimensions['t'] ?? null,
                            'n' => $dimensions['n'] ?? null,
                            'n_tul_1' => $dimensions['n_tul_1'] ?? null,
                            'n_tul_2' => $dimensions['n_tul_2'] ?? null,
                            'jarak' => $dimensions['jarak'] ?? null,
                            'dia_1' => $dimensions['dia_1'] ?? null,
                            'dia_2' => $dimensions['dia_2'] ?? null,
                            'dia_3' => $dimensions['dia_3'] ?? null,
                            'berat_1' => $dimensions['berat_1'] ?? null,
                            'berat_2' => $dimensions['berat_2'] ?? null,
                            'm2_peng' => $dimensions['m2_peng'] ?? null,
                            'qty' => $dimensions['qty'] ?? null,
                            'qty_beli' => $dimensions['qty_beli'] ?? null,
                            'jumlah' => $dimensions['jumlah'] ?? null,
                        ]);
                        $updatedCount++;
                        $item = $existingItem;
                    } else {
                        $duplicateUse = RabBreakdownItem::where('rab_breakdown_id', $rabBreakdownId)
                            ->where('rab_item_id', $rabItem->id)
                            ->exists();
                        if ($duplicateUse) {
                            throw new \RuntimeException("Item RAPP {$masterKode} sudah digunakan pada breakdown ini.");
                        }

                        // Create new item
                        $item = RabBreakdownItem::create([
                            'rab_breakdown_id' => $rabBreakdownId,
                            'item_code' => $itemData['item_code'],
                            'uraian' => $rabItem->data->uraian ?? $rabItem->keterangan ?? $itemData['uraian'],
                            'volume_rab' => (float) ($rabItem->volume ?? 0),
                            'satuan' => $rabItem->satuan ?? ($rabItem->data->satuan ?? $itemData['satuan']),
                            'unit_price' => (float) ($rabItem->harga_satuan ?? 0),
                            'total_price' => ((float) ($rabItem->volume ?? 0)) * ((float) ($rabItem->harga_satuan ?? 0)),
                            'rab_item_id' => $rabItem->id,
                            'notes' => $itemData['notes'] ?? null,
                            'p' => $dimensions['p'] ?? null,
                            'l' => $dimensions['l'] ?? null,
                            't' => $dimensions['t'] ?? null,
                            'n' => $dimensions['n'] ?? null,
                            'n_tul_1' => $dimensions['n_tul_1'] ?? null,
                            'n_tul_2' => $dimensions['n_tul_2'] ?? null,
                            'jarak' => $dimensions['jarak'] ?? null,
                            'dia_1' => $dimensions['dia_1'] ?? null,
                            'dia_2' => $dimensions['dia_2'] ?? null,
                            'dia_3' => $dimensions['dia_3'] ?? null,
                            'berat_1' => $dimensions['berat_1'] ?? null,
                            'berat_2' => $dimensions['berat_2'] ?? null,
                            'm2_peng' => $dimensions['m2_peng'] ?? null,
                            'qty' => $dimensions['qty'] ?? null,
                            'qty_beli' => $dimensions['qty_beli'] ?? null,
                            'jumlah' => $dimensions['jumlah'] ?? null,
                        ]);
                        $importedCount++;
                    }

                    // Auto-map to Master Data if enabled
                    if ($autoMap) {
                        $this->mapToMasterData($item, $masterKode, $this->dimensionPayloadFromItem($item));
                        $mappedCount++;
                    }
                } catch (ValidationException $ve) {
                    $flat = collect($ve->errors())->flatten();
                    $msg = (string) ($flat->first() ?? $ve->getMessage());
                    throw new \RuntimeException("Baris {$rowNo} ({$itemCodeRef} | {$masterKodeRef}): {$msg}");
                } catch (\Throwable $rowError) {
                    throw new \RuntimeException("Baris {$rowNo} ({$itemCodeRef} | {$masterKodeRef}): {$rowError->getMessage()}");
                }
            }

            // Update RAB Breakdown budget
            $rabBreakdown->updateBudget();
            $needsResubmit = $this->markForReapprovalIfNeeded($rabBreakdown);

            DB::commit();

            return redirect()
                ->route('dev.rab-breakdown.show', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdownId])
                ->with('success', "Import berhasil! {$importedCount} item baru, {$updatedCount} item diperbarui, {$mappedCount} item di-mapping ke Master Data."
                    . ($needsResubmit ? ' Status approval RAB Breakdown kembali draft, silakan submit ulang.' : ''));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Import gagal: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Export RAB Breakdown data
     */
    public function export($projectId, $rabBreakdownId = null)
    {
        $project = Project::findOrFail($projectId);
        
        if ($rabBreakdownId) {
            // Export specific 
            $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
            $items = $rabBreakdown->items()->with('budgetSources')->get();
            
            $fileName = "rab-breakdown-{$rabBreakdown->rab_breakdown_code}-{$project->code}.xlsx";
            
            // Create Excel export
            // TODO: Implement Excel export using Laravel Excel or PhpSpreadsheet
            // For now, return JSON response
            return response()->json([
                'success' => true,
                'message' => 'Export feature coming soon',
                'data' => [
                    'rabBreakdown' => $rabBreakdown,
                    'items' => $items,
                    'total_items' => $items->count(),
                    'total_budget' => $items->sum('total_price')
                ]
            ]);
            
        } else {
            // Export all RAB Breakdown for project
            $rabBreakdownList = RabBreakdown::where('project_id', $projectId)
                ->withCount('items')
                ->get();
            
            $fileName = "rab-breakdown-all-{$project->code}.xlsx";
            
            return response()->json([
                'success' => true,
                'message' => 'Export all RAB Breakdown feature coming soon',
                'data' => [
                    'project' => $project,
                    'rab_breakdown_list' => $rabBreakdownList,
                    'total_rab_breakdowns' => $rabBreakdownList->count()
                ]
            ]);
        }
    }

    /**
     * Download Excel template
     */
    public function downloadTemplate()
    {
        $templatePath = storage_path('app/templates/rab-breakdown-items-template.csv');
        
        if (!file_exists($templatePath)) {
            // Create template if doesn't exist
            $this->createTemplate();
        }
        
        return response()->download($templatePath, 'rab-breakdown-items-template.csv');
    }

    // ==================== PROGRESS TRACKING ====================

    /**
     * Submit RAB Breakdown untuk approval HO
     */
    public function submit($projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);

        if (!$rabBreakdown->canSubmit()) {
            return back()->with('error', 'RAB Breakdown tidak dapat disubmit. Pastikan status draft/rejected dan minimal punya 1 item.');
        }

        $rabBreakdown->approval_status = 'submitted';
        $rabBreakdown->submitted_at = now();
        $rabBreakdown->submitted_by = auth()->id();
        $rabBreakdown->approved_at = null;
        $rabBreakdown->approved_by = null;
        $rabBreakdown->rejected_at = null;
        $rabBreakdown->rejected_by = null;
        $rabBreakdown->rejected_reason = null;
        $rabBreakdown->save();
        $projectName = Project::where('id', $projectId)->value('name');
        NotificationService::notifyHO(
            'RAB Breakdown Diajukan',
            'RAB Breakdown ' . ($rabBreakdown->name ?? ('#' . $rabBreakdown->id))
                . ' diajukan' . ($projectName ? ' untuk proyek ' . $projectName : '') . '.',
            route('dev.rab-breakdown.show', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown->id]),
            'approval',
            [
                'doc_type' => 'rab_breakdown',
                'doc_id' => $rabBreakdown->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'RAB Breakdown berhasil disubmit dan menunggu persetujuan HO.');
    }

    /**
     * Approve RAB Breakdown oleh HO
     */
    public function approve($projectId, $rabBreakdownId)
    {
        if (!$this->isHO()) {
            abort(403, 'Hanya HO yang dapat approve RAB Breakdown.');
        }

        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);

        if (!$rabBreakdown->canApprove()) {
            return back()->with('error', 'RAB Breakdown hanya bisa di-approve dari status submitted.');
        }

        $rabBreakdown->approval_status = 'approved';
        $rabBreakdown->approved_at = now();
        $rabBreakdown->approved_by = auth()->id();
        $rabBreakdown->rejected_at = null;
        $rabBreakdown->rejected_by = null;
        $rabBreakdown->rejected_reason = null;
        $rabBreakdown->save();
        NotificationService::notifySubmitter(
            $rabBreakdown,
            'RAB Breakdown Disetujui',
            'RAB Breakdown ' . ($rabBreakdown->name ?? ('#' . $rabBreakdown->id)) . ' disetujui.',
            route('dev.rab-breakdown.show', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown->id]),
            'approval',
            [
                'doc_type' => 'rab_breakdown',
                'doc_id' => $rabBreakdown->id,
                'project_id' => $projectId,
            ]
        );

        return back()->with('success', 'RAB Breakdown berhasil di-approve.');
    }

    /**
     * Reject RAB Breakdown oleh HO
     */
    public function reject(Request $request, $projectId, $rabBreakdownId)
    {
        if (!$this->isHO()) {
            abort(403, 'Hanya HO yang dapat reject RAB Breakdown.');
        }

        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);

        if (!$rabBreakdown->canReject()) {
            return back()->with('error', 'RAB Breakdown hanya bisa di-reject dari status submitted.');
        }

        $rabBreakdown->approval_status = 'rejected';
        $rabBreakdown->rejected_at = now();
        $rabBreakdown->rejected_by = auth()->id();
        $reason = trim((string) $request->input('rejected_reason', ''));
        $rabBreakdown->rejected_reason = $reason !== '' ? $reason : null;
        $rabBreakdown->save();
        $message = 'RAB Breakdown ' . ($rabBreakdown->name ?? ('#' . $rabBreakdown->id)) . ' ditolak.';
        if ($reason !== '') {
            $message .= ' Alasan: ' . $reason;
        }
        NotificationService::notifySubmitter(
            $rabBreakdown,
            'RAB Breakdown Ditolak',
            $message,
            route('dev.rab-breakdown.show', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdown->id]),
            'approval',
            [
                'doc_type' => 'rab_breakdown',
                'doc_id' => $rabBreakdown->id,
                'project_id' => $projectId,
                'rejected_reason' => $reason,
            ]
        );

        return back()->with('success', 'RAB Breakdown berhasil di-reject untuk revisi.');
    }

    /**
     * Update RAB Breakdown progress
     */
    public function updateProgress(Request $request, $projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $this->ensureEditable($rabBreakdown);

        $request->validate([
            'actual_amount' => 'required|numeric|min:0'
        ]);

        $rabBreakdown->actual_amount = $request->actual_amount;
        $rabBreakdown->calculateProgress();
        $rabBreakdown->save();
        $needsResubmit = $this->markForReapprovalIfNeeded($rabBreakdown);

        return back()->with('success', $needsResubmit
            ? 'Progress berhasil diperbarui. Status approval RAB Breakdown kembali draft, silakan submit ulang.'
            : 'Progress berhasil diperbarui!');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Validate a single row of data
     */
    private function validateRow($row, $rowNumber)
    {
        $validations = [];
        
        // Check required fields
        $requiredFields = ['item_code', 'uraian', 'volume_rab', 'satuan', 'unit_price'];
        
        foreach ($requiredFields as $field) {
            if (empty($row[$field] ?? '')) {
                $validations[] = [
                    'type' => 'error',
                    'message' => "Baris {$rowNumber}: Kolom '{$field}' harus diisi"
                ];
            }
        }
        
        // Validate item_code format
        if (!empty($row['item_code'] ?? '')) {
            if (!preg_match('/^[A-Za-z](?:\.\d+)?$/', $row['item_code'])) {
                $validations[] = [
                    'type' => 'warning',
                    'message' => "Baris {$rowNumber}: Format 'item_code' mungkin tidak standar (contoh: A, A.1, B.1)"
                ];
            }
        }
        
        // Validate numeric fields
        if (!empty($row['volume_rab'] ?? '') && !is_numeric($row['volume_rab'])) {
            $validations[] = [
                'type' => 'error',
                'message' => "Baris {$rowNumber}: 'volume_rab' harus angka"
            ];
        }
        
        if (!empty($row['unit_price'] ?? '') && !is_numeric($row['unit_price'])) {
            $validations[] = [
                'type' => 'error',
                'message' => "Baris {$rowNumber}: 'unit_price' harus angka"
            ];
        }

        foreach (['p', 'l', 't', 'n', 'n_tul_1', 'n_tul_2', 'jarak', 'dia_1', 'dia_2', 'dia_3', 'berat_1', 'berat_2', 'm2_peng', 'qty', 'qty_beli', 'jumlah'] as $dimensionField) {
            if (($row[$dimensionField] ?? '') !== '' && !is_numeric($row[$dimensionField])) {
                $validations[] = [
                    'type' => 'error',
                    'message' => "Baris {$rowNumber}: '{$dimensionField}' harus angka"
                ];
            }
        }
        
        // Check master_kode exists in Master Data
        if (!empty($row['master_kode'] ?? '')) {
            // Here you would check if the master code exists in your Master Data table
            // For now, we'll just validate format
            if (!preg_match('/^[A-Z]{2}-\d+$/', $row['master_kode'])) {
                $validations[] = [
                    'type' => 'warning',
                    'message' => "Baris {$rowNumber}: Format 'master_kode' tidak valid (contoh: JS-001, MT-133)"
                ];
            }
        }
        
        // If no validations yet, add success
        if (empty($validations)) {
            $validations[] = [
                'type' => 'success',
                'message' => "Baris {$rowNumber}: Data valid"
            ];
        }
        
        return $validations;
    }

    /**
     * Get overall status for a row
     */
    private function getRowStatus($validations)
    {
        foreach ($validations as $validation) {
            if ($validation['type'] === 'error') {
                return 'error';
            }
        }
        
        foreach ($validations as $validation) {
            if ($validation['type'] === 'warning') {
                return 'warning';
            }
        }

        return 'success';
    }

    private function nullableNumeric($value, ?float $default = null): ?float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return is_numeric($value) ? (float) $value : $default;
    }

    private function extractDimensionsFromRow(array $row): array
    {
        return [
            'p' => $this->nullableNumeric($row['p'] ?? null),
            'l' => $this->nullableNumeric($row['l'] ?? null),
            't' => $this->nullableNumeric($row['t'] ?? null),
            'n' => $this->nullableNumeric($row['n'] ?? null),
            'n_tul_1' => $this->nullableNumeric($row['n_tul_1'] ?? null),
            'n_tul_2' => $this->nullableNumeric($row['n_tul_2'] ?? null),
            'jarak' => $this->nullableNumeric($row['jarak'] ?? null),
            'dia_1' => $this->nullableNumeric($row['dia_1'] ?? null),
            'dia_2' => $this->nullableNumeric($row['dia_2'] ?? null),
            'dia_3' => $this->nullableNumeric($row['dia_3'] ?? null),
            'berat_1' => $this->nullableNumeric($row['berat_1'] ?? null),
            'berat_2' => $this->nullableNumeric($row['berat_2'] ?? null),
            'm2_peng' => $this->nullableNumeric($this->valueByAliases($row, ['m2_peng', 'm2_peng_', 'm2_peng__'])),
            'qty' => $this->nullableNumeric($row['qty'] ?? null),
            'qty_beli' => $this->nullableNumeric($this->valueByAliases($row, ['qty_beli', 'qty_beli_', 'qty_beli__'])),
            'jumlah' => $this->nullableNumeric($row['jumlah'] ?? null),
        ];
    }

    private function dimensionPayloadFromItem(RabBreakdownItem $item): array
    {
        return [
            'p' => $this->nullableNumeric($item->p, (float) $item->volume_rab),
            'l' => $this->nullableNumeric($item->l),
            't' => $this->nullableNumeric($item->t),
            'n' => $this->nullableNumeric($item->n),
            'n_tul_1' => $this->nullableNumeric($item->n_tul_1),
            'n_tul_2' => $this->nullableNumeric($item->n_tul_2),
            'jarak' => $this->nullableNumeric($item->jarak),
            'dia_1' => $this->nullableNumeric($item->dia_1),
            'dia_2' => $this->nullableNumeric($item->dia_2),
            'dia_3' => $this->nullableNumeric($item->dia_3),
            'berat_1' => $this->nullableNumeric($item->berat_1),
            'berat_2' => $this->nullableNumeric($item->berat_2),
            'm2_peng' => $this->nullableNumeric($item->m2_peng),
            'qty' => $this->nullableNumeric($item->qty),
            'qty_beli' => $this->nullableNumeric($item->qty_beli),
            'jumlah' => $this->nullableNumeric($item->jumlah),
        ];
    }

    private function valueByAliases(array $row, array $aliases)
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $row)) {
                return $row[$alias];
            }
        }
        return null;
    }

    /**
     * Map RAB Breakdown item to Master Data
     */
    private function mapToMasterData($rabBreakdownItem, $masterKode, array $dimensions = [])
    {
        // Look up the Master Data record
        $masterItem = DB::table('master_data')
            ->where('kode', $masterKode)
            ->first();

        if ($masterItem) {
            $defaultVolume = $this->nullableNumeric($dimensions['p'] ?? null, (float) $rabBreakdownItem->volume_rab);
            $qtyBeli = $this->nullableNumeric($dimensions['qty_beli'] ?? null, $defaultVolume);
            $jumlah = $this->nullableNumeric($dimensions['jumlah'] ?? null, (float) $qtyBeli * (float) $masterItem->harga);

            $this->assertAllocationIsReasonable(
                (string) ($rabBreakdownItem->satuan ?? ''),
                (float) ($rabBreakdownItem->volume_rab ?? 0),
                (float) ($rabBreakdownItem->unit_price ?? 0),
                (float) $qtyBeli,
                (float) $jumlah,
                trim((string) ($rabBreakdownItem->item_code ?? '')) !== ''
                    ? (string) $rabBreakdownItem->item_code
                    : ((string) ($masterKode ?: ('RBD Item #' . (string) $rabBreakdownItem->id)))
            );

            // Delete existing budget sources for this item
            RabBreakdownBudgetSource::where('rab_breakdown_item_id', $rabBreakdownItem->id)->delete();
            
            // Create new budget source
            RabBreakdownBudgetSource::create([
                'rab_breakdown_item_id' => $rabBreakdownItem->id,
                'master_kode' => $masterItem->kode,
                'master_kategori' => $masterItem->kode_kategori ?? substr($masterItem->kode, 0, 2),
                'master_uraian' => $masterItem->uraian,
                'master_satuan' => $masterItem->satuan,
                'master_harga' => $masterItem->harga,
                'allocated_volume' => $qtyBeli,
                'allocated_amount' => $jumlah,
                'p' => $dimensions['p'] ?? null,
                'l' => $dimensions['l'] ?? null,
                't' => $dimensions['t'] ?? null,
                'n' => $dimensions['n'] ?? null,
                'n_tul_1' => $dimensions['n_tul_1'] ?? null,
                'n_tul_2' => $dimensions['n_tul_2'] ?? null,
                'jarak' => $dimensions['jarak'] ?? null,
                'dia_1' => $dimensions['dia_1'] ?? null,
                'dia_2' => $dimensions['dia_2'] ?? null,
                'dia_3' => $dimensions['dia_3'] ?? null,
                'berat_1' => $dimensions['berat_1'] ?? null,
                'berat_2' => $dimensions['berat_2'] ?? null,
                'm2_peng' => $dimensions['m2_peng'] ?? null,
                'qty' => $dimensions['qty'] ?? null,
                'qty_beli' => $qtyBeli,
                'jumlah' => $jumlah,
                'notes' => 'Auto-mapped dari import Excel'
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Create Excel template
     */
    private function createTemplate()
    {
        // In a real implementation, you would use PhpSpreadsheet or Laravel Excel
        // For now, we'll create a simple CSV template
        
        $templatePath = storage_path('app/templates');
        if (!file_exists($templatePath)) {
            mkdir($templatePath, 0755, true);
        }
        
        $csvContent = "item_code,uraian,volume_rab,satuan,unit_price,master_kode,p,l,t,n,n_tul_1,n_tul_2,jarak,dia_1,dia_2,dia_3,berat_1,berat_2,m2_peng,qty,qty_beli,jumlah,notes\n";
        $csvContent .= "A.1,Demolish bangunan lama,16,OH,150000,JS-001,16,0,0,1,0,0,0,0,0,0,0,0,0,16,16,2400000,Contoh item pertama\n";
        $csvContent .= "A.2,Relokasi sisa bongkaran,8,Rit,400000,SR-008,8,0,0,1,0,0,0,0,0,0,0,0,0,8,8,3200000,Contoh item kedua\n";
        $csvContent .= "B.1,Pondasi Strauss diameter 30cm,176,OH,675000,JS-065,176,0,0,1,0,0,0,30,0,0,0,0,0,176,176,118800000,Contoh item ketiga\n";
        $csvContent .= "C.1,Beton K250 untuk pile cap,45,m3,1250000,MT-109,45,1,1,1,0,0,0,0,0,0,0,0,45,45,45,56250000,Contoh item keempat\n";
        
        file_put_contents($templatePath . '/rab-breakdown-items-template.csv', $csvContent);
        
        // Also create XLSX version if possible
        // This would require PhpSpreadsheet installation
    }

    // ==================== REPORTS ====================

    /**
     * Project RAB Breakdown report
     */
    public function projectRabBreakdownReport($projectId)
    {
        $project = Project::findOrFail($projectId);
        $rabBreakdownList = RabBreakdown::where('project_id', $projectId)
            ->orderBy('order_number')
            ->with(['items' => function($query) {
                $query->select('*');
            }])
            ->get();
        $rabBreakdownList->each(function ($rb) {
            $rb->setRelation('items', $this->sortItemsNaturally($rb->items));
        });
        
        return view('dev.rab-breakdown.report', ['project' => $project, 'rabBreakdownList' => $rabBreakdownList]);
    }

    /**
     * Backward-compatible alias.
     */
    public function projectWBSReport($projectId)
    {
        return $this->projectRabBreakdownReport($projectId);
    }

    /**
     * Global RAB Breakdown reports
     */
    public function globalReports()
    {
        $rabBreakdownStats = DB::table('rab_breakdowns')
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(budget_amount) as total_budget'))
            ->groupBy('status')
            ->get();
            
        $projectsWithRabBreakdowns = Project::has('rabBreakdowns')
            ->withCount('rabBreakdowns')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        return view('dev.rab-breakdown.global-reports', ['rabBreakdownStats' => $rabBreakdownStats, 'projectsWithRabBreakdowns' => $projectsWithRabBreakdowns]);
    }

    /**
     * Progress report
     */
    public function progressReport()
    {
        $progressData = DB::table('rab_breakdowns')
            ->select(
                DB::raw('AVG(progress_percentage) as avg_progress'),
                DB::raw('COUNT(*) as total_rab_breakdowns'),
                DB::raw('SUM(CASE WHEN progress_percentage >= 100 THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN progress_percentage < 100 AND progress_percentage > 0 THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN progress_percentage = 0 THEN 1 ELSE 0 END) as not_started')
            )
            ->first();
        
        return view('dev.rab-breakdown.progress-report', compact('progressData'));
    }

    // ==================== API METHODS ====================

    /**
     * API: Get project RAB Breakdown list
     */
    public function apiProjectRabBreakdown($projectId)
    {
        $rabBreakdownList = RabBreakdown::where('project_id', $projectId)
            ->orderBy('order_number')
            ->with(['items' => function($query) {
                $query->select('*');
            }])
            ->get();
        $rabBreakdownList->each(function ($rb) {
            $rb->setRelation('items', $this->sortItemsNaturally($rb->items));
        });
        
        return response()->json([
            'success' => true,
            'data' => $rabBreakdownList,
            'count' => $rabBreakdownList->count()
        ]);
    }

    /**
     * Backward-compatible alias.
     */
    public function apiProjectWBS($projectId)
    {
        return $this->apiProjectRabBreakdown($projectId);
    }

    /**
     * API: Get project RAB Breakdown summary.
     */
    public function apiProjectRabBreakdownSummary($projectId)
    {
        return $this->publicProjectSummary($projectId);
    }

    /**
     * API: Get RAB Breakdown list
     */
    public function apiIndex($projectId)
    {
        $rabBreakdownList = RabBreakdown::where('project_id', $projectId)
            ->orderBy('order_number')
            ->withCount('items')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $rabBreakdownList
        ]);
    }

    /**
     * API: Get single 
     */
    public function apiShow($projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)
            ->with(['items.budgetSources', 'items.rabItem'])
            ->findOrFail($rabBreakdownId);
        $rabBreakdown->setRelation('items', $this->sortItemsNaturally($rabBreakdown->items));
        
        return response()->json([
            'success' => true,
            'data' => $rabBreakdown
        ]);
    }

    /**
     * API: Get RAB Breakdown items
     */
    public function apiGetItems($projectId, $rabBreakdownId)
    {
        $items = RabBreakdownItem::whereHas('rabBreakdown', function($query) use ($projectId, $rabBreakdownId) {
                $query->where('project_id', $projectId)
                      ->where('id', $rabBreakdownId);
            })
            ->with(['budgetSources', 'rabItem'])
            ->get();
        $items = $this->sortItemsNaturally($items);
        
        return response()->json([
            'success' => true,
            'data' => $items,
            'count' => $items->count()
        ]);
    }

    /**
     * API: Update progress
     */
    public function apiUpdateProgress(Request $request, $projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        
        $request->validate([
            'progress_percentage' => 'required|numeric|min:0|max:100'
        ]);
        
        $rabBreakdown->progress_percentage = $request->progress_percentage;
        $rabBreakdown->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Progress updated successfully',
            'data' => $rabBreakdown
        ]);
    }

    /**
     * API: Update actual amount
     */
    public function apiUpdateActual(Request $request, $projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        
        $request->validate([
            'actual_amount' => 'required|numeric|min:0'
        ]);
        
        $rabBreakdown->actual_amount = $request->actual_amount;
        $rabBreakdown->calculateProgress();
        $rabBreakdown->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Actual amount updated successfully',
            'data' => $rabBreakdown
        ]);
    }

    /**
     * API: Calculate RAB Breakdown budget
     */
    public function apiCalculateBudget($projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        
        $rabBreakdown->updateBudget();
        
        return response()->json([
            'success' => true,
            'message' => 'Budget calculated successfully',
            'data' => [
                'budget_amount' => $rabBreakdown->budget_amount,
                'item_count' => $rabBreakdown->items()->count(),
                'total_items_price' => $rabBreakdown->items()->sum('total_price')
            ]
        ]);
    }

    /**
     * API: Calculate progress
     */
    public function apiCalculateProgress($projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        
        $rabBreakdown->calculateProgress();
        
        return response()->json([
            'success' => true,
            'message' => 'Progress calculated successfully',
            'data' => [
                'progress_percentage' => $rabBreakdown->progress_percentage,
                'actual_amount' => $rabBreakdown->actual_amount,
                'budget_amount' => $rabBreakdown->budget_amount
            ]
        ]);
    }

    /**
     * API: Get RAB Breakdown summary
     */
    public function apiGetSummary($projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        
        $summary = [
            'rabBreakdown' => $rabBreakdown,
            'items_count' => $rabBreakdown->items()->count(),
            'total_budget' => $rabBreakdown->budget_amount,
            'total_actual' => $rabBreakdown->actual_amount,
            'progress' => $rabBreakdown->progress_percentage,
            'items_by_category' => $rabBreakdown->items()
                ->select(DB::raw('SUBSTR(item_code, 1, 1) as category'), // SQLite: SUBSTR bukan SUBSTRING
                         DB::raw('count(*) as count'),
                         DB::raw('sum(total_price) as total'))
                ->groupBy('category')
                ->get()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    // ==================== SETTINGS ====================

    /**
     *  categories settings
     */
    public function categoriesSettings()
    {
        $predefinedRabBreakdowns = [
            ['code' => 'RAB-001', 'name' => 'PEKERJAAN PERSIAPAN', 'order' => 1],
            ['code' => 'RAB-002', 'name' => 'PEKERJAAN TANAH', 'order' => 2],
            ['code' => 'RAB-003', 'name' => 'PEKERJAAN PONDASI', 'order' => 3],
            ['code' => 'RAB-004', 'name' => 'PEKERJAAN STRUKTUR', 'order' => 4],
            ['code' => 'RAB-005', 'name' => 'PEKERJAAN ARSITEKTUR', 'order' => 5],
            ['code' => 'RAB-006', 'name' => 'PEKERJAAN MEKANIKAL', 'order' => 6],
            ['code' => 'RAB-007', 'name' => 'PEKERJAAN ELEKTRIKAL', 'order' => 7],
            ['code' => 'RAB-008', 'name' => 'PEKERJAAN SANITASI & PLAMBING', 'order' => 8],
            ['code' => 'RAB-009', 'name' => 'PEKERJAAN LANSEKAP', 'order' => 9],
            ['code' => 'RAB-010', 'name' => 'PEKERJAAN FINISHING', 'order' => 10],
            ['code' => 'RAB-011', 'name' => 'PENGADAAN PERABOT & PERALATAN', 'order' => 11],
        ];
        
        $usageStats = DB::table('rab_breakdowns')
            ->select('rab_breakdown_code', DB::raw('count(*) as usage_count'))
            ->groupBy('rab_breakdown_code')
            ->get()
            ->keyBy('rab_breakdown_code');
        
        return view('dev.rab-breakdown.categories-settings', ['predefinedRabBreakdowns' => $predefinedRabBreakdowns, 'usageStats' => $usageStats]);
    }

    /**
     * Update categories settings
     */
    public function updateCategoriesSettings(Request $request)
    {
        // TODO: Implement categories settings update
        return back()->with('success', 'Pengaturan kategori berhasil diperbarui!');
    }

    /**
     * API: Get categories
     */
    public function apiGetCategories()
    {
        $predefinedRabBreakdowns = [
            ['code' => 'RAB-001', 'name' => 'PEKERJAAN PERSIAPAN', 'order' => 1],
            ['code' => 'RAB-002', 'name' => 'PEKERJAAN TANAH', 'order' => 2],
            ['code' => 'RAB-003', 'name' => 'PEKERJAAN PONDASI', 'order' => 3],
            ['code' => 'RAB-004', 'name' => 'PEKERJAAN STRUKTUR', 'order' => 4],
            ['code' => 'RAB-005', 'name' => 'PEKERJAAN ARSITEKTUR', 'order' => 5],
            ['code' => 'RAB-006', 'name' => 'PEKERJAAN MEKANIKAL', 'order' => 6],
            ['code' => 'RAB-007', 'name' => 'PEKERJAAN ELEKTRIKAL', 'order' => 7],
            ['code' => 'RAB-008', 'name' => 'PEKERJAAN SANITASI & PLAMBING', 'order' => 8],
            ['code' => 'RAB-009', 'name' => 'PEKERJAAN LANSEKAP', 'order' => 9],
            ['code' => 'RAB-010', 'name' => 'PEKERJAAN FINISHING', 'order' => 10],
            ['code' => 'RAB-011', 'name' => 'PENGADAAN PERABOT & PERALATAN', 'order' => 11],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $predefinedRabBreakdowns
        ]);
    }

    /**
     * API: Update categories
     */
    public function apiUpdateCategories(Request $request)
    {
        // TODO: Implement API categories update
        return response()->json([
            'success' => true,
            'message' => 'Categories updated successfully'
        ]);
    }

    // ==================== DASHBOARD STATS ====================

    /**
     * API: Dashboard RAB Breakdown stats
     */
    public function apiDashboardStats()
    {
        $stats = [
            'total_rab_breakdowns' => RabBreakdown::count(),
            'total_projects_with_rab_breakdowns' => Project::has('rabBreakdowns')->count(),
            'total_budget' => RabBreakdown::sum('budget_amount'),
            'total_actual' => RabBreakdown::sum('actual_amount'),
            'avg_progress' => RabBreakdown::avg('progress_percentage') ?? 0,
            'status_distribution' => RabBreakdown::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * API: Progress chart data
     */
    public function apiProgressChart()
    {
        // SQLite tidak punya DATE(), gunakan strftime
        $data = RabBreakdown::select(
                DB::raw("strftime('%Y-%m-%d', created_at) as date"),
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(progress_percentage) as avg_progress')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // ==================== PUBLIC API ====================

    /**
     * Public: Get RAB Breakdown categories
     */
    public function publicCategories()
    {
        $predefinedRabBreakdowns = [
            ['code' => 'RAB-001', 'name' => 'PEKERJAAN PERSIAPAN', 'order' => 1],
            ['code' => 'RAB-002', 'name' => 'PEKERJAAN TANAH', 'order' => 2],
            ['code' => 'RAB-003', 'name' => 'PEKERJAAN PONDASI', 'order' => 3],
            ['code' => 'RAB-004', 'name' => 'PEKERJAAN STRUKTUR', 'order' => 4],
            ['code' => 'RAB-005', 'name' => 'PEKERJAAN ARSITEKTUR', 'order' => 5],
            ['code' => 'RAB-006', 'name' => 'PEKERJAAN MEKANIKAL', 'order' => 6],
            ['code' => 'RAB-007', 'name' => 'PEKERJAAN ELEKTRIKAL', 'order' => 7],
            ['code' => 'RAB-008', 'name' => 'PEKERJAAN SANITASI & PLAMBING', 'order' => 8],
            ['code' => 'RAB-009', 'name' => 'PEKERJAAN LANSEKAP', 'order' => 9],
            ['code' => 'RAB-010', 'name' => 'PEKERJAAN FINISHING', 'order' => 10],
            ['code' => 'RAB-011', 'name' => 'PENGADAAN PERABOT & PERALATAN', 'order' => 11],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $predefinedRabBreakdowns
        ]);
    }

    /**
     * Public: Get project RAB Breakdown summary
     */
    public function publicProjectSummary($projectId)
    {
        $project = Project::findOrFail($projectId);
        
        $rabBreakdownList = RabBreakdown::where('project_id', $projectId)
            ->select('id', 'rab_breakdown_code', 'name', 'progress_percentage', 'status')
            ->orderBy('order_number')
            ->get();
        
        $summary = [
            'project' => $project->only(['id', 'name', 'code', 'location']),
            'rab_breakdown_count' => $rabBreakdownList->count(),
            'total_progress' => $rabBreakdownList->avg('progress_percentage') ?? 0,
            'rab_breakdown_list' => $rabBreakdownList
        ];
        
        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get RAB Breakdown items table partial
     */
    public function getItemsTablePartial($projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $items = $this->sortItemsNaturally($rabBreakdown->items()->get());
        
        return view('dev.rab-breakdown.items.partials.rab-breakdown-items-table', [
            'items' => $items,
            'projectId' => $projectId,
            'rabBreakdownId' => $rabBreakdownId
        ]);
    }

    /**
     * Get budget sources table partial
     */
    public function getBudgetSourcesPartial($projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        $budgetSources = RabBreakdownBudgetSource::whereHas('rabBreakdownItem', function($query) use ($rabBreakdownId) {
            $query->where('rab_breakdown_id', $rabBreakdownId);
        })->get();
        
        return view('dev.rab-breakdown.items.partials.budget-sources-table', [
            'budgetSources' => $budgetSources
        ]);
    }

    /**
     * Get progress form partial
     */
    public function getProgressFormPartial($projectId, $rabBreakdownId)
    {
        $rabBreakdown = RabBreakdown::where('project_id', $projectId)->findOrFail($rabBreakdownId);
        
        return view('dev.rab-breakdown.items.partials.progress-form', [
            'rabBreakdown' => $rabBreakdown,
            'budgetAmount' => $rabBreakdown->budget_amount,
            'actualAmount' => $rabBreakdown->actual_amount,
            'progressPercentage' => $rabBreakdown->progress_percentage,
            'status' => $rabBreakdown->status,
            'lastUpdated' => $rabBreakdown->updated_at->format('d M Y H:i'),
            'progressNotes' => $rabBreakdown->progress_notes ?? '',
            'updateUrl' => route('dev.rab-breakdown.update-progress', ['projectId' => $projectId, 'rabBreakdown' => $rabBreakdownId])
        ]);
    }

    /**
     * Get import preview partial
     */
    public function getImportPreviewPartial(Request $request, $projectId, $rabBreakdownId)
    {
        $previewData = $request->session()->get('import_preview_data', []);
        
        return view('dev.rab-breakdown.items.partials.import-preview', [
            'previewData' => $previewData
        ]);
    }

    // ==================== TEST VIEW ====================

    /**
     * Test view
     */
    public function testView($projectId)
    {
        $project = Project::findOrFail($projectId);
        $rabBreakdownList = RabBreakdown::where('project_id', $projectId)->get();
        
        return view('dev.rab-breakdown.test', ['project' => $project, 'rabBreakdownList' => $rabBreakdownList]);
    }
}












