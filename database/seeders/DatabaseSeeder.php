<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Untuk SQLite, tidak bisa menggunakan SET FOREIGN_KEY_CHECKS
        // Gunakan pendekatan yang berbeda berdasarkan driver database
        
        if (App::environment('local') && config('database.default') === 'sqlite') {
            $this->command->info('SQLite detected. Using SQLite-compatible seeding...');
            $this->seedForSQLite();
        } else {
            // Untuk MySQL/PostgreSQL
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            $this->seedForMySQL();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Seeding khusus untuk SQLite
     */
    protected function seedForSQLite(): void
    {
        // Hapus data dalam urutan yang benar untuk menghindari constraint violation
        // Mulai dari tabel yang memiliki foreign key ke tabel lain
        
        // Pastikan kita berada di environment local/development
        if (!App::environment(['local', 'testing'])) {
            $this->command->error('Seeding with truncate is only allowed in local/testing environment!');
            return;
        }
        
        // Delete data (bukan truncate untuk menghindari SQLite issues)
        $this->deleteTablesData();
        
        // Jalankan seeders
        $this->call([
            // Seeder untuk RAB Breakdown
            RabBreakdownSeeder::class,
            
            // Seeder untuk Budget Control & Voucher (DIPERBAIKI)
            BudgetControlVoucherSeeder::class,

            // Seeder akun demo login agar konsisten dengan halaman login
            DemoLoginUsersSeeder::class,
            
            // Seeder lainnya (jika ada)
            // RoleSeeder::class,
            // PermissionSeeder::class,
            // UserSeeder::class,
            // ProvinceSeeder::class,
            // ClientSeeder::class,
            // CategorySeeder::class,
            // ProjectSeeder::class,
            // ProjectFileSeeder::class,
            // DummyDataSeeder::class,
        ]);
        
        $this->command->info('SQLite seeding completed successfully!');
        $this->command->info('Note: Budget Control categories use: MT, JS, SB, AT, SR, HO, RN, OTHER');
    }
    
    /**
     * Seeding untuk MySQL/PostgreSQL
     */
    protected function seedForMySQL(): void
    {
        // Hapus data dengan truncate (hanya untuk development)
        if (App::environment(['local', 'testing'])) {
            $this->truncateTables();
        }
        
        // Jalankan seeders
        $this->call([
            // Seeder untuk RAB Breakdown
            RabBreakdownSeeder::class,
            
            // Seeder untuk Budget Control & Voucher (DIPERBAIKI)
            BudgetControlVoucherSeeder::class,

            // Seeder akun demo login agar konsisten dengan halaman login
            DemoLoginUsersSeeder::class,
            
            // Seeder lainnya
            // RoleSeeder::class,
            // PermissionSeeder::class,
            // UserSeeder::class,
            // ProvinceSeeder::class,
            // ClientSeeder::class,
            // CategorySeeder::class,
            // ProjectSeeder::class,
            // ProjectFileSeeder::class,
            // DummyDataSeeder::class,
        ]);
        
        $this->command->info('Note: Budget Control categories use: MT, JS, SB, AT, SR, HO, RN, OTHER');
    }

    /**
     * Hapus data tabel untuk SQLite (menggunakan DELETE bukan TRUNCATE)
     */
    protected function deleteTablesData(): void
    {
        // Urutan DELETE penting untuk constraint foreign key
        $tablesToDelete = [
            // Tabel Budget Control & Voucher System (urut dari child ke parent)
            'voucher_items',
            'vouchers',
            'budget_controls',
            'vendors',
            
            // Tabel RAB Breakdown dengan foreign key
            'rab_breakdown_budget_sources',
            'rab_breakdown_items',
            
            // Tabel lainnya dengan foreign key
            'project_files',
            'rab_breakdowns',
            
            // Tabel utama (dihapus setelahnya)
            'projects',
            'clients',
            'categories',
            'provinces',
            'users',
            'permissions',
            'roles',
        ];
        
        foreach ($tablesToDelete as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                try {
                    DB::table($table)->delete();
                    $this->command->info("✓ Deleted data from: {$table}");
                } catch (\Exception $e) {
                    $this->command->warn("Failed to delete from {$table}: {$e->getMessage()}");
                }
            }
        }
        
        // Reset auto-increment untuk SQLite
        if (config('database.default') === 'sqlite') {
            try {
                DB::statement('DELETE FROM sqlite_sequence');
                $this->command->info('✓ Reset SQLite auto-increment sequence');
            } catch (\Exception $e) {
                $this->command->warn("Failed to reset SQLite sequence: {$e->getMessage()}");
            }
        }
    }

    /**
     * Truncate tabel untuk MySQL/PostgreSQL (hanya development)
     */
    protected function truncateTables(): void
    {
        // Ini hanya untuk development/testing
        if (!App::environment(['local', 'testing'])) {
            $this->command->error('Truncate is only allowed in local/testing environment!');
            return;
        }
        
        $tablesToTruncate = [
            // Tabel Budget Control & Voucher (dimulai dari child tables)
            'voucher_items',
            'vouchers',
            'budget_controls',
            'vendors',
            
            // Tabel RAB Breakdown
            'rab_breakdown_budget_sources',
            'rab_breakdown_items',
            
            // Tabel lainnya
            'project_files',
            'rab_breakdowns',
            'projects',
            'clients',
            'categories',
            'provinces',
            'users',
            'permissions',
            'roles',
        ];
        
        foreach ($tablesToTruncate as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                try {
                    DB::table($table)->truncate();
                    $this->command->info("✓ Truncated table: {$table}");
                } catch (\Exception $e) {
                    $this->command->warn("Failed to truncate {$table}: {$e->getMessage()}");
                }
            }
        }
    }
}

