<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per (location-row × column × sub-column) of a section instance.
     *
     * Column index meaning depends on the section template:
     *   has_machine_setup=true → 0=Machine Setup, 1..N=Exposure I..N
     *   has_machine_setup=false → 0..N-1=Exposure I..N
     *
     * sub_column captures slot variants:
     *   time_slot_type=start_end_ab       → 'A' | 'B'
     *   time_slot_type=start_end_multi    → 'S1' | 'S1-2' | 'S1-3'
     *   else                              → null
     *
     * Monitoring analyst fills time_start, time_end, sp_value.
     * Reading analyst fills reading_total (B), reading_fungi (F).
     * location_conclusion is computed when reading rows are saved.
     */
    public function up(): void
    {
        Schema::create('section_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('section_instance_location_id')
                ->constrained('section_instance_locations')->cascadeOnDelete();
            $table->unsignedTinyInteger('column_index');
            $table->string('sub_column', 4)->nullable();
            $table->string('sp_value', 20)->nullable();
            $table->string('time_start', 5)->nullable();
            $table->string('time_end', 5)->nullable();
            $table->string('reading_total', 10)->nullable();
            $table->string('reading_fungi', 10)->nullable();
            $table->enum('location_conclusion', ['MS', 'TMS'])->nullable();
            $table->foreignUuid('filled_by_monitoring')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->foreignUuid('filled_by_reading')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            // The combination uniquely identifies a single editable cell-row.
            $table->unique(
                ['section_instance_location_id', 'column_index', 'sub_column'],
                'section_entries_unique_cell',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_entries');
    }
};
