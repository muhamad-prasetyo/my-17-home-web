<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Attendance;
use Carbon\Carbon;

class AvgCheckInChart extends ChartWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static ?string $heading = 'Avg Check In';

    protected function getData(): array
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(29); // 30 days including today

        $period = Carbon::parse($startDate)->daysUntil($endDate);

        $labels = [];
        $dailyAvgCheckInData = [];

        foreach ($period as $date) {
            $formattedDate = $date->format('D d M');
            $labels[] = $formattedDate;
            $dailyAvgCheckInData[$formattedDate] = ['totalMinutes' => 0, 'count' => 0];
        }

        $attendances = Attendance::select('date', 'time_in')
            ->whereNotNull('time_in')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        foreach ($attendances as $attendance) {
            $formattedDate = Carbon::parse($attendance->date)->format('D d M');
            if (!isset($dailyAvgCheckInData[$formattedDate])) {
                // Lewati data yang tidak ada di range period
                continue;
            }
            if ($attendance->time_in) {
                list($hours, $minutes, $seconds) = array_map('intval', explode(':', $attendance->time_in));
                $dailyAvgCheckInData[$formattedDate]['totalMinutes'] += ($hours * 60) + $minutes;
                $dailyAvgCheckInData[$formattedDate]['count']++;
            }
        }

        $avgCheckInData = [];
        $labelsWithTime = [];
        foreach ($labels as $label) {
            $totalMinutes = $dailyAvgCheckInData[$label]['totalMinutes'];
            $count = $dailyAvgCheckInData[$label]['count'];
            $avg = $count > 0 ? $totalMinutes / $count : 0;
            $avgCheckInData[] = $avg;
            // Format waktu rata-rata
            $hours = floor($avg / 60);
            $minutes = floor($avg % 60);
            $formattedTime = sprintf('%02d:%02d', $hours, $minutes);
            $labelsWithTime[] = $label . "\n" . $formattedTime;
        }

        return [
            'labels' => $labelsWithTime,
            'datasets' => [
                [
                    'label' => 'Rata-rata Check In',
                    'data' => $avgCheckInData,
                    'backgroundColor' => '#87CEEB', // Sky Blue
                    'borderColor' => '#4682B4', // Steel Blue
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'ticks' => [
                        'callback' => 'function(value) { 
                            let hours = Math.floor(value / 60);
                            let minutes = Math.floor(value % 60);
                            return (hours < 10 ? "0" : "") + hours + ":" + (minutes < 10 ? "0" : "") + minutes;
                        }',
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            let value = context.raw;
                            let hours = Math.floor(value / 60);
                            let minutes = Math.floor(value % 60);
                            return "Avg Check In: " + (hours < 10 ? "0" : "") + hours + ":" + (minutes < 10 ? "0" : "") + minutes;
                        }',
                    ],
                ],
            ],
        ];
    }
} 