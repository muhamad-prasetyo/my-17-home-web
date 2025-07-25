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
        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->id();
            // User who is being transferred
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Original and target schedules
            $table->foreignId('current_schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->foreignId('target_schedule_id')->constrained('schedules')->onDelete('cascade');
            // Optional reason for transfer
            $table->text('reason')->nullable();
            // Dates
            $table->date('request_date')->useCurrent();
            $table->date('effective_date');
            // Status of the request
            $table->string('status')->default('pending');
            // Approver and approval timestamp
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('approval_date')->nullable();
            // Optional rejection reason
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_requests');
    }
};
