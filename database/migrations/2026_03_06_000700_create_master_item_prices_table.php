<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('master_item_prices')) {
            Schema::create('master_item_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('master_data_id');
                $table->unsignedBigInteger('province_id')->nullable();
                $table->string('supplier_id')->nullable();
                $table->decimal('price', 15, 2);
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->timestamps();

                $table->foreign('master_data_id')->references('id')->on('master_data')->onDelete('cascade');
                $table->foreign('province_id')->references('id')->on('provinces')->nullOnDelete();

                $table->index(['master_data_id', 'province_id']);
                $table->index(['effective_from', 'effective_to']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_item_prices');
    }
};
