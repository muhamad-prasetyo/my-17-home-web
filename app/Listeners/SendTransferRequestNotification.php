<?php

namespace App\Listeners;

use App\Events\TransferRequestApproved;
use Kreait\Laravel\Firebase\Facades\FirebaseMessaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FCMNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendTransferRequestNotification
{
    public function handle(TransferRequestApproved $event): void
    {
        $transfer = $event->transferRequest;
        $user = $transfer->user;
        $targetOffice = $transfer->targetSchedule->office;
        $currentOffice = $transfer->currentSchedule->office;

        if (!$user || !$targetOffice || !$currentOffice) {
            Log::error("Transfer notification failed: Missing required data", [
                'transfer_id' => $transfer->id,
                'user_id' => $transfer->user_id
            ]);
            return;
        }

        // Prevent duplicate notifications
        $cacheKey = "transfer_notification_{$transfer->id}";
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, now()->addHours(24));

        // 1. Send notification to the transferred user
        $this->sendNotificationToUser(
            $user,
            'Transfer Request Approved',
            "Permintaan transfer Anda ke outlet {$targetOffice->name} efektif pada {$transfer->effective_date} telah disetujui."
        );

        // 2. Send notification to cashiers at current office
        $cashiers = $this->getCashiersFromOffice($currentOffice->id);
        foreach ($cashiers as $cashier) {
            $this->sendNotificationToUser(
                $cashier,
                'Informasi Transfer Karyawan',
                "{$user->name} akan dipindahkan dari outlet ini ke {$targetOffice->name} pada {$transfer->effective_date}."
            );
        }

        // 3. Send notification to all staff at target office
        $targetStaff = $this->getStaffFromOffice($targetOffice->id);
        foreach ($targetStaff as $staff) {
            // Skip if the staff is the transferred user
            if ($staff->id === $user->id) {
                continue;
            }
            
            $this->sendNotificationToUser(
                $staff,
                'Karyawan Baru Akan Bergabung',
                "{$user->name} akan bergabung dengan outlet ini mulai {$transfer->effective_date}."
            );
        }
    }

    /**
     * Send notification to a specific user using their device tokens
     */
    private function sendNotificationToUser($user, $title, $body)
    {
        // Gather all tokens: fcm_token field and deviceTokens relation
        $tokens = [];
        if (!empty($user->fcm_token)) {
            $tokens[] = $user->fcm_token;
        }
        if (method_exists($user, 'deviceTokens')) {
            $tokens = array_merge($tokens, $user->deviceTokens()->pluck('device_token')->toArray());
        }
        
        if (empty($tokens)) {
            Log::info("[FCM Transfer] No device tokens found for User ID: {$user->id}");
            return;
        }

        $notification = FCMNotification::create($title, $body);
        $dataPayload = [
            'type' => 'transfer_approved',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        foreach ($tokens as $token) {
            if (empty($token)) {
                continue;
            }
            
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withData($dataPayload);
                
                $messaging = app('firebase.messaging');
                $messaging->send($message);
                
                Log::info("[FCM Transfer] Notification sent to token: {$token} for user {$user->id}");
            } catch (\Throwable $e) {
                Log::error("[FCM Transfer] Failed to send to token {$token}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Get all cashiers from a specific office
     */
    private function getCashiersFromOffice($officeId)
    {
        return \App\Models\User::whereHas('roles', function ($query) {
                $query->where('name', 'kasir');
            })
            ->whereHas('schedule', function ($query) use ($officeId) {
                $query->whereHas('office', function ($subQuery) use ($officeId) {
                    $subQuery->where('id', $officeId);
                });
            })
            ->get();
    }

    /**
     * Get all staff from a specific office
     */
    private function getStaffFromOffice($officeId)
    {
        return \App\Models\User::whereHas('schedule', function ($query) use ($officeId) {
                $query->whereHas('office', function ($subQuery) use ($officeId) {
                    $subQuery->where('id', $officeId);
                });
            })
            ->get();
    }
} 