<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('rab_id');

            $table->string('voucher_no', 100);
            $table->date('voucher_date');
            $table->string('vendor_name')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'rab_id']);
            $table->index(['rab_id', 'voucher_date']);

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('rab_id')->references('id')->on('rabs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_vouchers');
    }
};
