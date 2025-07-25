<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class EmployeeAttendanceStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalEmployees = User::count();
        $today = Carbon::today();
        $dailyAttendance = Attendance::whereDate('date', $today)->count();

        return [
            Stat::make('Total Karyawan', $totalEmployees)
                ->description('Jumlah seluruh karyawan')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            Stat::make('Absensi Hari Ini', $dailyAttendance)
                ->description('Jumlah absensi pada tanggal ' . $today->format('d M Y'))
                ->chart([17, 16, 14, 15, 14, 13, 12])
                ->color('info'),
        ];
    }
}
