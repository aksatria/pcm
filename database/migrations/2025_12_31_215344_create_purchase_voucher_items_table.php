<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_voucher_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchase_voucher_id');
            $table->unsignedBigInteger('rab_item_id');

            $table->decimal('qty', 18, 4);
            $table->decimal('price', 18, 2);
            $table->decimal('amount', 18, 2);

            $table->timestamps();

            $table->index(['rab_item_id']);
            $table->index(['purchase_voucher_id']);

            $table->foreign('purchase_voucher_id')
                ->references('id')->on('purchase_vouchers')
                ->onDelete('cascade');

            $table->foreign('rab_item_id')
                ->references('id')->on('rab_items')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_voucher_items');
    }
};
