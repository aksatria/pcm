<?php

namespace Tests\Feature;

use App\Models\MasterData;
use App\Models\MasterItemPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataPricesWriteTest extends TestCase
{
    use RefreshDatabase;

    private function makeMasterData(array $overrides = []): MasterData
    {
        return MasterData::create(array_merge([
            'code' => 'MT.010',
            'category' => 'MT',
            'name' => 'Pasir',
            'unit' => 'm3',
            'price' => 200000,
            'description' => 'Write test item',
            'is_active' => true,
            'created_by' => 'seed',
            'updated_by' => 'seed',
        ], $overrides));
    }

    public function test_ho_can_create_price(): void
    {
        $user = User::factory()->create(['role' => 'ho']);
        $masterData = $this->makeMasterData();

        $response = $this->actingAs($user)->postJson("/dev/data/{$masterData->id}/prices", [
            'price' => 225000,
            'effective_from' => now()->toDateString(),
            'effective_to' => now()->addDays(30)->toDateString(),
            'supplier_id' => 'SUP-1',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('master_item_prices', [
            'master_data_id' => $masterData->id,
            'price' => 225000,
            'supplier_id' => 'SUP-1',
        ]);
    }

    public function test_ho_can_update_price(): void
    {
        $user = User::factory()->create(['role' => 'ho']);
        $masterData = $this->makeMasterData(['code' => 'MT.011']);
        $price = MasterItemPrice::create([
            'master_data_id' => $masterData->id,
            'price' => 200000,
            'effective_from' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson("/dev/data/prices/{$price->id}", [
            'price' => 210000,
            'effective_from' => now()->toDateString(),
            'effective_to' => now()->addDays(10)->toDateString(),
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('master_item_prices', [
            'id' => $price->id,
            'price' => 210000,
        ]);
    }

    public function test_ho_can_delete_price(): void
    {
        $user = User::factory()->create(['role' => 'ho']);
        $masterData = $this->makeMasterData(['code' => 'MT.012']);
        $price = MasterItemPrice::create([
            'master_data_id' => $masterData->id,
            'price' => 230000,
        ]);

        $response = $this->actingAs($user)->deleteJson("/dev/data/prices/{$price->id}");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('master_item_prices', ['id' => $price->id]);
    }

    public function test_non_ho_cannot_create_update_or_delete_price(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $masterData = $this->makeMasterData(['code' => 'MT.013']);
        $price = MasterItemPrice::create([
            'master_data_id' => $masterData->id,
            'price' => 240000,
        ]);

        $this->actingAs($user)
            ->postJson("/dev/data/{$masterData->id}/prices", ['price' => 250000])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson("/dev/data/prices/{$price->id}", ['price' => 260000])
            ->assertForbidden();

        $this->actingAs($user)
            ->deleteJson("/dev/data/prices/{$price->id}")
            ->assertForbidden();
    }
}
