<?php
// database/migrations/2024_01_01_000001_create_rabs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRabsTable extends Migration
{
    public function up()
    {
        Schema::create('rabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('version')->default('1.0');
            $table->decimal('total_budget', 15, 2)->default(0);
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->text('notes')->nullable();
            $table->json('breakdown')->nullable(); // Untuk menyimpan breakdown per kategori
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
            $table->unique(['project_id', 'version']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rabs');
    }
}