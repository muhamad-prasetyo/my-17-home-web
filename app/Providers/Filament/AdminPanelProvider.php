<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Support\Facades\Blade;
use BezhanSalleh\FilamentShield\Resources\RoleResource;
use Filament\Navigation\NavigationGroup;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use Illuminate\Support\Facades\App;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Filament\Facades\Filament;
use Filament\Navigation\UserMenuItem;
use Filament\Navigation\NavigationItem;
use Livewire\Livewire;
// use App\Filament\Widgets\DailyAttendanceChart;
// use App\Filament\Widgets\EmployeeAttendanceStats;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Configure the Filament panel.
     */
    public function panel(Panel $panel): Panel
    {
        // Ensure $panel is not null, though it should always be provided by Filament
        // This is a speculative fix for the linter error.
        if ($panel === null) {
             // Potentially throw an exception or handle this unexpected state
             // For now, we assume Filament always provides a Panel instance.
        }

        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: App::path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: App::path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Overview::class,
                \App\Filament\Pages\CustomDatabaseNotifications::class,
            ])
            ->widgets([
                \App\Filament\Widgets\DashboardStatsOverview::class,
                \App\Filament\Widgets\OnTimeLoginChart::class,
                \App\Filament\Widgets\OnLeavesChart::class,
                \App\Filament\Widgets\ActiveTransferRequestChart::class,
                \App\Filament\Widgets\PresentEmployeesWidget::class,
                \App\Filament\Widgets\AvgCheckInChart::class,
                \App\Filament\Widgets\AvgCheckOutChart::class,
                // Tambahkan widget lain di sini jika ada
            ])
            ->navigationItems([
                NavigationItem::make()
                    ->label('Overview')
                    ->icon('heroicon-o-chart-bar')
                    ->url('/admin/overview')
                    ->sort(0),
                NavigationItem::make()
                    ->label('Pengumuman')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->url('/admin/announcements')
                    ->sort(1),
                NavigationItem::make()
                    ->label('Peta Lokasi')
                    ->icon('heroicon-o-map')
                    ->url('/admin/map')
                    ->sort(2),
                NavigationItem::make()
                    ->label('Notifikasi')
                    ->icon('heroicon-o-bell')
                    ->url('/admin/notifications')
                    ->sort(3)
                    ->badge(fn () => \Illuminate\Support\Facades\Auth::user()?->unreadNotifications?->count() ?? 0),
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Manajemen Absensi')
                    ->icon('heroicon-o-clock'),
                NavigationGroup::make()
                    ->label('Manajemen Cuti & Izin')
                    ->icon('heroicon-o-calendar'),
                NavigationGroup::make()
                    ->label('Manajemen Karyawan')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make()
                    ->label('Pengaturan Perusahaan')
                    ->icon('heroicon-o-building-office-2'),
                NavigationGroup::make()
                    ->label('Setting Dashboard'),
                NavigationGroup::make()
                    ->label('Filament Shield'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                \Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin::make(),
                FilamentFullCalendarPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => Blade::render('<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>')
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => Blade::render('<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>')
            )
            ->renderHook(
                'panels::head.end',
                fn (): string => Blade::render('@vite([\'resources/js/app.js\'])')
            )
            ->renderHook(
                'panels::topbar.start',
                fn (): string => Blade::render('@livewire("custom-notification-bell")')
            )
            ->userMenuItems([
                // User menu items removed as per the instructions
            ]);
        return $panel;
    }
}

