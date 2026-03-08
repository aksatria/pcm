<?php
// database/seeders/MasterItemSeeder.php

namespace Database\Seeders;

use App\Models\MasterItem;
use Illuminate\Database\Seeder;

class MasterItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // MATERIAL (MT)
            [
                'code' => 'MT-001',
                'name' => 'Semen Portland PC 50kg',
                'unit' => 'zak',
                'category' => 'MT',
                'base_price' => 65000,
                'notes' => 'Semen Portland Type I'
            ],
            [
                'code' => 'MT-002', 
                'name' => 'Pasir Lumajang',
                'unit' => 'm³',
                'category' => 'MT',
                'base_price' => 280000,
                'notes' => 'Pasir cor berkualitas'
            ],
            [
                'code' => 'MT-003',
                'name' => 'Batu Split 1-2',
                'unit' => 'm³', 
                'category' => 'MT',
                'base_price' => 320000,
                'notes' => 'Batu split ukuran 1-2 cm'
            ],
            [
                'code' => 'MT-004',
                'name' => 'Bata Merah Press',
                'unit' => 'bh',
                'category' => 'MT', 
                'base_price' => 850,
                'notes' => 'Bata merah press oven'
            ],

            // JASA (JS)
            [
                'code' => 'JS-001',
                'name' => 'Pekerja Harian',
                'unit' => 'OH',
                'category' => 'JS',
                'base_price' => 120000,
                'notes' => 'Tenaga harian terampil'
            ],
            [
                'code' => 'JS-002',
                'name' => 'Tukang Batu',
                'unit' => 'OH', 
                'category' => 'JS',
                'base_price' => 150000,
                'notes' => 'Tukang batu berpengalaman'
            ],
            [
                'code' => 'JS-003',
                'name' => 'Mandor',
                'unit' => 'OH',
                'category' => 'JS',
                'base_price' => 200000,
                'notes' => 'Mandor lapangan'
            ],

            // ALAT (AT)
            [
                'code' => 'AT-001',
                'name' => 'Sewa Excavator PC 75',
                'unit' => 'jam',
                'category' => 'AT',
                'base_price' => 320000,
                'notes' => 'Excavator mini PC 75'
            ],
            [
                'code' => 'AT-002',
                'name' => 'Sewa Vibrator Roller',
                'unit' => 'hari',
                'category' => 'AT',
                'base_price' => 850000, 
                'notes' => 'Vibrator roller 1 ton'
            ],
        ];

        foreach ($items as $item) {
            MasterItem::create($item);
        }

        $this->command->info('Seeded master items with categories: Material, Jasa, Alat.');
    }
}