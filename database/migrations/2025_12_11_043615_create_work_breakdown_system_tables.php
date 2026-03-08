<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table 1: Work Breakdown Structures
        if (!Schema::hasTable('work_breakdown_structures')) {
            Schema::create('work_breakdown_structures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
                $table->string('wbs_code'); // WBS-001 sampai WBS-011
                $table->string('name'); // "PEKERJAAN PERSIAPAN", "PEKERJAAN TANAH", dll
                $table->integer('order_number'); // 1-11
                $table->text('description')->nullable();
                $table->decimal('budget_amount', 15, 2)->default(0);
                $table->decimal('actual_amount', 15, 2)->default(0);
                $table->decimal('progress_percentage', 5, 2)->default(0);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->enum('status', ['not_started', 'in_progress', 'completed', 'delayed'])->default('not_started');
                $table->timestamps();
                
                // Indexes
                $table->index(['project_id', 'wbs_code']);
                $table->unique(['project_id', 'wbs_code']);
            });
        }

        // Table 2: WBS Items
        if (!Schema::hasTable('wbs_items')) {
            Schema::create('wbs_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wbs_id')->constrained('work_breakdown_structures')->onDelete('cascade');
                $table->string('item_code'); // A.1, A.2, B.1, dll (dari Excel)
                $table->string('uraian'); // "Demolish bangunan lama"
                $table->decimal('volume_rab', 10, 2)->default(0); // Volume dari RAB
                $table->string('satuan')->default('LS'); // Ls, m3, m2, unit
                $table->decimal('unit_price', 15, 2)->default(0); // Harga satuan
                $table->decimal('total_price', 15, 2)->default(0); // Total harga
                // Link ke RAB items jika ada
                $table->foreignId('rab_item_id')->nullable()->constrained('rab_items')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                
                // Indexes
                $table->index(['wbs_id', 'item_code']);
            });
        }

        // Table 3: WBS Budget Sources
        if (!Schema::hasTable('wbs_budget_sources')) {
            Schema::create('wbs_budget_sources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wbs_item_id')->constrained('wbs_items')->onDelete('cascade');
                $table->string('master_kode'); // JS-001, MT-133, dll
                $table->string('master_kategori'); // Material, Jasa, Subkon, dll
                $table->string('master_uraian');
                $table->string('master_satuan');
                $table->decimal('master_harga', 15, 2);
                $table->decimal('allocated_volume', 10, 2);
                $table->decimal('allocated_amount', 15, 2);
                $table->text('notes')->nullable();
                $table->timestamps();
                
                // Indexes
                $table->index(['wbs_item_id', 'master_kode']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wbs_budget_sources');
        Schema::dropIfExists('wbs_items');
        Schema::dropIfExists('work_breakdown_structures');
    }
};