<?php

namespace Database\Seeders;

use App\Models\MasterCodeCounter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterCodeCounterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['category' => 'MT', 'description' => 'Counter untuk kategori Material'],
            ['category' => 'JS', 'description' => 'Counter untuk kategori Jasa'],
            ['category' => 'AT', 'description' => 'Counter untuk kategori Alat'],
            ['category' => 'HO', 'description' => 'Counter untuk kategori Head Office'],
            ['category' => 'SR', 'description' => 'Counter untuk kategori Sirkulasi'],
            ['category' => 'SB', 'description' => 'Counter untuk kategori SubKon'],
        ];

        foreach ($categories as $category) {
            MasterCodeCounter::firstOrCreate(
                ['category' => $category['category']],
                [
                    'last_number' => 0,
                    'description' => $category['description'],
                    'created_by' => 'system',
                    'updated_by' => 'system'
                ]
            );
        }
    }
}