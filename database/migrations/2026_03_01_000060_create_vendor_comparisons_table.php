<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_comparisons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('rab_id');

            $table->string('comparison_no', 100);
            $table->date('comparison_date')->nullable();

            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('decision_vendor_id')->nullable();
            $table->decimal('final_amount', 15, 2)->default(0);
            $table->decimal('difference_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'rab_id']);
            $table->index(['comparison_no']);

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('rab_id')->references('id')->on('rabs')->onDelete('cascade');
            $table->foreign('decision_vendor_id')->references('id')->on('vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_comparisons');
    }
};
