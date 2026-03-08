<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateMasterCodeCountersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasTable('master_code_counters')) {
            Schema::create('master_code_counters', function (Blueprint $table) {
                $table->id();
                $table->string('category', 10)->unique()->comment('Kategori: MT, JS, AT, HO, SR, SB');
                $table->integer('last_number')->default(0)->comment('Nomor terakhir yang digunakan');
                $table->text('description')->nullable()->comment('Deskripsi kategori');
                $table->string('created_by')->default('system');
                $table->string('updated_by')->default('system');
                $table->timestamps();

                // Indexes
                $table->index('category');
                $table->index('last_number');
            });
        } else {
            Schema::table('master_code_counters', function (Blueprint $table) {
                if (!Schema::hasColumn('master_code_counters', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('master_code_counters', 'created_by')) {
                    $table->string('created_by')->default('system');
                }
                if (!Schema::hasColumn('master_code_counters', 'updated_by')) {
                    $table->string('updated_by')->default('system');
                }
            });
        }

        // Insert initial data untuk semua kategori
        $categories = [
            ['category' => 'MT', 'last_number' => 0, 'description' => 'Material'],
            ['category' => 'JS', 'last_number' => 0, 'description' => 'Jasa'],
            ['category' => 'AT', 'last_number' => 0, 'description' => 'Alat'],
            ['category' => 'HO', 'last_number' => 0, 'description' => 'Head Office'],
            ['category' => 'SR', 'last_number' => 0, 'description' => 'Sirkulasi'],
            ['category' => 'SB', 'last_number' => 0, 'description' => 'SubKon'],
        ];

        foreach ($categories as $category) {
            DB::table('master_code_counters')->updateOrInsert(
                ['category' => $category['category']],
                $category
            );
        }

        // Juga update counter berdasarkan data yang sudah ada di master_data
        $this->syncExistingCounters();
    }

    /**
     * Sync counter dengan data yang sudah ada
     */
    private function syncExistingCounters()
    {
        try {
            // Ambil nomor terbesar untuk setiap kategori dari master_data
            $counters = DB::table('master_data')
                ->select('category', DB::raw('MAX(CAST(SUBSTRING_INDEX(code, ".", -1) AS UNSIGNED)) as max_number'))
                ->where('code', 'REGEXP', '^[A-Z]{2}\.[0-9]+$')
                ->groupBy('category')
                ->get();

            foreach ($counters as $counter) {
                if ($counter->max_number > 0) {
                    DB::table('master_code_counters')
                        ->where('category', $counter->category)
                        ->update(['last_number' => $counter->max_number]);
                }
            }
        } catch (\Exception $e) {
            // Jika ada error, skip saja - table master_data mungkin belum ada
            \Log::info('Sync counters skipped: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('master_code_counters');
    }
}
