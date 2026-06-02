<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename SP/Shift value column and remove redundant monitoring filler id.
     */
    public function up(): void
    {
        if (Schema::hasColumn('section_entries', 'sp_value')) {
            DB::statement('ALTER TABLE section_entries RENAME COLUMN sp_value TO column_label_value');
        }

        DB::table('field_locks')
            ->where('table_name', 'section_entries')
            ->where('field_name', 'sp_value')
            ->update(['field_name' => 'column_label_value']);

        if (Schema::hasColumn('section_entries', 'filled_by_monitoring')) {
            Schema::table('section_entries', function (Blueprint $table) {
                $table->dropForeign(['filled_by_monitoring']);
                $table->dropColumn('filled_by_monitoring');
            });
        }
    }

    /**
     * Restore previous schema shape.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('section_entries', 'filled_by_monitoring')) {
            Schema::table('section_entries', function (Blueprint $table) {
                $table->foreignUuid('filled_by_monitoring')->nullable();
                $table->foreign('filled_by_monitoring')->references('id')->on('users')->nullOnDelete();
            });
        }

        DB::table('field_locks')
            ->where('table_name', 'section_entries')
            ->where('field_name', 'column_label_value')
            ->update(['field_name' => 'sp_value']);

        if (Schema::hasColumn('section_entries', 'column_label_value')) {
            DB::statement('ALTER TABLE section_entries RENAME COLUMN column_label_value TO sp_value');
        }
    }
};
