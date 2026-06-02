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
        Schema::create('medium_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_id')->constrained('reports')->cascadeOnDelete();
            // Nullable to allow virtual entries like "Swab Kit" that don't map to a MediumTemplate.
            $table->foreignUuid('medium_id')->nullable()->constrained('medium_templates')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->boolean('is_swab')->default(false);
            $table->string('batch_number')->nullable();
            $table->string('gpt_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medium_entries');
    }
};
