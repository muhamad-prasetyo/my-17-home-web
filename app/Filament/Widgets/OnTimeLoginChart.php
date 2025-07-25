<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Attendance;
use Carbon\Carbon;

class OnTimeLoginChart extends ChartWidget
{
    protected static ?string $heading = 'On Time Login';
    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subMonths(11)->startOfMonth(); // 12 months including current month

        $period = Carbon::parse($startDate)->monthsUntil($endDate);

        $labels = [];
        $monthlyOnTimeData = [];

        foreach ($period as $date) {
            $formattedMonth = $date->format('M Y');
            $labels[] = $formattedMonth;
            $monthlyOnTimeData[$formattedMonth] = 0;
        }

        $attendances = Attendance::select('date', 'is_late')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        foreach ($attendances as $attendance) {
            $formattedMonth = Carbon::parse($attendance->date)->format('M Y');
            if (!$attendance->is_late) {
                $monthlyOnTimeData[$formattedMonth]++;
            }
        }

        $onTimeData = [];
        foreach ($labels as $label) {
            $onTimeData[] = $monthlyOnTimeData[$label];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'On Time Absensi',
                    'data' => $onTimeData,
                    'backgroundColor' => '#90EE90', // Light Green
                    'borderColor' => '#32CD32', // Lime Green
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