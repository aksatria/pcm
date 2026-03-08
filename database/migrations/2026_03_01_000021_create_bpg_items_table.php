<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpg_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bpg_id');
            $table->unsignedBigInteger('rab_item_id');

            $table->string('item_code_snapshot', 50)->nullable();
            $table->string('item_name_snapshot', 255)->nullable();
            $table->string('unit_snapshot', 50)->nullable();

            $table->decimal('qty', 15, 4)->default(0);
            $table->string('unit', 50)->nullable();
            $table->date('issue_date')->nullable();
            $table->text('work_notes')->nullable();

            $table->timestamps();

            $table->index(['bpg_id']);
            $table->index(['rab_item_id']);

            $table->foreign('bpg_id')->references('id')->on('bpgs')->onDelete('cascade');
            $table->foreign('rab_item_id')->references('id')->on('rab_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bpg_items');
    }
};
