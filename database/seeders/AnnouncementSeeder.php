<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Announcement;
use Illuminate\Support\Str;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing entries
        Announcement::truncate();

        // Create sample announcements
        Announcement::create([
            'title'       => 'Jadwal WFH 2023 Ditiadakan',
            'slug'        => Str::slug('Jadwal WFH 2023 Ditiadakan'),
            'excerpt'     => 'Per tanggal 1 Januari 2023, jadwal WFH sudah ditiadakan.',
            'content'     => '<p>Per tanggal 1 Januari 2023, jadwal WFH sudah ditiadakan. Diharapkan seluruh karyawan sudah mulai WFO. Jika tidak, diharapkan ada izin tertulis.</p><p><em>Rifqi Faihan Akbar, Founder</em></p>',
            'banner_path' => null,
        ]);

        Announcement::create([
            'title'       => 'Maintenance Sistem',
            'slug'        => Str::slug('Maintenance Sistem'),
            'excerpt'     => 'Sistem akan maintenance pada Minggu, 5 Februari 2023.',
            'content'     => '<p>Sistem akan maintenance pada Minggu, 5 Februari 2023 mulai pukul 00:00 hingga 04:00. Harap menyimpan pekerjaan Anda sebelum waktu tersebut.</p>',
            'banner_path' => null,
        ]);
    }
} 