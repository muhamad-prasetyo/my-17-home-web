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
        Schema::table('user_device_tokens', function(Blueprint $t) {
          $t->string('device_name')->nullable()->after('device_type');
          $t->string('last_location')->nullable()->after('device_name');
        });
    }
    public function down(): void
    {
        Schema::table('user_device_tokens', function(Blueprint $t) {
          $t->dropColumn(['device_name','last_location']);
        });
    }
};
