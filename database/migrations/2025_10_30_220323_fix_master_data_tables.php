<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Create master_data table
        if (!Schema::hasTable('master_data')) {
            Schema::create('master_data', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->enum('category', ['MT', 'JS', 'AT', 'HO', 'SR', 'SB']);
                $table->string('name');
                $table->string('unit');
                $table->decimal('price', 15, 2);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('updated_by')->constrained('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create master_code_counters table
        if (!Schema::hasTable('master_code_counters')) {
            Schema::create('master_code_counters', function (Blueprint $table) {
                $table->string('category')->primary();
                $table->unsignedInteger('last_number')->default(0);
                $table->timestamps();
            });
        }

        // Create master_data_audits table
        if (!Schema::hasTable('master_data_audits')) {
            Schema::create('master_data_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('master_data_id')->constrained()->onDelete('cascade');
                $table->string('action');
                $table->json('old_data')->nullable();
                $table->json('new_data')->nullable();
                $table->foreignId('user_id')->constrained();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('master_data_audits');
        Schema::dropIfExists('master_code_counters');
        Schema::dropIfExists('master_data');
    }
};