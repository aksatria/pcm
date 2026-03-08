<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lpbs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('rab_id');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();

            $table->string('lpb_no', 100);
            $table->date('lpb_date')->nullable();

            $table->string('status', 20)->default('draft');
            $table->string('delivered_by', 100)->nullable();
            $table->string('received_by', 100)->nullable();
            $table->string('known_by', 100)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'rab_id']);
            $table->index(['lpb_no']);

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('rab_id')->references('id')->on('rabs')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lpbs');
    }
};
