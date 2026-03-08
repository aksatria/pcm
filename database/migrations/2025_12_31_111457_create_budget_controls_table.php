<?php
// database/migrations/2025_01_20_000001_create_budget_controls_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('budget_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('rab_id')->nullable()->constrained()->onDelete('set null');
            $table->string('kode', 20)->index(); // MT-001, JS-001, dll
            $table->text('uraian');
            $table->string('satuan', 20);
            
            // RAPP BUDGET (PLAN)
            $table->decimal('volume_plan', 15, 4)->default(0);
            $table->decimal('harga_satuan_plan', 15, 2)->default(0);
            $table->decimal('jumlah_plan', 15, 2)->default(0);
            
            // REALISASI (ACTUAL)
            $table->decimal('volume_real', 15, 4)->default(0);
            $table->decimal('jumlah_real', 15, 2)->default(0);
            
            // SISA (CALCULATED - akan pakai generated column atau accessor)
            $table->decimal('volume_sisa', 15, 4)->storedAs('volume_plan - volume_real');
            $table->decimal('jumlah_sisa', 15, 2)->storedAs('jumlah_plan - jumlah_real');
            
            // PERSENTASE REALISASI
            $table->decimal('percentage', 5, 2)->storedAs(
                'CASE WHEN jumlah_plan > 0 THEN (jumlah_real / jumlah_plan) * 100 ELSE 0 END'
            );
            
            // KATEGORI (sesuai Excel)
            $table->enum('kategori', ['MT', 'JS', 'SB', 'AT', 'SR', 'HO', 'RN', 'OTHER'])->default('MT');
            
            // STATUS TRACKING
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('active');
            $table->boolean('is_selected')->default(false); // Untuk checkbox selection
            
            $table->text('keterangan')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['project_id', 'kategori']);
            $table->index(['kode', 'project_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('budget_controls');
    }
};