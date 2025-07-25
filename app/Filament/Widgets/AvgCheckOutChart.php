<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Attendance;
use Carbon\Carbon;

class AvgCheckOutChart extends ChartWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static ?string $heading = 'Avg Check Out';

    protected function getData(): array
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(29); // 30 days including today

        $period = Carbon::parse($startDate)->daysUntil($endDate);

        $labels = [];
        $dailyAvgCheckOutData = [];

        foreach ($period as $date) {
            $formattedDate = $date->format('D d M');
            $labels[] = $formattedDate;
            $dailyAvgCheckOutData[$formattedDate] = ['totalMinutes' => 0, 'count' => 0];
        }

        $attendances = Attendance::select('date', 'time_out')
            ->whereNotNull('time_out')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        foreach ($attendances as $attendance) {
            $formattedDate = Carbon::parse($attendance->date)->format('D d M');
            if (!isset($dailyAvgCheckOutData[$formattedDate])) {
                // Lewati data yang tidak ada di range period
                continue;
            }
            if ($attendance->time_out) {
                list($hours, $minutes, $seconds) = array_map('intval', explode(':', $attendance->time_out));
                $dailyAvgCheckOutData[$formattedDate]['totalMinutes'] += ($hours * 60) + $minutes;
                $dailyAvgCheckOutData[$formattedDate]['count']++;
            }
        }

        $avgCheckOutData = [];
        $labelsWithTime = [];
        foreach ($labels as $label) {
            $totalMinutes = $dailyAvgCheckOutData[$label]['totalMinutes'];
            $count = $dailyAvgCheckOutData[$label]['count'];
            $avg = $count > 0 ? $totalMinutes / $count : 0;
            $avgCheckOutData[] = $avg;
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
                    'label' => 'Rata-rata Check Out',
                    'data' => $avgCheckOutData,
                    'backgroundColor' => '#FFC0CB', // Pink
                    'borderColor' => '#FF69B4', // Hot Pink
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
                            return "Avg Check Out: " + (hours < 10 ? "0" : "") + hours + ":" + (minutes < 10 ? "0" : "") + minutes;
                        }',
                    ],
                ],
            ],
        ];
    }
} 