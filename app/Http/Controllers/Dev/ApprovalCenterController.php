<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Spp;
use App\Models\Bpg;
use App\Models\Lpb;
use App\Models\PurchaseOrder;
use App\Models\Spk;
use App\Models\VendorComparison;
use App\Models\PurchaseVoucher;
use App\Models\Rab;
use App\Models\RabBreakdown;
use App\Models\Voucher;
use App\Models\Project;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class ApprovalCenterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isHO = $user && method_exists($user, 'isHO') && $user->isHO();
        if (!$isHO) {
            abort(403);
        }

        $items = $this->collectItems();
        $projects = Project::orderBy('name')->get(['id', 'name']);
        $types = [
            'RAPP',
            'RAB Breakdown',
            'SPP',
            'BPG',
            'LPB',
            'PO',
            'SPK',
            'Komparasi',
            'Voucher Pembelian',
            'Voucher',
        ];

        $filters = [
            'type' => trim((string) $request->get('type', '')),
            'project_id' => trim((string) $request->get('project_id', '')),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'sort_by' => trim((string) $request->get('sort_by', 'date')),
            'sort_dir' => trim((string) $request->get('sort_dir', 'desc')),
        ];

        $items = $this->applyFilters($items, $filters);

        $sortBy = in_array($filters['sort_by'], ['date', 'type', 'project', 'amount'], true) ? $filters['sort_by'] : 'date';
        $sortDir = strtolower($filters['sort_dir']) === 'asc' ? 'asc' : 'desc';

        $items = $items->sortBy(function ($row) use ($sortBy) {
            return match ($sortBy) {
                'type' => $row['type'] ?? '',
                'project' => $row['project'] ?? '',
                'amount' => (float) ($row['amount'] ?? 0),
                default => $row['date'] ? Carbon::parse($row['date'])->timestamp : 0,
            };
        }, SORT_REGULAR, $sortDir === 'desc')->values();

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginated = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => url()->current(),
                'query' => $request->query(),
            ]
        );

        return view('dev.approvals.index', [
            'items' => $paginated,
            'projects' => $projects,
            'types' => $types,
            'filters' => $filters,
        ]);
    }

    public function urls(Request $request)
    {
        $user = $request->user();
        $isHO = $user && method_exists($user, 'isHO') && $user->isHO();
        if (!$isHO) {
            abort(403);
        }

        $filters = [
            'type' => trim((string) $request->get('type', '')),
            'project_id' => trim((string) $request->get('project_id', '')),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $items = $this->applyFilters($this->collectItems(), $filters)->values();

        return response()->json([
            'count' => $items->count(),
            'items' => $items->map(function ($row) {
                return [
                    'approve_url' => $row['approve_url'],
                    'reject_url' => $row['reject_url'],
                ];
            }),
        ]);
    }

    private function collectItems()
    {
        $items = collect();

        $items = $items->concat(
            Rab::with(['project'])
                ->where('status', 'submitted')->get()
                ->map(function ($row) {
                    return [
                        'type' => 'RAPP',
                        'number' => $row->name ?? ('RAPP #' . $row->id),
                        'date' => $row->submitted_at ?? $row->created_at,
                        'project_id' => $row->project_id,
                        'project' => $row->project?->name,
                        'rab' => $row->name,
                        'vendor' => null,
                        'amount' => $row->total_budget,
                        'show_url' => route('dev.rab-baseline.show', [$row->project_id, $row->id]),
                        'approve_url' => route('dev.rab-baseline.approve', [$row->project_id, $row->id]),
                        'reject_url' => route('dev.rab-baseline.reject', [$row->project_id, $row->id]),
                    ];
                })
        );

        $items = $items->concat(
            RabBreakdown::with(['project'])
                ->where('approval_status', 'submitted')->get()
                ->map(function ($row) {
                    return [
                        'type' => 'RAB Breakdown',
                        'number' => ($row->rab_breakdown_code ? ($row->rab_breakdown_code . ' - ') : '') . ($row->name ?? ('RAB Breakdown #' . $row->id)),
                        'date' => $row->submitted_at ?? $row->created_at,
                        'project_id' => $row->project_id,
                        'project' => $row->project?->name,
                        'rab' => null,
                        'vendor' => null,
                        'amount' => $row->budget_amount,
                        'show_url' => route('dev.rab-breakdown.show', [$row->project_id, $row->id]),
                        'approve_url' => route('dev.rab-breakdown.approve', [$row->project_id, $row->id]),
                        'reject_url' => route('dev.rab-breakdown.reject', [$row->project_id, $row->id]),
                    ];
                })
        );

        $items = $items->concat(
            Spp::with(['project', 'rab', 'vendor'])
                ->where('status', 'submitted')->get()
                ->map(function ($row) {
                    return [
                        'type' => 'SPP',
                        'number' => $row->spp_no,
                        'date' => $row->spp_date,
                        'project_id' => $row->project_id,
                        'project' => $row->project?->name,
                        'rab' => $row->rab?->name,
                        'vendor' => $row->vendor?->nama,
                        'amount' => null,
                        'show_url' => route('dev.rabs.spps.show', [$row->project_id, $row->rab_id, $row->id]),
                        'approve_url' => route('dev.rabs.spps.approve', [$row->project_id, $row->rab_id, $row->id]),
                        'reject_url' => route('dev.rabs.spps.reject', [$row->project_id, $row->rab_id, $row->id]),
                    ];
                })
        );

        $items = $items->concat(
            Bpg::with(['project', 'rab'])
                ->where('status', 'submitted')->get()
                ->map(function ($row) {
                    return [
                        'type' => 'BPG',
                        'number' => $row->bpg_no,
                        'date' => $row->bpg_date,
                        'project_id' => $row->project_id,
                        'project' => $row->project?->name,
                        'rab' => $row->rab?->name,
                        'vendor' => null,
                        'amount' => null,
                        'show_url' => route('dev.rabs.bpgs.show', [$row->project_id, $row->rab_id, $row->id]),
                        'approve_url' => route('dev.rabs.bpgs.approve', [$row->project_id, $row->rab_id, $row->id]),
                        'reject_url' => route('dev.rabs.bpgs.reject', [$row->project_id, $row->rab_id, $row->id]),
                    ];
                })
        );

        $items = $items->concat(
            Lpb::with(['project', 'rab', 'vendor'])
                ->where('status', 'submitted')->get()
                ->map(function ($row) {
                    return [
                        'type' => 'LPB',
                        'number' => $row->lpb_no,
                        'date' => $row->lpb_date,
                        'project_id' => $row->project_id,
                        'project' => $row->project?->name,
                        'rab' => $row->rab?->name,
                        'vendor' => $row->vendor?->nama,
                        'amount' => null,
                        'show_url' => route('dev.rabs.lpbs.show', [$row->project_id, $row->rab_id, $row->id]),
                        'approve_url' => route('dev.rabs.lpbs.approve', [$row->project_id, $row->rab_id, $row->id]),
                        'reject_url' => route('dev.rabs.lpbs.reject', [$row->project_id, $row->rab_id, $row->id]),
                    ];
                })
        );

        $items = $items->concat(
            PurchaseOrder::with(['project', 'rab', 'vendor'])
                ->where('status', 'submitted')->get()
                ->map(function ($row) {
                    return [
                        'type' => 'PO',
                        'number' => $row->po_no,
                        'date' => $row->po_date,
                        'project_id' => $row->project_id,
                        'project' => $row->project?->name,
                        'rab' => $row->rab?->name,
                        'vendor' => $row->vendor?->nama,
                        'amount' => $row->total_amount,
                        'show_url' => route('dev.rabs.purchase-orders.show', [$row->project_id, $row->rab_id, $row->id]),
                        'approve_url' => route('dev.rabs.purchase-orders.approve', [$row->project_id, $row->rab_id, $row->id]),
                        'reject_url' => route('dev.rabs.purchase-orders.reject', [$row->project_id, $row->rab_id, $row->id]),
                    ];
                })
        );

        $items = $items->concat(
            Spk::with(['project', 'rab', 'vendor'])
                ->where('status', 'submitted')->get()
                ->map(function ($row) {
                    return [
                        'type' => 'SPK',
                        'number' => $row->spk_no,
                        'date' => $row->spk_date,
                        'project_id' => $row->project_id,
                        'project' => $row->project?->name,
                        'rab' => $row->rab?->name,
                        'vendor' => $row->vendor?->nama,
                        'amount' => $row->total_amount,
                        'show_url' => route('dev.rabs.spks.show', [$row->project_id, $row->rab_id, $row->id]),
                        'approve_url' => route('dev.rabs.spks.approve', [$row->project_id, $row->rab_id, $row->id]),
                        'reject_url' => route('dev.rabs.spks.reject', [$row->project_id, $row->rab_id, $row->id]),
                    ];
                })
        );

        $items = $items->concat(
            VendorComparison::with(['project', 'rab'])
                ->where('status', 'submitted')->get()
                ->map(function ($row) {
                    return [
                        'type' => 'Komparasi',
                        'number' => $row->comparison_no,
                        'date' => $row->comparison_date,
                        'project_id' => $row->project_id,
                        'project' => $row->project?->name,
                        'rab' => $row->rab?->name,
                        'vendor' => null,
                        'amount' => $row->final_amount,
                        'show_url' => route('dev.rabs.vendor-comparisons.show', [$row->project_id, $row->rab_id, $row->id]),
                        'approve_url' => route('dev.rabs.vendor-comparisons.approve', [$row->project_id, $row->rab_id, $row->id]),
                        'reject_url' => route('dev.rabs.vendor-comparisons.reject', [$row->project_id, $row->rab_id, $row->id]),
                    ];
                })
        );

        $items = $items->concat(
            PurchaseVoucher::with(['project', 'rab', 'vendor'])
                ->where('status', 'submitted')->get()
                ->map(function ($row) {
                    return [
                        'type' => 'Voucher Pembelian',
                        'number' => $row->voucher_no,
                        'date' => $row->voucher_date,
                        'project_id' => $row->project_id,
                        'project' => $row->project?->name,
                        'rab' => $row->rab?->name,
                        'vendor' => $row->vendor?->nama ?? $row->vendor_name,
                        'amount' => $row->total_amount,
                        'show_url' => route('dev.rabs.purchase-vouchers.show', ['projectId' => $row->project_id, 'rabId' => $row->rab_id, 'voucherId' => $row->id]),
                        'approve_url' => route('dev.rabs.purchase-vouchers.approve', ['projectId' => $row->project_id, 'rabId' => $row->rab_id, 'voucherId' => $row->id]),
                        'reject_url' => route('dev.rabs.purchase-vouchers.reject', ['projectId' => $row->project_id, 'rabId' => $row->rab_id, 'voucherId' => $row->id]),
                    ];
                })
        );

        $items = $items->concat(
            Voucher::with(['project', 'vendor'])
                ->where('status', 'submitted')->get()
                ->map(function ($row) {
                    return [
                        'type' => 'Voucher',
                        'number' => $row->voucher_number,
                        'date' => $row->tanggal,
                        'project_id' => $row->project_id,
                        'project' => $row->project?->name,
                        'rab' => null,
                        'vendor' => $row->vendor?->nama,
                        'amount' => $row->total_bayar,
                        'show_url' => route('dev.vouchers.show', ['projectId' => $row->project_id, 'id' => $row->id]),
                        'approve_url' => route('dev.vouchers.approve', ['projectId' => $row->project_id, 'id' => $row->id]),
                        'reject_url' => route('dev.vouchers.reject', ['projectId' => $row->project_id, 'id' => $row->id]),
                    ];
                })
        );

        return $items;
    }

    private function applyFilters($items, array $filters)
    {
        return $items->filter(function ($row) use ($filters) {
            if (($filters['type'] ?? '') !== '' && $row['type'] !== $filters['type']) {
                return false;
            }
            if (($filters['project_id'] ?? '') !== '' && (string) $row['project_id'] !== (string) $filters['project_id']) {
                return false;
            }
            if (!empty($filters['date_from'])) {
                $d = $row['date'] ? Carbon::parse($row['date'])->toDateString() : null;
                if (!$d || $d < $filters['date_from']) {
                    return false;
                }
            }
            if (!empty($filters['date_to'])) {
                $d = $row['date'] ? Carbon::parse($row['date'])->toDateString() : null;
                if (!$d || $d > $filters['date_to']) {
                    return false;
                }
            }
            return true;
        });
    }
}



