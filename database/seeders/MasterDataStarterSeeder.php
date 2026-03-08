<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MasterDataStarterSeeder extends Seeder
{
    public function run()
    {
        // First, ensure we have at least one user
        $userId = DB::table('users')->value('id');
        
        if (!$userId) {
            $userId = DB::table('users')->insertGetId([
                'name' => 'System Administrator',
                'email' => 'admin@pcm.local',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Insert initial counter data only if table is empty
        if (DB::table('master_code_counters')->count() === 0) {
            $categories = ['MT', 'JS', 'AT', 'HO', 'SR', 'SB'];
            foreach ($categories as $category) {
                DB::table('master_code_counters')->insert([
                    'category' => $category,
                    'last_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            $this->command->info('Master code counters created.');
        }

        // Insert sample master data only if table is empty
        if (DB::table('master_data')->count() === 0) {
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
                ],
                [
                    'code' => 'HO.001',
                    'category' => 'HO',
                    'name' => 'Management Fee',
                    'unit' => 'proyek',
                    'price' => 5000000.00,
                    'description' => 'Biaya management proyek',
                    'is_active' => true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'code' => 'SR.001',
                    'category' => 'SR',
                    'name' => 'Transport Material',
                    'unit' => 'trip',
                    'price' => 350000.00,
                    'description' => 'Biaya transportasi material',
                    'is_active' => true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);
            $this->command->info('Sample master data created.');
        }

        $this->command->info('Master Data Starter seeding completed successfully!');
    }
}