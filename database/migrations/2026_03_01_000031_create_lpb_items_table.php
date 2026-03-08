<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lpb_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lpb_id');
            $table->unsignedBigInteger('rab_item_id');

            $table->string('item_code_snapshot', 50)->nullable();
            $table->string('item_name_snapshot', 255)->nullable();
            $table->string('unit_snapshot', 50)->nullable();

            $table->decimal('qty', 15, 4)->default(0);
            $table->string('unit', 50)->nullable();
            $table->date('arrival_date')->nullable();
            $table->string('doc_reference', 100)->nullable();
            $table->text('work_notes')->nullable();

            $table->timestamps();

            $table->index(['lpb_id']);
            $table->index(['rab_item_id']);

            $table->foreign('lpb_id')->references('id')->on('lpbs')->onDelete('cascade');
            $table->foreign('rab_item_id')->references('id')->on('rab_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lpb_items');
    }
};
