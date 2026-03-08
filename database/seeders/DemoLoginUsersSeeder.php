<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoLoginUsersSeeder extends Seeder
{
    /**
     * Keep login hints stable with explicit roles:
     * - HO: admin@pcm.local / password
     * - Staff: staff@demo.test / password
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@pcm.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'role' => 'ho',
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@demo.test'],
            [
                'name' => 'Staff Demo',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'is_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        // Avoid accidental HO duplication for known legacy account.
        $legacy = User::where('email', 'adhe5381@gmail.com')->first();
        if ($legacy) {
            $legacy->role = 'staff';
            $legacy->is_admin = false;
            $legacy->save();
        }
    }
}

