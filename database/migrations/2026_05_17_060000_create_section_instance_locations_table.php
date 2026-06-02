<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Snapshot of the locations attached to a section_instance.
     *
     * Created when a report is bootstrapped (one row per Location whose
     * section_id matches the template Section). On duplicate, the rows are
     * copied from the source instance — entries however start blank.
     *
     * We keep this table (instead of reading live from `locations`) so that:
     *   - duplicate instances can carry independent entry sets
     *   - removing a location from the template does not break historical reports
     */
    public function up(): void
    {
        Schema::create('section_instance_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('section_instance_id')->constrained('section_instances')->cascadeOnDelete();
            $table->foreignUuid('location_id')->constrained('locations')->restrictOnDelete();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['section_instance_id', 'location_id'],
                'sil_instance_location_unique',
            );
            $table->index(['section_instance_id', 'display_order'], 'sil_instance_order_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_instance_locations');
    }
};
