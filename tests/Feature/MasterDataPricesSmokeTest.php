<?php

namespace Tests\Feature;

use App\Models\MasterData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataPricesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function makeMasterData(array $overrides = []): MasterData
    {
        return MasterData::create(array_merge([
            'code' => 'MT.001',
            'category' => 'MT',
            'name' => 'Semen',
            'unit' => 'zak',
            'price' => 80000,
            'description' => 'Smoke test item',
            'is_active' => true,
            'created_by' => 'seed',
            'updated_by' => 'seed',
        ], $overrides));
    }

    public function test_prices_index_endpoint_renders_without_runtime_error(): void
    {
        $user = User::factory()->create(['role' => 'ho']);
        $masterData = $this->makeMasterData();

        $response = $this->actingAs($user)->get("/dev/data/{$masterData->id}/prices");

        $response->assertOk();
        $response->assertSee('Daftar Harga');
    }

    public function test_prices_comparison_endpoint_returns_success_json(): void
    {
        $user = User::factory()->create(['role' => 'ho']);
        $masterData = $this->makeMasterData(['code' => 'JS.001', 'category' => 'JS', 'name' => 'Jasa Tukang']);

        $response = $this->actingAs($user)->getJson("/dev/data/{$masterData->id}/prices/comparison");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data',
                'master_data' => ['kode_item', 'uraian_item', 'base_price'],
            ]);
    }
}
