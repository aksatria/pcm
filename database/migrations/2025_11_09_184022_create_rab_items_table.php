<?php
// database/migrations/2024_01_01_000002_create_rab_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRabItemsTable extends Migration
{
    public function up()
    {
        Schema::create('rab_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rab_id')->constrained()->onDelete('cascade');
            $table->foreignId('data_id')->constrained('data')->onDelete('cascade');
            $table->string('item_type'); // MT, JS, AT, HO, SR, SB
            $table->decimal('volume', 15, 4)->default(1);
            $table->string('satuan');
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();

            $table->index(['rab_id', 'item_type']);
            $table->index(['data_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rab_items');
    }
}