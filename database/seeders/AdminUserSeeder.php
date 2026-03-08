<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ===== HO #1 =====
        $ho1Email = 'admin@pcm.local';

        $ho1 = [
            'name' => 'Administrator',
            'email' => $ho1Email,
            'password' => Hash::make('password'),
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // role ho (kalau ada kolom role)
        if (Schema::hasColumn('users', 'role')) {
            $ho1['role'] = 'ho';
        }

        // tetap set is_admin untuk kompatibilitas (kalau ada kolomnya)
        if (Schema::hasColumn('users', 'is_admin')) {
            $ho1['is_admin'] = true;
        }

        if (DB::table('users')->where('email', $ho1Email)->exists()) {
            DB::table('users')->where('email', $ho1Email)->update($ho1);
        } else {
            DB::table('users')->insert($ho1);
        }

        // ===== HO #2 (akun kamu yang ada) =====
        $ho2Email = 'adhe5381@gmail.com';

        $ho2 = [
            'name' => 'Adhe',
            'email' => $ho2Email,
            'password' => Hash::make('membanguN5381'),
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('users', 'role')) {
            $ho2['role'] = 'ho';
        }

        if (Schema::hasColumn('users', 'is_admin')) {
            $ho2['is_admin'] = true;
        }

        if (DB::table('users')->where('email', $ho2Email)->exists()) {
            DB::table('users')->where('email', $ho2Email)->update($ho2);
        } else {
            DB::table('users')->insert($ho2);
        }

        $this->command?->info('HO users setup completed!');
        $this->command?->info('HO 1: admin@pcm.local / password');
        $this->command?->info('HO 2: adhe5381@gmail.com / membanguN5381');
    }
}
