<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_template_id')->constrained('report_templates')->cascadeOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('product_name');
            $table->string('batch_number');
            $table->string('status', 30)->default('pending');
            $table->timestamp('monitoring_started_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->foreignUuid('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('batch_number');
            $table->index('status');
            $table->index('printed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
