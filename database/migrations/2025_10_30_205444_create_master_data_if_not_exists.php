<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Cek dulu apakah tabel sudah ada
        if (!Schema::hasTable('master_data')) {
            Schema::create('master_data', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique(); // HAPUS duplicate unique() di sini
                $table->enum('category', ['MT', 'JS', 'AT', 'HO', 'SR', 'SB']);
                $table->string('name');
                $table->string('unit');
                $table->decimal('price', 15, 2);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        
        // HAPUS BAGIAN INI JIKA ADA:
        // Schema::table('master_data', function (Blueprint $table) {
        //     $table->unique('code'); // INI YANG MEMBUAT ERROR
        // });
    }

    public function down()
    {
        Schema::dropIfExists('master_data');
    }
};