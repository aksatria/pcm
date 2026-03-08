<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // STAFF DEFAULT
        $email = 'staff@demo.test';

        $data = [
            'name' => 'Staff Proyek',
            'email' => $email,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Kalau kolom role sudah ada, isi staff
        if (Schema::hasColumn('users', 'role')) {
            $data['role'] = 'staff';
        }

        // Insert jika belum ada
        if (!DB::table('users')->where('email', $email)->exists()) {
            DB::table('users')->insert($data);
        }
    }
}
