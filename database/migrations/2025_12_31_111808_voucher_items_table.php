<?php
// database/migrations/2025_01_20_000004_create_voucher_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('voucher_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->onDelete('cascade');
            $table->foreignId('budget_control_id')->nullable()->constrained()->onDelete('set null');
            
            // Item details
            $table->string('kode', 20);
            $table->text('uraian');
            $table->decimal('qty', 15, 4)->default(0);
            $table->string('satuan', 20);
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('total_harga', 15, 2)->default(0);
            
            // Status tracking
            $table->enum('status', ['pending', 'ordered', 'delivered', 'completed', 'cancelled'])->default('pending');
            $table->date('tanggal_pesan')->nullable();
            $table->date('tanggal_terima')->nullable();
            
            // Link ke Data master jika perlu
            $table->foreignId('data_id')->nullable()->constrained('data')->onDelete('set null');
            
            $table->text('keterangan')->nullable();
            $table->integer('urutan')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['voucher_id', 'budget_control_id']);
            $table->index(['kode', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('voucher_items');
    }
};