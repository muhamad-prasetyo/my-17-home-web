<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CustomNotificationBell extends Component
{
    public $notifications;
    public $unreadCount;
    public $open = false;

    public function mount()
    {
        $user = Auth::user();
        $this->notifications = $user->notifications->sortByDesc('created_at')->take(10);
        $this->unreadCount = $user->unreadNotifications->count();
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->mount();
    }

    public function render()
    {
        return view('livewire.custom-notification-bell');
    }
} 