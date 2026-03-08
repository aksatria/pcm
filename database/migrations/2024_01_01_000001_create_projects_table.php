<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('location');
            $table->string('pic')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->bigInteger('budget')->default(0);
            $table->enum('status', ['Planning', 'Active', 'On Hold', 'Completed', 'Cancelled'])->default('Planning');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('status');
            $table->index('client_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
};