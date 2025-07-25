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
        Schema::table('attendances', function (Blueprint $table) {
            // Define an array of columns to drop
            $columnsToDrop = [];

            if (Schema::hasColumn('attendances', 'new_time_in')) {
                $columnsToDrop[] = 'new_time_in';
            }
            if (Schema::hasColumn('attendances', 'new_time_out')) {
                $columnsToDrop[] = 'new_time_out';
            }
            if (Schema::hasColumn('attendances', 'new_latlon_in')) {
                $columnsToDrop[] = 'new_latlon_in';
            }
            if (Schema::hasColumn('attendances', 'new_latlon_out')) {
                $columnsToDrop[] = 'new_latlon_out';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * Optional: If you want to be able to rollback and re-add these columns,
     * you would define their schema here. However, since they were manually
     * added and then their migration deleted, it might be safer not to
     * provide a down() method that re-adds them, to avoid re-introducing
     * inconsistencies if the original (deleted) migration had different definitions.
     * For now, we'll leave the down() method to re-add them with basic types
     * if you ever need to roll back this specific migration.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Re-add columns if they were dropped. Adjust types if known.
            if (!Schema::hasColumn('attendances', 'new_time_in')) {
                $table->time('new_time_in')->nullable();
            }
            if (!Schema::hasColumn('attendances', 'new_time_out')) {
                $table->time('new_time_out')->nullable();
            }
            if (!Schema::hasColumn('attendances', 'new_latlon_in')) {
                $table->string('new_latlon_in')->nullable();
            }
            if (!Schema::hasColumn('attendances', 'new_latlon_out')) {
                $table->string('new_latlon_out')->nullable();
            }
        });
    }
}; 