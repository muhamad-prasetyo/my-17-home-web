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
        // Add indexes to attendances table
        Schema::table('attendances', function (Blueprint $table) {
            // Primary search pattern for user's attendance records
            $table->index(['user_id', 'date'], 'idx_attendances_user_date');
            
            // For status filtering
            $table->index(['date', 'status_attendance'], 'idx_attendances_date_status');
            
            // For complex filtering needs
            $table->index(['user_id', 'date', 'status_attendance'], 'idx_attendances_user_date_status');
            
            // For finding records that need checkout
            $table->index(['user_id', 'date', 'time_out'], 'idx_attendances_user_date_timeout');
            
            // For related records in transfers
            if (Schema::hasColumn('attendances', 'related_attendance_id')) {
                $table->index('related_attendance_id', 'idx_attendances_related');
            }
        });
        
        // Add indexes to transfer_requests table
        Schema::table('transfer_requests', function (Blueprint $table) {
            // For user's approved transfers on a specific date
            $table->index(['user_id', 'status', 'effective_date'], 'idx_transfers_user_status_date');
            
            // For finding transfers by target schedule
            $table->index('target_schedule_id', 'idx_transfers_target_schedule');
            
            // For finding transfers by current schedule
            $table->index('current_schedule_id', 'idx_transfers_current_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_user_date');
            $table->dropIndex('idx_attendances_date_status');
            $table->dropIndex('idx_attendances_user_date_status');
            $table->dropIndex('idx_attendances_user_date_timeout');
            $table->dropIndex('idx_attendances_related');
        });
        
        // Remove indexes from transfer_requests table
        Schema::table('transfer_requests', function (Blueprint $table) {
            $table->dropIndex('idx_transfers_user_status_date');
            $table->dropIndex('idx_transfers_target_schedule');
            $table->dropIndex('idx_transfers_current_schedule');
        });
    }
};
