<?php
// database/migrations/2025_01_20_000002_create_vendors_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('kode_vendor', 20)->unique()->nullable(); // V-001
            $table->string('nama', 255);
            $table->string('perusahaan', 255)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->string('bank', 50)->nullable();
            $table->string('no_rekening', 50)->nullable();
            $table->string('nama_rekening', 255)->nullable();
            $table->string('alamat', 500)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendors');
    }
};