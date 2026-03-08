<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpgs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('rab_id');

            $table->string('bpg_no', 100);
            $table->date('bpg_date')->nullable();

            $table->string('status', 20)->default('draft');
            $table->string('requested_by', 100)->nullable();
            $table->string('approved_by', 100)->nullable();
            $table->string('known_by', 100)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'rab_id']);
            $table->index(['bpg_no']);

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('rab_id')->references('id')->on('rabs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bpgs');
    }
};
