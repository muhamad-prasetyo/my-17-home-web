<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Cuti Tahunan',
                'description' => 'Cuti tahunan yang diberikan setiap tahun',
                'requires_balance' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Cuti Sakit',
                'description' => 'Cuti karena sakit dengan surat dokter',
                'requires_balance' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Cuti Melahirkan',
                'description' => 'Cuti untuk karyawan yang melahirkan',
                'requires_balance' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Cuti Penting',
                'description' => 'Cuti untuk keperluan penting seperti pernikahan, kematian keluarga, dll',
                'requires_balance' => true,
                'is_active' => true,
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            \App\Models\LeaveType::firstOrCreate(['name' => $leaveType['name']], $leaveType);
        }
    }
}