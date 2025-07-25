<?php

namespace App\Listeners;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendPermissionRequestNotification
{
    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        Log::info('[DEBUG] SendPermissionRequestNotification handle method CALLED for event: ' . get_class($event));

        $permission = $event->permission ?? $event;
        $user = $permission->user;

        if (!$user) {
            Log::warning('[PERMISSION] User not found for permission ID: ' . $permission->id);
            return;
        }

        // Kirim notifikasi ke admin (Filament dashboard)
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
            \Log::error('Gagal mengambil admin users untuk notifikasi izin: ' . $e->getMessage());
        }

        $adminUsers = $adminUsers->unique('id');
        $userName = $user->name;
        $dateFormatted = Carbon::parse($permission->date_permission)->translatedFormat('d F Y');
        $adminTitle = 'Pengajuan Izin/Laporan Baru';
        $adminBody = "$userName mengajukan izin/laporan untuk tanggal $dateFormatted.";

        if ($adminUsers->isEmpty()) {
            \Log::error('Tidak ada admin (super_admin/hrd) yang ditemukan untuk menerima notifikasi izin.');
        }

        foreach ($adminUsers as $admin) {
            \Log::info('Mengirim notifikasi izin ke admin', ['admin_id' => $admin->id, 'permission_id' => $permission->id]);
            $admin->notify(new \App\Notifications\PermissionRequestNotification([
                'title' => $adminTitle,
                'body' => $adminBody,
                'permission_id' => $permission->id,
                'type' => 'permission_request',
            ]));
            \Log::info('Notifikasi izin berhasil dikirim ke admin', ['admin_id' => $admin->id, 'permission_id' => $permission->id]);
        }
    }
} 