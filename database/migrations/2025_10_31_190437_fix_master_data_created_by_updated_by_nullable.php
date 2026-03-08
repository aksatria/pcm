<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Untuk SQLite, kita perlu approach yang lebih sederhana
        if (DB::getDriverName() === 'sqlite') {
            // 1. Drop foreign key constraints dulu (jika ada)
            try {
                Schema::table('master_data_audits', function (Blueprint $table) {
                    $table->dropForeign(['master_data_id']);
                });
            } catch (\Exception $e) {
                // Ignore error jika constraint tidak ada
            }

            // 2. Backup data master_data
            $backupData = [];
            if (Schema::hasTable('master_data')) {
                $backupData = DB::table('master_data')->get()->toArray();
            }

            // 3. Drop tables yang bermasalah
            Schema::dropIfExists('master_data_audits');
            Schema::dropIfExists('master_code_counters');
            
            // 4. Alter table master_data dengan approach yang lebih aman
            if (Schema::hasTable('master_data')) {
                // Create temporary table dengan struktur baru
                Schema::create('master_data_temp', function (Blueprint $table) {
                    $table->id();
                    $table->string('code')->unique();
                    $table->enum('category', ['MT', 'JS', 'AT', 'HO', 'SR', 'SB']);
                    $table->string('name');
                    $table->string('unit');
                    $table->decimal('price', 15, 2);
                    $table->text('description')->nullable();
                    $table->boolean('is_active')->default(true);
                    $table->string('created_by')->nullable();
                    $table->string('updated_by')->nullable();
                    $table->timestamps();
                    $table->softDeletes();
                });

                // Copy data dari table lama ke temporary
                foreach ($backupData as $data) {
                    DB::table('master_data_temp')->insert([
                        'id' => $data->id,
                        'code' => $data->code,
                        'category' => $data->category,
                        'name' => $data->name,
                        'unit' => $data->unit,
                        'price' => $data->price,
                        'description' => $data->description,
                        'is_active' => $data->is_active,
                        'created_by' => $data->created_by ?? 'system',
                        'updated_by' => $data->updated_by ?? 'system',
                        'created_at' => $data->created_at,
                        'updated_at' => $data->updated_at,
                        'deleted_at' => $data->deleted_at,
                    ]);
                }

                // Drop table lama dan rename temporary
                Schema::dropIfExists('master_data');
                Schema::rename('master_data_temp', 'master_data');
            }

            // 5. Recreate related tables
            if (!Schema::hasTable('master_code_counters')) {
                Schema::create('master_code_counters', function (Blueprint $table) {
                    $table->string('category')->primary();
                    $table->unsignedInteger('last_number')->default(0);
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('master_data_audits')) {
                Schema::create('master_data_audits', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('master_data_id')->constrained()->onDelete('cascade');
                    $table->string('action');
                    $table->json('old_data')->nullable();
                    $table->json('new_data')->nullable();
                    $table->string('user_id')->nullable();
                    $table->string('ip_address')->nullable();
                    $table->text('user_agent')->nullable();
                    $table->timestamps();
                });
            }
            
        } else {
            // Untuk MySQL/PostgreSQL, bisa pakai ALTER TABLE
            Schema::table('master_data', function (Blueprint $table) {
                $table->string('created_by')->nullable()->change();
                $table->string('updated_by')->nullable()->change();
            });
            
            Schema::table('master_data_audits', function (Blueprint $table) {
                $table->string('user_id')->nullable()->change();
            });
        }
    }

    public function down()
    {
        // Rollback tidak diperlukan untuk perbaikan struktur
        // Karena ini adalah perbaikan data integrity
    }
};