<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class OnLeavesChart extends ChartWidget
{
    protected static ?string $heading = 'On Leaves';
    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subMonths(11)->startOfMonth(); // 12 months including current month

        $period = Carbon::parse($startDate)->monthsUntil($endDate);

        $labels = [];
        $monthlyLeavesData = [];

        foreach ($period as $date) {
            $formattedMonth = $date->format('M Y');
            $labels[] = $formattedMonth;
            $monthlyLeavesData[$formattedMonth] = 0;
        }

        $leaves = LeaveRequest::select('start_date')
            ->whereBetween('start_date', [$startDate, $endDate])
            ->get();

        foreach ($leaves as $leave) {
            $formattedMonth = Carbon::parse($leave->start_date)->format('M Y');
            $monthlyLeavesData[$formattedMonth]++;
        }

        $leavesData = [];
        foreach ($labels as $label) {
            $leavesData[] = $monthlyLeavesData[$label];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Jumlah Cuti',
                    'data' => $leavesData,
                    'backgroundColor' => '#FFD700', // Gold
                    'borderColor' => '#FFA500', // Orange
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
} 