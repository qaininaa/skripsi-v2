<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per-instance approvals.
     *
     * role:
     *   - monitoring → analyst who finalized monitoring
     *   - reading    → analyst who finalized reading
     *   - review     → supervisor sign-off (later phase)
     *   - approval   → manager / QC approval (later phase)
     */
    public function up(): void
    {
        Schema::create('section_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('section_instance_id')
                ->constrained('section_instances')->cascadeOnDelete();
            $table->enum('role', ['monitoring', 'reading', 'review', 'approval']);
            $table->foreignUuid('signed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('signed_at');
            $table->timestamps();

            $table->unique(['section_instance_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_signatures');
    }
};
