<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Rab;
use App\Models\RabItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Lpb;
use App\Models\LpbItem;
use App\Models\Vendor;
use App\Models\PurchaseVoucher;
use App\Models\PurchaseVoucherItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Dev\Concerns\LocksDocumentStatus;
use App\Support\AuditLogger;
use App\Support\NotificationService;

class PurchaseVoucherController extends Controller
{
    use LocksDocumentStatus;

    protected function resolveVoucherSource(int $projectId, int $rabId, ?int $poId, ?int $lpbId): array
    {
        if ($poId && $lpbId) {
            throw ValidationException::withMessages([
                'source' => 'Pilih salah satu: PO atau LPB.',
            ]);
        }
        if (!$poId && !$lpbId) {
            throw ValidationException::withMessages([
                'source' => 'Voucher wajib referensi PO atau LPB.',
            ]);
        }

        if ($poId) {
            $po = PurchaseOrder::with('vendor')
                ->where('project_id', $projectId)
                ->where('rab_id', $rabId)
                ->where('id', $poId)
                ->first();
            if (!$po || ($po->status ?? '') !== 'approved') {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'PO harus berasal dari proyek ini dan berstatus approved.',
                ]);
            }
            if (($po->status ?? '') === 'cancelled') {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'PO sudah dibatalkan.',
                ]);
            }
            if (!$po->vendor_id || !$po->vendor) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'Vendor pada PO belum diisi.',
                ]);
            }
            $lpbIds = Lpb::where('purchase_order_id', $po->id)->pluck('id');
            return [
                'type' => 'po',
                'po' => $po,
                'lpb_ids' => $lpbIds,
                'vendor' => $po->vendor,
            ];
        }

        $lpb = Lpb::with('vendor')
            ->where('project_id', $projectId)
            ->where('rab_id', $rabId)
            ->where('id', $lpbId)
            ->first();
        if (!$lpb || ($lpb->status ?? '') !== 'approved') {
            throw ValidationException::withMessages([
                'lpb_id' => 'LPB harus berasal dari proyek ini dan berstatus approved.',
            ]);
        }
        if (!$lpb->vendor_id || !$lpb->vendor) {
            throw ValidationException::withMessages([
                'lpb_id' => 'Vendor pada LPB belum diisi.',
            ]);
        }

        return [
            'type' => 'lpb',
            'lpb' => $lpb,
            'vendor' => $lpb->vendor,
        ];
    }

    protected function validateVoucherItemsAgainstSource(array $source, array $items, ?int $currentVoucherId = null): void
    {
        $requestedTotals = collect($items)
            ->filter(fn ($row) => !empty($row['rab_item_id']))
            ->groupBy(fn ($row) => (int) $row['rab_item_id'])
            ->map(fn ($rows) => $rows->sum(fn ($row) => (float) ($row['qty'] ?? 0)));

        if ($source['type'] === 'po') {
            /** @var PurchaseOrder $po */
            $po = $source['po'];
            $poItems = PurchaseOrderItem::where('purchase_order_id', $po->id)->get()->keyBy('rab_item_id');
            $lpbIds = $source['lpb_ids'] ?? collect();

            foreach ($requestedTotals as $rabItemId => $requestedQty) {
                $rabItemId = (int) $rabItemId;
                $poItem = $poItems->get($rabItemId);
                if (!$poItem) {
                    throw ValidationException::withMessages([
                        'items' => 'Item voucher harus berasal dari PO yang dipilih.',
                    ]);
                }

                $existingQty = PurchaseVoucherItem::where('rab_item_id', $rabItemId)
                    ->whereHas('voucher', function ($q) use ($po, $lpbIds, $currentVoucherId) {
                        $q->where(function ($w) use ($po, $lpbIds) {
                            $w->where('purchase_order_id', $po->id);
                            if ($lpbIds && $lpbIds->isNotEmpty()) {
                                $w->orWhereIn('lpb_id', $lpbIds);
                            }
                        });
                        $q->whereNotIn('status', ['rejected', 'cancelled']);
                        if ($currentVoucherId) {
                            $q->where('id', '!=', $currentVoucherId);
                        }
                    })
                    ->sum('qty');

                $remaining = max(0, (float) ($poItem->qty ?? 0) - (float) $existingQty);
                if ($requestedQty > $remaining + 0.00001) {
                    throw ValidationException::withMessages([
                        'items' => 'Qty voucher melebihi sisa PO untuk item ' . ($poItem->item_code_snapshot ?? $rabItemId) . '. Sisa: ' . number_format($remaining, 2, ',', '.'),
                    ]);
                }
            }
        }

        if ($source['type'] === 'lpb') {
            /** @var Lpb $lpb */
            $lpb = $source['lpb'];
            $lpbItems = LpbItem::where('lpb_id', $lpb->id)->get()->keyBy('rab_item_id');

            foreach ($requestedTotals as $rabItemId => $requestedQty) {
                $rabItemId = (int) $rabItemId;
                $lpbItem = $lpbItems->get($rabItemId);
                if (!$lpbItem) {
                    throw ValidationException::withMessages([
                        'items' => 'Item voucher harus berasal dari LPB yang dipilih.',
                    ]);
                }

                $existingQty = PurchaseVoucherItem::where('rab_item_id', $rabItemId)
                    ->whereHas('voucher', function ($q) use ($lpb, $currentVoucherId) {
                        $q->where('lpb_id', $lpb->id)
                            ->whereNotIn('status', ['rejected', 'cancelled']);
                        if ($currentVoucherId) {
                            $q->where('id', '!=', $currentVoucherId);
                        }
                    })
                    ->sum('qty');

                $remaining = max(0, (float) ($lpbItem->qty ?? 0) - (float) $existingQty);
                if ($requestedQty > $remaining + 0.00001) {
                    throw ValidationException::withMessages([
                        'items' => 'Qty voucher melebihi sisa LPB untuk item ' . ($lpbItem->item_code_snapshot ?? $rabItemId) . '. Sisa: ' . number_format($remaining, 2, ',', '.'),
                    ]);
                }
            }
        }
    }
    /**
     * Parse angka format Indonesia / mixed ke float
     * Contoh: "16.200", "16,20", "16.200,50"
     */
    protected function parseIdNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^\d,.-]/', '', (string) $value);
        // buang titik ribuan
        $clean = str_replace('.', '', $clean);
        // koma jadi desimal
        $clean = str_replace(',', '.', $clean);

        return (float) $clean;
    }

    /**
     * Hanya admin yang boleh edit / delete voucher
     */
    protected function ensureHO(): void
    {
        $user = auth()->user();
        if (!$user || !method_exists($user, 'isHO') || !$user->isHO()) {
            abort(403, 'Hanya HO yang boleh melakukan aksi ini.');
        }
    }

    /**
     * Recalculate realisasi & sisa untuk RabItem terkait
     * Sesuai aturan Excel: realisasi_amount = SUM(qty) Ã— harga_satuan RAPP
     */
    protected function recalcRabItems(array $rabItemIds): void
    {
        $ids = collect($rabItemIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        $items = RabItem::whereIn('id', $ids)->get();

        foreach ($items as $item) {
            $budgetVol = (float) ($item->volume ?? 0);
            $hs        = (float) ($item->harga_satuan ?? 0);
            $budgetAmt = $budgetVol * $hs;

            // total qty semua voucher untuk item ini
            $realisasiVol = (float) PurchaseVoucherItem::where('rab_item_id', $item->id)
                ->whereHas('voucher', function ($q) {
                    $q->whereNotIn('status', ['rejected', 'cancelled']);
                })
                ->sum('qty');
            $realisasiAmt = $realisasiVol * $hs;

            $item->update([
                'realisasi_volume' => $realisasiVol,
                'realisasi_amount' => max(0, $realisasiAmt),
                'sisa_volume'      => max(0, $budgetVol - $realisasiVol),
                'sisa_amount'      => max(0, $budgetAmt - $realisasiAmt),
            ]);
        }
    }

    /**
     * Rekap voucher per RAPP
     */
    public function index(Request $request, $projectId, $rabId)
    {
        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        $q = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id);

        $search   = trim((string) $request->get('q', ''));
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');
        $vendor   = trim((string) $request->get('vendor', ''));

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('voucher_no', 'like', "%{$search}%")
                    ->orWhere('invoice_no', 'like', "%{$search}%")
                    ->orWhere('payment_purpose', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%");
            });
        }

        if ($vendor !== '') {
            $q->where('vendor_name', 'like', "%{$vendor}%");
        }

        if ($dateFrom) {
            $q->whereDate('voucher_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('voucher_date', '<=', $dateTo);
        }

        $vouchers = $q
            ->withCount('items')
            ->with(['purchaseOrder', 'lpb'])
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->appends($request->query());

        $totals = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->selectRaw('
                COALESCE(SUM(subtotal_amount), 0) AS subtotal_sum,
                COALESCE(SUM(tax_amount), 0)      AS tax_sum,
                COALESCE(SUM(total_amount), 0)    AS total_sum
            ')
            ->first();

        return view('dev.purchase_vouchers.index', [
            'project'  => $project,
            'rab'      => $rab,
            'vouchers' => $vouchers,
            'totals'   => $totals,
            'filters'  => [
                'q'         => $search,
                'vendor'    => $vendor,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ],
        ]);
    }

    public function printIndex(Request $request, $projectId, $rabId)
    {
        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        $q = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id);

        $search   = trim((string) $request->get('q', ''));
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');
        $vendor   = trim((string) $request->get('vendor', ''));

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('voucher_no', 'like', "%{$search}%")
                    ->orWhere('invoice_no', 'like', "%{$search}%")
                    ->orWhere('payment_purpose', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%");
            });
        }

        if ($vendor !== '') {
            $q->where('vendor_name', 'like', "%{$vendor}%");
        }

        if ($dateFrom) {
            $q->whereDate('voucher_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('voucher_date', '<=', $dateTo);
        }

        $vouchers = $q->orderByDesc('voucher_date')->orderByDesc('id')->get();

        $totals = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->when($search !== '', function ($w) use ($search) {
                $w->where(function ($ww) use ($search) {
                    $ww->where('voucher_no', 'like', "%{$search}%")
                        ->orWhere('invoice_no', 'like', "%{$search}%")
                        ->orWhere('payment_purpose', 'like', "%{$search}%")
                        ->orWhere('vendor_name', 'like', "%{$search}%");
                });
            })
            ->when($vendor !== '', fn($w) => $w->where('vendor_name', 'like', "%{$vendor}%"))
            ->when($dateFrom, fn($w) => $w->whereDate('voucher_date', '>=', $dateFrom))
            ->when($dateTo, fn($w) => $w->whereDate('voucher_date', '<=', $dateTo))
            ->selectRaw('
                COALESCE(SUM(subtotal_amount), 0) AS subtotal_sum,
                COALESCE(SUM(tax_amount), 0)      AS tax_sum,
                COALESCE(SUM(total_amount), 0)    AS total_sum
            ')
            ->first();

        return view('pdf.purchase_voucher_rekap', [
            'project'  => $project,
            'rab'      => $rab,
            'vouchers' => $vouchers,
            'totals'   => $totals,
            'filters'  => [
                'q'         => $search,
                'vendor'    => $vendor,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ],
        ]);
    }

    public function pdfIndex(Request $request, $projectId, $rabId)
    {
        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        $q = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id);

        $search   = trim((string) $request->get('q', ''));
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');
        $vendor   = trim((string) $request->get('vendor', ''));

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('voucher_no', 'like', "%{$search}%")
                    ->orWhere('invoice_no', 'like', "%{$search}%")
                    ->orWhere('payment_purpose', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%");
            });
        }

        if ($vendor !== '') {
            $q->where('vendor_name', 'like', "%{$vendor}%");
        }

        if ($dateFrom) {
            $q->whereDate('voucher_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('voucher_date', '<=', $dateTo);
        }

        $vouchers = $q->orderByDesc('voucher_date')->orderByDesc('id')->get();

        $totals = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->when($search !== '', function ($w) use ($search) {
                $w->where(function ($ww) use ($search) {
                    $ww->where('voucher_no', 'like', "%{$search}%")
                        ->orWhere('invoice_no', 'like', "%{$search}%")
                        ->orWhere('payment_purpose', 'like', "%{$search}%")
                        ->orWhere('vendor_name', 'like', "%{$search}%");
                });
            })
            ->when($vendor !== '', fn($w) => $w->where('vendor_name', 'like', "%{$vendor}%"))
            ->when($dateFrom, fn($w) => $w->whereDate('voucher_date', '>=', $dateFrom))
            ->when($dateTo, fn($w) => $w->whereDate('voucher_date', '<=', $dateTo))
            ->selectRaw('
                COALESCE(SUM(subtotal_amount), 0) AS subtotal_sum,
                COALESCE(SUM(tax_amount), 0)      AS tax_sum,
                COALESCE(SUM(total_amount), 0)    AS total_sum
            ')
            ->first();

        $data = [
            'project'  => $project,
            'rab'      => $rab,
            'vouchers' => $vouchers,
            'totals'   => $totals,
            'filters'  => [
                'q'         => $search,
                'vendor'    => $vendor,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ],
        ];

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.purchase_voucher_rekap', $data)
                ->setPaper('a4', 'portrait')
                ->stream('Rekap-Voucher-Pembelian.pdf');
        }

        return view('pdf.purchase_voucher_rekap', $data);
    }

    /**
     * Detail voucher
     */
    public function show(Request $request, $projectId, $rabId, $voucherId)
    {
        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        $voucher = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->with(['items.rabItem.data'])
            ->findOrFail($voucherId);

        return view('dev.purchase_vouchers.show', [
            'project' => $project,
            'rab'     => $rab,
            'voucher' => $voucher,
        ]);
    }

    public function print($projectId, $rabId, $voucherId)
    {
        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        $voucher = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->with(['items.rabItem.data', 'purchaseOrder', 'lpb'])
            ->findOrFail($voucherId);

        $logoSrc = asset('images/logo-dipo.png');
        return view('pdf.purchase_voucher', compact('project', 'rab', 'voucher', 'logoSrc'));
    }

    public function pdf($projectId, $rabId, $voucherId)
    {
        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        $voucher = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->with(['items.rabItem.data', 'purchaseOrder', 'lpb'])
            ->findOrFail($voucherId);

        $data = compact('project', 'rab', 'voucher');
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $filename = 'Voucher-' . ($voucher->voucher_no ?? $voucher->id) . '.pdf';
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.purchase_voucher', $data)
                ->setPaper('a4', 'portrait')
                ->stream($filename);
        }

        return view('pdf.purchase_voucher', $data);
    }

    /**
     * Store voucher (dipanggil dari modal via AJAX)
     * + Validasi Kritis: tidak boleh melebihi sisa volume & sisa Rp (berbasis harga RAPP)
     * + Auto nomor voucher & invoice pakai PROJECTCODE kalau input kosong
     */
    public function store(Request $request, $projectId, $rabId)
    {
        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        $voucherNo   = trim((string) $request->input('voucher_no'));
        $voucherDate = $request->input('voucher_date');
        $vendorName  = trim((string) $request->input('vendor_name', ''));
        $invoiceNo   = trim((string) $request->input('invoice_no', ''));
        $paymentMethod = trim((string) $request->input('payment_method', ''));
        $bank        = trim((string) $request->input('bank', ''));
        $accountNo   = trim((string) $request->input('account_no', ''));
        $accountName = trim((string) $request->input('account_name', ''));
        $statusInput = trim((string) $request->input('status', ''));
        $dueDate     = $request->input('due_date');
        $purpose     = trim((string) $request->input('payment_purpose', ''));
        $notes       = trim((string) $request->input('notes', ''));
        $taxPercent  = $this->parseIdNumber($request->input('tax_percent', 0));
        $poId        = $request->filled('purchase_order_id') ? (int) $request->input('purchase_order_id') : null;
        $lpbId       = $request->filled('lpb_id') ? (int) $request->input('lpb_id') : null;

        $itemsInput = $request->input('items', []);

        $allowedStatus = ['draft', 'submitted', 'approved', 'rejected'];
        $status = in_array($statusInput, $allowedStatus, true) ? $statusInput : 'draft';
        // Enforce workflow: status can only change via submit/approve/reject.
        $status = 'draft';

        /**
         * AUTO NUMBERING
         * Format:
         *  PV/0001/PROJECTCODE/2026
         *  INV/0001/PROJECTCODE/2026
         * Basis: per project + RAPP + tahun (voucher_date)
         */
        $year = date('Y');

        // Normalisasi PROJECTCODE: ambil dari project->code, kalau kosong pakai PRJ
        $rawCode = $project->code ?? 'PRJ';
        $projectCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $rawCode));
        if ($projectCode === '') {
            $projectCode = 'PRJ';
        }

        // Hitung nomor urut per project + RAPP + tahun
        $baseQuery = PurchaseVoucher::where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->whereYear('voucher_date', $year);

        $nextNumber = $baseQuery->count() + 1;
        $running    = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        if ($voucherNo === '') {
            $voucherNo = 'PV/' . $running . '/' . $projectCode . '/' . $year;
        }

        if ($invoiceNo === '') {
            $invoiceNo = 'INV/' . $running . '/' . $projectCode . '/' . $year;
        }
        // END AUTO NUMBERING

        if (!$voucherDate) {
            return response()->json([
                'status'  => false,
                'message' => 'Tanggal voucher wajib diisi.',
            ], 422);
        }

        try {
            $source = $this->resolveVoucherSource($project->id, $rab->id, $poId, $lpbId);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->errors() ? collect($e->errors())->flatten()->first() : 'Referensi voucher tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        }

        $vendor = $source['vendor'];
        if ($vendorName !== '' && strcasecmp($vendorName, (string) $vendor->nama) !== 0) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor voucher harus sama dengan vendor pada PO/LPB.',
            ], 422);
        }
        $vendorName = $vendor->nama ?? $vendorName;

        if (!is_array($itemsInput) || count($itemsInput) === 0) {
            return response()->json([
                'status'  => false,
                'message' => 'Tidak ada item yang dikirim.',
            ], 422);
        }

        // Ambil semua RabItem yang dipakai
        $rabItemIds = collect($itemsInput)->pluck('rab_item_id')->map(fn($v) => (int)$v)->unique()->values();
        $rabItems   = RabItem::with('data')
            ->where('rab_id', $rab->id)
            ->whereIn('id', $rabItemIds)
            ->get()
            ->keyBy('id');

        if ($rabItems->count() !== $rabItemIds->count()) {
            return response()->json([
                'status'  => false,
                'message' => 'Ada item RAPP yang tidak valid.',
            ], 422);
        }

        $lines       = [];
        $subtotal    = 0.0;
        $requestedTotals = collect($itemsInput)
            ->filter(fn ($row) => !empty($row['rab_item_id']))
            ->groupBy(fn ($row) => (int) $row['rab_item_id'])
            ->map(fn ($rows) => $rows->sum(fn ($row) => $this->parseIdNumber($row['qty'] ?? 0)));
        $checkedRabItems = [];

        foreach ($itemsInput as $row) {
            $rabItemId = (int) ($row['rab_item_id'] ?? 0);
            $qty       = $this->parseIdNumber($row['qty'] ?? 0);
            $price     = $this->parseIdNumber($row['price'] ?? 0);

            if ($rabItemId <= 0 || $qty <= 0 || $price < 0) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Qty dan harga harus lebih dari nol.',
                ], 422);
            }

            /** @var RabItem $rabItem */
            $rabItem = $rabItems->get($rabItemId);

            $budgetVol = (float) ($rabItem->volume ?? 0);
            $hsRapp    = (float) ($rabItem->harga_satuan ?? 0);

            if (!isset($checkedRabItems[$rabItemId])) {
                $checkedRabItems[$rabItemId] = true;
                $requestedQty = (float) ($requestedTotals[$rabItemId] ?? 0);

                // total qty semua voucher yang sudah ada
                $existingQty = (float) PurchaseVoucherItem::where('rab_item_id', $rabItemId)
                    ->whereHas('voucher', function ($q) {
                        $q->whereNotIn('status', ['rejected', 'cancelled']);
                    })
                    ->sum('qty');

                // ===== VALIDASI SISA VOLUME =====
                $sisaVol = $budgetVol - $existingQty;
                if ($sisaVol < 0) {
                    $sisaVol = 0;
                }

                if ($requestedQty > $sisaVol + 0.00001) {
                    return response()->json([
                        'status'  => false,
                        'message' => "Qty voucher melebihi sisa volume untuk item ID {$rabItemId}. Sisa: " . number_format($sisaVol, 2, ',', '.'),
                    ], 422);
                }

                // ===== VALIDASI SISA RP (berbasis harga RAPP) =====
                $budgetAmt          = $budgetVol * $hsRapp;
                $existingRealAmt    = $existingQty * $hsRapp;
                $sisaAmtRappBasis   = max(0, $budgetAmt - $existingRealAmt);
                $amountRappBasisNow = $requestedQty * $hsRapp;

                if ($amountRappBasisNow > $sisaAmtRappBasis + 1) {
                    return response()->json([
                        'status'  => false,
                        'message' => "Nilai voucher (berdasar harga RAPP) melebihi sisa Rp untuk item ID {$rabItemId}. Sisa Rp: " . number_format($sisaAmtRappBasis, 0, ',', '.'),
                    ], 422);
                }
            }

            // amount invoice (boleh beda dengan RAPP, tapi tidak mempengaruhi realisasi_amount)
            $amount = $qty * $price;
            $subtotal += $amount;

            $lines[] = [
                'rab_item_id'               => $rabItemId,
                'qty'                       => $qty,
                'price'                     => $price,
                'amount'                    => $amount,
                'rab_harga_satuan_snapshot' => $hsRapp,
                'rab_volume_snapshot'       => $budgetVol,
            ];
        }

        try {
            $this->validateVoucherItemsAgainstSource($source, $lines, null);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->errors() ? collect($e->errors())->flatten()->first() : 'Item voucher tidak sesuai sumber.',
                'errors' => $e->errors(),
            ], 422);
        }

        $taxAmount = $subtotal * ($taxPercent / 100);
        $total     = $subtotal + $taxAmount;

        try {
            $result = DB::transaction(function () use (
                $project,
                $rab,
                $voucherNo,
                $voucherDate,
                $vendorName,
                $invoiceNo,
                $paymentMethod,
                $bank,
                $accountNo,
                $accountName,
                $status,
                $dueDate,
                $purpose,
                $notes,
                $taxPercent,
                $subtotal,
                $taxAmount,
                $total,
                $lines,
                $source,
                $vendor
            ) {
                $voucher = PurchaseVoucher::create([
                    'project_id'           => $project->id,
                    'project_code_snapshot'=> $project->code ?? null,
                    'project_name_snapshot'=> $project->name ?? null,
                    'rab_id'               => $rab->id,
                    'purchase_order_id'    => $source['type'] === 'po' ? $source['po']->id : null,
                    'lpb_id'               => $source['type'] === 'lpb' ? $source['lpb']->id : null,
                    'vendor_id'            => $vendor->id,
                    'voucher_no'           => $voucherNo,
                    'voucher_date'         => $voucherDate,
                    'vendor_name'          => $vendorName ?: null,
                    'invoice_no'           => $invoiceNo ?: null,
                    'payment_method'       => $paymentMethod ?: null,
                    'bank'                 => $bank ?: null,
                    'account_no'           => $accountNo ?: null,
                    'account_name'         => $accountName ?: null,
                    'status'               => $status,
                    'due_date'             => $dueDate ?: null,
                    'payment_purpose'      => $purpose ?: null,
                    'notes'                => $notes ?: null,
                    'tax_percent'          => $taxPercent,
                    'tax_amount'           => $taxAmount,
                    'subtotal_amount'      => $subtotal,
                    'total_amount'         => $total,
                    'created_by'           => auth()->id(),
                ]);

                $rabItemIds = [];

                foreach ($lines as $line) {
                    $line['purchase_voucher_id'] = $voucher->id;
                    PurchaseVoucherItem::create($line);
                    $rabItemIds[] = $line['rab_item_id'];
                }

                $this->recalcRabItems($rabItemIds);

                $voucher->loadCount('items');

                AuditLogger::log('purchase_voucher.created', $voucher, null, $voucher->toArray(), [
                    'project_id' => $project->id,
                    'rab_id' => $rab->id,
                ]);

                return [
                    'voucher' => $voucher,
                ];
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Terjadi kesalahan saat menyimpan voucher: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Voucher berhasil dibuat.',
            'data'    => [
                'voucher_id'      => $result['voucher']->id,
                'voucher_no'      => $result['voucher']->voucher_no,
                'voucher_date'    => optional($result['voucher']->voucher_date)->format('Y-m-d'),
                'subtotal_amount' => (float) $result['voucher']->subtotal_amount,
                'tax_amount'      => (float) $result['voucher']->tax_amount,
                'total_amount'    => (float) $result['voucher']->total_amount,
                'item_count'      => (int) $result['voucher']->items_count,
            ],
        ]);
    }

    /**
     * Form Edit voucher (ADMIN ONLY)
     */
    public function edit($projectId, $rabId, $voucherId)
    {
        $this->ensureHO();

        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        $voucher = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->with(['items.rabItem.data', 'purchaseOrder', 'lpb'])
            ->findOrFail($voucherId);

        $approvedPos = PurchaseOrder::with('vendor')
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->get();
        $approvedLpbs = Lpb::with('vendor', 'purchaseOrder')
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->get();
        if ($voucher->purchase_order_id && !$approvedPos->pluck('id')->contains($voucher->purchase_order_id)) {
            $currentPo = PurchaseOrder::with('vendor')
                ->where('project_id', $project->id)
                ->where('rab_id', $rab->id)
                ->where('id', $voucher->purchase_order_id)
                ->first();
            if ($currentPo) {
                $approvedPos = $approvedPos->prepend($currentPo);
            }
        }
        if ($voucher->lpb_id && !$approvedLpbs->pluck('id')->contains($voucher->lpb_id)) {
            $currentLpb = Lpb::with('vendor', 'purchaseOrder')
                ->where('project_id', $project->id)
                ->where('rab_id', $rab->id)
                ->where('id', $voucher->lpb_id)
                ->first();
            if ($currentLpb) {
                $approvedLpbs = $approvedLpbs->prepend($currentLpb);
            }
        }

        return view('dev.purchase_vouchers.edit', [
            'project' => $project,
            'rab'     => $rab,
            'voucher' => $voucher,
            'approvedPos' => $approvedPos,
            'approvedLpbs' => $approvedLpbs,
        ]);
    }

    /**
     * Update voucher (ADMIN ONLY)
     * + Validasi Kritis: tidak boleh melebihi sisa volume & sisa Rp (berbasis harga RAPP)
     */
    public function update(Request $request, $projectId, $rabId, $voucherId)
    {
        $this->ensureHO();

        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        /** @var PurchaseVoucher $voucher */
        $voucher = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->with('items')
            ->findOrFail($voucherId);
        $before = $voucher->toArray();
        $this->ensureEditableStatus($voucher, 'Voucher Pembelian');
        $before = $voucher->toArray();

        // Validasi basic
        $validated = $request->validate([
            'voucher_no'      => ['required', 'string', 'max:100'],
            'voucher_date'    => ['required', 'date'],
            'vendor_name'     => ['nullable', 'string', 'max:255'],
            'invoice_no'      => ['nullable', 'string', 'max:100'],
            'bank'            => ['nullable', 'string', 'max:100'],
            'account_no'      => ['nullable', 'string', 'max:100'],
            'account_name'    => ['nullable', 'string', 'max:150'],
            'payment_purpose' => ['nullable', 'string', 'max:255'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'tax_percent'     => ['nullable'],
            'payment_method'  => ['nullable', 'string', 'max:100'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'lpb_id'            => ['nullable', 'exists:lpbs,id'],
        ]);

        $itemsInput = $request->input('items', []);
        if (!is_array($itemsInput) || !count($itemsInput)) {
            return back()
                ->withErrors(['items' => 'Minimal harus ada satu item di voucher.'])
                ->withInput();
        }

        $poId = $validated['purchase_order_id'] ?? $voucher->purchase_order_id;
        $lpbId = $validated['lpb_id'] ?? $voucher->lpb_id;
        try {
            $source = $this->resolveVoucherSource($project->id, $rab->id, $poId, $lpbId);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }

        $vendor = $source['vendor'];
        if (!empty($validated['vendor_name']) && strcasecmp($validated['vendor_name'], (string) $vendor->nama) !== 0) {
            return back()
                ->withErrors(['vendor_name' => 'Vendor voucher harus sama dengan vendor pada PO/LPB.'])
                ->withInput();
        }
        $validated['vendor_name'] = $vendor->nama ?? ($validated['vendor_name'] ?? null);

        // Map qty lama voucher ini (per rab_item_id)
        $oldQtyPerRabItem = $voucher->items
            ->groupBy('rab_item_id')
            ->map(function ($rows) {
                return (float) $rows->sum('qty');
            });

        $rabItemIds = collect($itemsInput)->pluck('rab_item_id')->map(fn($v) => (int)$v)->unique()->values();
        $rabItems   = RabItem::with('data')
            ->where('rab_id', $rab->id)
            ->whereIn('id', $rabItemIds)
            ->get()
            ->keyBy('id');

        if ($rabItems->count() !== $rabItemIds->count()) {
            return back()
                ->withErrors(['items' => 'Ada item RAPP yang tidak valid.'])
                ->withInput();
        }

        $taxPercent = $this->parseIdNumber($validated['tax_percent'] ?? 0);

        $lines    = [];
        $subtotal = 0.0;
        $allRabItemIdsForRecalc = [];
        $requestedTotals = collect($itemsInput)
            ->filter(fn ($row) => !empty($row['rab_item_id']))
            ->groupBy(fn ($row) => (int) $row['rab_item_id'])
            ->map(fn ($rows) => $rows->sum(fn ($row) => $this->parseIdNumber($row['qty'] ?? 0)));
        $checkedRabItems = [];

        foreach ($itemsInput as $key => $row) {
            $rabItemId = (int) ($row['rab_item_id'] ?? 0);
            $qty       = $this->parseIdNumber($row['qty'] ?? 0);
            $price     = $this->parseIdNumber($row['price'] ?? 0);

            if ($rabItemId <= 0 || $qty <= 0 || $price < 0) {
                return back()
                    ->withErrors(['items' => 'Qty dan harga harus lebih dari nol.'])
                    ->withInput();
            }

            /** @var RabItem $rabItem */
            $rabItem = $rabItems->get($rabItemId);

            $budgetVol = (float) ($rabItem->volume ?? 0);
            $hsRapp    = (float) ($rabItem->harga_satuan ?? 0);

            if (!isset($checkedRabItems[$rabItemId])) {
                $checkedRabItems[$rabItemId] = true;
                $requestedQty = (float) ($requestedTotals[$rabItemId] ?? 0);

                // total qty semua voucher untuk rab_item ini
                $totalAllVoucherQty = (float) PurchaseVoucherItem::where('rab_item_id', $rabItemId)
                    ->whereHas('voucher', function ($q) {
                        $q->whereNotIn('status', ['rejected', 'cancelled']);
                    })
                    ->sum('qty');
                $oldQtyThisVoucher  = (float) ($oldQtyPerRabItem->get($rabItemId) ?? 0);

                // qty dari voucher lain (selain voucher yang sedang di-edit)
                $existingQtyOtherVoucher = $totalAllVoucherQty - $oldQtyThisVoucher;
                if ($existingQtyOtherVoucher < 0) {
                    $existingQtyOtherVoucher = 0;
                }

                // ===== VALIDASI SISA VOLUME =====
                $sisaVol = $budgetVol - $existingQtyOtherVoucher;
                if ($sisaVol < 0) {
                    $sisaVol = 0;
                }

                if ($requestedQty > $sisaVol + 0.00001) {
                    return back()
                        ->withErrors([
                            'items' => "Qty voucher melebihi sisa volume untuk item ID {$rabItemId}. Sisa yang boleh dipakai: " . number_format($sisaVol, 2, ',', '.'),
                        ])
                        ->withInput();
                }

                // ===== VALIDASI SISA RP (berbasis harga RAPP) =====
                $budgetAmt                = $budgetVol * $hsRapp;
                $existingRealAmtOther     = $existingQtyOtherVoucher * $hsRapp;
                $sisaAmtRappBasis         = max(0, $budgetAmt - $existingRealAmtOther);
                $amountRappBasisRequested = $requestedQty * $hsRapp;

                if ($amountRappBasisRequested > $sisaAmtRappBasis + 1) {
                    return back()
                        ->withErrors([
                            'items' => "Nilai voucher (berdasar harga RAPP) melebihi sisa Rp untuk item ID {$rabItemId}. Sisa Rp yang boleh dipakai: " . number_format($sisaAmtRappBasis, 0, ',', '.'),
                        ])
                        ->withInput();
                }
            }

            $amount   = $qty * $price;
            $subtotal += $amount;

            $lines[] = [
                'rab_item_id'               => $rabItemId,
                'qty'                       => $qty,
                'price'                     => $price,
                'amount'                    => $amount,
                'rab_harga_satuan_snapshot' => $hsRapp,
                'rab_volume_snapshot'       => $budgetVol,
            ];

            $allRabItemIdsForRecalc[] = $rabItemId;
        }

        try {
            $this->validateVoucherItemsAgainstSource($source, $lines, $voucher->id);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }

        $taxAmount = $subtotal * ($taxPercent / 100);
        $total     = $subtotal + $taxAmount;

        try {
            DB::transaction(function () use (
                $voucher,
                $validated,
                $source,
                $vendor,
                $taxPercent,
                $subtotal,
                $taxAmount,
                $total,
                $lines,
                $allRabItemIdsForRecalc,
                $oldQtyPerRabItem
            ) {
                // Update header voucher
                $voucher->update([
                    'voucher_no'      => $validated['voucher_no'],
                    'voucher_date'    => $validated['voucher_date'],
                    'vendor_name'     => $validated['vendor_name'] ?? null,
                    'vendor_id'       => $vendor->id,
                    'purchase_order_id' => $source['type'] === 'po' ? $source['po']->id : null,
                    'lpb_id'            => $source['type'] === 'lpb' ? $source['lpb']->id : null,
                    'invoice_no'      => $validated['invoice_no'] ?? null,
                    'bank'            => $validated['bank'] ?? null,
                    'account_no'      => $validated['account_no'] ?? null,
                    'account_name'    => $validated['account_name'] ?? null,
                    'payment_purpose' => $validated['payment_purpose'] ?? null,
                    'notes'           => $validated['notes'] ?? null,
                    'tax_percent'     => $taxPercent,
                    'tax_amount'      => $taxAmount,
                    'subtotal_amount' => $subtotal,
                    'total_amount'    => $total,
                    'payment_method'  => $validated['payment_method'] ?? null,
                ]);

                // kumpulkan semua rab_item_id lama + baru untuk direcalc
                $oldRabItemIds = $oldQtyPerRabItem->keys()->all();
                $recalcIds     = array_unique(array_merge($oldRabItemIds, $allRabItemIdsForRecalc));

                // hapus item lama voucher ini
                PurchaseVoucherItem::where('purchase_voucher_id', $voucher->id)->delete();

                // insert item baru
                foreach ($lines as $line) {
                    $line['purchase_voucher_id'] = $voucher->id;
                    PurchaseVoucherItem::create($line);
                }

                // Recalculate RabItem
                $this->recalcRabItems($recalcIds);
            });
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['system' => 'Terjadi kesalahan saat update voucher: ' . $e->getMessage()])
                ->withInput();
        }

        $voucher->refresh();
        AuditLogger::log('purchase_voucher.updated', $voucher, $before, $voucher->toArray(), [
            'project_id' => $project->id,
            'rab_id' => $rab->id,
        ]);

        return redirect()
            ->route('dev.rabs.purchase-vouchers.show', [
                'projectId' => $project->id,
                'rabId'     => $rab->id,
                'voucherId' => $voucher->id,
            ])
            ->with('status', 'Voucher berhasil diperbarui.');
    }

    /**
     * Hapus voucher (ADMIN ONLY)
     * - Hapus semua item
     * - Recalc realisasi & sisa RabItem
     */
    public function destroy($projectId, $rabId, $voucherId)
    {
        $this->ensureHO();

        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        /** @var PurchaseVoucher $voucher */
        $voucher = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->with('items')
            ->findOrFail($voucherId);
        $this->ensureEditableStatus($voucher, 'Voucher Pembelian');

        $rabItemIds = $voucher->items->pluck('rab_item_id')->unique()->values()->all();

        try {
            DB::transaction(function () use ($voucher, $rabItemIds) {
                PurchaseVoucherItem::where('purchase_voucher_id', $voucher->id)->delete();
                $voucher->delete();

                $this->recalcRabItems($rabItemIds);
            });
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['system' => 'Gagal menghapus voucher: ' . $e->getMessage()]);
        }

        AuditLogger::log('purchase_voucher.deleted', $voucher, $before, null, [
            'project_id' => $project->id,
            'rab_id' => $rab->id,
        ]);

        return redirect()
            ->route('dev.rab-baseline.purchase-vouchers.index', [
                'projectId' => $projectId,
                'rabId'     => $rabId,
            ])
            ->with('status', 'Voucher berhasil dihapus.');
    }

    public function submit($projectId, $rabId, $voucherId)
    {
        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        $voucher = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->findOrFail($voucherId);

        try {
            $this->resolveVoucherSource(
                $project->id,
                $rab->id,
                $voucher->purchase_order_id,
                $voucher->lpb_id
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        if (($rab->status ?? '') !== 'approved') {
            return back()->with('status', 'RAPP harus approved sebelum submit voucher.');
        }
        if (!in_array($voucher->status ?? 'draft', ['draft', 'rejected'], true)) {
            return back()->with('status', 'Voucher hanya bisa disubmit dari status draft/rejected.');
        }

        $before = $voucher->toArray();
        $voucher->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => auth()->user()->name ?? null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        $voucher->refresh();
        AuditLogger::log('purchase_voucher.submitted', $voucher, $before, $voucher->toArray(), [
            'project_id' => $project->id,
            'rab_id' => $rab->id,
        ]);

        NotificationService::notifyHO(
            'Voucher Pembelian Diajukan',
            'Voucher ' . ($voucher->voucher_no ?? $voucher->id) . ' diajukan' . ($project->name ? ' untuk proyek ' . $project->name : '') . '.',
            route('dev.rabs.purchase-vouchers.show', [$project->id, $rab->id, $voucher->id]),
            'approval',
            [
                'doc_type' => 'purchase_voucher',
                'doc_id' => $voucher->id,
                'project_id' => $project->id,
            ]
        );

        return back()->with('status', 'Voucher berhasil disubmit.');
    }

    public function approve($projectId, $rabId, $voucherId)
    {
        $this->ensureHO();

        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        $voucher = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->findOrFail($voucherId);

        if (($voucher->status ?? '') !== 'submitted') {
            return back()->with('status', 'Voucher hanya bisa di-approve dari status submitted.');
        }

        $before = $voucher->toArray();
        $voucher->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->user()->name ?? null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejected_reason' => null,
        ]);
        $voucher->refresh();
        AuditLogger::log('purchase_voucher.approved', $voucher, $before, $voucher->toArray(), [
            'project_id' => $project->id,
            'rab_id' => $rab->id,
        ]);
        NotificationService::notifyHO(
            'Voucher Disetujui',
            'Voucher ' . ($voucher->voucher_no ?? $voucher->id) . ' disetujui untuk proyek ' . ($project->name ?? '-') . '.',
            route('dev.rabs.purchase-vouchers.show', [$project->id, $rab->id, $voucher->id]),
            'approval',
            [
                'doc_type' => 'purchase_voucher',
                'doc_id' => $voucher->id,
                'project_id' => $project->id,
            ]
        );
        NotificationService::notifySubmitter(
            $voucher,
            'Voucher Disetujui',
            'Voucher ' . ($voucher->voucher_no ?? $voucher->id) . ' disetujui.',
            route('dev.rabs.purchase-vouchers.show', [$project->id, $rab->id, $voucher->id]),
            'approval',
            [
                'doc_type' => 'purchase_voucher',
                'doc_id' => $voucher->id,
                'project_id' => $project->id,
            ]
        );

        return back()->with('status', 'Voucher berhasil di-approve.');
    }

    public function reject(Request $request, $projectId, $rabId, $voucherId)
    {
        $this->ensureHO();

        $project = Project::findOrFail($projectId);
        $rab     = Rab::where('project_id', $project->id)->findOrFail($rabId);

        $voucher = PurchaseVoucher::query()
            ->where('project_id', $project->id)
            ->where('rab_id', $rab->id)
            ->findOrFail($voucherId);

        if (($voucher->status ?? '') !== 'submitted') {
            return back()->with('status', 'Voucher hanya bisa di-reject dari status submitted.');
        }

        $before = $voucher->toArray();
        $reason = trim((string) $request->input('rejected_reason', ''));
        $voucher->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => auth()->user()->name ?? null,
            'rejected_reason' => $reason !== '' ? $reason : null,
        ]);
        $voucher->refresh();
        AuditLogger::log('purchase_voucher.rejected', $voucher, $before, $voucher->toArray(), [
            'project_id' => $project->id,
            'rab_id' => $rab->id,
        ]);
        NotificationService::notifyHO(
            'Voucher Ditolak',
            'Voucher ' . ($voucher->voucher_no ?? $voucher->id) . ' ditolak untuk proyek ' . ($project->name ?? '-') . '.',
            route('dev.rabs.purchase-vouchers.show', [$project->id, $rab->id, $voucher->id]),
            'approval',
            [
                'doc_type' => 'purchase_voucher',
                'doc_id' => $voucher->id,
                'project_id' => $project->id,
            ]
        );
        NotificationService::notifySubmitter(
            $voucher,
            'Voucher Ditolak',
            'Voucher ' . ($voucher->voucher_no ?? $voucher->id) . ' ditolak.' . ($voucher->rejected_reason ? ' Alasan: ' . $voucher->rejected_reason : ''),
            route('dev.rabs.purchase-vouchers.show', [$project->id, $rab->id, $voucher->id]),
            'approval',
            [
                'doc_type' => 'purchase_voucher',
                'doc_id' => $voucher->id,
                'project_id' => $project->id,
                'rejected_reason' => $voucher->rejected_reason,
            ]
        );

        return back()->with('status', 'Voucher berhasil di-reject.');
    }
}



