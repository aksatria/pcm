<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('rab_id');
            $table->unsignedBigInteger('vendor_id')->nullable();

            $table->string('spp_no', 100);
            $table->date('spp_date')->nullable();
            $table->date('schedule_date')->nullable();

            $table->string('status', 20)->default('draft');
            $table->string('requested_by', 100)->nullable();
            $table->string('approved_by', 100)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'rab_id']);
            $table->index(['spp_no']);

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('rab_id')->references('id')->on('rabs')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spps');
    }
};
