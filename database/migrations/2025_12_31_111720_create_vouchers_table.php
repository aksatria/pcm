<?php
// database/migrations/2025_01_20_000003_create_vouchers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('voucher_number', 50)->unique(); // Format: MT/HO/G-12508-001
            $table->date('tanggal');
            
            // Informasi Vendor
            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('set null');
            $table->string('tujuan_transfer', 255)->nullable();
            $table->string('bank', 50)->nullable();
            $table->string('nama_rekening', 255)->nullable();
            $table->string('no_rekening', 50)->nullable();
            
            // Pembayaran
            $table->enum('pembayaran', ['cash', 'transfer', 'tempo'])->default('transfer');
            $table->date('jatuh_tempo')->nullable();
            
            // Approval
            $table->string('diajukan_oleh', 100)->nullable();
            $table->string('disetujui_oleh', 100)->nullable();
            $table->date('tanggal_pengajuan')->nullable();
            $table->date('tanggal_persetujuan')->nullable();
            
            // Status
            $table->enum('status', ['draft', 'submitted', 'approved', 'paid', 'rejected', 'completed'])->default('draft');
            
            // Financial
            $table->decimal('total_tagihan', 15, 2)->default(0);
            $table->decimal('ppn', 15, 2)->default(0);
            $table->decimal('ongkir', 15, 2)->default(0);
            $table->decimal('total_bayar', 15, 2)->default(0);
            
            $table->text('keterangan')->nullable();
            $table->text('catatan_reject')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['project_id', 'status']);
            $table->index(['voucher_number', 'tanggal']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vouchers');
    }
};