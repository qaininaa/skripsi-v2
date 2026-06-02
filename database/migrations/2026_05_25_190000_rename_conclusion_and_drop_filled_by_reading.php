<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Use concise conclusion naming and rely on field_locks for reading actor.
     */
    public function up(): void
    {
        if (Schema::hasColumn('section_entries', 'location_conclusion')) {
            DB::statement('ALTER TABLE section_entries RENAME COLUMN location_conclusion TO conclusion');
        }

        if (Schema::hasColumn('section_entries', 'filled_by_reading')) {
            Schema::table('section_entries', function (Blueprint $table) {
                $table->dropForeign(['filled_by_reading']);
                $table->dropColumn('filled_by_reading');
            });
        }
    }

    /**
     * Restore previous schema.
     */
    public function down(): void
    {
        if (Schema::hasColumn('section_entries', 'conclusion')) {
            DB::statement('ALTER TABLE section_entries RENAME COLUMN conclusion TO location_conclusion');
        }

        if (! Schema::hasColumn('section_entries', 'filled_by_reading')) {
            Schema::table('section_entries', function (Blueprint $table) {
                $table->foreignUuid('filled_by_reading')->nullable()->after('filled_by_monitoring');
                $table->foreign('filled_by_reading')->references('id')->on('users')->nullOnDelete();
            });
        }
    }
};
