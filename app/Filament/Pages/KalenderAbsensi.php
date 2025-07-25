<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AttendanceCalendarWidget;
use Filament\Pages\Page;

class KalenderAbsensi extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Manajemen Absensi';

    protected static string $view = 'filament.pages.kalender-absensi';

    protected static ?string $title = 'Kalender Absensi';

    protected function getHeaderWidgets(): array
    {
        return [
            AttendanceCalendarWidget::class,
        ];
    }
}
