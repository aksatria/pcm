<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('rab_id');
            $table->unsignedBigInteger('vendor_id')->nullable();

            $table->string('spk_no', 100);
            $table->date('spk_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->string('status', 20)->default('draft');
            $table->string('scope', 255)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'rab_id']);
            $table->index(['spk_no']);

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('rab_id')->references('id')->on('rabs')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spks');
    }
};
