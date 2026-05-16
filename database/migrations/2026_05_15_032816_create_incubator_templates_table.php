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
        Schema::create('incubator_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_template_id')->constrained('report_templates')->cascadeOnDelete();
            $table->string('label');
            $table->unsignedTinyInteger('min_day');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incubator_templates');
    }
};
