<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function afterCreate(): void
    {
        // Kirim FCM ke semua user setelah pengumuman dibuat
        $announcement = $this->record;
        $title = 'Pengumuman Baru';
        $body = $announcement->title . ' - ' . ($announcement->excerpt ?? '');
        $dataPayload = [
            'type' => 'announcement',
            'announcement_id' => (string)$announcement->id,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];
        $users = \App\Models\User::all();
        $messaging = app('firebase.messaging');
        $notification = \Kreait\Firebase\Messaging\Notification::create($title, $body);
        foreach ($users as $user) {
            $tokens = [];
            if (!empty($user->fcm_token)) {
                $tokens[] = $user->fcm_token;
            }
            if (method_exists($user, 'deviceTokens')) {
                $tokens = array_merge($tokens, $user->deviceTokens()->pluck('device_token')->toArray());
            }
            $tokens = array_filter(array_unique($tokens));
            foreach ($tokens as $token) {
                try {
                    $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $token)
                        ->withNotification($notification)
                        ->withData($dataPayload);
                    $messaging->send($message);
                } catch (\Throwable $e) {
                    \Log::error('[FCM Announcement] Gagal kirim ke token ' . $token . ': ' . $e->getMessage());
                }
            }
        }
    }
}
