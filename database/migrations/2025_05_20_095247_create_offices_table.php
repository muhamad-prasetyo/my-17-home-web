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
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->float('latitude')->default(0);
            $table->float('longitude')->default(0);
            $table->integer('radius_meter');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('type', ['face_recognition', 'qr_code'])->default('face_recognition');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
