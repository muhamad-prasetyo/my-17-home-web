<?php

namespace App\Providers;

use App\Filament\Widgets\AttendanceCalendarWidget;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\LeaveRequest;
use App\Observers\LeaveRequestObserver;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Schema::defaultStringLength(191);

        // Observe LeaveRequest for approval and balance update
        LeaveRequest::observe(LeaveRequestObserver::class);

        // Manual Livewire Component Registration
        if (class_exists(AttendanceCalendarWidget::class)) {
            Livewire::component('filament.widgets.attendance-calendar-widget', AttendanceCalendarWidget::class);
        }
    }
}
