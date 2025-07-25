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
        Schema::table('users', function (Blueprint $table) {
            $table->date('tanggal_lahir')->nullable()->after('schedule_id');
            $table->string('kewarganegaraan')->nullable()->after('tanggal_lahir');
            $table->string('agama')->nullable()->after('kewarganegaraan');
            $table->string('jenis_kelamin')->nullable()->after('agama');
            $table->string('status_pernikahan')->nullable()->after('jenis_kelamin');
            $table->string('waktu_kontrak')->nullable()->after('status_pernikahan');
            $table->integer('tinggi_badan')->nullable()->after('waktu_kontrak'); // in cm
            $table->integer('berat_badan')->nullable()->after('tinggi_badan'); // in kg
            $table->string('golongan_darah')->nullable()->after('berat_badan');
            $table->string('gangguan_penglihatan')->nullable()->after('golongan_darah');
            $table->string('buta_warna')->nullable()->after('gangguan_penglihatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_lahir',
                'kewarganegaraan',
                'agama',
                'jenis_kelamin',
                'status_pernikahan',
                'waktu_kontrak',
                'tinggi_badan',
                'berat_badan',
                'golongan_darah',
                'gangguan_penglihatan',
                'buta_warna',
            ]);
        });
    }
};