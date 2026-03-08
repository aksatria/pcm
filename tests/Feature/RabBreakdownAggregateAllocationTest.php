<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Data;
use App\Models\Project;
use App\Models\Rab;
use App\Models\RabBreakdown;
use App\Models\RabBreakdownItem;
use App\Models\RabItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RabBreakdownAggregateAllocationTest extends TestCase
{
    use RefreshDatabase;

    private function makeScenario(): array
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'is_admin' => false,
        ]);

        $client = Client::create([
            'name' => 'Client Aggregate Test',
        ]);

        $project = Project::create([
            'code' => 'PRJ-RAB-AGG-001',
            'name' => 'Project Aggregate Guardrail',
            'client_id' => $client->id,
            'location' => 'Gresik',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'budget' => 100000000,
            'status' => 'Planning',
        ]);

        $rab = Rab::create([
            'project_id' => $project->id,
            'name' => 'RAB Approved Aggregate',
            'status' => 'approved',
            'is_active' => true,
            'total_budget' => 100000000,
            'approved_at' => now(),
        ]);

        $data = Data::create([
            'kode' => 'MT-001',
            'kategori' => 'Material',
            'kode_kategori' => 'MT',
            'uraian' => 'Paku beton',
            'satuan' => 'm3',
            'harga' => 1000,
            'status' => true,
        ]);

        $rabItem = RabItem::create([
            'rab_id' => $rab->id,
            'data_id' => $data->id,
            'item_type' => 'MT',
            'volume' => 100,
            'satuan' => 'm3',
            'harga_satuan' => 1000,
            'keterangan' => 'Paku beton acuan',
            'urutan' => 1,
        ]);

        $rabA = RabBreakdown::create([
            'project_id' => $project->id,
            'rab_breakdown_code' => 'RAB-001',
            'name' => 'PEKERJAAN PERSIAPAN',
            'order_number' => 1,
            'status' => 'not_started',
            'approval_status' => 'draft',
            'budget_amount' => 0,
            'actual_amount' => 0,
        ]);

        $rabB = RabBreakdown::create([
            'project_id' => $project->id,
            'rab_breakdown_code' => 'RAB-002',
            'name' => 'PEKERJAAN TANAH',
            'order_number' => 2,
            'status' => 'not_started',
            'approval_status' => 'draft',
            'budget_amount' => 0,
            'actual_amount' => 0,
        ]);

        return compact('staff', 'project', 'rabA', 'rabB', 'rabItem');
    }

    public function test_same_rapp_item_can_be_allocated_in_different_headers_when_total_is_within_limit(): void
    {
        $ctx = $this->makeScenario();

        $first = $this->actingAs($ctx['staff'])->post(
            route('dev.rab-breakdown.items.store', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $ctx['rabA']->id,
            ]),
            [
                'item_code' => 'A.1',
                'rab_item_id' => $ctx['rabItem']->id,
                'qty_beli' => 50,
                'jumlah' => 50000,
            ]
        );
        $first->assertStatus(302);
        $first->assertSessionHasNoErrors();

        $second = $this->actingAs($ctx['staff'])->post(
            route('dev.rab-breakdown.items.store', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $ctx['rabB']->id,
            ]),
            [
                'item_code' => 'B.1',
                'rab_item_id' => $ctx['rabItem']->id,
                'qty_beli' => 50,
                'jumlah' => 50000,
            ]
        );
        $second->assertStatus(302);
        $second->assertSessionHasNoErrors();

        $this->assertDatabaseCount('rab_breakdown_items', 2);
    }

    public function test_cross_header_allocation_is_rejected_when_aggregate_exceeds_baseline_limit(): void
    {
        $ctx = $this->makeScenario();

        RabBreakdownItem::create([
            'rab_breakdown_id' => $ctx['rabA']->id,
            'item_code' => 'A.1',
            'uraian' => 'Item existing',
            'volume_rab' => 100,
            'satuan' => 'm3',
            'unit_price' => 1000,
            'total_price' => 100000,
            'rab_item_id' => $ctx['rabItem']->id,
            'qty_beli' => 100,
            'jumlah' => 100000,
        ]);

        $response = $this->actingAs($ctx['staff'])->from(
            route('dev.rab-breakdown.items.create', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $ctx['rabB']->id,
            ])
        )->post(
            route('dev.rab-breakdown.items.store', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $ctx['rabB']->id,
            ]),
            [
                'item_code' => 'B.1',
                'rab_item_id' => $ctx['rabItem']->id,
                'qty_beli' => 30,
                'jumlah' => 30000,
            ]
        );

        $response->assertRedirect(route('dev.rab-breakdown.items.create', [
            'projectId' => $ctx['project']->id,
            'rabBreakdown' => $ctx['rabB']->id,
        ]));
        $response->assertSessionHasErrors(['qty_beli']);
        $this->assertDatabaseCount('rab_breakdown_items', 1);
    }
}
