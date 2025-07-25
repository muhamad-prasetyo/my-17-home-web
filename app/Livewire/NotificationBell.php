<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationBell extends Component
{
    public $unreadNotificationsCount = 0;
    public $unreadNotifications = [];

    protected static ?string $pollingInterval = '5s'; // Poll every 5 seconds

    public function mount()
    {
        $this->getUnreadNotifications();
    }

    public function getUnreadNotifications()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $this->unreadNotifications = $user->unreadNotifications()->limit(5)->get(); // Ambil 5 notifikasi terakhir
            $this->unreadNotificationsCount = $user->unreadNotifications()->count();
        }
    }

    public function markAllAsRead()
    {
        if (Auth::check()) {
            Auth::user()->unreadNotifications->markAsRead();
            $this->getUnreadNotifications(); // Refresh the count and list
            Notification::make()
                ->title('Semua notifikasi ditandai sudah dibaca.')
                ->success()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
} 