<?php

namespace Tests\Feature;

use App\Models\Data;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataBulkUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function makeData(array $overrides = []): Data
    {
        return Data::create(array_merge([
            'kode' => 'MT-001',
            'kategori' => 'Material',
            'kode_kategori' => 'MT',
            'uraian' => 'Semen Portland',
            'satuan' => 'zak',
            'harga' => 75000,
            'status' => true,
            'created_by' => 'test',
            'updated_by' => 'test',
        ], $overrides));
    }

    public function test_ho_can_bulk_update_data(): void
    {
        $user = User::factory()->create(['role' => 'ho']);
        $item = $this->makeData();

        $response = $this->actingAs($user)->postJson(route('dev.data.bulkUpdate'), [
            'ids' => [$item->id],
            'field' => 'price',
            'value' => '120000',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('data', [
            'id' => $item->id,
            'harga' => 120000,
            'updated_by' => $user->name,
        ]);
    }

    public function test_bulk_update_returns_422_for_invalid_payload(): void
    {
        $user = User::factory()->create(['role' => 'ho']);

        $response = $this->actingAs($user)->postJson(route('dev.data.bulkUpdate'), [
            'field' => 'price',
            'value' => '100000',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi gagal',
            ])
            ->assertJsonStructure([
                'errors' => ['ids'],
            ]);
    }

    public function test_non_ho_cannot_bulk_update_data(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $item = $this->makeData(['kode' => 'MT-002', 'uraian' => 'Pasir Beton']);

        $response = $this->actingAs($user)->postJson(route('dev.data.bulkUpdate'), [
            'ids' => [$item->id],
            'field' => 'price',
            'value' => '90000',
        ]);

        $response->assertForbidden();
    }

    public function test_bulk_update_category_updates_code_and_label_fields(): void
    {
        $user = User::factory()->create(['role' => 'ho']);
        $item = $this->makeData([
            'kode' => 'MT-003',
            'kategori' => 'Material',
            'kode_kategori' => 'MT',
            'uraian' => 'Batu Split',
        ]);

        $response = $this->actingAs($user)->postJson(route('dev.data.bulkUpdate'), [
            'ids' => [$item->id],
            'field' => 'category',
            'value' => 'JS',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('data', [
            'id' => $item->id,
            'kode_kategori' => 'JS',
            'kategori' => 'Jasa',
            'updated_by' => $user->name,
        ]);
    }
}
