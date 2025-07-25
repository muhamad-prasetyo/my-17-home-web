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
        // Add additional indexes to attendances table
        Schema::table('attendances', function (Blueprint $table) {
            // For filtering by attendance type
            $table->index('attendance_type', 'idx_attendances_type');
            
            // For filtering attendances by company
            if (Schema::hasColumn('attendances', 'company_id')) {
                $table->index('company_id', 'idx_attendances_company');
            }
            
            // Composite index for company reports
            if (Schema::hasColumn('attendances', 'company_id')) {
                $table->index(['company_id', 'date'], 'idx_attendances_company_date');
            }
            
            // For transfer related queries
            if (Schema::hasColumn('attendances', 'transfer_request_id')) {
                $table->index('transfer_request_id', 'idx_attendances_transfer_request');
            }
        });
        
        // Add indexes to users table for faster lookups
        Schema::table('users', function (Blueprint $table) {
            $table->index('email', 'idx_users_email');
            
            if (Schema::hasColumn('users', 'schedule_id')) {
                $table->index('schedule_id', 'idx_users_schedule');
            }
        });
        
        // Add indexes to leave_requests table
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_leave_requests_user_status');
            $table->index(['start_date', 'end_date'], 'idx_leave_requests_date_range');
        });
        
        // Add indexes to permissions table
        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'user_id') && Schema::hasColumn('permissions', 'is_approved')) {
                $table->index(['user_id', 'is_approved'], 'idx_permissions_user_approved');
            }
            
            if (Schema::hasColumn('permissions', 'date_permission')) {
                $table->index('date_permission', 'idx_permissions_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_type');
            
            if (Schema::hasColumn('attendances', 'company_id')) {
                $table->dropIndex('idx_attendances_company');
                $table->dropIndex('idx_attendances_company_date');
            }
            
            if (Schema::hasColumn('attendances', 'transfer_request_id')) {
                $table->dropIndex('idx_attendances_transfer_request');
            }
        });
        
        // Remove indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            
            if (Schema::hasColumn('users', 'schedule_id')) {
                $table->dropIndex('idx_users_schedule');
            }
        });
        
        // Remove indexes from leave_requests table
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex('idx_leave_requests_user_status');
            $table->dropIndex('idx_leave_requests_date_range');
        });
        
        // Remove indexes from permissions table
        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'user_id') && Schema::hasColumn('permissions', 'is_approved')) {
                $table->dropIndex('idx_permissions_user_approved');
            }
            
            if (Schema::hasColumn('permissions', 'date_permission')) {
                $table->dropIndex('idx_permissions_date');
            }
        });
    }
};
