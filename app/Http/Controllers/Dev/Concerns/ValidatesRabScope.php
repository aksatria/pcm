<?php

namespace App\Http\Controllers\Dev\Concerns;

use App\Models\RabItem;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait ValidatesRabScope
{
    protected function loadRabItemsOrFail(int $rabId, array $items): Collection
    {
        $rabItemIds = collect($items)
            ->pluck('rab_item_id')
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->unique()
            ->values();

        if ($rabItemIds->isEmpty()) {
            return collect();
        }

        $rabItems = RabItem::with('data')
            ->where('rab_id', $rabId)
            ->whereIn('id', $rabItemIds)
            ->get()
            ->keyBy('id');

        if ($rabItems->count() !== $rabItemIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Ada item RAPP yang tidak valid.',
            ]);
        }

        return $rabItems;
    }

    protected function assertVendorsInProject(array $vendorIds, int $projectId, string $field = 'vendor_id'): void
    {
        $ids = collect($vendorIds)
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $count = Vendor::forProject($projectId)
            ->whereIn('id', $ids)
            ->count();

        if ($count !== $ids->count()) {
            throw ValidationException::withMessages([
                $field => 'Vendor tidak sesuai proyek.',
            ]);
        }
    }

    protected function assertQtyWithinRabBudget(
        array $items,
        Collection $rabItems,
        string $itemModelClass,
        string $foreignKey,
        ?int $currentDocId,
        string $relationName,
        int $projectId,
        int $rabId
    ): void {
        if (empty($items) || $rabItems->isEmpty()) {
            return;
        }

        $rabItemIds = $rabItems->keys()->values();

        $existingTotals = $itemModelClass::query()
            ->selectRaw('rab_item_id, SUM(qty) as total_qty')
            ->whereIn('rab_item_id', $rabItemIds)
            ->when($currentDocId, function ($query) use ($foreignKey, $currentDocId) {
                $query->where($foreignKey, '!=', $currentDocId);
            })
            ->whereHas($relationName, function ($query) use ($projectId, $rabId) {
                $query->where('project_id', $projectId)
                    ->where('rab_id', $rabId)
                    ->whereNotIn('status', ['rejected', 'cancelled']);
            })
            ->groupBy('rab_item_id')
            ->pluck('total_qty', 'rab_item_id');

        $requestedTotals = collect($items)
            ->filter(fn ($row) => !empty($row['rab_item_id']))
            ->groupBy(fn ($row) => (int) $row['rab_item_id'])
            ->map(fn ($rows) => $rows->sum(fn ($row) => (float) ($row['qty'] ?? 0)));

        foreach ($requestedTotals as $rabItemId => $requestedQty) {
            $rabItemId = (int) $rabItemId;
            $rabItem = $rabItems->get($rabItemId);
            if (!$rabItem) {
                continue;
            }

            $budgetVol = (float) ($rabItem->volume ?? 0);
            $usedQty = (float) ($existingTotals[$rabItemId] ?? 0);
            $remaining = max(0, $budgetVol - $usedQty);

            if ($requestedQty > $remaining + 0.00001) {
                $label = $rabItem->data->kode ?? $rabItem->id;
                throw ValidationException::withMessages([
                    'items' => 'Qty melebihi sisa RAPP untuk item ' . $label . '. Sisa: ' . number_format($remaining, 2, ',', '.'),
                ]);
            }
        }
    }
}


