<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Office;
use App\Models\User;
use App\Models\Attendance;
use App\Models\TransferRequest;
use Carbon\Carbon;
use App\Models\UserDayOff;
use App\Models\LeaveRequest;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalOffices = Office::count();
        $activeEmployees = User::count(); // Asumsi semua user aktif

        // Get today's date for attendance calculations
        $today = Carbon::today();

        $totalPresentToday = Attendance::whereDate('date', $today)
            ->whereNotNull('time_in')
            ->count();

        $lateUsersToday = Attendance::whereDate('date', $today)
            ->where('is_late', true)
            ->count();

        $activeTransferRequests = TransferRequest::where('status', 'pending')
            ->count();

        // Tambahan statistik baru
        $totalLibur = UserDayOff::whereDate('date', $today)->count();
        $totalIzinCuti = LeaveRequest::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where('status', 'approved')
            ->distinct('user_id')
            ->count('user_id');

        return [
            Stat::make('Total Offices', $totalOffices)
                ->description('Jumlah kantor yang terdaftar')
                ->descriptionIcon('heroicon-s-building-office')
                ->color('primary'),
            Stat::make('Total User yang Telat', $lateUsersToday)
                ->description('Jumlah karyawan terlambat hari ini')
                ->descriptionIcon('heroicon-s-exclamation-triangle')
                ->color('danger'),
            Stat::make('Active Employees', $activeEmployees)
                ->description('Karyawan aktif saat ini')
                ->descriptionIcon('heroicon-s-user-group')
                ->color('success'),
            Stat::make('Total Present Today', $totalPresentToday)
                ->description('Karyawan hadir hari ini')
                ->descriptionIcon('heroicon-s-check-circle')
                ->color('warning'),
            Stat::make('Permintaan Transfer Aktif', $activeTransferRequests)
                ->description('Permintaan transfer yang belum diproses')
                ->descriptionIcon('heroicon-s-arrow-path')
                ->color('info'),
            // Tambahan card baru
            Stat::make('Total Libur Hari Ini', $totalLibur)
                ->description('Jumlah karyawan yang libur hari ini')
                ->descriptionIcon('heroicon-s-calendar-days')
                ->color('info'),
            Stat::make('Total Izin/Cuti Hari Ini', $totalIzinCuti)
                ->description('Jumlah karyawan izin/cuti hari ini')
                ->descriptionIcon('heroicon-s-clipboard-document-check')
                ->color('warning'),
        ];
    }
} 