<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;
use App\Models\User; // Assuming you have a User model for admins
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\Log; // Ditambahkan

class RealtimeNotificationHandler extends Component
{
    #[On('echo-private:admin-notifications,permission.request.sent')]
    public function handlePermissionRequestSent($event)
    {
        Log::info('RealtimeNotificationHandler: Event permission.request.sent DITERIMA', $event); // Ditambahkan

        // Ensure we have an authenticated user in context (the admin viewing the panel)
        if (Auth::check()) {
            $loggedInUser = Auth::user();
            Log::info('RealtimeNotificationHandler: User terautentikasi, mengirim notifikasi ke user ID: ' . $loggedInUser->id); // Ditambahkan

            Notification::make()
                ->title('Permintaan Izin Baru Diterima')
                ->body($event['message'] . ' Oleh: ' . $event['userName'] . '. Jenis: ' . $event['requestType'])
                ->success()
                ->sendToDatabase($loggedInUser) // Send to the logged-in admin's database notifications
                ->broadcast($loggedInUser);   // Broadcast to the logged-in admin's browser session
        } else {
            Log::warning('RealtimeNotificationHandler: Tidak ada user terautentikasi, notifikasi tidak dikirim.'); // Ditambahkan
        }
        // If you want to notify ALL admins, even those not currently logged in:
        // $admins = User::where('role', 'admin')->get(); // Adjust role query as needed
        // if ($admins->isNotEmpty()) {
        //     Notification::make()
        //         ->title('Permintaan Izin Baru Diterima')
        //         ->body($event['message'] . ' Oleh: ' . $event['userName'] . '. Jenis: ' . $event['requestType'])
        //         ->success()
        //         ->sendToDatabase($admins)
        //         ->broadcast($admins);
        // }
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                {{-- This component handles realtime notifications and does not render visible UI --}}
            </div>
        BLADE;
    }
}
