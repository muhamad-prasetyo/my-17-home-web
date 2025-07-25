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
        if (!Schema::hasColumn('users', 'schedule_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('schedule_id')->nullable()->constrained('schedules')->onDelete('set null');
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('schedule_id')->references('id')->on('schedules')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
        });
        // Note: schedule_id column remains if existed prior to migration
    }
};
