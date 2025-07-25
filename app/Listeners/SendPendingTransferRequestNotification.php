<?php

namespace App\Listeners;

use App\Events\TransferRequestCreated;
use Carbon\Carbon;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FCMNotification;
use Illuminate\Support\Facades\Log;

class SendPendingTransferRequestNotification
{
    public function handle(TransferRequestCreated $event): void
    {
        $transfer = $event->transferRequest;
        $user = $transfer->user;

        // Kumpulkan token perangkat
        $tokens = [];
        if (!empty($user->fcm_token)) {
            $tokens[] = $user->fcm_token;
        }
        if (method_exists($user, 'deviceTokens')) {
            $tokens = array_merge($tokens, $user->deviceTokens()->pluck('device_token')->toArray());
        }
        if (empty($tokens)) {
            Log::info("[FCM Pending Transfer] Tidak ada token device untuk User ID: {$user->id}");
            return;
        }

        // Buat payload notifikasi
        $formattedDate = Carbon::parse($transfer->effective_date)->format('d F Y');
        $notification = FCMNotification::create(
            'Permintaan Transfer Diajukan',
            "Permintaan transfer Anda ke outlet {$transfer->targetSchedule->office->name} efektif pada {$formattedDate} telah diajukan dan berstatus pending."
        );

        foreach ($tokens as $token) {
            if (empty($token)) {
                continue;
            }
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withData([
                        'type' => 'transfer_pending',
                        'transfer_request_id' => (string)$transfer->id,
                    ]);
                app('firebase.messaging')->send($message);
                Log::info("[FCM Pending Transfer] Notifikasi terkirim ke token: {$token}");
            } catch (\Throwable $e) {
                Log::error("[FCM Pending Transfer] Gagal mengirim ke token {$token}: " . $e->getMessage());
                // Hapus token jika error 'Requested entity was not found'
                if (strpos($e->getMessage(), 'Requested entity was not found') !== false) {
                    // Hapus dari kolom fcm_token user jika sama
                    if ($user->fcm_token === $token) {
                        $user->fcm_token = null;
                        $user->save();
                        Log::info("[FCM Pending Transfer] Token utama user dihapus: {$token}");
                    }
                    // Hapus dari tabel device_tokens jika ada relasi
                    if (method_exists($user, 'deviceTokens')) {
                        $user->deviceTokens()->where('device_token', $token)->delete();
                        Log::info("[FCM Pending Transfer] Token device dihapus dari tabel device_tokens: {$token}");
                    }
                }
            }
        }

        // Kirim notifikasi ke admin (Filament dashboard)
        $adminUsers = collect();
        try {
            if (\Spatie\Permission\Models\Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
                $adminUsers = $adminUsers->merge(\App\Models\User::role('super_admin')->get());
            }
            if (\Spatie\Permission\Models\Role::where('name', 'hrd')->where('guard_name', 'web')->exists()) {
                $adminUsers = $adminUsers->merge(\App\Models\User::role('hrd')->get());
            }
        } catch (\Throwable $e) {
            \Log::error('Gagal mengambil admin users untuk notifikasi transfer: ' . $e->getMessage());
        }
        $adminUsers = $adminUsers->unique('id');
        $userName = $user->name;
        $adminTitle = 'Permintaan Transfer Baru';
        $adminBody = "$userName mengajukan transfer ke outlet {$transfer->targetSchedule->office->name} pada {$formattedDate}.";
        foreach ($adminUsers as $admin) {
            \Log::info('Mengirim notifikasi transfer ke admin', ['admin_id' => $admin->id, 'transfer_id' => $transfer->id]);
            $admin->notify(new \App\Notifications\TransferRequestNotification([
                'title' => $adminTitle,
                'body' => $adminBody,
                'transfer_request_id' => $transfer->id,
                'type' => 'transfer_request',
            ]));
            \Log::info('Notifikasi transfer berhasil dikirim ke admin', ['admin_id' => $admin->id, 'transfer_id' => $transfer->id]);
        }
    }
} 