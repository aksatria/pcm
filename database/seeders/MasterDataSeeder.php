<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    public function run()
    {
        // Insert initial counter data
        $categories = ['MT', 'JS', 'AT', 'HO', 'SR', 'SB'];
        foreach ($categories as $category) {
            DB::table('master_code_counters')->insert([
                'category' => $category,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Get first user ID
        $userId = DB::table('users')->value('id') ?? 1;

        // Insert sample master data
        DB::table('master_data')->insert([
            [
                'code' => 'MT.001',
                'category' => 'MT',
                'name' => 'Semen Portland',
                'unit' => 'zak',
                'price' => 75000.00,
                'description' => 'Semen merek Tiga Roda',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'JS.001', 
                'category' => 'JS',
                'name' => 'Pekerja Harian',
                'unit' => 'hari',
                'price' => 120000.00,
                'description' => 'Tenaga kerja harian',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'AT.001',
                'category' => 'AT',
                'name' => 'Excavator Mini',
                'unit' => 'hari',
                'price' => 1500000.00,
                'description' => 'Sewa excavator mini',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}