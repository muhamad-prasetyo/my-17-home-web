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
        Schema::table('permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('permissions', 'date_permission')) {
                $table->date('date_permission')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('permissions', 'reason')) {
                $table->text('reason')->nullable()->after('date_permission');
            }
            if (! Schema::hasColumn('permissions', 'image')) {
                $table->string('image')->nullable()->after('reason');
            }
            if (! Schema::hasColumn('permissions', 'is_approved')) {
                $table->boolean('is_approved')->default(false)->after('image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('permissions', 'is_approved')) {
                $columns[] = 'is_approved';
            }
            if (Schema::hasColumn('permissions', 'image')) {
                $columns[] = 'image';
            }
            if (Schema::hasColumn('permissions', 'reason')) {
                $columns[] = 'reason';
            }
            if (Schema::hasColumn('permissions', 'date_permission')) {
                $columns[] = 'date_permission';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}; 