<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Approval / review pipeline rows attached to a Report.
 *
 * step:
 *   2 = supervisor review
 *   3 = manager approval
 *
 * status:
 *   pending  → waiting on the assignee
 *   approved → assignee signed off, next step (if any) is created
 *   returned → assignee sent back to an analyst with notes
 *
 * notes & returned_to_user_id are filled when status=returned.
 *
 * Step 1 (analyst) is NOT modeled here — analyst sign-off is captured by
 * SectionSignature(role=monitoring|reading) per section instance, and the
 * report status (in_progress_*, pending_review) drives visibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_id')->constrained('reports')->cascadeOnDelete();
            $table->unsignedTinyInteger('step');
            $table->string('role_label', 50);
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignUuid('returned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['report_id', 'step']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_approvals');
    }
};
