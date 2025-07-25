<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class UserRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        $avatar = null;
        // Cek jika user punya avatar custom (misal: field avatar_url atau avatar di tabel users)
        if (isset($this->user->avatar_url) && $this->user->avatar_url) {
            $avatar = $this->user->avatar_url;
        } elseif (isset($this->user->avatar) && $this->user->avatar) {
            $avatar = $this->user->avatar;
        } else {
            // Fallback ke avatar default lokal (path benar)
            $avatar = asset('images/users/default_avatar.png');
        }
        return [
            'title' => 'User Baru Terdaftar',
            'body' => $this->user->name . ' telah berhasil mendaftar dengan email ' . $this->user->email,
            'user_id' => $this->user->id,
            'type' => 'user_registered',
            'avatar' => $avatar,
        ];
    }

    public function toDatabase($notifiable)
    {
        return $this->toArray($notifiable);
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
} 