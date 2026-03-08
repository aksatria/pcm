<?php
// app/Services/PriceResolverService.php

namespace App\Services;

use App\Models\MasterData; // ✅ DIUBAH: MasterItem -> MasterData
use App\Models\Project;
use App\Models\MasterItemPrice;
use Illuminate\Support\Collection;

class PriceResolverService
{
    /**
     * Resolve price for a master item in a specific project
     */
    public static function resolvePriceForProject(MasterData $item, Project $project, $date = null): float // ✅ DIUBAH: MasterItem -> MasterData
    {
        $date = $date ?: now()->toDateString();
        $provinceId = $project->province_id;

        // Query untuk mencari harga yang sesuai
        $query = MasterItemPrice::where('master_item_id', $item->id)
            ->where(function($q) use ($provinceId) {
                $q->where('province_id', $provinceId)
                  ->orWhereNull('province_id');
            })
            ->where(function($q) use ($date) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date);
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->orderByRaw('province_id IS NULL, province_id DESC')
            ->orderBy('effective_from', 'desc');

        $price = $query->first();
        
        return $price ? (float) $price->price : (float) $item->base_price;
    }

    /**
     * Resolve prices for multiple items in batch
     */
    public static function resolvePricesForProject(Collection $items, Project $project, $date = null): array
    {
        $date = $date ?: now()->toDateString();
        $provinceId = $project->province_id;
        $itemIds = $items->pluck('id')->toArray();

        // Get all applicable prices
        $prices = MasterItemPrice::whereIn('master_item_id', $itemIds)
            ->where(function($q) use ($provinceId) {
                $q->where('province_id', $provinceId)
                  ->orWhereNull('province_id');
            })
            ->where(function($q) use ($date) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date);
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->get()
            ->groupBy('master_item_id');

        $resolvedPrices = [];

        foreach ($items as $item) {
            $itemPrices = $prices->get($item->id, collect());
            
            // Prioritize province-specific prices, then global prices
            $provincePrice = $itemPrices->where('province_id', $provinceId)->sortByDesc('effective_from')->first();
            $globalPrice = $itemPrices->whereNull('province_id')->sortByDesc('effective_from')->first();
            
            $resolvedPrice = $provincePrice ?: $globalPrice;
            
            $resolvedPrices[$item->id] = [
                'price' => $resolvedPrice ? (float) $resolvedPrice->price : (float) $item->base_price,
                'price_record' => $resolvedPrice,
                'is_province_specific' => (bool) $provincePrice,
                'is_global' => (bool) $globalPrice && !$provincePrice,
                'is_base_price' => !$resolvedPrice,
            ];
        }

        return $resolvedPrices;
    }

    /**
     * Get price resolution details for logging/snapshot
     */
    public static function getPriceResolutionDetails(MasterData $item, Project $project, $date = null): array // ✅ DIUBAH: MasterItem -> MasterData
    {
        $date = $date ?: now()->toDateString();
        $provinceId = $project->province_id;

        $query = MasterItemPrice::where('master_item_id', $item->id)
            ->where(function($q) use ($provinceId) {
                $q->where('province_id', $provinceId)
                  ->orWhereNull('province_id');
            })
            ->where(function($q) use ($date) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date);
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->orderByRaw('province_id IS NULL, province_id DESC')
            ->orderBy('effective_from', 'desc');

        $priceRecord = $query->first();

        return [
            'master_item_id' => $item->id,
            'base_price' => (float) $item->base_price,
            'resolved_price' => $priceRecord ? (float) $priceRecord->price : (float) $item->base_price,
            'province_id' => $provinceId,
            'province_name' => $project->province?->name,
            'price_record_id' => $priceRecord?->id,
            'effective_from' => $priceRecord?->effective_from?->toISOString(),
            'effective_to' => $priceRecord?->effective_to?->toISOString(),
            'resolution_date' => $date,
            'used_province_price' => (bool) ($priceRecord && $priceRecord->province_id),
            'used_global_price' => (bool) ($priceRecord && !$priceRecord->province_id),
            'used_base_price' => !$priceRecord,
        ];
    }

    /**
     * Get available provinces for an item with prices
     */
    public static function getAvailableProvincesForItem(MasterData $item): Collection // ✅ DIUBAH: MasterItem -> MasterData
    {
        return MasterItemPrice::where('master_item_id', $item->id)
            ->whereNotNull('province_id')
            ->with('province')
            ->get()
            ->pluck('province')
            ->unique()
            ->filter();
    }

    /**
     * Check if item has province-specific pricing
     */
    public static function hasProvincePricing(MasterData $item): bool // ✅ DIUBAH: MasterItem -> MasterData
    {
        return MasterItemPrice::where('master_item_id', $item->id)
            ->whereNotNull('province_id')
            ->exists();
    }
}