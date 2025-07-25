<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\TransferRequest;
use Carbon\Carbon;

class ActiveTransferRequestChart extends ChartWidget
{
    protected static ?string $heading = 'Permintaan Transfer Aktif';
    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subMonths(11)->startOfMonth(); // 12 bulan termasuk bulan ini

        $period = Carbon::parse($startDate)->monthsUntil($endDate);

        $labels = [];
        $monthlyTransferData = [];

        foreach ($period as $date) {
            $formattedMonth = $date->format('M Y');
            $labels[] = $formattedMonth;
            $monthlyTransferData[$formattedMonth] = 0;
        }

        $transfers = TransferRequest::select('request_date')
            ->where('status', 'pending')
            ->whereBetween('request_date', [$startDate, $endDate])
            ->get();

        foreach ($transfers as $transfer) {
            $formattedMonth = Carbon::parse($transfer->request_date)->format('M Y');
            if (isset($monthlyTransferData[$formattedMonth])) {
                $monthlyTransferData[$formattedMonth]++;
            }
        }

        $transferData = [];
        foreach ($labels as $label) {
            $transferData[] = $monthlyTransferData[$label];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Jumlah Permintaan Transfer Aktif',
                    'data' => $transferData,
                    'backgroundColor' => '#ADD8E6', // Light Blue
                    'borderColor' => '#87CEEB', // Sky Blue
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