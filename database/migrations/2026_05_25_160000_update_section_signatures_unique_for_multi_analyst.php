<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow multiple analysts to sign the same section/role pair.
     *
     * Old rule:
     *   unique(section_instance_id, role)
     *
     * New rule:
     *   unique(section_instance_id, role, signed_by)
     *
     * This keeps each analyst idempotent per role while preserving
     * timestamp + signer history for handoff workflows.
     */
    public function up(): void
    {
        Schema::table('section_signatures', function (Blueprint $table) {
            $table->unique(
                ['section_instance_id', 'role', 'signed_by'],
                'section_signatures_instance_role_signer_unique',
            );
            $table->dropUnique('section_signatures_section_instance_id_role_unique');
        });
    }

    /**
     * Restore the original one-row-per-role behavior.
     */
    public function down(): void
    {
        Schema::table('section_signatures', function (Blueprint $table) {
            $table->unique(['section_instance_id', 'role']);
            $table->dropUnique('section_signatures_instance_role_signer_unique');
        });
    }
};
