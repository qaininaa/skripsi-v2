<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A section_instance represents one "section block" inside a Report.
     *
     * Bootstrapped 1:1 with Section template rows (instance_number = 1) when
     * Admin QC creates a new report. Admin may later duplicate a section,
     * producing a new row with instance_number = 2, 3, ... that points to its
     * source via parent_instance_id. Each instance has its own:
     *   - rows (section_instance_locations)  → physical sampling points
     *   - column entries (section_entries)   → per-column data
     *   - note (free text)                   → catatan
     *   - signatures (section_signatures)    → 4 roles
     *   - final_conclusion (MS|TMS)          → cached aggregate of leaf rows
     */
    public function up(): void
    {
        Schema::create('section_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignUuid('section_id')->constrained('sections')->cascadeOnDelete();
            $table->unsignedTinyInteger('instance_number')->default(1);
            $table->foreignUuid('parent_instance_id')->nullable()
                ->constrained('section_instances')->nullOnDelete();
            $table->string('duplication_reason')->nullable();
            $table->text('note')->nullable();
            $table->enum('final_conclusion', ['MS', 'TMS'])->nullable();
            $table->timestamps();

            $table->unique(['report_id', 'section_id', 'instance_number']);
            $table->index(['report_id', 'section_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_instances');
    }
};
