<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\DashboardStatsOverview;
// Hapus atau komentari widget lain untuk isolasi masalah
// use App\Filament\Widgets\OnTimeLoginChart;
// use App\Filament\Widgets\OnLeavesChart;
// use App\Filament\Widgets\ActiveTransferRequestChart;
// use App\Filament\Widgets\PresentEmployeesWidget;
// use App\Filament\Widgets\AvgCheckInChart;
// use App\Filament\Widgets\AvgCheckOutChart;

class Overview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.overview';

  
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Overview';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\DashboardStatsOverview::class,
            \App\Filament\Widgets\OnTimeLoginChart::class,
            \App\Filament\Widgets\OnLeavesChart::class,
            \App\Filament\Widgets\ActiveTransferRequestChart::class,
            \App\Filament\Widgets\PresentEmployeesWidget::class,
            \App\Filament\Widgets\AvgCheckInChart::class,
            \App\Filament\Widgets\AvgCheckOutChart::class,
      
         
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Kosongkan untuk diagnostik
        ];
    }
} 