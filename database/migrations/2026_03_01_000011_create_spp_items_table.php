<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spp_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spp_id');
            $table->unsignedBigInteger('rab_item_id');
            $table->unsignedBigInteger('vendor_id')->nullable();

            $table->string('item_code_snapshot', 50)->nullable();
            $table->string('item_name_snapshot', 255)->nullable();
            $table->string('unit_snapshot', 50)->nullable();

            $table->decimal('qty', 15, 4)->default(0);
            $table->string('unit', 50)->nullable();
            $table->date('schedule_date')->nullable();
            $table->text('work_notes')->nullable();

            $table->timestamps();

            $table->index(['spp_id']);
            $table->index(['rab_item_id']);

            $table->foreign('spp_id')->references('id')->on('spps')->onDelete('cascade');
            $table->foreign('rab_item_id')->references('id')->on('rab_items')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spp_items');
    }
};
