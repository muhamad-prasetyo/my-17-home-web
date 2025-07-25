<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Notifications\AbsensiCheckinNotification;
use App\Models\User;
use Carbon\Carbon;
use App\Models\FakeGpsLog;

class ReportFakeGpsController extends Controller
{
    public function report(Request $request)
    {
        $user = Auth::user();
        $isMockLocation = $request->input('is_mock_location', false);
        Log::info('DEBUG API REPORT FAKE GPS', [
            'user_id' => $user ? $user->id : null,
            'is_mock_location' => $isMockLocation,
            'request' => $request->all()
        ]);
        if ($isMockLocation && $user) {
            // Simpan log pelanggaran ke tabel fake_gps_logs
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $deviceInfo = $request->input('device_info');
            $ipAddress = $request->ip();
            $detectedAt = now();
            FakeGpsLog::create([
                'user_id' => $user->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'device_info' => $deviceInfo,
                'ip_address' => $ipAddress,
                'detected_at' => $detectedAt,
            ]);
            // Kirim notifikasi ke admin
            $adminUsers = collect();
            try {
                if (\Spatie\Permission\Models\Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
                    $adminUsers = $adminUsers->merge(User::role('super_admin')->get());
                }
                if (\Spatie\Permission\Models\Role::where('name', 'hrd')->where('guard_name', 'web')->exists()) {
                    $adminUsers = $adminUsers->merge(User::role('hrd')->get());
                }
            } catch (\Throwable $e) {
                Log::error('Gagal mengambil admin users untuk notifikasi fake GPS: ' . $e->getMessage());
            }
            $adminUsers = $adminUsers->unique('id');
            $userName = $user->name;
            $dateFormatted = Carbon::now()->translatedFormat('d F Y');
            $timeFormatted = Carbon::now()->format('H:i');
            $adminTitle = "Peringatan Fake GPS";
            $adminBody = "$userName terdeteksi menggunakan Fake GPS pada tanggal $dateFormatted pukul $timeFormatted (bukan saat absen).";
            foreach ($adminUsers as $admin) {
                $admin->notify(new AbsensiCheckinNotification([
                    'title' => $adminTitle,
                    'body' => $adminBody,
                    'attendance_id' => null,
                    'type' => 'attendance',
                ]));
            }
            Log::info('DEBUG API REPORT FAKE GPS NOTIFIKASI DIKIRIM', [
                'user_id' => $user->id,
                'admin_count' => $adminUsers->count(),
                'admin_ids' => $adminUsers->pluck('id')->toArray()
            ]);
            return response()->json(['message' => 'Fake GPS terdeteksi! Admin telah diberi notifikasi.'], 200);
        }
        return response()->json(['message' => 'Tidak ada fake GPS terdeteksi atau user tidak ditemukan.'], 400);
    }
} 