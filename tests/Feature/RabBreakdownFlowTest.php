<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Rab;
use App\Models\RabBreakdown;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RabBreakdownFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeScenario(): array
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_admin' => false,
        ]);
        $ho = User::factory()->create([
            'role' => 'ho',
            'is_admin' => false,
        ]);

        $client = Client::create([
            'name' => 'Client Test',
        ]);

        $project = Project::create([
            'code' => 'PRJ-RAB-001',
            'name' => 'Project RAB Breakdown Test',
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

        return compact('user', 'ho', 'project');
    }

    public function test_authenticated_user_can_open_rab_breakdown_pages(): void
    {
        $ctx = $this->makeScenario();

        $indexResponse = $this->actingAs($ctx['user'])
            ->get(route('dev.rab-breakdown.index', ['projectId' => $ctx['project']->id]));

        $indexResponse->assertOk();

        $createResponse = $this->actingAs($ctx['user'])
            ->get(route('dev.rab-breakdown.create', ['projectId' => $ctx['project']->id]));

        $createResponse->assertOk();
        $createResponse->assertSee('RAB-001');
    }

    public function test_legacy_wbs_index_redirects_to_rab_breakdown_index(): void
    {
        $ctx = $this->makeScenario();

        $response = $this->actingAs($ctx['user'])
            ->get(route('dev.rab-breakdown.legacy.wbs.index', ['projectId' => $ctx['project']->id]));

        $response->assertRedirect(route('dev.rab-breakdown.index', ['projectId' => $ctx['project']->id]));
    }

    public function test_user_can_create_rab_breakdown_and_order_number_is_mapped(): void
    {
        $ctx = $this->makeScenario();

        $response = $this->actingAs($ctx['user'])->post(
            route('dev.rab-breakdown.store', ['projectId' => $ctx['project']->id]),
            [
                'rab_breakdown_code' => 'RAB-001',
                'name' => 'PEKERJAAN PERSIAPAN',
                'description' => 'Tes create RAB Breakdown',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
                'budget_amount' => 5000000,
            ]
        );

        $rabBreakdown = RabBreakdown::where('project_id', $ctx['project']->id)
            ->where('rab_breakdown_code', 'RAB-001')
            ->first();

        $this->assertNotNull($rabBreakdown);

        $response->assertRedirect(route('dev.rab-breakdown.show', [
            'projectId' => $ctx['project']->id,
            'rabBreakdown' => $rabBreakdown->id,
        ]));

        $this->assertDatabaseHas('rab_breakdowns', [
            'id' => $rabBreakdown->id,
            'project_id' => $ctx['project']->id,
            'rab_breakdown_code' => 'RAB-001',
            'order_number' => 1,
            'status' => 'not_started',
            'approval_status' => 'draft',
        ]);
    }

    public function test_duplicate_rab_breakdown_code_in_same_project_is_rejected(): void
    {
        $ctx = $this->makeScenario();

        RabBreakdown::create([
            'project_id' => $ctx['project']->id,
            'rab_breakdown_code' => 'RAB-001',
            'name' => 'PEKERJAAN PERSIAPAN',
            'order_number' => 1,
            'status' => 'not_started',
            'approval_status' => 'draft',
        ]);

        $response = $this->actingAs($ctx['user'])->from(
            route('dev.rab-breakdown.create', ['projectId' => $ctx['project']->id])
        )->post(
            route('dev.rab-breakdown.store', ['projectId' => $ctx['project']->id]),
            [
                'rab_breakdown_code' => 'RAB-001',
                'name' => 'Duplikat Kode',
                'description' => 'Harus ditolak',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(1)->toDateString(),
                'budget_amount' => 1000,
            ]
        );

        $response->assertRedirect(route('dev.rab-breakdown.create', ['projectId' => $ctx['project']->id]));
        $response->assertSessionHasErrors(['rab_breakdown_code']);
    }

    public function test_invalid_rab_breakdown_code_format_is_rejected(): void
    {
        $ctx = $this->makeScenario();

        $response = $this->actingAs($ctx['user'])->from(
            route('dev.rab-breakdown.create', ['projectId' => $ctx['project']->id])
        )->post(
            route('dev.rab-breakdown.store', ['projectId' => $ctx['project']->id]),
            [
                'rab_breakdown_code' => 'ABC-001',
                'name' => 'Format Salah',
                'description' => 'Harus ditolak',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(1)->toDateString(),
                'budget_amount' => 1000,
            ]
        );

        $response->assertRedirect(route('dev.rab-breakdown.create', ['projectId' => $ctx['project']->id]));
        $response->assertSessionHasErrors(['rab_breakdown_code']);
    }

    public function test_lowercase_rab_code_is_normalized_to_uppercase(): void
    {
        $ctx = $this->makeScenario();

        $response = $this->actingAs($ctx['user'])->post(
            route('dev.rab-breakdown.store', ['projectId' => $ctx['project']->id]),
            [
                'rab_breakdown_code' => 'rab-005',
                'name' => 'PEKERJAAN ARSITEKTUR',
                'description' => 'Normalisasi kode',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'budget_amount' => 2000,
            ]
        );

        $response->assertStatus(302);

        $this->assertDatabaseHas('rab_breakdowns', [
            'project_id' => $ctx['project']->id,
            'rab_breakdown_code' => 'RAB-005',
            'order_number' => 5,
        ]);
    }

}
