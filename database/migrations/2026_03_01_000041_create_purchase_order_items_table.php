<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('rab_item_id');

            $table->string('item_code_snapshot', 50)->nullable();
            $table->string('item_name_snapshot', 255)->nullable();
            $table->string('unit_snapshot', 50)->nullable();

            $table->string('specification', 255)->nullable();
            $table->decimal('qty', 15, 4)->default(0);
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);

            $table->timestamps();

            $table->index(['purchase_order_id']);
            $table->index(['rab_item_id']);

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('rab_item_id')->references('id')->on('rab_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
