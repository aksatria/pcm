<?php
// database/seeders/MasterItemPriceSeeder.php

namespace Database\Seeders;

use App\Models\MasterItem;
use App\Models\MasterItemPrice;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MasterItemPriceSeeder extends Seeder
{
    public function run(): void
    {
        // Get provinces - dengan error handling
        $jawaTimur = Province::where('code', 'ID-JI')->first();
        $papua = Province::where('code', 'ID-PA')->first();
        $jakarta = Province::where('code', 'ID-JK')->first();
        $bali = Province::where('code', 'ID-BA')->first();

        // ✅ PERBAIKAN: Cek jika provinsi tidak ditemukan
        if (!$jawaTimur || !$papua || !$jakarta || !$bali) {
            $this->command->error('Some provinces not found. Please run ProvincesSeeder first.');
            return;
        }

        // Get items - dengan error handling
        $semen = MasterItem::where('code', 'MT-001')->first();
        $pasir = MasterItem::where('code', 'MT-002')->first();
        $batuSplit = MasterItem::where('code', 'MT-003')->first();
        $pekerja = MasterItem::where('code', 'JS-001')->first();
        $tukang = MasterItem::where('code', 'JS-002')->first();
        $excavator = MasterItem::where('code', 'AT-001')->first();

        // ✅ PERBAIKAN: Cek jika items tidak ditemukan
        if (!$semen || !$pasir || !$batuSplit || !$pekerja || !$tukang || !$excavator) {
            $this->command->error('Some master items not found. Please run MasterItemSeeder first.');
            return;
        }

        $prices = [
            // Semen - different prices per province
            [
                'master_item_id' => $semen->id,
                'province_id' => $jawaTimur->id,
                'price' => 58000,
                'effective_from' => Carbon::now()->subMonths(3),
                'effective_to' => Carbon::now()->addMonths(3),
            ],
            [
                'master_item_id' => $semen->id, 
                'province_id' => $papua->id,
                'price' => 78000,
                'effective_from' => Carbon::now()->subMonths(2),
                'effective_to' => Carbon::now()->addMonths(4),
            ],
            [
                'master_item_id' => $semen->id,
                'province_id' => $jakarta->id, 
                'price' => 62000,
                'effective_from' => Carbon::now()->subMonth(),
                'effective_to' => Carbon::now()->addMonths(2),
            ],
            [
                'master_item_id' => $semen->id,
                'province_id' => $bali->id,
                'price' => 59000,
                'effective_from' => Carbon::now()->subMonths(2),
            ],

            // Pasir - different prices per province
            [
                'master_item_id' => $pasir->id,
                'province_id' => $jawaTimur->id,
                'price' => 450000,
                'effective_from' => Carbon::now()->subMonths(2),
            ],
            [
                'master_item_id' => $pasir->id,
                'province_id' => $papua->id,
                'price' => 600000,
                'effective_from' => Carbon::now()->subMonths(1),
            ],

            // Batu Split - different prices per province
            [
                'master_item_id' => $batuSplit->id,
                'province_id' => $jawaTimur->id,
                'price' => 320000,
                'effective_from' => Carbon::now()->subMonths(2),
            ],
            [
                'master_item_id' => $batuSplit->id,
                'province_id' => $papua->id,
                'price' => 450000,
                'effective_from' => Carbon::now()->subMonths(1),
            ],

            // Pekerja - global price (no province)
            [
                'master_item_id' => $pekerja->id,
                'province_id' => null,
                'price' => 125000,
                'effective_from' => Carbon::now()->subMonths(1),
                'effective_to' => Carbon::now()->addMonths(6),
            ],

            // Tukang - global price (no province)
            [
                'master_item_id' => $tukang->id,
                'province_id' => null,
                'price' => 155000,
                'effective_from' => Carbon::now()->subMonths(1),
            ],

            // Excavator - different prices per province
            [
                'master_item_id' => $excavator->id,
                'province_id' => $jawaTimur->id,
                'price' => 300000,
                'effective_from' => Carbon::now()->subMonths(2),
            ],
            [
                'master_item_id' => $excavator->id,
                'province_id' => $papua->id,
                'price' => 420000,
                'effective_from' => Carbon::now()->subMonths(1),
            ],
        ];

        foreach ($prices as $price) {
            MasterItemPrice::create($price);
        }

        $this->command->info('Seeded master item prices with province-specific and global pricing.');
    }
}