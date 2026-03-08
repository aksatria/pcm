<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Rab;
use App\Models\RabBreakdown;
use App\Models\RabBreakdownItem;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RabBreakdownWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('broadcasting.default', 'null');
        Config::set('queue.default', 'sync');
    }

    private function makeScenario(): array
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'is_admin' => false,
        ]);
        $ho = User::factory()->create([
            'role' => 'ho',
            'is_admin' => false,
        ]);

        $client = Client::create([
            'name' => 'Client Workflow Test',
        ]);

        $project = Project::create([
            'code' => 'PRJ-RAB-WF-001',
            'name' => 'Project RAB Breakdown Workflow Test',
            'client_id' => $client->id,
            'location' => 'Gresik',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'budget' => 100000000,
            'status' => 'Planning',
        ]);

        Rab::create([
            'project_id' => $project->id,
            'name' => 'RAB Baseline Approved',
            'status' => 'approved',
            'is_active' => true,
            'total_budget' => 100000000,
            'approved_at' => now(),
        ]);

        $rabBreakdown = RabBreakdown::create([
            'project_id' => $project->id,
            'rab_breakdown_code' => 'RAB-002',
            'name' => 'PEKERJAAN TANAH',
            'order_number' => 2,
            'status' => 'not_started',
            'approval_status' => 'draft',
        ]);

        RabBreakdownItem::create([
            'rab_breakdown_id' => $rabBreakdown->id,
            'item_code' => 'A.1',
            'uraian' => 'Item untuk submit',
            'volume_rab' => 1,
            'satuan' => 'LS',
            'unit_price' => 10000,
            'total_price' => 10000,
        ]);

        return compact('staff', 'ho', 'project', 'rabBreakdown');
    }

    public function test_submit_approve_reject_workflow_runs_as_expected(): void
    {
        $ctx = $this->makeScenario();

        $submitResponse = $this->actingAs($ctx['staff'])->post(
            route('dev.rab-breakdown.submit', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $ctx['rabBreakdown']->id,
            ])
        );
        $submitResponse->assertStatus(302);
        $this->assertDatabaseHas('rab_breakdowns', [
            'id' => $ctx['rabBreakdown']->id,
            'approval_status' => 'submitted',
        ]);

        $approveResponse = $this->actingAs($ctx['ho'])->post(
            route('dev.rab-breakdown.approve', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $ctx['rabBreakdown']->id,
            ])
        );
        $approveResponse->assertStatus(302);
        $this->assertDatabaseHas('rab_breakdowns', [
            'id' => $ctx['rabBreakdown']->id,
            'approval_status' => 'approved',
        ]);

        $ctx['rabBreakdown']->update([
            'approval_status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $rejectResponse = $this->actingAs($ctx['ho'])->post(
            route('dev.rab-breakdown.reject', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $ctx['rabBreakdown']->id,
            ]),
            ['rejected_reason' => 'Perlu revisi volume']
        );
        $rejectResponse->assertStatus(302);
        $this->assertDatabaseHas('rab_breakdowns', [
            'id' => $ctx['rabBreakdown']->id,
            'approval_status' => 'rejected',
            'rejected_reason' => 'Perlu revisi volume',
        ]);
    }

    public function test_staff_cannot_approve_or_reject(): void
    {
        $ctx = $this->makeScenario();

        $ctx['rabBreakdown']->update([
            'approval_status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $approveResponse = $this->actingAs($ctx['staff'])->post(
            route('dev.rab-breakdown.approve', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $ctx['rabBreakdown']->id,
            ])
        );
        $approveResponse->assertForbidden();

        $rejectResponse = $this->actingAs($ctx['staff'])->post(
            route('dev.rab-breakdown.reject', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $ctx['rabBreakdown']->id,
            ])
        );
        $rejectResponse->assertForbidden();
    }
}
