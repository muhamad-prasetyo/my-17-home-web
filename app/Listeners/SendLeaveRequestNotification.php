<?php

namespace App\Listeners;

// use App\Events\LeaveRequestApproved;
// use Kreait\Laravel\Firebase\Facades\FirebaseMessaging; // Komentari atau hapus jika app() berhasil
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendLeaveRequestNotification
{
    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        Log::info('[FCM DEBUG] SendLeaveRequestNotification handle method CALLED for event: '. get_class($event));

        $leave = $event->leaveRequest;
        $user = $leave->user;

        if (!$user) {
            Log::warning('[FCM] User not found for leave request ID: ' . $leave->id);
            return;
        }

        $token = $user->fcm_token;

        if (empty($token)) {
            Log::warning('[FCM] FCM token is empty for user ID: ' . $user->id . ' for leave request ID: ' . $leave->id);
            return;
        }
        
        Log::info('[FCM] Preparing to send notification for leave_request_id: ' . $leave->id . ' to user_id: ' . $user->id . ' with token: ' . $token);

        // Build notification title and body based on status
        $status = $leave->status;
        // Format tanggal ke Indonesia
        $start = Carbon::parse($leave->start_date)->translatedFormat('d F Y');
        $end = Carbon::parse($leave->end_date)->translatedFormat('d F Y');
        if ($start === $end) {
            $tanggal = $start;
        } else {
            $tanggal = "$start sampai $end";
        }
        switch ($status) {
            case 'pending':
                $title = 'Pengajuan Cuti Diterima';
                $body = "Pengajuan cuti Anda untuk tanggal $tanggal telah diterima dan menunggu persetujuan.";
                break;
            case 'approved':
                $title = 'Pengajuan Cuti Disetujui';
                $body = "Pengajuan cuti Anda untuk tanggal $tanggal telah disetujui.";
                break;
            case 'rejected':
                $title = 'Pengajuan Cuti Ditolak';
                $body = "Pengajuan cuti Anda untuk tanggal $tanggal ditolak.";
                break;
            case 'cancelled':
                $title = 'Pengajuan Cuti Dibatalkan';
                $body = "Pengajuan cuti Anda untuk tanggal $tanggal telah dibatalkan.";
                break;
            default:
                $title = 'Status Pengajuan Cuti';
                $body = "Status pengajuan cuti Anda: $status.";
        }
        $notificationPayload = Notification::create($title, $body);

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification($notificationPayload)
            ->withData([
                'type' => 'leave_status_update',
                'leave_request_id' => (string)$leave->id,
                'status' => (string)$status,
            ]);

        // Proses kirim FCM ke user (dibungkus try-catch agar error FCM tidak hentikan proses lain)
        try {
            Log::info('[FCM] Attempting to send notification via app("firebase.messaging")->send()');
            $messaging = app('firebase.messaging'); // Gunakan app() untuk mendapatkan service instance
            // Send to the requesting user
            $messaging->send($message);
            Log::info('[FCM] Notification sent successfully to user ID: ' . $user->id . ' for leave ID: ' . $leave->id);
            
            // If approved, also notify all other users sharing the same schedule
            if ($status === 'approved') {
                $scheduleId = $leave->user->schedule_id;
                if ($scheduleId) {
                    $others = User::where('schedule_id', $scheduleId)
                        ->whereNotNull('fcm_token')
                        ->get();
                    foreach ($others as $other) {
                        // Skip original user to avoid duplicate
                        if ($other->id === $user->id) continue;
                        $otherToken = $other->fcm_token;
                        if (empty($otherToken)) continue;
                        $msg = CloudMessage::withTarget('token', $otherToken)
                            ->withNotification($notificationPayload)
                            ->withData([
                                'type' => 'leave_status_update',
                                'leave_request_id' => (string)$leave->id,
                                'status' => 'approved',
                            ]);
                        $messaging->send($msg);
                        Log::info('[FCM] Team notification sent to user ID: ' . $other->id);
                    }
                }
            }

            // Kirim notifikasi ke admin (Filament dashboard) - SELALU DIJALANKAN
            if ($status === 'pending') {
                $adminUsers = collect();
                try {
                    if (\Spatie\Permission\Models\Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
                        $adminUsers = $adminUsers->merge(\App\Models\User::role('super_admin')->get());
                    } else {
                        \Log::warning('Role super_admin tidak ditemukan pada guard web');
                    }
                    if (\Spatie\Permission\Models\Role::where('name', 'hrd')->where('guard_name', 'web')->exists()) {
                        $adminUsers = $adminUsers->merge(\App\Models\User::role('hrd')->get());
                    } else {
                        \Log::warning('Role hrd tidak ditemukan pada guard web');
                    }
                } catch (\Throwable $e) {
                    \Log::error('Gagal mengambil admin users untuk notifikasi cuti: ' . $e->getMessage());
                }
                $adminUsers = $adminUsers->unique('id');
                $userName = $user->name;
                $adminTitle = 'Pengajuan Cuti Baru';
                $adminBody = "$userName mengajukan cuti untuk tanggal $tanggal.";
                if ($adminUsers->isEmpty()) {
                    \Log::error('Tidak ada admin (super_admin/hrd) yang ditemukan untuk menerima notifikasi cuti.');
                }
                foreach ($adminUsers as $admin) {
                    \Log::info('Mengirim notifikasi cuti ke admin', ['admin_id' => $admin->id, 'leave_id' => $leave->id]);
                    $admin->notify(new \App\Notifications\LeaveRequestNotification([
                        'title' => $adminTitle,
                        'body' => $adminBody,
                        'leave_request_id' => $leave->id,
                        'type' => 'leave_request',
                    ]));
                    \Log::info('Notifikasi cuti berhasil dikirim ke admin', ['admin_id' => $admin->id, 'leave_id' => $leave->id]);
                }
            }
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error('[FCM] Failed to send FCM notification (MessagingException) to user ID: ' . $user->id . '. Error: ' . $e->getMessage());
        } catch (\Throwable $e) { 
            Log::error('[FCM] An unexpected error occurred (Throwable) while sending FCM to user ID: ' . $user->id . '. Error: ' . $e->getMessage() . ' Line: ' . $e->getLine() . ' File: ' . $e->getFile());
        }
    }
} 