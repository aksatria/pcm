<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_comparison_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_comparison_id');
            $table->unsignedBigInteger('rab_item_id');

            $table->string('item_code_snapshot', 50)->nullable();
            $table->string('item_name_snapshot', 255)->nullable();
            $table->string('unit_snapshot', 50)->nullable();

            $table->decimal('qty', 15, 4)->default(0);
            $table->string('unit', 50)->nullable();

            $table->decimal('rapp_unit_price', 15, 2)->default(0);
            $table->decimal('rapp_total', 15, 2)->default(0);

            $table->unsignedBigInteger('vendor1_id')->nullable();
            $table->decimal('vendor1_unit_price', 15, 2)->default(0);
            $table->decimal('vendor1_total', 15, 2)->default(0);

            $table->unsignedBigInteger('vendor2_id')->nullable();
            $table->decimal('vendor2_unit_price', 15, 2)->default(0);
            $table->decimal('vendor2_total', 15, 2)->default(0);

            $table->unsignedBigInteger('vendor3_id')->nullable();
            $table->decimal('vendor3_unit_price', 15, 2)->default(0);
            $table->decimal('vendor3_total', 15, 2)->default(0);

            $table->timestamps();

            $table->index(['vendor_comparison_id']);
            $table->index(['rab_item_id']);

            $table->foreign('vendor_comparison_id')->references('id')->on('vendor_comparisons')->onDelete('cascade');
            $table->foreign('rab_item_id')->references('id')->on('rab_items')->onDelete('cascade');
            $table->foreign('vendor1_id')->references('id')->on('vendors')->nullOnDelete();
            $table->foreign('vendor2_id')->references('id')->on('vendors')->nullOnDelete();
            $table->foreign('vendor3_id')->references('id')->on('vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_comparison_items');
    }
};
