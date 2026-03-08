<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Data;
use App\Models\Project;
use App\Models\Rab;
use App\Models\RabItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RappReapprovalFlowTest extends TestCase
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
            'name' => 'Client Test',
        ]);

        $project = Project::create([
            'code' => 'PRJ-TEST-001',
            'name' => 'Project Test',
            'client_id' => $client->id,
            'location' => 'Pekanbaru',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'budget' => 100000000,
            'status' => 'Planning',
        ]);

        $rab = Rab::create([
            'project_id' => $project->id,
            'name' => 'RAPP Test',
            'version' => '1.0',
            'status' => 'approved',
            'total_budget' => 0,
            'created_by' => $staff->id,
            'approved_by' => $ho->id,
            'approved_at' => now(),
        ]);

        $dataA = Data::create([
            'kode' => 'MT-001',
            'kategori' => 'Material',
            'kode_kategori' => 'MT',
            'uraian' => 'Semen',
            'satuan' => 'zak',
            'harga' => 80000,
            'status' => true,
            'created_by' => 'test',
            'updated_by' => 'test',
        ]);

        $dataB = Data::create([
            'kode' => 'MT-002',
            'kategori' => 'Material',
            'kode_kategori' => 'MT',
            'uraian' => 'Pasir',
            'satuan' => 'm3',
            'harga' => 200000,
            'status' => true,
            'created_by' => 'test',
            'updated_by' => 'test',
        ]);

        $item = RabItem::create([
            'rab_id' => $rab->id,
            'data_id' => $dataA->id,
            'item_type' => 'MT',
            'volume' => 2,
            'satuan' => 'zak',
            'harga_satuan' => 80000,
            'urutan' => 1,
            'created_by' => $staff->id,
        ]);

        $rab->updateTotals();
        $rab->status = 'approved';
        $rab->approved_by = $ho->id;
        $rab->approved_at = now();
        $rab->save();

        return compact('staff', 'ho', 'project', 'rab', 'item', 'dataA', 'dataB');
    }

    public function test_staff_can_update_item_on_approved_rapp_and_status_returns_to_submitted(): void
    {
        $ctx = $this->makeScenario();

        $response = $this->actingAs($ctx['staff'])->putJson(
            route('dev.rab-baseline.items.update', [$ctx['project']->id, $ctx['rab']->id, $ctx['item']->id]),
            [
                'field' => 'volume',
                'value' => 3,
            ]
        );

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('rabs', [
            'id' => $ctx['rab']->id,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('rab_items', [
            'id' => $ctx['item']->id,
            'volume' => 3,
        ]);
    }

    public function test_staff_can_quick_add_on_approved_rapp_and_status_returns_to_submitted(): void
    {
        $ctx = $this->makeScenario();

        $response = $this->actingAs($ctx['staff'])->postJson(
            route('dev.rab-baseline.quick-add', [$ctx['project']->id, $ctx['rab']->id]),
            [
                'data_id' => $ctx['dataB']->id,
                'volume' => 1,
                'harga_satuan' => 100000,
            ]
        );

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('rabs', [
            'id' => $ctx['rab']->id,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('rab_items', [
            'rab_id' => $ctx['rab']->id,
            'data_id' => $ctx['dataB']->id,
        ]);
    }

    public function test_staff_can_delete_item_on_approved_rapp_and_status_returns_to_submitted(): void
    {
        $ctx = $this->makeScenario();

        $response = $this->actingAs($ctx['staff'])->delete(
            route('dev.rab-baseline.items.destroy', [$ctx['project']->id, $ctx['rab']->id, $ctx['item']->id])
        );

        $response->assertStatus(302);

        $this->assertDatabaseHas('rabs', [
            'id' => $ctx['rab']->id,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseMissing('rab_items', [
            'id' => $ctx['item']->id,
        ]);
    }

    public function test_ho_can_update_item_on_approved_rapp_and_status_returns_to_submitted(): void
    {
        $ctx = $this->makeScenario();

        $response = $this->actingAs($ctx['ho'])->putJson(
            route('dev.rab-baseline.items.update', [$ctx['project']->id, $ctx['rab']->id, $ctx['item']->id]),
            [
                'field' => 'harga_satuan',
                'value' => 90000,
            ]
        );

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('rabs', [
            'id' => $ctx['rab']->id,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('rab_items', [
            'id' => $ctx['item']->id,
            'harga_satuan' => 90000,
        ]);
    }

    public function test_staff_can_bulk_delete_on_approved_rapp_and_status_returns_to_submitted(): void
    {
        $ctx = $this->makeScenario();

        $response = $this->actingAs($ctx['staff'])->delete(
            route('dev.rab-baseline.items.bulk.delete', [$ctx['project']->id, $ctx['rab']->id]),
            [
                'item_ids' => [$ctx['item']->id],
            ]
        );

        $response->assertStatus(302);

        $this->assertDatabaseHas('rabs', [
            'id' => $ctx['rab']->id,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseMissing('rab_items', [
            'id' => $ctx['item']->id,
        ]);
    }

    public function test_ho_can_bulk_delete_on_approved_rapp_and_status_returns_to_submitted(): void
    {
        $ctx = $this->makeScenario();

        $response = $this->actingAs($ctx['ho'])->delete(
            route('dev.rab-baseline.items.bulk.delete', [$ctx['project']->id, $ctx['rab']->id]),
            [
                'item_ids' => [$ctx['item']->id],
            ]
        );

        $response->assertStatus(302);

        $this->assertDatabaseHas('rabs', [
            'id' => $ctx['rab']->id,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseMissing('rab_items', [
            'id' => $ctx['item']->id,
        ]);
    }

    public function test_ho_can_import_excel_on_approved_rapp_and_status_returns_to_submitted(): void
    {
        $ctx = $this->makeScenario();

        $csv = implode("\n", [
            'Kode,Nama Item,Sat,Volume RAPP,Harga Satuan',
            'MT-002,Pasir,m3,2,150000',
        ]);

        $file = UploadedFile::fake()->createWithContent('rapp-import.csv', $csv);

        $response = $this->actingAs($ctx['ho'])->post(
            route('dev.rab-baseline.import.excel', [$ctx['project']->id, $ctx['rab']->id]),
            [
                'excel_file' => $file,
                'import_mode' => 'skip',
                'preview' => '0',
                'auto_fix_thousand' => '0',
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rabs', [
            'id' => $ctx['rab']->id,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('rab_items', [
            'rab_id' => $ctx['rab']->id,
            'data_id' => $ctx['dataB']->id,
            'volume' => 2,
            'harga_satuan' => 150000,
        ]);
    }

    public function test_staff_cannot_import_excel_due_to_route_role_middleware(): void
    {
        $ctx = $this->makeScenario();

        $csv = implode("\n", [
            'Kode,Nama Item,Sat,Volume RAPP,Harga Satuan',
            'MT-002,Pasir,m3,2,150000',
        ]);

        $file = UploadedFile::fake()->createWithContent('rapp-import.csv', $csv);

        $response = $this->actingAs($ctx['staff'])->post(
            route('dev.rab-baseline.import.excel', [$ctx['project']->id, $ctx['rab']->id]),
            [
                'excel_file' => $file,
                'import_mode' => 'skip',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('rabs', [
            'id' => $ctx['rab']->id,
            'status' => 'approved',
        ]);
    }
}
