<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Spp;
use App\Models\Bpg;
use App\Models\Lpb;
use App\Models\PurchaseOrder;
use App\Models\Spk;
use App\Models\VendorComparison;
use App\Models\PurchaseVoucher;
use Illuminate\Database\Eloquent\Model;
use App\Support\NotificationService;

class AuditLogger
{
    public static function log(string $action, ?Model $model = null, ?array $before = null, ?array $after = null, array $meta = []): void
    {
        $user = auth()->user();

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'before' => $before,
            'after' => $after,
            'meta' => $meta,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        if (str_contains($action, 'submitted')) {
            $metaDoc = self::docMeta($model);
            $title = 'Approval Diperlukan';
            $message = 'Ada dokumen baru menunggu approval.';
            $href = route('dev.dashboard');
            if ($metaDoc) {
                $title = 'Approval Diperlukan: ' . $metaDoc['label'] . ' ' . $metaDoc['number'];
                $message = 'Dokumen ' . $metaDoc['label'] . ' ' . $metaDoc['number'] . ' menunggu approval.';
                $href = $metaDoc['href'] ?? $href;
            }

            NotificationService::notifyHO($title, $message, $href, 'approval', [
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->getKey(),
            ]);
        }
    }

    protected static function docMeta(?Model $model): ?array
    {
        if (!$model) {
            return null;
        }

        $projectId = $model->project_id ?? null;
        $rabId = $model->rab_id ?? null;
        $id = $model->getKey();

        if ($model instanceof Spp) {
            return [
                'label' => 'SPP',
                'number' => $model->spp_no ?? $id,
                'href' => $projectId && $rabId ? route('dev.rabs.spps.show', [$projectId, $rabId, $id]) : null,
            ];
        }
        if ($model instanceof Bpg) {
            return [
                'label' => 'BPG',
                'number' => $model->bpg_no ?? $id,
                'href' => $projectId && $rabId ? route('dev.rabs.bpgs.show', [$projectId, $rabId, $id]) : null,
            ];
        }
        if ($model instanceof Lpb) {
            return [
                'label' => 'LPB',
                'number' => $model->lpb_no ?? $id,
                'href' => $projectId && $rabId ? route('dev.rabs.lpbs.show', [$projectId, $rabId, $id]) : null,
            ];
        }
        if ($model instanceof PurchaseOrder) {
            return [
                'label' => 'PO',
                'number' => $model->po_no ?? $id,
                'href' => $projectId && $rabId ? route('dev.rabs.purchase-orders.show', [$projectId, $rabId, $id]) : null,
            ];
        }
        if ($model instanceof Spk) {
            return [
                'label' => 'SPK',
                'number' => $model->spk_no ?? $id,
                'href' => $projectId && $rabId ? route('dev.rabs.spks.show', [$projectId, $rabId, $id]) : null,
            ];
        }
        if ($model instanceof VendorComparison) {
            return [
                'label' => 'Komparasi',
                'number' => $model->comparison_no ?? $id,
                'href' => $projectId && $rabId ? route('dev.rabs.vendor-comparisons.show', [$projectId, $rabId, $id]) : null,
            ];
        }
        if ($model instanceof PurchaseVoucher) {
            return [
                'label' => 'Voucher Pembelian',
                'number' => $model->voucher_no ?? $id,
                'href' => $projectId && $rabId ? route('dev.rabs.purchase-vouchers.show', [$projectId, $rabId, $id]) : null,
            ];
        }

        return null;
    }
}
