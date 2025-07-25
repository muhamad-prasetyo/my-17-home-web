<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class CustomDatabaseNotifications extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Notifikasi';
    protected static ?string $title = 'Notifikasi';
    protected static ?string $slug = 'notifications';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.custom-database-notifications';

    public ?string $filterType = null;
    public ?string $filterStatus = null;
    public ?string $filterDate = null;
    public ?string $search = null;

    public function mount(): void
    {
        // Mark notifications as read when page is accessed
        Auth::user()->unreadNotifications->markAsRead();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_all_read')
                ->label('Tandai Semua Dibaca')
                ->icon('heroicon-o-check')
                ->action(function () {
                    Auth::user()->unreadNotifications->markAsRead();
                    $this->dispatch('$refresh');
                }),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('filterType')
                    ->label('Jenis Notifikasi')
                    ->options([
                        'leave_request' => 'Cuti',
                        'transfer_request' => 'Transfer',
                        'permission_request' => 'Izin/Report',
                        'attendance' => 'Absensi',
                    ])
                    ->placeholder('Semua Jenis')
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->dispatch('$refresh')),
                
                Select::make('filterStatus')
                    ->label('Status')
                    ->options([
                        'unread' => 'Belum Dibaca',
                        'read' => 'Sudah Dibaca',
                    ])
                    ->placeholder('Semua Status')
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->dispatch('$refresh')),
                
                DatePicker::make('filterDate')
                    ->label('Tanggal')
                    ->placeholder('Semua Tanggal')
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->dispatch('$refresh')),
            ]);
    }

    public function getNotificationUrl($notification)
    {
        $data = $notification->data;
        $type = $data['type'] ?? '';

        switch ($type) {
            case 'leave_request':
                $id = $data['leave_request_id'] ?? null;
                return $id ? "/admin/leave-requests/{$id}" : null;
            
            case 'transfer_request':
                $id = $data['transfer_request_id'] ?? null;
                return $id ? "/admin/transfer-requests/{$id}" : null;
            
            case 'permission_request':
                $id = $data['permission_id'] ?? null;
                return $id ? "/admin/laporan-karyawan/{$id}" : null;
            
            case 'attendance':
                $id = $data['attendance_id'] ?? null;
                return $id ? "/admin/attendances/{$id}" : null;
            
            default:
                return null;
        }
    }

    public function getNotificationIcon($notification)
    {
        $data = $notification->data;
        $type = $data['type'] ?? '';

        switch ($type) {
            case 'leave_request':
                return 'heroicon-o-calendar';
            
            case 'transfer_request':
                return 'heroicon-o-arrow-path';
            
            case 'permission_request':
                return 'heroicon-o-document-text';
            
            case 'attendance':
                return 'heroicon-o-clock';
            
            default:
                return 'heroicon-o-bell';
        }
    }

    public function getNotificationColor($notification)
    {
        $data = $notification->data;
        $type = $data['type'] ?? '';

        switch ($type) {
            case 'leave_request':
                return 'warning';
            
            case 'transfer_request':
                return 'info';
            
            case 'permission_request':
                return 'danger';
            
            case 'attendance':
                return 'success';
            
            default:
                return 'gray';
        }
    }

    public function getFilteredNotifications()
    {
        $notifications = Auth::user()->notifications;

        // Filter berdasarkan jenis
        if ($this->filterType) {
            $notifications = $notifications->filter(function ($notification) {
                return $notification->data['type'] === $this->filterType;
            });
        }

        // Filter berdasarkan status
        if ($this->filterStatus === 'unread') {
            $notifications = $notifications->whereNull('read_at');
        } elseif ($this->filterStatus === 'read') {
            $notifications = $notifications->whereNotNull('read_at');
        }

        // Filter berdasarkan tanggal
        if ($this->filterDate) {
            $notifications = $notifications->filter(function ($notification) {
                return $notification->created_at->format('Y-m-d') === $this->filterDate;
            });
        }

        // Filter berdasarkan search
        if ($this->search) {
            $notifications = $notifications->filter(function ($notification) {
                $title = $notification->data['title'] ?? '';
                $body = $notification->data['body'] ?? '';
                return stripos($title, $this->search) !== false || stripos($body, $this->search) !== false;
            });
        }

        return $notifications->sortByDesc('created_at');
    }

    public function getNotificationStats()
    {
        $notifications = Auth::user()->notifications;

        return [
            'total' => $notifications->count(),
            'unread' => $notifications->whereNull('read_at')->count(),
            'read' => $notifications->whereNotNull('read_at')->count(),
            'by_type' => [
                'leave_request' => $notifications->filter(fn($n) => ($n->data['type'] ?? null) === 'leave_request')->count(),
                'transfer_request' => $notifications->filter(fn($n) => ($n->data['type'] ?? null) === 'transfer_request')->count(),
                'permission_request' => $notifications->filter(fn($n) => ($n->data['type'] ?? null) === 'permission_request')->count(),
                'attendance' => $notifications->filter(fn($n) => ($n->data['type'] ?? null) === 'attendance')->count(),
            ]
        ];
    }

    protected function getViewData(): array
    {
        return [
            'notifications' => $this->getFilteredNotifications(),
            'stats' => $this->getNotificationStats(),
        ];
    }
} 