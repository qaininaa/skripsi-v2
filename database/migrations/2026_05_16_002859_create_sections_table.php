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
        Schema::create('sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_template_id')->constrained('report_templates')->cascadeOnDelete();
            $table->string('measurement_unit', 50);
            $table->enum('measurement_type', ['settle_plate', 'air_sampler', 'contact_plate', 'swab']);
            $table->unsignedTinyInteger('max_column')->default(1);
            $table->string('column_label', 50)->nullable();
            $table->enum('time_slot_type', ['by_location', 'start_end', 'start_end_ab', 'start_end_multi'])->default('start_end');
            $table->boolean('has_machine_setup')->default(false);
            $table->unsignedSmallInteger('order')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
