<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class PresentEmployeesWidget extends Widget
{
    protected static ?string $pollingInterval = '30s';

    protected static string $view = 'filament.widgets.present-employees-widget';

    protected int | string | array $columnSpan = 'full';

    public $presentEmployees;

    public function mount(): void
    {
        $today = Carbon::today();

        $this->presentEmployees = User::whereHas('attendances', function ($query) use ($today) {
            $query->whereDate('date', $today)
                  ->whereNotNull('time_in');
        })
        ->get()
        ->map(function ($user) {
            $user->avatar_url = $user->avatar_url ?? asset('images/default_avatar.png');
            return $user;
        });
    }
} 