<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\TransferRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    //index
    public function index(Request $request)
    {
        $attendances = Attendance::with('user')
            ->when($request->input('name'), function ($query, $name) {
                $query->whereHas('user', function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%');
                });
            })->orderBy('id', 'desc')->paginate(10);
        return view('pages.absensi.index', compact('attendances'));
    }

    //show
    public function show($id)
    {
        $attendance = Attendance::with('user')->find($id);
        return view('pages.absensi.show', compact('attendance'));
    }

    //create
    public function create()
    {
        $users = User::all();
        return view('pages.absensi.create', compact('users'));
    }

    //store
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'date' => 'required|date',
            'time_in' => 'required',
        ]);

        Attendance::create([
            'user_id' => $request->user_id,
            'date' => $request->date,
            'time_in' => $request->time_in,
            'time_out' => $request->time_out,
            'latlon_in' => $request->latlon_in,
            'latlon_out' => $request->latlon_out,
        ]);

        return redirect()->route('attendances.index')->with('success', 'Attendance created successfully');
    }

    //edit
    public function edit($id)
    {
        $attendance = Attendance::find($id);
        $users = User::all();
        return view('pages.absensi.edit', compact('attendance', 'users'));
    }

    //update
    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required',
            'date' => 'required|date',
            'time_in' => 'required',
        ]);

        $attendance = Attendance::find($id);
        $attendance->update([
            'user_id' => $request->user_id,
            'date' => $request->date,
            'time_in' => $request->time_in,
            'time_out' => $request->time_out,
            'latlon_in' => $request->latlon_in,
            'latlon_out' => $request->latlon_out,
        ]);

        return redirect()->route('attendances.index')->with('success', 'Attendance updated successfully');
    }

    //destroy
    public function destroy($id)
    {
        $attendance = Attendance::find($id);
        $attendance->delete();
        return redirect()->route('attendances.index')->with('success', 'Attendance deleted successfully');
    }

    public function clockIn(Request $request)
    {
        // 2. Validasi Input Dasar
        $validator = Validator::make($request->all(), [
            'latlon_in' => 'required|string',
            'attendance_type' => 'required|in:face_recognition,qr_code',
            'identity_data' => 'required|string',
            'late_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Input validation failed', 'errors' => $validator->errors()], 422);
        }

        // 3. Autentikasi Pengguna (Ditangani oleh middleware)
        $user = Auth::user();

        // 4. Cek Status Cuti/Izin Hari Ini
        // TODO: Implement logic to check if the user has approved leave/permission for today.
        // $is_on_leave_or_permission = $user->hasApprovedLeaveOrPermissionToday(); // Placeholder method

        // if ($is_on_leave_or_permission) {
        //     return response()->json(['message' => 'Anda sedang cuti/izin hari ini.'], 400);
        // }

        // 5. Cek Status WFA Pengguna
        if ($user->is_wfa) {
            // Jika Pengguna adalah WFA: Lewati Validasi Lokasi GPS Server-Side
            // Lompat langsung ke langkah Verifikasi Identitas
            $schedule_id_to_use = $user->schedule_id; // Assuming user has a default schedule_id

        } else {
            // Implement server-side location validation and transfer logic
            $is_transfer_clock_in = false;
            $transferRequest = TransferRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereDate('effective_date', now()->toDateString())
                ->first();
            if ($transferRequest) {
                // Ensure the user has clocked out from original outlet
                $originalAttendance = Attendance::where('user_id', $user->id)
                    ->where('schedule_id', $transferRequest->current_schedule_id)
                    ->whereDate('date', now()->toDateString())
                    ->whereNotNull('time_out')
                    ->first();
                if (!$originalAttendance) {
                    return response()->json(['message' => 'Harap lakukan Absen Out di outlet asal sebelum Absen In di outlet tujuan.'], 403);
                }
                $office = $transferRequest->targetSchedule->office;
                $schedule_id_to_use = $transferRequest->target_schedule_id;
                $is_transfer_clock_in = true;
            } else {
                // Normal location validation
                $office = $user->schedule->office;
                $schedule_id_to_use = $user->schedule_id;
            }
            // Parse latitude and longitude
            list($lat, $lng) = explode(',', $request->latlon_in);
            $distance = $this->calculateDistance((float)$lat, (float)$lng, (float)$office->latitude, (float)$office->longitude);
            if ($distance > $office->radius_meter) {
                return response()->json(['message' => 'Lokasi Anda di luar radius kantor yang diizinkan.'], 403);
            }
        }

        // Langkah selanjutnya (Verifikasi Identitas) akan diimplementasikan di sini

        // 6. Verifikasi Identitas (Face Recognition / QR Code)
        // TODO: Implement actual identity verification logic using attendance_type and identity_data.
        $identityVerified = true; // Placeholder: Assume verification succeeds for now

        // Example using a hypothetical service:
        // use App\Services\IdentityVerificationService;
        // $identityService = new IdentityVerificationService();
        // $identityVerified = $identityService->verify($request->attendance_type, $request->identity_data, $user);

        if (!$identityVerified) {
            return response()->json(['message' => 'Verifikasi identitas gagal.'], 401);
        }

        // Langkah selanjutnya (Cek Absensi Ganda) akan diimplementasikan di sini

        // 7. Cek Absensi Ganda Hari Ini
        $existingAttendance = Attendance::where('user_id', $user->id)
                                        ->whereDate('date', now()->toDateString())
                                        ->first();

        if ($existingAttendance) {
            if ($existingAttendance->time_out) {
                // Sudah selesai absensi hari ini (sudah ada time_out)
                return response()->json(['message' => 'Anda telah menyelesaikan absensi hari ini'], 400);
            } else {
                // Sudah clock in tapi belum clock out
                return response()->json(['message' => 'Anda sudah clock in hari ini'], 400);
            }
        }

        // Langkah selanjutnya (Tentukan schedule_id) akan diimplementasikan di sini

        // 8. Tentukan schedule_id untuk disimpan
        // $schedule_id_to_use should have been determined in step 5.
        // If WFA, it might be the user's default schedule_id.
        // If not WFA and location validated, it's the normal or target transfer schedule_id.

        if (is_null($schedule_id_to_use)) {
             // This should ideally not happen if location validation in step 5 is complete
             // and correctly sets $schedule_id_to_use, but as a safety net:
             return response()->json(['message' => 'Gagal menentukan jadwal untuk absensi.'], 500);
        }

        // 9. Buat dan Simpan Catatan Absensi
        $attendance = new Attendance();
        $attendance->user_id = $user->id;
        $attendance->schedule_id = $schedule_id_to_use;
        $attendance->date = now()->toDateString();
        $attendance->time_in = now()->toTimeString();
        $attendance->latlon_in = $request->latlon_in;
        $attendance->attendance_type = $request->attendance_type;
        $attendance->late_reason = $request->late_reason;

        // Determine status_attendance based on WFA or Transfer
        if ($user->is_wfa) {
            $attendance->status_attendance = 'checked_in_wfa';
        } elseif (!empty($is_transfer_clock_in) && $is_transfer_clock_in) {
            $attendance->status_attendance = 'transfer_in';
        } else {
            $attendance->status_attendance = 'checked_in';
        }
        $attendance->is_late = false; // Default

        // TODO: Calculate is_late based on schedule time_in and actual time_in for non-WFA/Transfer
        // if (!$user->is_wfa && !$is_transfer_clock_in) { // Assuming $is_transfer_clock_in is determined in step 5
        //     $schedule = $attendance->schedule; // Load the schedule
        //     if ($schedule && $attendance->time_in > $schedule->time_in) {
        //         $attendance->is_late = true;
        //     }
        // }


        $attendance->save();

        // Langkah selanjutnya (Kirim Notifikasi) akan diimplementasikan di sini

        // 10. Kirim Notifikasi
        // TODO: Implement FCM notification sending logic for Clock-In.
        // Example: NotificationService::sendClockInNotification($user, $attendance);

        // 11. Respon Sukses
        return response()->json(['message' => 'Clock-in berhasil', 'attendance' => $attendance], 201);
    }

    public function clockOut(Request $request)
    {
        // 2. Validasi Input Dasar
        $validator = Validator::make($request->all(), [
            'latlon_out' => 'required|string',
            'attendance_type' => 'required|in:face_recognition,qr_code',
            'identity_data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Input validation failed', 'errors' => $validator->errors()], 422);
        }

        // 3. Autentikasi Pengguna (Ditangani oleh middleware)
        $user = Auth::user();

        // 4. Cek Absensi Aktif
        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('date', now()->toDateString())
                                ->whereNull('time_out')
                                ->first();

        if (!$attendance) {
            return response()->json(['message' => 'Tidak ada absensi aktif hari ini. Silakan clock in terlebih dahulu.'], 400);
        }

        // Catatan: Safety check if ($attendance->time_out) di alur Anda sebenarnya bisa dihapus karena sudah ditangani oleh query whereNull('time_out').

        // Langkah selanjutnya akan diimplementasikan di sini

        // 5. Cek Status WFA Pengguna
        if ($user->is_wfa) {
            // Jika Pengguna adalah WFA: Lewati Validasi Lokasi GPS Server-Side
            // Lompat langsung ke langkah Verifikasi Identitas
            // TODO: Handle WFA user flow for clock out.

        } else {
            // Implement server-side location validation and transfer logic for Clock-Out
            $is_transfer_clock_out = false;
            $transferRequest = TransferRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereDate('effective_date', now()->toDateString())
                ->first();
            if ($transferRequest && $attendance->schedule_id == $transferRequest->current_schedule_id) {
                $office = $transferRequest->currentSchedule->office;
                $is_transfer_clock_out = true;
            } else {
                $office = $attendance->schedule->office;
            }
            // Parse latitude and longitude
            list($lat, $lng) = explode(',', $request->latlon_out);
            $distance = $this->calculateDistance((float)$lat, (float)$lng, (float)$office->latitude, (float)$office->longitude);
            if ($distance > $office->radius_meter) {
                return response()->json(['message' => 'Lokasi Anda di luar radius kantor yang diizinkan untuk clock out.'], 403);
            }
        }

        // Langkah selanjutnya (Verifikasi Identitas) akan diimplementasikan di sini

        // 6. Verifikasi Identitas (Face Recognition / QR Code)
        // TODO: Implement actual identity verification logic using attendance_type and identity_data.
        $identityVerified = true; // Placeholder: Assume verification succeeds for now

        // Example using a hypothetical service:
        // use App\Services\IdentityVerificationService;
        // $identityService = new IdentityVerificationService();
        // $identityVerified = $identityService->verify($request->attendance_type, $request->identity_data, $user);

        if (!$identityVerified) {
            return response()->json(['message' => 'Verifikasi identitas gagal.'], 401);
        }

        // Langkah selanjutnya (Update Catatan Absensi) akan diimplementasikan di sini

        // 7. Update Catatan Absensi
        $attendance->time_out = now()->toTimeString();
        $attendance->latlon_out = $request->latlon_out;

        // Determine status_attendance for Clock-Out based on WFA or Transfer
        if ($user->is_wfa) {
            $attendance->status_attendance = 'checked_out_wfa';
        } elseif (!empty($is_transfer_clock_out) && $is_transfer_clock_out) {
            $attendance->status_attendance = 'transfer_out';
        } else {
            $attendance->status_attendance = 'checked_out';
        }

        $attendance->save();

        // Langkah selanjutnya (Kirim Notifikasi) akan diimplementasikan di sini

        // 8. Kirim Notifikasi
        // TODO: Implement FCM notification sending logic for Clock-Out.
        // Example: NotificationService::sendClockOutNotification($user, $attendance);

        // 9. Respon Sukses
        return response()->json(['message' => 'Clock-out berhasil', 'attendance' => $attendance], 200);
    }

    /**
     * Calculate distance between two lat/lng points in meters.
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // in meters
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
