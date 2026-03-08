<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\RabBreakdown;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RabBreakdownImportAndLockTest extends TestCase
{
    use RefreshDatabase;

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
            'name' => 'Client Import Test',
        ]);

        $project = Project::create([
            'code' => 'PRJ-RAB-IMP-001',
            'name' => 'Project RAB Breakdown Import Test',
            'client_id' => $client->id,
            'location' => 'Gresik',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'budget' => 100000000,
            'status' => 'Planning',
        ]);

        return compact('staff', 'ho', 'project');
    }

    public function test_validate_import_endpoint_requires_file_and_accepts_valid_csv(): void
    {
        $ctx = $this->makeScenario();

        $rabBreakdown = RabBreakdown::create([
            'project_id' => $ctx['project']->id,
            'rab_breakdown_code' => 'RAB-003',
            'name' => 'PEKERJAAN PONDASI',
            'order_number' => 3,
            'status' => 'not_started',
            'approval_status' => 'draft',
        ]);

        $missingFileResponse = $this->actingAs($ctx['staff'])->postJson(
            route('dev.rab-breakdown.validate-import', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $rabBreakdown->id,
            ])
        );
        $missingFileResponse->assertStatus(422)->assertJsonValidationErrors(['file']);

        $csv = implode("\n", [
            'item_code,uraian,volume_rab,satuan,unit_price,master_kode,notes',
            'A.1,Item Test,1,LS,10000,,catatan',
        ]);
        $file = UploadedFile::fake()->createWithContent('rab-breakdown.csv', $csv);

        $okResponse = $this->actingAs($ctx['staff'])->postJson(
            route('dev.rab-breakdown.validate-import', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $rabBreakdown->id,
            ]),
            ['file' => $file]
        );

        $okResponse->assertOk()->assertJson([
            'success' => true,
        ]);
    }

    public function test_edit_is_locked_for_staff_when_submitted_but_ho_can_access(): void
    {
        $ctx = $this->makeScenario();

        $rabBreakdown = RabBreakdown::create([
            'project_id' => $ctx['project']->id,
            'rab_breakdown_code' => 'RAB-004',
            'name' => 'PEKERJAAN STRUKTUR',
            'order_number' => 4,
            'status' => 'in_progress',
            'approval_status' => 'submitted',
        ]);

        $staffResponse = $this->actingAs($ctx['staff'])->get(
            route('dev.rab-breakdown.edit', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $rabBreakdown->id,
            ])
        );
        $staffResponse->assertForbidden();

        $hoResponse = $this->actingAs($ctx['ho'])->get(
            route('dev.rab-breakdown.edit', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $rabBreakdown->id,
            ])
        );
        $hoResponse->assertOk();
    }
}

