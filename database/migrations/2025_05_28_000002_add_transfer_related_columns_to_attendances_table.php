<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Columns to store the original schedule/office details for transfers
            if (!Schema::hasColumn('attendances', 'original_schedule_id')) {
                // Ensure schedule_id column exists, place after it if so, otherwise after user_id
                $table->foreignId('original_schedule_id')
                      ->nullable()
                      ->after(Schema::hasColumn('attendances', 'schedule_id') ? 'schedule_id' : 'user_id')
                      ->constrained('schedules')
                      ->onDelete('set null');
            }
            
            // Optional: Columns for rolling attendance (transfer) if needed for detailed tracking
            if (!Schema::hasColumn('attendances', 'related_attendance_id')) {
                // Ensure original_schedule_id column exists, place after it if so, otherwise after schedule_id or user_id
                $columnToPlaceAfter = 'user_id'; // Default
                if (Schema::hasColumn('attendances', 'original_schedule_id')) {
                    $columnToPlaceAfter = 'original_schedule_id';
                } elseif (Schema::hasColumn('attendances', 'schedule_id')) {
                    $columnToPlaceAfter = 'schedule_id';
                }
                
                $table->foreignId('related_attendance_id')
                      ->nullable()
                      ->after($columnToPlaceAfter)
                      ->comment('Self-referencing ID for linking transfer attendance records'); 
                // Consider adding: ->constrained('attendances')->onDelete('set null');
                // if you want a strict foreign key relationship to the same table.
                // Be cautious with self-referencing foreign keys on large tables or with specific DB engines.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'related_attendance_id')) {
                // If a foreign key constraint was added, it needs to be dropped first
                // Example: if (DB::getDriverName() !== 'sqlite') { $table->dropForeign(['related_attendance_id']); }
                $table->dropColumn('related_attendance_id');
            }

            if (Schema::hasColumn('attendances', 'original_schedule_id')) {
                if (DB::getDriverName() !== 'sqlite') { // SQLite does not support dropping foreign keys in this manner easily
                    $table->dropForeign(['original_schedule_id']);
                }
                $table->dropColumn('original_schedule_id');
            }
        });
    }
}; 