<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename reading columns to CFU naming and persist computed total.
     */
    public function up(): void
    {
        if (Schema::hasColumn('section_entries', 'reading_total')) {
            DB::statement(
                'ALTER TABLE section_entries CHANGE COLUMN reading_total cfu_bacteri VARCHAR(10) NULL'
            );
        }

        if (Schema::hasColumn('section_entries', 'reading_fungi')) {
            DB::statement(
                'ALTER TABLE section_entries CHANGE COLUMN reading_fungi cfu_fungsi VARCHAR(10) NULL'
            );
        }

        if (! Schema::hasColumn('section_entries', 'cfu_total')) {
            Schema::table('section_entries', function (Blueprint $table) {
                $table->string('cfu_total', 10)->nullable()->after('cfu_fungsi');
            });
        }

        DB::table('field_locks')
            ->where('table_name', 'section_entries')
            ->where('field_name', 'reading_total')
            ->update(['field_name' => 'cfu_bacteri']);

        DB::table('field_locks')
            ->where('table_name', 'section_entries')
            ->where('field_name', 'reading_fungi')
            ->update(['field_name' => 'cfu_fungsi']);

        // Backfill total for existing rows where at least one CFU value is numeric.
        DB::statement("
            UPDATE section_entries
            SET cfu_total = CASE
                WHEN (NULLIF(cfu_bacteri, '') REGEXP '^[0-9]+$')
                  OR (NULLIF(cfu_fungsi, '') REGEXP '^[0-9]+$')
                THEN CAST(COALESCE(NULLIF(cfu_bacteri, ''), '0') AS UNSIGNED)
                   + CAST(COALESCE(NULLIF(cfu_fungsi, ''), '0') AS UNSIGNED)
                ELSE NULL
            END
        ");
    }

    /**
     * Restore previous reading_* naming.
     */
    public function down(): void
    {
        if (Schema::hasColumn('section_entries', 'cfu_total')) {
            Schema::table('section_entries', function (Blueprint $table) {
                $table->dropColumn('cfu_total');
            });
        }

        DB::table('field_locks')
            ->where('table_name', 'section_entries')
            ->where('field_name', 'cfu_bacteri')
            ->update(['field_name' => 'reading_total']);

        DB::table('field_locks')
            ->where('table_name', 'section_entries')
            ->where('field_name', 'cfu_fungsi')
            ->update(['field_name' => 'reading_fungi']);

        if (Schema::hasColumn('section_entries', 'cfu_bacteri')) {
            DB::statement(
                'ALTER TABLE section_entries CHANGE COLUMN cfu_bacteri reading_total VARCHAR(10) NULL'
            );
        }

        if (Schema::hasColumn('section_entries', 'cfu_fungsi')) {
            DB::statement(
                'ALTER TABLE section_entries CHANGE COLUMN cfu_fungsi reading_fungi VARCHAR(10) NULL'
            );
        }
    }
};
