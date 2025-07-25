<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Models\Attendance;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\LeaveRequest;

class UserController extends Controller
{
    //get by user id
    public function getUserId($id)
    {
        $user = User::with(['schedule.office'])->find($id);

        if (!$user) {
            return response([
                'status' => 'Error',
                'message' => 'User not found',
            ], 404);
        }

        // Fallback: jika avatar kosong, isi dengan image_url
        if (!$user->avatar && $user->image_url) {
            $user->avatar = $user->image_url;
        }

        // Calculate Monthly Presence (total attendance days in current month)
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $monthlyPresence = Attendance::where('user_id', $id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->distinct('date')
            ->count();

        // Calculate Total Approved Leave Days
        $approvedLeaves = LeaveRequest::where('user_id', $id)
            ->where('status', 'approved')
            ->get();

        $totalLeaveDays = 0;
        foreach ($approvedLeaves as $leave) {
            $startDate = Carbon::parse($leave->start_date);
            $endDate = Carbon::parse($leave->end_date);
            $totalLeaveDays += $startDate->diffInDays($endDate) + 1;
        }

        // Add custom attributes to the user model instance
        $user->monthly_presence = $monthlyPresence;
        $user->monthly_leave = $totalLeaveDays;

        // Tambahkan field jadwal lengkap jika ada
        $scheduleData = null;
        if ($user->schedule) {
            $scheduleData = [
                'id' => $user->schedule->id,
                'office_area' => $user->schedule->office ? $user->schedule->office->name : null,
                'schedule_name' => $user->schedule->schedule_name,
                'start_time' => $user->schedule->start_time,
                'end_time' => $user->schedule->end_time,
                'attendance_type' => $user->schedule->attendance_type ?? null,
            ];
        }

        Log::info("DEBUG: UserController@getUserId - User ID: {$user->id}, is_wfa: {$user->is_wfa}");
        return response([
            'status' => 'Success',
            'message' => 'User found',
            'data' => $user->makeVisible('face_embedding'),
            'user_schedule' => $scheduleData,
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
                'name' => 'required',
                'email' => 'required|email',
                'phone' => 'required',
                'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'tanggal_lahir' => 'nullable|date',
                'kewarganegaraan' => 'nullable|string|max:255',
                'agama' => 'nullable|string|max:255',
                'jenis_kelamin' => 'nullable|string|in:Laki-Laki,Perempuan',
                'status_pernikahan' => 'nullable|string|in:Lajang,Menikah,Bercerai',
                'waktu_kontrak' => 'nullable|string|max:255',
                'tinggi_badan' => 'nullable|integer',
                'berat_badan' => 'nullable|integer',
                'golongan_darah' => 'nullable|string|in:A,B,AB,O',
                'gangguan_penglihatan' => 'nullable|string|in:Ya,Tidak',
                'buta_warna' => 'nullable|string|in:Ya,Tidak',
            ]);

            $user = User::find($request->id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;

            // Update new fields
            if ($request->has('tanggal_lahir')) {
                $user->tanggal_lahir = $request->tanggal_lahir;
            }
            if ($request->has('kewarganegaraan')) {
                $user->kewarganegaraan = $request->kewarganegaraan;
            }
            if ($request->has('agama')) {
                $user->agama = $request->agama;
            }
            if ($request->has('jenis_kelamin')) {
                $user->jenis_kelamin = $request->jenis_kelamin;
            }
            if ($request->has('status_pernikahan')) {
                $user->status_pernikahan = $request->status_pernikahan;
            }
            if ($request->has('waktu_kontrak')) {
                $user->waktu_kontrak = $request->waktu_kontrak;
            }
            if ($request->has('tinggi_badan')) {
                $user->tinggi_badan = $request->tinggi_badan;
            }
            if ($request->has('berat_badan')) {
                $user->berat_badan = $request->berat_badan;
            }
            if ($request->has('golongan_darah')) {
                $user->golongan_darah = $request->golongan_darah;
            }
            if ($request->has('gangguan_penglihatan')) {
                $user->gangguan_penglihatan = $request->gangguan_penglihatan;
            }
            if ($request->has('buta_warna')) {
                $user->buta_warna = $request->buta_warna;
            }

            if ($request->hasFile('image')) {
                // Hapus gambar lama jika ada
                if ($user->image_url) {
                    Storage::disk('public')->delete($user->image_url);
                }

                $image = $request->file('image');
                $image_name = time() . '.' . $image->getClientOriginalExtension();
                $filePath = $image->storeAs('images/users', $image_name, 'public');
                $user->image_url = $filePath;
            }
            $user->save();
            // Fallback: jika avatar kosong, isi dengan image_url
            if (!$user->avatar && $user->image_url) {
                $user->avatar = $user->image_url;
            }
            return response([
                'status' => 'Success',
                'message' => 'Update user success',
                'data' => $user->makeVisible('face_embedding'),
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
            ]);
        }
    }

    // Get user and attendance data by user ID and date
    public function getUserAttendanceByDate(Request $request, $userId)
    {
        // Validasi input tanggal
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $user = User::find($userId);

        if (!$user) {
            return response([
                'status' => 'Error',
                'message' => 'User not found',
                'data' => null
            ], 404);
        }

        Log::info("DEBUG: UserController@getUserAttendanceByDate - User ID: {$user->id}, is_wfa: {$user->is_wfa}");

        $date = $request->date;

        // Ambil data kehadiran untuk user dan tanggal tertentu
        $attendance = Attendance::where('user_id', $userId)
                                ->whereDate('date', $date)
                                ->first();

        return response([
            'status' => 'Success',
            'message' => 'User and attendance data retrieved successfully',
            'data' => [
                'user' => $user,
                'attendance' => $attendance // Akan null jika tidak ada data kehadiran pada tanggal tersebut
            ]
        ], 200);
    }

    // APPROVE USER OLEH ADMIN/HRD
    public function approveUser($id)
    {
        $user = \App\Models\User::findOrFail($id);
        $user->is_approved = true;
        $user->approved_at = now();
        $user->save();
        return response(['message' => 'User approved!']);
    }


    public function getUserDevices(Request $request) {
        $user = $request->user();
        $devices = $user->deviceTokens()->get();
        return response([
            'status' => 'Success',
            'data' => $devices
        ], 200);
    }
}
