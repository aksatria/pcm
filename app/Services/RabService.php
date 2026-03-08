<?php
// app/Services/RabService.php

namespace App\Services;

use App\Models\MasterData; // ✅ DIUBAH: MasterItem -> MasterData
use App\Models\RabItem;
use Illuminate\Support\Facades\DB;

class RabService
{
    /**
     * Initialize RAB draft items from master items for a project.
     *
     * @param \App\Models\Project $project
     * @return void
     */
    public function initRabFromMaster($project)
    {
        $masterItems = MasterData::all(); // ✅ DIUBAH: MasterItem -> MasterData
        $now = now()->toDateString();

        DB::transaction(function() use ($masterItems, $project, $now) {
            foreach ($masterItems as $m) {
                $price = \App\Services\PriceResolverService::resolvePriceForProject($m, $project, $now);
                RabItem::create([
                    'project_id' => $project->id,
                    'master_item_id' => $m->id,
                    'parent_id' => null,
                    'code' => null,
                    'title' => $m->name,
                    'unit' => $m->unit,
                    'qty' => 0,
                    'price_internal' => $price,
                    'price_external' => null,
                    'price_snapshot_meta' => [
                        'source'=>'resolved',
                        'price'=>$price,
                        'resolved_at'=>now()->toDateTimeString(),
                        'province_id'=>$project->province_id
                    ],
                    'total_internal' => 0,
                    'total_external' => 0,
                    'status' => 'draft',
                ]);
            }
        });
    }

    /**
     * Approve RAB: finalize external prices (apply markup) and freeze items.
     *
     * @param \App\Models\Project $project
     * @param \App\Models\User $approver
     * @param float|null $markupPercent
     * @return void
     */
    public function approveRab($project, $approver, $markupPercent = null)
    {
        $markupPercent = $markupPercent ?? config('rabs.default_markup_percent', 0);

        DB::transaction(function() use ($project, $approver, $markupPercent) {
            $items = $project->rabItems()->where('status','draft')->get();
            foreach ($items as $it) {
                $priceExt = round((float)$it->price_internal * (1 + $markupPercent/100), 2);
                $meta = array_merge(is_array($it->price_snapshot_meta) ? $it->price_snapshot_meta : ($it->price_snapshot_meta ?? []), [
                    'approved_by' => $approver->id,
                    'approved_at' => now()->toDateTimeString(),
                    'finalized_price_internal' => (float)$it->price_internal,
                    'finalized_price_external' => $priceExt,
                ]);
                $it->update([
                    'price_external' => $priceExt,
                    'price_snapshot_meta' => $meta,
                    'status' => 'approved',
                    'total_internal' => $it->qty * $it->price_internal,
                    'total_external' => $it->qty * $priceExt,
                ]);
            }
            $project->update(['rab_status' => 'approved']);
        });
    }
}
