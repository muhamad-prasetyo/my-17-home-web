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
            // Kolom-kolom untuk attendance transfer
            $table->boolean('is_transfer_day')->default(false)->after('status_attendance');
            $table->unsignedBigInteger('transfer_request_id')->nullable()->after('is_transfer_day');
            
            // Informasi check-in/out di kantor asal (source)
            $table->unsignedBigInteger('source_office_id')->nullable()->after('transfer_request_id');
            $table->time('source_time_in')->nullable()->after('source_office_id');
            $table->time('source_time_out')->nullable()->after('source_time_in');
            $table->string('source_latlon_in')->nullable()->after('source_time_out');
            $table->string('source_latlon_out')->nullable()->after('source_latlon_in');
            
            // Informasi check-in/out di kantor tujuan (destination)
            $table->unsignedBigInteger('destination_office_id')->nullable()->after('source_latlon_out');
            $table->time('destination_time_in')->nullable()->after('destination_office_id');
            $table->time('destination_time_out')->nullable()->after('destination_time_in');
            $table->string('destination_latlon_in')->nullable()->after('destination_time_out');
            $table->string('destination_latlon_out')->nullable()->after('destination_latlon_in');
            
            // Status transfer (menggantikan simple stage di response)
            $table->enum('transfer_status', [
                'pending', 
                'checked_in_at_source',
                'checked_out_from_source',
                'checked_in_at_destination',
                'completed'
            ])->nullable()->after('destination_latlon_out');
            
            // Foreign key references
            $table->foreign('transfer_request_id')->references('id')->on('transfer_requests')->onDelete('set null');
            $table->foreign('source_office_id')->references('id')->on('offices')->onDelete('set null');
            $table->foreign('destination_office_id')->references('id')->on('offices')->onDelete('set null');
            
            // Index untuk pencarian cepat
            $table->index('is_transfer_day');
            $table->index('transfer_request_id');
            $table->index(['user_id', 'date', 'is_transfer_day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Hapus foreign key constraints
            $table->dropForeign(['transfer_request_id']);
            $table->dropForeign(['source_office_id']);
            $table->dropForeign(['destination_office_id']);
            
            // Hapus indexes
            $table->dropIndex(['is_transfer_day']);
            $table->dropIndex(['transfer_request_id']);
            $table->dropIndex(['user_id', 'date', 'is_transfer_day']);
            
            // Hapus kolom-kolom
            $table->dropColumn([
                'is_transfer_day',
                'transfer_request_id',
                'source_office_id',
                'source_time_in',
                'source_time_out',
                'source_latlon_in',
                'source_latlon_out',
                'destination_office_id',
                'destination_time_in',
                'destination_time_out',
                'destination_latlon_in',
                'destination_latlon_out',
                'transfer_status'
            ]);
        });
    }
};
