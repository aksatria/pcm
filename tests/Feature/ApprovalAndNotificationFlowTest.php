<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Client;
use App\Models\Project;
use App\Models\Rab;
use App\Models\RabBreakdown;
use App\Models\RabBreakdownItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalAndNotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent external broadcast calls during feature tests.
        config()->set('broadcasting.default', 'null');
    }

    private function makeScenario(): array
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'is_admin' => false,
        ]);
        $ho = User::factory()->create([
            'role' => 'ho',
            'is_admin' => true,
        ]);

        $client = Client::create([
            'name' => 'Client Approval Test',
        ]);

        $project = Project::create([
            'code' => 'PRJ-APPR-001',
            'name' => 'Project Approval Test',
            'client_id' => $client->id,
            'location' => 'Gresik',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'budget' => 50000000,
            'status' => 'Planning',
        ]);

        $rabBreakdown = RabBreakdown::create([
            'project_id' => $project->id,
            'rab_breakdown_code' => 'RAB-002',
            'name' => 'PEKERJAAN TANAH',
            'order_number' => 2,
            'status' => 'not_started',
            'approval_status' => 'submitted',
            'budget_amount' => 1000000,
            'actual_amount' => 0,
        ]);

        return compact('staff', 'ho', 'project', 'rabBreakdown');
    }

    public function test_dev_root_redirects_to_dashboard_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_admin' => false,
        ]);

        $this->actingAs($user)
            ->get('/dev')
            ->assertRedirect(route('dev.dashboard'));
    }

    public function test_legacy_rab_baseline_urls_redirect_to_canonical_rapps_urls(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'is_admin' => false,
        ]);

        $client = Client::create(['name' => 'Client Redirect Test']);
        $project = Project::create([
            'code' => 'PRJ-RAPP-REDIR-001',
            'name' => 'Project Redirect Test',
            'client_id' => $client->id,
            'location' => 'Gresik',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'budget' => 10000000,
            'status' => 'Planning',
        ]);
        $rab = Rab::create([
            'project_id' => $project->id,
            'name' => 'RAPP Redirect Test',
            'status' => 'draft',
            'is_active' => false,
            'total_budget' => 0,
        ]);

        $this->actingAs($user)
            ->get("/dev/projects/{$project->id}/rab-baseline")
            ->assertRedirect(route('dev.rab-baseline.index', ['projectId' => $project->id]));

        $this->actingAs($user)
            ->get("/dev/projects/{$project->id}/rab-baseline/{$rab->id}/table")
            ->assertRedirect(route('dev.rab-baseline.table', [
                'projectId' => $project->id,
                'rabId' => $rab->id,
            ]));
    }

    public function test_approval_center_is_forbidden_for_staff_and_accessible_for_ho(): void
    {
        $ctx = $this->makeScenario();

        $this->actingAs($ctx['staff'])
            ->get(route('dev.approvals.index'))
            ->assertForbidden();

        $this->actingAs($ctx['ho'])
            ->get(route('dev.approvals.index'))
            ->assertOk()
            ->assertSee('Approval Center');
    }

    public function test_feed_includes_rab_breakdown_pending_count_for_ho(): void
    {
        $ctx = $this->makeScenario();

        $response = $this->actingAs($ctx['ho'])
            ->getJson(route('dev.notifications.feed'));

        $response->assertOk();
        $response->assertJsonPath('approvals_badge', 1);
        $response->assertJsonFragment([
            'id' => 'pending-rab-breakdown',
            'type' => 'pending',
            'title' => 'Approval RAB Breakdown',
        ]);
    }

    public function test_notification_href_is_normalized_to_current_request_host(): void
    {
        $ctx = $this->makeScenario();

        $notification = AppNotification::create([
            'user_id' => $ctx['staff']->id,
            'type' => 'system',
            'title' => 'Normalize URL',
            'message' => 'URL should follow current host',
            'href' => 'http://localhost/dev/projects/1/rab-breakdown/2',
        ]);

        $feed = $this->actingAs($ctx['staff'])
            ->withServerVariables([
                'HTTP_HOST' => 'localhost:8000',
                'SERVER_PORT' => '8000',
            ])
            ->getJson('/dev/notifications/feed');

        $feed->assertOk();
        $item = collect($feed->json('items'))->firstWhere('id', (string) $notification->id);
        $this->assertNotNull($item);
        $this->assertStringStartsWith('http://localhost:8000/', (string) ($item['href'] ?? ''));
    }

    public function test_rab_breakdown_submit_approve_reject_creates_notifications_for_expected_users(): void
    {
        $ctx = $this->makeScenario();

        // Prepare a submit-able breakdown (draft/rejected + at least 1 item).
        $ctx['rabBreakdown']->update([
            'approval_status' => 'draft',
        ]);

        RabBreakdownItem::create([
            'rab_breakdown_id' => $ctx['rabBreakdown']->id,
            'item_code' => 'A.1',
            'uraian' => 'Item test notifikasi',
            'volume_rab' => 1,
            'satuan' => 'LS',
            'unit_price' => 10000,
            'total_price' => 10000,
        ]);

        AppNotification::query()->delete();

        // Submit by staff -> notification to HO.
        $this->actingAs($ctx['staff'])
            ->post(route('dev.rab-breakdown.submit', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $ctx['rabBreakdown']->id,
            ]))
            ->assertStatus(302);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $ctx['ho']->id,
            'type' => 'approval',
            'title' => 'RAB Breakdown Diajukan',
        ]);

        // Approve by HO -> notification to submitter.
        $this->actingAs($ctx['ho'])
            ->post(route('dev.rab-breakdown.approve', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $ctx['rabBreakdown']->id,
            ]))
            ->assertStatus(302);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $ctx['staff']->id,
            'type' => 'approval',
            'title' => 'RAB Breakdown Disetujui',
        ]);

        // Set back to submitted, then reject -> notification to submitter with reason.
        $ctx['rabBreakdown']->refresh();
        $ctx['rabBreakdown']->update([
            'approval_status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => $ctx['staff']->id,
        ]);

        $reason = 'Perlu revisi volume';
        $this->actingAs($ctx['ho'])
            ->post(route('dev.rab-breakdown.reject', [
                'projectId' => $ctx['project']->id,
                'rabBreakdown' => $ctx['rabBreakdown']->id,
            ]), [
                'rejected_reason' => $reason,
            ])
            ->assertStatus(302);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $ctx['staff']->id,
            'type' => 'approval',
            'title' => 'RAB Breakdown Ditolak',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $ctx['staff']->id,
            'type' => 'approval',
            'message' => 'RAB Breakdown PEKERJAAN TANAH ditolak. Alasan: ' . $reason,
        ]);
    }
}
