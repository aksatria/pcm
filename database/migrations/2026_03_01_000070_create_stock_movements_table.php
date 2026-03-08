<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('rab_id');
            $table->unsignedBigInteger('rab_item_id');

            $table->string('movement_type', 10); // in | out
            $table->string('doc_type', 20)->nullable(); // lpb | bpg
            $table->unsignedBigInteger('doc_id')->nullable();
            $table->date('movement_date')->nullable();

            $table->decimal('qty', 15, 4)->default(0);
            $table->string('unit', 50)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'rab_id']);
            $table->index(['rab_item_id']);
            $table->index(['movement_type', 'movement_date']);
            $table->index(['doc_type', 'doc_id']);

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('rab_id')->references('id')->on('rabs')->onDelete('cascade');
            $table->foreign('rab_item_id')->references('id')->on('rab_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
