<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('section_id')->nullable()->constrained('sections')->cascadeOnDelete();
            $table->foreignUuid('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->enum('frequency', ['operational', 'daily', 'weekly', 'monthly', 'semi_annual']);
            $table->string('loc_number', 50);
            $table->string('measurement_type', 50);
            $table->unsignedSmallInteger('alert_limit_total')->nullable();
            $table->unsignedSmallInteger('alert_limit_fungi')->nullable();
            $table->unsignedSmallInteger('alert_action_total')->nullable();
            $table->unsignedSmallInteger('alert_action_fungi')->nullable();
            $table->timestamp('section_assigned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
