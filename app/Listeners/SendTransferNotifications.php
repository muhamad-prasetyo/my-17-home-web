<?php

namespace App\Listeners;

use App\Events\TransferRequestApproved;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Illuminate\Support\Facades\Cache;

class SendTransferNotifications
{
    /**
     * Handle the event.
     */
    public function handle(TransferRequestApproved $event): void
    {
        $transferRequest = $event->transferRequest;
        $user = $transferRequest->user;
        $targetOffice = $transferRequest->targetSchedule->office;
        $currentOffice = $transferRequest->currentSchedule->office;

        if (!$user || !$targetOffice || !$currentOffice) {
            Log::error("Transfer notification failed: Missing required data", [
                'transfer_id' => $transferRequest->id,
                'user_id' => $transferRequest->user_id
            ]);
            return;
        }

        // To prevent duplicate notifications if the event is processed multiple times
        $cacheKey = "transfer_notification_{$transferRequest->id}";
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, now()->addHours(24));

        // 1. Send notification to the transferred user
        $this->sendToUser(
            $user,
            "Transfer Disetujui", 
            "Permintaan transfer Anda ke {$targetOffice->name} telah disetujui dan akan berlaku pada {$transferRequest->effective_date}."
        );

        // 2. Send notification to cashiers at the current office
        $cashiers = $this->getCashiersFromOffice($currentOffice->id);
        foreach ($cashiers as $cashier) {
            $this->sendToUser(
                $cashier,
                "Informasi Transfer Karyawan",
                "{$user->name} akan dipindahkan dari outlet ini ke {$targetOffice->name} pada {$transferRequest->effective_date}."
            );
        }

        // 3. Send notification to all staff at the target office
        $targetStaff = $this->getAllStaffFromOffice($targetOffice->id);
        foreach ($targetStaff as $staff) {
            // Skip if the staff is the transferred user
            if ($staff->id === $user->id) {
                continue;
            }
            
            $this->sendToUser(
                $staff,
                "Karyawan Baru Akan Bergabung",
                "{$user->name} akan bergabung dengan outlet ini mulai {$transferRequest->effective_date}."
            );
        }
    }

    /**
     * Send FCM notification to a user
     */
    private function sendToUser($user, $title, $body)
    {
        if (!$user->fcm_token) {
            Log::info("Cannot send transfer notification: No FCM token for user {$user->id}");
            return;
        }

        try {
            $messaging = app('firebase.messaging');
            $notification = FirebaseNotification::create($title, $body);
            
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($notification)
                ->withData([
                    'type' => 'transfer',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);
                
            $messaging->send($message);
            
            Log::info("Transfer notification sent to user {$user->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send transfer notification to user {$user->id}: {$e->getMessage()}");
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
    private function getAllStaffFromOffice($officeId)
    {
        return \App\Models\User::whereHas('schedule', function ($query) use ($officeId) {
                $query->whereHas('office', function ($subQuery) use ($officeId) {
                    $subQuery->where('id', $officeId);
                });
            })
            ->get();
    }
} 