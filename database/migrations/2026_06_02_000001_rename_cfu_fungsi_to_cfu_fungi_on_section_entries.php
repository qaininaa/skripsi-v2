<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix typo in section_entries CFU fungi column naming.
     */
    public function up(): void
    {
        if (Schema::hasColumn('section_entries', 'cfu_fungsi')
            && ! Schema::hasColumn('section_entries', 'cfu_fungi')
        ) {
            DB::statement(
                'ALTER TABLE section_entries CHANGE COLUMN cfu_fungsi cfu_fungi VARCHAR(10) NULL'
            );
        }

        DB::table('field_locks')
            ->where('table_name', 'section_entries')
            ->whereIn('field_name', ['reading_fungi', 'cfu_fungsi'])
            ->update(['field_name' => 'cfu_fungi']);
    }

    /**
     * Restore previous typo for rollback compatibility with older migration.
     */
    public function down(): void
    {
        DB::table('field_locks')
            ->where('table_name', 'section_entries')
            ->where('field_name', 'cfu_fungi')
            ->update(['field_name' => 'cfu_fungsi']);

        if (Schema::hasColumn('section_entries', 'cfu_fungi')
            && ! Schema::hasColumn('section_entries', 'cfu_fungsi')
        ) {
            DB::statement(
                'ALTER TABLE section_entries CHANGE COLUMN cfu_fungi cfu_fungsi VARCHAR(10) NULL'
            );
        }
    }
};
