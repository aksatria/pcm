<?php

namespace Tests\Feature;

use App\Models\MasterData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDataAliasCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeMasterData(array $overrides = []): MasterData
    {
        return MasterData::create(array_merge([
            'code' => 'MT.100',
            'category' => 'MT',
            'name' => 'Semen Portland',
            'unit' => 'zak',
            'price' => 85000,
            'description' => 'Compat API test item',
            'is_active' => true,
            'created_by' => 'test',
            'updated_by' => 'test',
        ], $overrides));
    }

    public function test_index_endpoint_returns_json_on_new_and_legacy_paths(): void
    {
        $this->makeMasterData();

        $new = $this->getJson('/api/data');
        $legacy = $this->getJson('/api/master-data');

        $new->assertOk()->assertJsonStructure([
            'success',
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'category_counts',
            'kategori_list',
        ]);

        $legacy->assertOk()->assertJsonStructure([
            'success',
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'category_counts',
            'kategori_list',
        ]);
    }

    public function test_show_endpoint_is_accessible_from_new_and_legacy_paths(): void
    {
        $item = $this->makeMasterData(['code' => 'JS.201', 'category' => 'JS', 'name' => 'Jasa Tukang']);

        $new = $this->getJson("/api/data/{$item->id}");
        $legacy = $this->getJson("/api/master-data/{$item->id}");

        $new->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $item->id,
                    'code' => 'JS.201',
                ],
            ]);

        $legacy->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $item->id,
                    'code' => 'JS.201',
                ],
            ]);
    }

    public function test_generate_kode_endpoint_works_on_new_and_legacy_paths(): void
    {
        $new = $this->getJson('/api/data/generate/kode?category=MT');
        $legacy = $this->getJson('/api/master-data/generate/kode?category=MT');

        $new->assertOk()
            ->assertJson(['success' => true]);

        $legacy->assertOk()
            ->assertJson(['success' => true]);

        $this->assertStringStartsWith('MT.', $new->json('kode'));
        $this->assertStringStartsWith('MT.', $legacy->json('kode'));
    }

    public function test_export_template_endpoint_works_on_new_and_legacy_paths(): void
    {
        $new = $this->getJson('/api/data/export/template');
        $legacy = $this->getJson('/api/master-data/export/template');

        $new->assertOk()->assertJson(['success' => true]);
        $legacy->assertOk()->assertJson(['success' => true]);
    }

    public function test_advanced_endpoints_work_on_new_and_legacy_paths(): void
    {
        $this->makeMasterData();

        $newSearch = $this->getJson('/api/data/advanced/search?q=Semen');
        $legacySearch = $this->getJson('/api/master-data/advanced/search?q=Semen');
        $newAnalysis = $this->getJson('/api/data/advanced/price-analysis');
        $legacyAnalysis = $this->getJson('/api/master-data/advanced/price-analysis');

        $newSearch->assertOk()->assertJsonStructure(['success', 'data']);
        $legacySearch->assertOk()->assertJsonStructure(['success', 'data']);

        $newAnalysis->assertOk()->assertJsonStructure([
            'success',
            'data' => ['category_stats', 'price_changes', 'summary' => ['avg_price', 'max_price', 'min_price']],
        ]);
        $legacyAnalysis->assertOk()->assertJsonStructure([
            'success',
            'data' => ['category_stats', 'price_changes', 'summary' => ['avg_price', 'max_price', 'min_price']],
        ]);
    }

    public function test_generate_kode_returns_400_for_missing_or_invalid_category_on_both_paths(): void
    {
        $newMissing = $this->getJson('/api/data/generate/kode');
        $legacyMissing = $this->getJson('/api/master-data/generate/kode');
        $newInvalid = $this->getJson('/api/data/generate/kode?category=XX');
        $legacyInvalid = $this->getJson('/api/master-data/generate/kode?category=XX');

        $newMissing->assertStatus(400)->assertJson(['success' => false]);
        $legacyMissing->assertStatus(400)->assertJson(['success' => false]);
        $newInvalid->assertStatus(400)->assertJson(['success' => false]);
        $legacyInvalid->assertStatus(400)->assertJson(['success' => false]);
    }

    public function test_show_returns_404_for_missing_id_on_both_paths(): void
    {
        $new = $this->getJson('/api/data/999999');
        $legacy = $this->getJson('/api/master-data/999999');

        $new->assertStatus(404)->assertJson(['success' => false]);
        $legacy->assertStatus(404)->assertJson(['success' => false]);
    }

    public function test_import_requires_file_on_both_paths(): void
    {
        $new = $this->postJson('/api/data/import', []);
        $legacy = $this->postJson('/api/master-data/import', []);

        $new->assertStatus(422)->assertJsonValidationErrors(['file']);
        $legacy->assertStatus(422)->assertJsonValidationErrors(['file']);
    }
}
