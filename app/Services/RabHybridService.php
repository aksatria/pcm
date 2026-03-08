<?php
// app/Services/RabHybridService.php

namespace App\Services;

use App\Models\Project;
use App\Models\MasterData;
use App\Models\RabItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RabHybridService
{
    /**
     * Generate RAB items from master data for a project
     */
    public function generateRabFromMaster(Project $project): array
    {
        try {
            DB::beginTransaction();

            $masterItems = MasterData::whereNotNull('category') // ✅ FILTER: hanya ambil yang punya kategori
                                    ->where('category', '!=', '') // ✅ FILTER: kategori tidak kosong
                                    ->get();
            $generatedCount = 0;
            $skippedCount = 0;

            foreach ($masterItems as $masterItem) {
                // Validasi data master item
                if (!$this->isValidMasterItem($masterItem)) {
                    $skippedCount++;
                    continue;
                }

                // Resolve price based on project province
                $priceDetails = PriceResolverService::getPriceResolutionDetails($masterItem, $project);
                $resolvedPrice = $priceDetails['resolved_price'];

                // Check if item already exists
                $existingItem = RabItem::where('project_id', $project->id)
                    ->where('code', $masterItem->code)
                    ->first();

                if (!$existingItem) {
                    RabItem::create([
                        'project_id' => $project->id,
                        'code' => $masterItem->code,
                        'category' => $masterItem->category,
                        'description' => $masterItem->name,
                        'unit' => $masterItem->unit,
                        'volume' => 0, // Default volume, can be updated later
                        'unit_price' => $resolvedPrice,
                        'total_price' => 0,
                        'price_internal' => $resolvedPrice,
                        'price_external' => $resolvedPrice,
                        'notes' => $masterItem->notes,
                        'status' => RabItem::STATUS_DRAFT,
                        'order' => $this->getNextOrder($project, $masterItem->category),
                    ]);

                    $generatedCount++;
                }
            }

            DB::commit();

            Log::info('RAB generated from master data', [
                'project_id' => $project->id,
                'generated_count' => $generatedCount,
                'skipped_count' => $skippedCount,
                'total_master_items' => $masterItems->count(),
            ]);

            return [
                'success' => true,
                'generated_count' => $generatedCount,
                'skipped_count' => $skippedCount,
                'total_items' => $masterItems->count(),
                'message' => "Berhasil generate {$generatedCount} item RAB dari Master Data" . 
                            ($skippedCount > 0 ? " ({$skippedCount} item dilewati karena data tidak valid)" : "")
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to generate RAB from master data', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate master item data
     */
    private function isValidMasterItem(MasterData $masterItem): bool
    {
        // Validasi field yang required
        if (empty($masterItem->code)) {
            Log::warning('Master item skipped: missing code', ['item_id' => $masterItem->id]);
            return false;
        }

        if (empty($masterItem->category)) {
            Log::warning('Master item skipped: missing category', ['item_id' => $masterItem->id, 'code' => $masterItem->code]);
            return false;
        }

        if (empty($masterItem->name)) {
            Log::warning('Master item skipped: missing name', ['item_id' => $masterItem->id, 'code' => $masterItem->code]);
            return false;
        }

        if (empty($masterItem->unit)) {
            Log::warning('Master item skipped: missing unit', ['item_id' => $masterItem->id, 'code' => $masterItem->code]);
            return false;
        }

        return true;
    }

    /**
     * Get next order number for category with fallback
     */
    private function getNextOrder(Project $project, ?string $category): int
    {
        // Jika category null, gunakan default category
        $category = $category ?? 'MATERIAL';
        
        $lastOrder = RabItem::where('project_id', $project->id)
            ->where('category', $category)
            ->max('order');

        return ($lastOrder ?? 0) + 1;
    }

    /**
     * Approve RAB - freeze prices and create snapshot
     */
    public function approveRab(Project $project, $approvedBy): array
    {
        try {
            DB::beginTransaction();

            $rabItems = RabItem::where('project_id', $project->id)
                ->where('status', RabItem::STATUS_DRAFT)
                ->get();

            $approvedCount = 0;

            foreach ($rabItems as $rabItem) {
                // Create price snapshot before approval
                $masterItem = MasterData::where('code', $rabItem->code)->first();
                
                if ($masterItem) {
                    $priceDetails = PriceResolverService::getPriceResolutionDetails($masterItem, $project);
                    // Pastikan method createPriceSnapshot ada di model RabItem
                    if (method_exists($rabItem, 'createPriceSnapshot')) {
                        $rabItem->createPriceSnapshot($priceDetails);
                    }
                }

                // Freeze the prices
                $rabItem->update([
                    'price_internal' => $rabItem->unit_price,
                    'price_external' => $rabItem->unit_price,
                    'status' => RabItem::STATUS_APPROVED,
                ]);

                $approvedCount++;
            }

            DB::commit();

            Log::info('RAB approved and prices frozen', [
                'project_id' => $project->id,
                'approved_by' => $approvedBy,
                'approved_count' => $approvedCount,
            ]);

            return [
                'success' => true,
                'approved_count' => $approvedCount,
                'message' => "Berhasil approve {$approvedCount} item RAB"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to approve RAB', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update RAB item prices based on current master data (for draft RAB only)
     */
    public function refreshRabPrices(Project $project): array
    {
        try {
            DB::beginTransaction();

            $rabItems = RabItem::where('project_id', $project->id)
                ->where('status', RabItem::STATUS_DRAFT)
                ->get();

            $updatedCount = 0;
            $skippedCount = 0;

            foreach ($rabItems as $rabItem) {
                $masterItem = MasterData::where('code', $rabItem->code)->first();
                
                if ($masterItem && $this->isValidMasterItem($masterItem)) {
                    $newPrice = PriceResolverService::resolvePriceForProject($masterItem, $project);
                    
                    $rabItem->update([
                        'unit_price' => $newPrice,
                        'total_price' => $rabItem->volume * $newPrice,
                        'price_internal' => $newPrice,
                        'price_external' => $newPrice,
                    ]);

                    $updatedCount++;
                } else {
                    $skippedCount++;
                    Log::warning('RAB item price refresh skipped: master item not found or invalid', [
                        'rab_item_id' => $rabItem->id,
                        'code' => $rabItem->code
                    ]);
                }
            }

            DB::commit();

            Log::info('RAB prices refreshed from master data', [
                'project_id' => $project->id,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
            ]);

            return [
                'success' => true,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'message' => "Berhasil refresh harga {$updatedCount} item RAB" . 
                            ($skippedCount > 0 ? " ({$skippedCount} item tidak dapat di-update)" : "")
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to refresh RAB prices', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get RAB summary with price comparison
     */
    public function getRabSummary(Project $project): array
    {
        $rabItems = RabItem::where('project_id', $project->id)->get();
        
        $draftItems = $rabItems->where('status', RabItem::STATUS_DRAFT);
        $approvedItems = $rabItems->where('status', RabItem::STATUS_APPROVED);

        return [
            'total_items' => $rabItems->count(),
            'draft_items' => $draftItems->count(),
            'approved_items' => $approvedItems->count(),
            'total_draft_amount' => $draftItems->sum('total_price'),
            'total_approved_amount' => $approvedItems->sum('total_price'),
            'has_province_pricing' => $project->province_id !== null,
        ];
    }

    /**
     * Get master data statistics for the project
     */
    public function getMasterDataStats(Project $project): array
    {
        $totalMasterItems = MasterData::count();
        $validMasterItems = MasterData::whereNotNull('category')
                                    ->where('category', '!=', '')
                                    ->whereNotNull('code')
                                    ->where('code', '!=', '')
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->whereNotNull('unit')
                                    ->where('unit', '!=', '')
                                    ->count();
        
        $invalidMasterItems = $totalMasterItems - $validMasterItems;

        return [
            'total_master_items' => $totalMasterItems,
            'valid_master_items' => $validMasterItems,
            'invalid_master_items' => $invalidMasterItems,
            'has_province' => $project->province_id !== null,
        ];
    }
}