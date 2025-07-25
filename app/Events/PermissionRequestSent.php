<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PermissionRequestSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;
    public string $userName;
    public string $requestType;
    public $permissionId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $message, string $userName, string $requestType, $permissionId = null)
    {
        $this->message = $message;
        $this->userName = $userName;
        $this->requestType = $requestType;
        $this->permissionId = $permissionId;

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
            \Log::error('Gagal mengambil admin users untuk notifikasi izin: ' . $e->getMessage());
        }
        $adminUsers = $adminUsers->unique('id');
        $isReport = stripos($requestType, 'report') !== false;
        $adminTitle = $isReport ? 'Permintaan Report Baru' : 'Permintaan Izin Baru';
        $adminBody = $isReport
            ? "$userName mengajukan report: $requestType."
            : "$userName mengajukan izin: $requestType.";
        foreach ($adminUsers as $admin) {
            \Log::info('Mengirim notifikasi izin ke admin', ['admin_id' => $admin->id, 'permission_id' => $permissionId]);
            $admin->notify(new \App\Notifications\PermissionRequestNotification([
                'title' => $adminTitle,
                'body' => $adminBody,
                'permission_id' => $permissionId,
                'type' => 'permission_request',
            ]));
            \Log::info('Notifikasi izin berhasil dikirim ke admin', ['admin_id' => $admin->id, 'permission_id' => $permissionId]);
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-notifications'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'permission.request.sent';
    }
}
