<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\UserDeviceToken;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Illuminate\Support\Facades\Log;
use App\Models\TransferRequest;
use App\Models\User;
use App\Models\Office;
use App\Models\LeaveRequest;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use App\Services\IdentityVerificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendFirebaseNotification;
use App\Notifications\AbsensiCheckinNotification;
use App\Exports\AttendanceExport;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    /**
     * Cache durations for different operations
     */
    protected const CACHE_DURATION_SHORT = 60; // 1 minute
    protected const CACHE_DURATION_MEDIUM = 300; // 5 minutes
    protected const CACHE_DURATION_LONG = 1800; // 30 minutes

    /**
     * Constructor to initialize any common functionality
     * 
     * Note: To optimize database performance, ensure the following indexes exist:
     * - attendances: (user_id, date) - primary search pattern
     * - attendances: (date, status_attendance) - for status filtering 
     * - attendances: (user_id, date, status_attendance) - for complex filters
     * - transfer_requests: (user_id, status, effective_date) - for transfer lookups
     * 
     * Run these migrations:
     * 
     * Schema::table('attendances', function (Blueprint $table) {
     *     $table->index(['user_id', 'date']);
     *     $table->index(['date', 'status_attendance']);
     *     $table->index(['user_id', 'date', 'status_attendance']);
     * });
     * 
     * Schema::table('transfer_requests', function (Blueprint $table) {
     *     $table->index(['user_id', 'status', 'effective_date']);
     * });
     */
    public function __construct()
    {
        // Initialize any shared resources
    }

    /**
     * Get cached user instance with relationships preloaded
     * 
     * @param \App\Models\User $user
     * @return \App\Models\User
     */
    protected function getCachedUserWithRelations($user)
    {
        $cacheKey = "user_{$user->id}_with_relations";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($user) {
            return User::with([
                'schedule.office',
                'transferRequests' => function($query) {
                    $query->where('status', 'approved')
                         ->where('effective_date', '>=', Carbon::now()->subDays(1)->toDateString())
                         ->with(['currentSchedule.office', 'targetSchedule.office']);
                }
            ])->find($user->id);
        });
    }

    /**
     * Get cached schedule for optimization
     * 
     * @param int $scheduleId
     * @return \App\Models\Schedule|null
     */
    protected function getCachedSchedule($scheduleId)
    {
        return Cache::remember("schedule_{$scheduleId}", self::CACHE_DURATION_MEDIUM, function () use ($scheduleId) {
            return Schedule::with('office')->find($scheduleId);
        });
    }
    
    /**
     * Throttle FCM notifications to avoid rate limiting
     * Uses a cache-based throttling mechanism
     * 
     * @param \App\Models\User $user
     * @param string $type
     * @return bool Whether the notification should be sent
     */
    protected function shouldThrottleNotification($user, $type)
    {
        $cacheKey = "notification_throttle_{$user->id}_{$type}";
        if (Cache::has($cacheKey)) {
            return true; // Throttle this notification
        }
        
        // Set throttle for 5 seconds
        Cache::put($cacheKey, true, 5);
        return false;
    }

    /**
     * Clock in attendance
     */
    public function clockIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latlon_in' => 'required|string',
            'attendance_type' => 'required|in:face_recognition,qr_code,ON_SITE,WFH',
            'identity_data' => 'required|string',
            'late_reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $user = $this->getCachedUserWithRelations($user); // Get user with preloaded relations
        $today = Carbon::now()->toDateString();
        
        // Check for leave status
        $hasLeave = Cache::remember("user_{$user->id}_leave_status_{$today}", self::CACHE_DURATION_MEDIUM, function () use ($user, $today) {
            return LeaveRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->exists();
        });
        
        if ($hasLeave) {
            return response()->json(['message' => 'Anda tidak dapat melakukan absensi karena sedang cuti pada hari ini.'], 403);
        }
        
        // Check for user-specific day off
        $isDayOff = \App\Models\UserDayOff::where('user_id', $user->id)
            ->where('date', $today)
            ->exists();

        if ($isDayOff) {
            return response()->json(['message' => 'Anda tidak dapat melakukan absensi karena hari ini adalah hari libur yang telah ditetapkan untuk Anda.'], 403);
        }
        


        // Check for active transfer request for today
        $activeTransfer = $user->transferRequests()
                ->where('status', 'approved')
                ->where('effective_date', $today)
            ->first(); // Already loaded with currentSchedule.office and targetSchedule.office due to getCachedUserWithRelations

        $identityService = new IdentityVerificationService();

        if ($activeTransfer) {
            // --- TRANSFER DAY LOGIC WITH NEW STRUCTURE ---
            Log::info("User {$user->id} has active transfer request ID {$activeTransfer->id} for today {$today}.");

            // Cek apakah sudah ada attendance hari ini (baik normal atau transfer)
            $existingAttendance = Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->first();

            // Jika belum ada attendance sama sekali, buat baru dengan struktur transfer
            if (!$existingAttendance) {
                // Verify identity first
                if (!$identityService->verify($request->attendance_type, $request->identity_data, $user, 'clock_in')) {
                    return response()->json(['message' => 'Verifikasi identitas gagal.'], 401);
                }

                // Validasi lokasi (jika ON_SITE)
                $sourceOffice = $activeTransfer->currentSchedule->office;
                if (in_array($request->attendance_type, ['ON_SITE', 'face_recognition']) && $sourceOffice) {
                list($latIn, $lonIn) = explode(',', $request->latlon_in);
                $latIn = (float)trim($latIn);
                $lonIn = (float)trim($lonIn);
                    $distance = $this->getDistance($latIn, $lonIn, $sourceOffice->latitude, $sourceOffice->longitude, 'meters');
                    if ($distance > $sourceOffice->radius_meter) {
                        return response()->json(['message' => "Lokasi Anda ($distance meter) di luar radius kantor asal ({$sourceOffice->radius_meter} meter) {$sourceOffice->name}."], 403);
                    }
                }

                // Buat attendance baru dengan struktur transfer lengkap
            $attendance = new Attendance();
            $attendance->user_id = $user->id;
                $attendance->schedule_id = $activeTransfer->current_schedule_id;
            $attendance->date = $today;
            $attendance->attendance_type = $request->attendance_type;
                $attendance->is_transfer_day = true;
                $attendance->transfer_request_id = $activeTransfer->id;
                $attendance->source_office_id = $activeTransfer->currentSchedule->office_id;
                $attendance->destination_office_id = $activeTransfer->targetSchedule->office_id;
                $attendance->source_time_in = Carbon::now()->toTimeString();
                $attendance->source_latlon_in = $request->latlon_in;
                $attendance->transfer_status = 'checked_in_at_source';
                $attendance->status_attendance = 'present'; // tetap gunakan status standard untuk compatibility
                $attendance->time_in = Carbon::now()->toTimeString(); // duplikasi untuk backward compatibility
                $attendance->latlon_in = $request->latlon_in; // duplikasi untuk backward compatibility
                $attendance->late_reason = $request->late_reason;

                // Hitung keterlambatan
                $attendance->is_late = false;
                if ($activeTransfer->currentSchedule->start_time) {
                    $scheduleTime = Carbon::parse($activeTransfer->currentSchedule->start_time);
                    $actualTimeIn = Carbon::parse($attendance->source_time_in);
                    if ($actualTimeIn->gt($scheduleTime)) {
                $attendance->is_late = true;
                        $attendance->late_duration = $actualTimeIn->diffInMinutes($scheduleTime);
                        if (!$request->late_reason && $attendance->is_late) {
                            return response()->json(['message' => 'Alasan terlambat wajib diisi saat absensi datang terlambat.'], 422);
                        }
                    }
            }
            
            DB::beginTransaction();
            try {
                $attendance->save();
                    DB::commit();
                Cache::forget("user_{$user->id}_attendance_{$today}");
                Cache::forget("attendance_history_{$user->id}_{$today}");
                    Cache::forget("attendance_status_user_{$user->id}_{$today}");
                    if (!$this->shouldThrottleNotification($user, 'clock_in_source_transfer')) {
                        $this->sendAttendanceNotification($user, $attendance, 'clock_in_source_transfer');
                    }
                    
                    // Setelah $attendance->save(); dan sebelum return response sukses
                    $isMockLocation = $request->input('is_mock_location', false);
                    if ($isMockLocation) {
                        // Kirim notifikasi ke admin
                        $adminUsers = collect();
                        try {
                            if (\Spatie\Permission\Models\Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
                                $adminUsers = $adminUsers->merge(\App\Models\User::role('super_admin')->get());
                            }
                            if (\Spatie\Permission\Models\Role::where('name', 'hrd')->where('guard_name', 'web')->exists()) {
                                $adminUsers = $adminUsers->merge(\App\Models\User::role('hrd')->get());
                            }
                        } catch (\Throwable $e) {
                            \Log::error('Gagal mengambil admin users untuk notifikasi fake GPS: ' . $e->getMessage());
                        }
                        $adminUsers = $adminUsers->unique('id');
                        $userName = $user->name;
                        $dateFormatted = Carbon::parse($attendance->date)->translatedFormat('d F Y');
                        $timeFormatted = Carbon::parse($attendance->time_in ?? $attendance->time_out)->format('H:i');
                        $adminTitle = "Peringatan Fake GPS";
                        $adminBody = "$userName terdeteksi menggunakan Fake GPS saat absen pada tanggal $dateFormatted pukul $timeFormatted.";
                        foreach ($adminUsers as $admin) {
                            $admin->notify(new \App\Notifications\AbsensiCheckinNotification([
                                'title' => $adminTitle,
                                'body' => $adminBody,
                                'attendance_id' => $attendance->id ?? null,
                                'type' => 'attendance',
                            ]));
                        }
                    }
                    
                    return response()->json([
                        'message' => 'Clock-in di kantor asal (transfer) berhasil', 
                        'attendance' => $attendance,
                        'transfer_details' => [
                            'from_office' => $activeTransfer->currentSchedule->office->name,
                            'to_office' => $activeTransfer->targetSchedule->office->name,
                            'current_stage' => 'checked_in_at_source'
                        ]
                    ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                    Log::error("Error saving attendance for source transfer: " . $e->getMessage());
                    return response()->json(['message' => 'Terjadi kesalahan saat menyimpan data absensi transfer.'], 500);
                }
            } 
            // Attendance record sudah ada, cek status dan handle berbagai kemungkinan
            else {
                // Debug logging
                Log::info("[TRANSFER DEBUG] Processing check-in for transfer: ID {$activeTransfer->id}, Status {$activeTransfer->status}, Current stage: {$existingAttendance->transfer_status}");
                
                // If we already have an attendance record for today that is a transfer day
                if ($existingAttendance && $existingAttendance->is_transfer_day) {
                    switch ($existingAttendance->transfer_status) {
                        case 'checked_out_from_source':
                            // User has checked out from source office and is now checking in at destination
                            Log::info("[TRANSFER DEBUG] User checked out from source, now checking in at destination office");
                            
                            // Update record with destination check-in data
                            $existingAttendance->destination_time_in = Carbon::now()->toTimeString();
                            $existingAttendance->destination_latlon_in = $request->latlon_in;
                            $existingAttendance->transfer_status = 'checked_in_at_destination';
                            // Update untuk backward compatibility - set schedule to destination
                            $existingAttendance->schedule_id = $activeTransfer->target_schedule_id;
                            
                            try {
                                DB::beginTransaction();
                                $existingAttendance->save();
                                
                                // Verify the save was successful
                                $freshAttendance = Attendance::find($existingAttendance->id);
                                if (!$freshAttendance || $freshAttendance->destination_time_in === null) {
                                    Log::error("[TRANSFER ERROR] Failed to save destination_time_in: " . 
                                               json_encode($existingAttendance->toArray()));
                                    DB::rollBack();
                                    return response()->json([
                                        'message' => 'Error: Failed to update destination check-in data'
                                    ], 500);
                                }
                                
                                DB::commit();
                                
                                // Force clear cache for this user's today status
                                $cacheKey = "today_status_user_{$user->id}";
                                Cache::forget($cacheKey);
                                
                                // Send notification
                                $this->sendAttendanceNotification($user, $existingAttendance, 'transfer_destination_check_in');
                                
                                return response()->json([
                                    'message' => 'Berhasil melakukan absensi di kantor tujuan',
                                    'data' => $freshAttendance
                                ]);
                            } catch (\Exception $e) {
                                DB::rollBack();
                                Log::error("[TRANSFER ERROR] Exception during destination check-in: " . $e->getMessage());
                                return response()->json([
                                    'message' => 'Terjadi kesalahan saat menyimpan data absensi: ' . $e->getMessage()
                                ], 500);
                            }
                            break;
                            
                        case 'checked_in_at_source':
                            return response()->json([
                                'message' => 'Anda sudah clock-in di kantor asal. Silakan lakukan checkout dari kantor asal terlebih dahulu.',
                                'attendance' => $existingAttendance
                            ], 400);
                            
                        case 'checked_in_at_destination':
                            return response()->json([
                                'message' => 'Anda sudah clock-in di kantor tujuan. Silakan lakukan checkout untuk menyelesaikan hari kerja.',
                                'attendance' => $existingAttendance
                            ], 400);
                            
                        case 'completed':
                            return response()->json([
                                'message' => 'Proses transfer dan absensi Anda untuk hari ini sudah selesai.',
                                'attendance' => $existingAttendance
                            ], 400);
                            
                        default:
                            return response()->json([
                                'message' => 'Status transfer tidak valid. Silakan hubungi administrator.',
                                'attendance' => $existingAttendance
                            ], 400);
                    }
                }
                // Attendance sudah dalam format transfer, cek status dan proses sesuai
                else {
                    switch($existingAttendance->transfer_status) {
                        case 'checked_in_at_source':
                            return response()->json([
                                'message' => 'Anda sudah clock-in di kantor asal. Silakan lakukan checkout dari kantor asal terlebih dahulu.',
                                'attendance' => $existingAttendance
                            ], 400);
                            
                        case 'checked_out_from_source':
                            // Verify identity first for destination check-in
                if (!$identityService->verify($request->attendance_type, $request->identity_data, $user, 'clock_in')) {
                    return response()->json(['message' => 'Verifikasi identitas gagal.'], 401);
                }

                            // Validasi lokasi di kantor tujuan
                            $destinationOffice = $activeTransfer->targetSchedule->office;
                            if (in_array($request->attendance_type, ['ON_SITE', 'face_recognition']) && $destinationOffice) {
                                list($latIn, $lonIn) = explode(',', $request->latlon_in);
                                $latIn = (float)trim($latIn);
                                $lonIn = (float)trim($lonIn);
                                $distance = $this->getDistance($latIn, $lonIn, $destinationOffice->latitude, $destinationOffice->longitude, 'meters');
                                if ($distance > $destinationOffice->radius_meter) {
                                    return response()->json(['message' => "Lokasi Anda ($distance meter) di luar radius kantor tujuan ({$destinationOffice->radius_meter} meter) {$destinationOffice->name}."], 403);
                                }
                            }
                            
                            // Update record with destination check-in data
                            $existingAttendance->destination_time_in = Carbon::now()->toTimeString();
                            $existingAttendance->destination_latlon_in = $request->latlon_in;
                            $existingAttendance->transfer_status = 'checked_in_at_destination';
                            // Update untuk backward compatibility
                            $existingAttendance->schedule_id = $activeTransfer->target_schedule_id; // update ke jadwal tujuan
                            
                            try {
                                // Log the values before saving for debugging
                                Log::info("TRANSFER DEBUG: About to save destination check-in for user {$user->id}, attendance ID {$existingAttendance->id}");
                                Log::info("TRANSFER DEBUG: destination_time_in: {$existingAttendance->destination_time_in}");
                                Log::info("TRANSFER DEBUG: destination_latlon_in: {$existingAttendance->destination_latlon_in}");
                                Log::info("TRANSFER DEBUG: transfer_status: {$existingAttendance->transfer_status}");
                                Log::info("TRANSFER DEBUG: schedule_id updating from {$existingAttendance->getOriginal('schedule_id')} to {$activeTransfer->target_schedule_id}");
                                
                                // CRITICAL FIX: Force using DB transaction to ensure data integrity
                DB::beginTransaction();
                                
                                try {
                                    // Explicitly set fields to ensure they are saved
                                    $existingAttendance->destination_time_in = Carbon::now()->toTimeString();
                                    $existingAttendance->destination_latlon_in = $request->latlon_in;
                                    $existingAttendance->transfer_status = 'checked_in_at_destination';
                                    $existingAttendance->schedule_id = $activeTransfer->target_schedule_id;
                                    
                                    // First, save with normal ORM
                                    $saved = $existingAttendance->save();
                                    
                                    if (!$saved) {
                                        throw new \Exception("Failed to save attendance record through ORM");
                                    }
                                    
                                    // CRITICAL FIX: Double-check with direct SQL to ensure fields are set
                                    DB::table('attendances')
                                        ->where('id', $existingAttendance->id)
                                        ->update([
                                            'destination_time_in' => $existingAttendance->destination_time_in,
                                            'destination_latlon_in' => $existingAttendance->destination_latlon_in,
                                            'transfer_status' => 'checked_in_at_destination',
                                            'schedule_id' => $activeTransfer->target_schedule_id,
                                            'updated_at' => Carbon::now()
                                        ]);
                                    
                                    // Commit the transaction
                    DB::commit();
                                    
                                    // Clear all relevant caches
                                    Cache::forget("attendance_status_user_{$user->id}_{$today}");
                                    Cache::forget("user_{$user->id}_active_attendance_{$today}");
                    Cache::forget("user_{$user->id}_attendance_{$today}");
                    Cache::forget("attendance_history_{$user->id}_{$today}");
                                    
                    if (!$this->shouldThrottleNotification($user, 'transfer_in')) {
                                        $this->sendAttendanceNotification($user, $existingAttendance, 'transfer_in');
                    }
                                    
                                    // Check that the values were actually saved - load a fresh instance
                                    $reloadedAttendance = Attendance::find($existingAttendance->id);
                                    
                                    Log::info("TRANSFER DEBUG: After save, destination_time_in: {$reloadedAttendance->destination_time_in}");
                                    Log::info("TRANSFER DEBUG: After save, destination_latlon_in: {$reloadedAttendance->destination_latlon_in}");
                                    Log::info("TRANSFER DEBUG: After save, transfer_status: {$reloadedAttendance->transfer_status}");
                                    
                                    // Verification: If the destination_time_in is still null after saving, we have a problem
                                    if ($reloadedAttendance->destination_time_in === null) {
                                        throw new \Exception("Destination time_in still null after save - critical error");
                                    }
                                    
                                    return response()->json([
                                        'message' => 'Clock-in di kantor tujuan berhasil',
                                        'attendance' => $reloadedAttendance, // Return the fresh instance
                                        'transfer_details' => [
                                            'from_office' => $activeTransfer->currentSchedule->office->name,
                                            'to_office' => $activeTransfer->targetSchedule->office->name,
                                            'current_stage' => 'checked_in_at_destination'
                                        ]
                                    ], 200);
                                    
                                } catch (\Exception $innerException) {
                                    // Roll back the transaction on error
                    DB::rollBack();
                                    
                                    Log::error("Inner transaction error: " . $innerException->getMessage());
                                    Log::error("Stack trace: " . $innerException->getTraceAsString());
                                    
                                    throw $innerException; // Re-throw to outer catch
                                }
                                
                            } catch (\Exception $e) {
                                Log::error("Error updating attendance for destination checkin: " . $e->getMessage());
                                Log::error("Error stack trace: " . $e->getTraceAsString());
                                return response()->json(['message' => 'Terjadi kesalahan saat mengupdate data absensi untuk checkin tujuan: ' . $e->getMessage()], 500);
                            }
                            
                        case 'checked_in_at_destination':
                            return response()->json([
                                'message' => 'Anda sudah clock-in di kantor tujuan. Silakan lakukan checkout untuk menyelesaikan hari kerja.',
                                'attendance' => $existingAttendance
                            ], 400);
                            
                        case 'completed':
                            return response()->json([
                                'message' => 'Proses transfer dan absensi Anda untuk hari ini sudah selesai.',
                                'attendance' => $existingAttendance
                            ], 400);
                            
                        default:
                            return response()->json([
                                'message' => 'Status transfer tidak valid. Silakan hubungi administrator.',
                                'attendance' => $existingAttendance
                            ], 400);
            }
                }
            }
        } else {
            // NORMAL DAY LOGIC (no changes needed except clarifying normal day flag)
            // Check if attendance already exists for today
            $existingAttendance = Cache::remember("user_{$user->id}_attendance_{$today}", self::CACHE_DURATION_SHORT, function () use ($user, $today) {
                return Attendance::where('user_id', $user->id)
                    ->where('date', $today)
                    ->first();
            });

            if ($existingAttendance) {
                if ($existingAttendance->time_out) {
                    return response()->json(['message' => 'Anda telah menyelesaikan absensi hari ini', 'attendance' => $existingAttendance], 400);
                } else {
                    return response()->json(['message' => 'Anda sudah clock-in hari ini', 'attendance' => $existingAttendance], 400);
                }
            }

            // Tambahkan pengecekan fake GPS di sini
            $isMockLocation = $request->input('is_mock_location', false);
            \Log::info('DEBUG FAKE GPS', [
                'user_id' => $user->id,
                'is_mock_location' => $isMockLocation,
                'request' => $request->all()
            ]);
            if ($isMockLocation) {
                // Kirim notifikasi ke admin
                $adminUsers = collect();
                try {
                    if (\Spatie\Permission\Models\Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
                        $adminUsers = $adminUsers->merge(\App\Models\User::role('super_admin')->get());
                    }
                    if (\Spatie\Permission\Models\Role::where('name', 'hrd')->where('guard_name', 'web')->exists()) {
                        $adminUsers = $adminUsers->merge(\App\Models\User::role('hrd')->get());
                    }
                } catch (\Throwable $e) {
                    \Log::error('Gagal mengambil admin users untuk notifikasi fake GPS: ' . $e->getMessage());
                }
                $adminUsers = $adminUsers->unique('id');
                $userName = $user->name;
                $dateFormatted = Carbon::now()->translatedFormat('d F Y');
                $timeFormatted = Carbon::now()->format('H:i');
                $adminTitle = "Peringatan Fake GPS";
                $adminBody = "$userName terdeteksi menggunakan Fake GPS saat absen pada tanggal $dateFormatted pukul $timeFormatted.";
                foreach ($adminUsers as $admin) {
                    $admin->notify(new \App\Notifications\AbsensiCheckinNotification([
                        'title' => $adminTitle,
                        'body' => $adminBody,
                        'attendance_id' => null,
                        'type' => 'attendance',
                    ]));
                }
                \Log::info('DEBUG FAKE GPS NOTIFIKASI DIKIRIM', [
                    'user_id' => $user->id,
                    'admin_count' => $adminUsers->count(),
                    'admin_ids' => $adminUsers->pluck('id')->toArray()
                ]);
                return response()->json(['message' => 'Fake GPS terdeteksi! Absensi diblokir dan admin telah diberi notifikasi.'], 403);
            }

            $currentSchedule = $user->schedule; // User's current default schedule
            if (!$currentSchedule) {
                return response()->json(['message' => 'Tidak ada jadwal yang ditetapkan untuk Anda.'], 400);
            }
            $officeToValidate = $currentSchedule->office; // Assumes office relation is loaded via getCachedUserWithRelations

            if (in_array($request->attendance_type, ['ON_SITE', 'face_recognition'])) {
                 if (!$officeToValidate) {
                    return response()->json(['message' => 'Data kantor untuk jadwal Anda tidak ditemukan.'], 500);
                }
                list($latIn, $lonIn) = explode(',', $request->latlon_in);
                $latIn = (float)trim($latIn);
                $lonIn = (float)trim($lonIn);
                $distance = $this->getDistance($latIn, $lonIn, $officeToValidate->latitude, $officeToValidate->longitude, 'meters');
                if ($distance > $officeToValidate->radius_meter) {
                    return response()->json(['message' => "Lokasi Anda ($distance meter) di luar radius kantor ({$officeToValidate->radius_meter} meter) {$officeToValidate->name}."], 403);
                }
            }
            
            // WFA check (not explicitly handled for clock-in here beyond attendance_type, as location validation is skipped if not ON_SITE/face_recognition)
            // $user->is_wfa can be used to set status_attendance if needed.

            if (!$identityService->verify($request->attendance_type, $request->identity_data, $user, 'clock_in')) {
                return response()->json(['message' => 'Verifikasi identitas gagal.'], 401);
            }

        $attendance = new Attendance();
        $attendance->user_id = $user->id;
            $attendance->schedule_id = $currentSchedule->id;
        $attendance->date = $today;
        $attendance->time_in = Carbon::now()->toTimeString();
        $attendance->latlon_in = $request->latlon_in;
        $attendance->attendance_type = $request->attendance_type;
        $attendance->late_reason = $request->late_reason;
            $attendance->status_attendance = $user->is_wfa ? 'checked_in_wfa' : 'present'; // Adjust for WFA
            $attendance->is_transfer_day = false; // explicitly mark as non-transfer day

            $attendance->is_late = false;
            if (!$user->is_wfa && $currentSchedule->start_time) { // Don't check lateness for WFA
                $scheduleTime = Carbon::parse($currentSchedule->start_time);
                $actualTimeIn = Carbon::parse($attendance->time_in);
                if ($actualTimeIn->gt($scheduleTime)) {
                    $attendance->is_late = true;
                    $attendance->late_duration = $actualTimeIn->diffInMinutes($scheduleTime);
                    if (!$request->late_reason && $attendance->is_late) {
                        return response()->json(['message' => 'Alasan terlambat wajib diisi saat absensi datang terlambat.'], 422);
                    }
                }
            }
            
        DB::beginTransaction();
        try {
            $attendance->save();
                DB::commit();
            Cache::forget("user_{$user->id}_attendance_{$today}");
            Cache::forget("attendance_history_{$user->id}_{$today}");
                Cache::forget("attendance_status_user_{$user->id}_{$today}");
            if (!$this->shouldThrottleNotification($user, 'clock_in')) {
                $this->sendAttendanceNotification($user, $attendance, 'clock_in');
            }
            
            return response()->json(['message' => 'Clock-in berhasil', 'attendance' => $attendance], 201);
        } catch (\Exception $e) {
            DB::rollBack();
                Log::error("Error saving normal attendance: " . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat menyimpan data absensi.'], 500);
            }
        }
    }

    /**
     * Clock out attendance
     */
    public function clockOut(Request $request)
    {
        // 2. Validasi Input Dasar
        $validator = Validator::make($request->all(), [
            'latlon_out' => 'required|string',
            'attendance_type' => 'required|in:face_recognition,qr_code,ON_SITE,WFH',
            'identity_data' => 'required|string', // Added validation
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $user = $this->getCachedUserWithRelations($user);
        $today = Carbon::now()->toDateString();
        // Cek hari libur user
        $isDayOff = \App\Models\UserDayOff::where('user_id', $user->id)
            ->where('date', $today)
            ->exists();
        if ($isDayOff) {
            return response()->json(['message' => 'Anda tidak dapat melakukan absensi karena hari ini adalah hari libur yang telah ditetapkan untuk Anda.'], 403);
        }
        
        // Find active attendance record to update - cache lookup result
        $attendance = Cache::remember("user_{$user->id}_active_attendance_{$today}", 30, function () use ($user, $today) {
            return Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->whereNull('time_out')
                ->first();
        });

        if (!$attendance) {
            return response()->json([
                'message' => 'No active attendance found for today. Please check in first.'
            ], 400);
        }

        if ($attendance->time_out) {
            return response()->json([
                'message' => 'You have already clocked out today'
            ], 400);
        }

        // Check if this is a transfer day
        $transferRequest = Cache::remember("user_{$user->id}_transfer_request_{$today}", self::CACHE_DURATION_SHORT, function () use ($user, $today) {
            return $user->transferRequests()
                ->where('status', 'approved')
                ->where('effective_date', $today)
                ->with(['currentSchedule.office', 'targetSchedule.office'])
                ->first();
        });

        $identityService = new IdentityVerificationService();
        
        // Verify identity
        if (!$identityService->verify($request->attendance_type, $request->identity_data, $user, 'clock_out')) {
            return response()->json(['message' => 'Verifikasi identitas gagal.'], 401);
        }

        // TRANSFER DAY LOGIC WITH NEW STRUCTURE
        if ($transferRequest && $attendance->is_transfer_day) {
            Log::info("Processing clock-out for transfer day. User {$user->id}, attendance ID {$attendance->id}");
            
            // Determine which office we need to validate (source or destination)
            $officeToValidate = null;
            $isDestinationCheckout = false;
            
            switch($attendance->transfer_status) {
                case 'checked_in_at_source':
                    // Clock-out dari kantor asal
                    $officeToValidate = $transferRequest->currentSchedule->office;
                    $isDestinationCheckout = false;
                    break;
                    
                case 'checked_in_at_destination':
                    // Clock-out dari kantor tujuan
                    $officeToValidate = $transferRequest->targetSchedule->office;
                    $isDestinationCheckout = true;
                    break;
                    
                case 'checked_out_from_source':
                    return response()->json([
                        'message' => 'Anda sudah checkout dari kantor asal. Silakan lakukan check-in di kantor tujuan.'
                    ], 400);
                    
                case 'completed':
                    return response()->json([
                        'message' => 'Proses transfer dan absensi Anda untuk hari ini sudah selesai.'
                    ], 400);
                    
                default:
                    Log::error("Invalid transfer status {$attendance->transfer_status} for user {$user->id}");
                    return response()->json([
                        'message' => 'Status transfer tidak valid. Harap hubungi administrator.'
                    ], 400);
        }

            // Validate location
            if (in_array($request->attendance_type, ['ON_SITE', 'face_recognition']) && $officeToValidate) {
            list($latOut, $lonOut) = explode(',', $request->latlon_out);
            $latOut = (float)trim($latOut);
            $lonOut = (float)trim($lonOut);
                $distance = $this->getDistance($latOut, $lonOut, $officeToValidate->latitude, $officeToValidate->longitude, 'meters');
            
                if ($distance > $officeToValidate->radius_meter) {
                    $officeName = $officeToValidate->name ?? 'kantor';
                return response()->json([
                        'message' => "Lokasi Anda ($distance meter) di luar radius ($officeToValidate->radius_meter meter) $officeName."
                ], 403);
            }
        }
            
            // Update attendance based on current stage
            DB::beginTransaction();
            try {
                if (!$isDestinationCheckout) {
                    // Checkout dari kantor asal
                    $attendance->source_time_out = Carbon::now()->toTimeString();
                    $attendance->source_latlon_out = $request->latlon_out;
                    $attendance->transfer_status = 'checked_out_from_source';
                    // For backward compatibility
                    $attendance->time_out = Carbon::now()->toTimeString();
                    $attendance->latlon_out = $request->latlon_out;
                    
                    // Update schedule to target office if needed
                    $user->schedule_id = $transferRequest->target_schedule_id;
                    $user->save();
                    
                    Cache::forget("user_{$user->id}_with_relations");
                    
                    Log::info("User {$user->id} schedule updated to {$transferRequest->target_schedule_id} after source checkout");
                } else {
                    // Checkout dari kantor tujuan
                    $attendance->destination_time_out = Carbon::now()->toTimeString();
                    $attendance->destination_latlon_out = $request->latlon_out;
                    $attendance->transfer_status = 'completed';
                    // For backward compatibility
        $attendance->time_out = Carbon::now()->toTimeString();
        $attendance->latlon_out = $request->latlon_out;
                }
        
            $attendance->save();
                DB::commit();
            
                // Clear caches
            Cache::forget("user_{$user->id}_attendance_{$today}");
            Cache::forget("user_{$user->id}_active_attendance_{$today}");
            Cache::forget("attendance_history_{$user->id}_{$today}");
                Cache::forget("attendance_status_user_{$user->id}_{$today}");
            
                // Send notification
                $notificationType = $isDestinationCheckout ? 'clock_out' : 'transfer_out';
                if (!$this->shouldThrottleNotification($user, $notificationType)) {
                    $this->sendAttendanceNotification($user, $attendance, $notificationType);
                }
                
                $message = $isDestinationCheckout ? 'Clock-out dari kantor tujuan berhasil. Proses transfer selesai.' 
                    : 'Clock-out dari kantor asal berhasil. Silakan lanjutkan ke kantor tujuan.';
                    
                $nextStep = $isDestinationCheckout ? 'completed' : 'destination_check_in';
                
                return response()->json([
                    'message' => $message,
                    'attendance' => $attendance,
                    'transfer_details' => [
                        'from_office' => $transferRequest->currentSchedule->office->name,
                        'to_office' => $transferRequest->targetSchedule->office->name,
                        'current_stage' => $attendance->transfer_status,
                        'next_step' => $nextStep
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error updating attendance for transfer checkout: " . $e->getMessage());
                return response()->json([
                    'message' => 'Terjadi kesalahan saat memproses checkout transfer.'
                ], 500);
            }
        } 
        // If the attendance is for a normal day but there's an active transfer request
        // Let's convert it to transfer format
        else if ($transferRequest && !$attendance->is_transfer_day) {
            DB::beginTransaction();
            try {
                // Convert normal attendance to transfer format
                $attendance->is_transfer_day = true;
                $attendance->transfer_request_id = $transferRequest->id;
                $attendance->source_office_id = $transferRequest->currentSchedule->office_id;
                $attendance->destination_office_id = $transferRequest->targetSchedule->office_id;
                $attendance->source_time_in = $attendance->time_in;
                $attendance->source_latlon_in = $attendance->latlon_in;
                $attendance->source_time_out = Carbon::now()->toTimeString();
                $attendance->source_latlon_out = $request->latlon_out;
                $attendance->transfer_status = 'checked_out_from_source';
                
                // For backward compatibility
                $attendance->time_out = Carbon::now()->toTimeString();
                $attendance->latlon_out = $request->latlon_out;
                
                // Update user's schedule to destination
                $user->schedule_id = $transferRequest->target_schedule_id;
                $user->save();
                
                $attendance->save();
                DB::commit();
                
                // Clear caches
                Cache::forget("user_{$user->id}_with_relations");
                Cache::forget("user_{$user->id}_attendance_{$today}");
                Cache::forget("user_{$user->id}_active_attendance_{$today}");
                Cache::forget("attendance_history_{$user->id}_{$today}");
                Cache::forget("attendance_status_user_{$user->id}_{$today}");
                
                if (!$this->shouldThrottleNotification($user, 'transfer_out')) {
                    $this->sendAttendanceNotification($user, $attendance, 'transfer_out');
                }
                
                return response()->json([
                    'message' => 'Clock-out dari kantor asal berhasil. Silakan lanjutkan ke kantor tujuan.',
                    'attendance' => $attendance,
                    'transfer_details' => [
                        'from_office' => $transferRequest->currentSchedule->office->name,
                        'to_office' => $transferRequest->targetSchedule->office->name,
                        'current_stage' => 'checked_out_from_source',
                        'next_step' => 'destination_check_in'
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error converting normal attendance to transfer: " . $e->getMessage());
                return response()->json([
                    'message' => 'Terjadi kesalahan saat memproses checkout transfer.'
                ], 500);
            }
        }
        // Non-transfer day or old attendance format
        else {
            // Determine office for validation
            $officeToValidate = $attendance->schedule->office;
            
            // Validate location if ON_SITE
            if (in_array($request->attendance_type, ['ON_SITE', 'face_recognition']) && $officeToValidate) {
                list($latOut, $lonOut) = explode(',', $request->latlon_out);
                $latOut = (float)trim($latOut);
                $lonOut = (float)trim($lonOut);
                $distance = $this->getDistance($latOut, $lonOut, $officeToValidate->latitude, $officeToValidate->longitude, 'meters');
                
                if ($distance > $officeToValidate->radius_meter) {
                    return response()->json([
                        'message' => "Lokasi Anda ($distance meter) di luar radius kantor ({$officeToValidate->radius_meter} meter) {$officeToValidate->name}."
                    ], 403);
                }
            }
            
            // Normal check-out
            $attendance->time_out = Carbon::now()->toTimeString();
            $attendance->latlon_out = $request->latlon_out;
            $attendance->status_attendance = 'checked_out';
            
            try {
                $attendance->save();
                
                // Clear caches
                Cache::forget("user_{$user->id}_attendance_{$today}");
                Cache::forget("user_{$user->id}_active_attendance_{$today}");
                Cache::forget("attendance_history_{$user->id}_{$today}");
                Cache::forget("attendance_status_user_{$user->id}_{$today}");
                
                // Send notification
                if (!$this->shouldThrottleNotification($user, 'clock_out')) {
                    $this->sendAttendanceNotification($user, $attendance, 'clock_out');
                }
                
            return response()->json([
                'message' => 'Clock-out berhasil', 
                'attendance' => $attendance
            ], 200);
        } catch (\Exception $e) {
                Log::error("Error during normal checkout: " . $e->getMessage());
                return response()->json([
                    'message' => 'Terjadi kesalahan saat memproses data absensi.'
                ], 500);
            }
        }
    }

    /**
     * Get attendance history with optimized performance
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        $startDate = Carbon::parse($request->input('start_date', Carbon::now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse($request->input('end_date', Carbon::now()->endOfMonth()->toDateString()));
        $search = $request->input('search');
        $sort = $request->input('sort', 'desc'); // Default to 'desc' (newest first)
        $limit = (int) $request->input('limit', 30); // default 30 per page
        $page = (int) $request->input('page', 1);

        // Ambil data bulanan dengan logika yang sama persis seperti heatmap/kalender
        $month = $startDate->month;
        $year = $startDate->year;
        $monthlyData = $this->getDetailedMonthlyAttendance($user->id, $month, $year);

        // Filter hanya tanggal dalam rentang startDate-endDate
        $filtered = array_filter($monthlyData, function($item) use ($startDate, $endDate) {
            // Patch: pastikan title dan description tidak null
            if (!isset($item['title']) || $item['title'] === null) {
                $item['title'] = ($item['status'] === 'alfa') ? 'Alfa' : ucfirst($item['status']);
            }
            if (!isset($item['description']) || $item['description'] === null) {
                $item['description'] = '';
            }
            return Carbon::parse($item['date'])->between($startDate, $endDate);
        });

        // Optional: filter by search (status, title, dsb)
        if ($search) {
            $filtered = array_filter($filtered, function($item) use ($search) {
                return stripos($item['status'], $search) !== false ||
                       (isset($item['title']) && stripos($item['title'], $search) !== false);
            });
        }

        // Sort
        $sortDirection = strtolower($sort) === 'asc' ? SORT_ASC : SORT_DESC;
        usort($filtered, function($a, $b) use ($sortDirection) {
            return $sortDirection === SORT_ASC
                ? strcmp($a['date'], $b['date'])
                : strcmp($b['date'], $a['date']);
        });

        // Pagination manual
        $offset = ($page - 1) * $limit;
        $paged = array_slice($filtered, $offset, $limit);
        return response()->json(['data' => array_values($paged)]);
    }

    /**
     * Get today's attendance status
     * Enhanced to provide more detailed transfer status information
     */
    public function todayStatus()
    {
        $user = Auth::user();
        // Eager load schedule dan office
        $user = \App\Models\User::with(['schedule.office'])->find($user->id);
        $currentSchedule = $user->schedule;

        // Debug log untuk memastikan data dikirim
        \Log::info('DEBUG API current_schedule', [
            'user_id' => $user->id,
            'current_schedule' => $currentSchedule ? $currentSchedule->toArray() : null
        ]);

        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }
        $today = Carbon::now()->toDateString();
        // Tambahan: cek hari libur user
        $isDayOff = \App\Models\UserDayOff::where('user_id', $user->id)
            ->where('date', $today)
            ->exists();
        if ($isDayOff) {
            return response()->json([
                'has_checked_in' => false,
                'has_checked_out' => false,
                'attendance' => null,
                'status' => 'libur',
                'message' => 'Hari ini adalah hari libur yang telah ditetapkan untuk Anda.',
                'transfer_details' => null,
                'current_schedule' => null,
            ]);
        }
        
        // Use cache to prevent repeated queries for the same data
        // Cache key should be unique per user and day to reflect daily status
        $cacheKey = "attendance_status_user_{$user->id}_{$today}";
        
        // Attempt to retrieve from cache first
        $cachedStatus = Cache::get($cacheKey);
        if ($cachedStatus) {
            return response()->json($cachedStatus);
        }

        // Get a fully loaded user with relations for better performance and to reduce redundant queries
        $user = $this->getCachedUserWithRelations($user);
        $currentSchedule = $user->schedule; // User's active schedule for today (non-transfer context)

        // Check for active transfer request for today
            $transferRequest = $user->transferRequests()
                ->where('status', 'approved')
                ->whereDate('effective_date', $today)
            // Eager load relations ONLY if $transferRequest is found
            // ->with(['currentSchedule.office', 'targetSchedule.office']) 
                ->first();

        $responseData = [];
            
            if ($transferRequest) {
            // Eager load relations now that we know $transferRequest exists
            $transferRequest->loadMissing(['currentSchedule.office', 'targetSchedule.office']);

            // Validate that schedules and offices are loaded
            if (!$transferRequest->currentSchedule || !$transferRequest->currentSchedule->office ||
                !$transferRequest->targetSchedule || !$transferRequest->targetSchedule->office) {
                
                Log::error("Transfer request {$transferRequest->id} for user {$user->id} is missing schedule or office details.");
                // Fallback to normal status if transfer data is incomplete, or return specific error
                // For now, let's try to show a generic error or allow fallback to normal status
                 return response()->json([
                    'has_checked_in' => false,
                    'has_checked_out' => false,
                    'attendance' => null,
                    'message' => 'Data transfer tidak lengkap. Silakan hubungi administrator.',
                    'transfer_details' => null, 
                    'current_schedule' => $currentSchedule ? $currentSchedule->loadMissing('office')->toArray() : null,
                ], 500); // Internal Server Error if critical data is missing
            }
            
            // Cek apakah ada attendance dengan struktur transfer baru
            $transferAttendance = Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->where('is_transfer_day', true)
                ->where('transfer_request_id', $transferRequest->id)
                ->first();
                
            if ($transferAttendance) {
                // Menggunakan attendance dengan struktur baru
                $hasCheckedIn = $transferAttendance->source_time_in !== null || $transferAttendance->destination_time_in !== null;
                $hasCheckedOut = false;
                
                // Tentukan status checkout berdasarkan stage
                switch ($transferAttendance->transfer_status) {
                    case 'checked_out_from_source':
                        // Sudah checkout dari kantor asal, tapi belum selesai proses sepenuhnya
                        $hasCheckedOut = false;
                        break;
                    case 'completed':
                        // Sudah checkout dari kantor tujuan, proses selesai
                        $hasCheckedOut = true;
                        break;
                    default:
                        $hasCheckedOut = false;
                }
                
                // Tentukan pesan status
                $statusMessage = '';
                switch ($transferAttendance->transfer_status) {
                    case 'pending':
                        $statusMessage = 'Menunggu absensi di kantor asal.';
                        break;
                    case 'checked_in_at_source':
                        $statusMessage = 'Anda sudah absen masuk di kantor asal.';
                        break;
                    case 'checked_out_from_source':
                        $statusMessage = 'Menunggu absen masuk di kantor tujuan.';
                        break;
                    case 'checked_in_at_destination':
                        $statusMessage = 'Anda sudah absen masuk di kantor tujuan.';
                        break;
                    case 'completed':
                        $statusMessage = 'Proses transfer selesai.';
                        break;
                    default:
                        $statusMessage = 'Status transfer tidak diketahui.';
                }
                
                // Tentukan current schedule berdasarkan stage
                $currentScheduleToUse = $transferAttendance->transfer_status === 'checked_out_from_source' || 
                                        $transferAttendance->transfer_status === 'checked_in_at_destination' || 
                                        $transferAttendance->transfer_status === 'completed'
                    ? $transferRequest->targetSchedule
                    : $transferRequest->currentSchedule;
                
                $responseData = [
                    'has_checked_in' => $hasCheckedIn,
                    'has_checked_out' => $hasCheckedOut,
                    'attendance' => $transferAttendance->toArray(),
                    'message' => $statusMessage,
                    'transfer_details' => $this->formatTransferDetails(
                        $transferRequest, 
                        $transferAttendance->transfer_status
                    ),
                    'current_schedule' => $currentScheduleToUse->toArray()
                ];
                
                // Cache the response
                Cache::put($cacheKey, $responseData, self::CACHE_DURATION_SHORT);
                return response()->json($responseData);
            }
            
            // Fallback ke logika lama jika tidak ada attendance dengan struktur baru
            // Logika untuk hari transfer
            $sourceCheckInRecord = Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->where(function($query) use ($transferRequest) {
                    // Cek baik attendance dengan status transfer khusus ATAU attendance normal yang sudah ada
                    $query->where(function($q) use ($transferRequest) {
                        $q->where('schedule_id', $transferRequest->current_schedule_id)
                          ->where('status_attendance', 'checked_in_source_transfer');
                    })
                    ->orWhere(function($q) use ($transferRequest) {
                        // Cek juga attendance normal yang sudah terjadi sebelumnya
                        $q->where('schedule_id', $transferRequest->current_schedule_id)
                          ->whereIn('status_attendance', ['present', 'checked_in']);
                    });
                })
                ->orderBy('time_in', 'desc')
                ->first();

            // Jika tidak ditemukan attendance spesifik, coba cari attendance normal yang sudah ada
            if (!$sourceCheckInRecord) {
                $regularCheckIn = Attendance::where('user_id', $user->id)
                    ->where('date', $today)
                    ->whereIn('status_attendance', ['present', 'checked_in'])
                    ->whereNotNull('time_in')
                    ->orderBy('time_in', 'desc')
                    ->first();
                
                // Jika ada attendance normal dan belum check-out, gunakan sebagai source attendance
                if ($regularCheckIn && !$regularCheckIn->time_out) {
                    $sourceCheckInRecord = $regularCheckIn;
                    
                    // Update status attendance menjadi transfer-related
                    // Hapus baris ini jika tidak ingin mengubah status attendance yang sudah ada
                    // $regularCheckIn->status_attendance = 'checked_in_source_transfer';
                    // $regularCheckIn->save();
                    
                    Log::info("Found existing regular attendance for user {$user->id} on transfer day, using as source attendance.");
                }
            }

            $transferOutRecord = Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->where('status_attendance', 'transfer_out')
                ->where('original_schedule_id', $transferRequest->current_schedule_id) 
                ->first();

            $responseData = [
                    'has_checked_in' => true, // Still considered checked-in (at source, and now ready for destination)
                    'has_checked_out' => true, // Effectively checked out from source
                    'attendance' => $transferOutRecord ? $transferOutRecord->toArray() : null, // Show the transfer_out record
                    'message' => 'Menunggu absen masuk di lokasi transfer tujuan.',
                    'transfer_details' => $this->formatTransferDetails($transferRequest, 'checked_out_from_source', $transferOutRecord, null, $sourceCheckInRecord),
                    'current_schedule' => $transferRequest->targetSchedule->toArray()
                ];
        } else {
            // Not a transfer day, check for normal attendance
            $attendance = Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->orderBy('time_in', 'desc') // Get the latest record for the day
                ->first();

            if ($attendance) {
                $responseData = [
                    'has_checked_in' => (bool)$attendance->time_in,
                    'has_checked_out' => (bool)$attendance->time_out,
                    'attendance' => $attendance->toArray(),
                    'message' => 'Status absensi hari ini.',
                    'transfer_details' => null,
                    'current_schedule' => $currentSchedule ? $currentSchedule->loadMissing('office')->toArray() : null,
                ];
            } else {
                // No attendance record at all for a normal day
                $responseData = [
                    'has_checked_in' => false,
                    'has_checked_out' => false,
                    'attendance' => null,
                    'message' => 'Belum ada data absensi untuk hari ini.',
                    'transfer_details' => null,
                    'current_schedule' => $currentSchedule ? $currentSchedule->loadMissing('office')->toArray() : null,
                ];
            }
        }
        
        // Cache the successful response
        Cache::put($cacheKey, $responseData, self::CACHE_DURATION_SHORT);
        return response()->json($responseData);
    }

    /**
     * Send attendance notification
     */
    protected function sendAttendanceNotification($user, Attendance $attendance, string $type)
    {
        // Skip if the user doesn't exist
        if (!$user) {
            Log::warning("Cannot send notification - user not found");
            return;
        }
        
        // Define notification content based on type
        $title = '';
        $body = '';
        $data = ['type' => $type];

        switch ($type) {
            case 'clock_in':
                $title = 'Clock In';
                $body = "You've successfully clocked in at " . Carbon::parse($attendance->time_in)->format('H:i');
                break;
            case 'clock_out':
                $title = 'Clock Out';
                $body = "You've successfully clocked out at " . Carbon::parse($attendance->time_out)->format('H:i');
                break;
            case 'clock_in_source_transfer':
                $title = 'Transfer Day Check-in';
                $body = "Successfully checked in at source office at " . Carbon::parse($attendance->source_time_in)->format('H:i');
                break;
            case 'clock_out_source_transfer':
                $title = 'Transfer Day Complete';
                $body = "Successfully completed source office attendance at " . Carbon::parse($attendance->source_time_out)->format('H:i');
                break;
            case 'clock_in_destination_transfer':
                $title = 'Transfer Day Check-in';
                $body = "Successfully checked in at destination office at " . Carbon::parse($attendance->destination_time_in)->format('H:i');
                break;
            case 'clock_out_destination_transfer':
                $title = 'Transfer Day Complete';
                $body = "Successfully completed destination office attendance at " . Carbon::parse($attendance->destination_time_out)->format('H:i');
                break;
            default:
                $title = 'Attendance Update';
                $body = 'Your attendance has been updated.';
        }

        // Use job to send notification asynchronously
        SendFirebaseNotification::dispatch(
            $user->id,
            $title,
            $body,
            $data,
            $attendance->id
        );

        // Kirim notifikasi Laravel ke database untuk user
        $user->notify(new AbsensiCheckinNotification([
            'title' => $title,
            'body' => $body,
            'attendance_id' => $attendance->id,
            'type' => $type,
        ]));

        // Kirim notifikasi ke admin dashboard
        $adminUsers = collect();
        try {
            if (\Spatie\Permission\Models\Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
                $adminUsers = $adminUsers->merge(\App\Models\User::role('super_admin')->get());
            }
            if (\Spatie\Permission\Models\Role::where('name', 'hrd')->where('guard_name', 'web')->exists()) {
                $adminUsers = $adminUsers->merge(\App\Models\User::role('hrd')->get());
            }
        } catch (\Throwable $e) {
            \Log::error('Gagal mengambil admin users untuk notifikasi absensi: ' . $e->getMessage());
        }

        $adminUsers = $adminUsers->unique('id');
        $userName = $user->name;
        $dateFormatted = Carbon::parse($attendance->date)->translatedFormat('d F Y');
        $timeFormatted = Carbon::parse($attendance->time_in ?? $attendance->time_out)->format('H:i');
        
        $action = $attendance->time_in ? 'Check-in' : 'Check-out';
        $adminTitle = "Absensi $action";
        $adminBody = "$userName melakukan $action pada tanggal $dateFormatted pukul $timeFormatted.";

        foreach ($adminUsers as $admin) {
            $admin->notify(new AbsensiCheckinNotification([
                'title' => $adminTitle,
                'body' => $adminBody,
                'attendance_id' => $attendance->id,
                'type' => 'attendance',
            ]));
        }
    }

    /**
     * Helper function to calculate distance between two lat/lon points using Haversine formula.
     * @param float $lat1 Latitude of point 1
     * @param float $lon1 Longitude of point 1
     * @param float $lat2 Latitude of point 2
     * @param float $lon2 Longitude of point 2
     * @param string $unit Unit of distance calculation (e.g., 'km', 'miles', 'meters')
     * @return float Distance between the two points in the specified unit.
     */
    private function getDistance(float $lat1, float $lon1, float $lat2, float $lon2, string $unit = "meters") {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        
        if ($unit == "kilometers") {
            return ($miles * 1.609344);
        } else if ($unit == "meters") {
            return ($miles * 1.609344 * 1000);
        }
        return $miles;
    }

    /**
     * Helper function to format transfer details for the API response.
     *
     * @param \App\Models\TransferRequest $transferRequest
     * @param string $stage The current stage of the transfer (e.g., 'pending_source_action', 'checked_out_from_source', 'completed')
     * @param \App\Models\Attendance|null $transferOutRecord
     * @param \App\Models\Attendance|null $transferInRecord
     * @param \App\Models\Attendance|null $sourceCheckInRecord
     * @return array
     */
    protected function formatTransferDetails(
        TransferRequest $transferRequest,
        string $stage,
        ?Attendance $transferOutRecord = null,
        ?Attendance $transferInRecord = null,
        ?Attendance $sourceCheckInRecord = null
    ): array {
        $fromOffice = optional($transferRequest->currentSchedule->office);
        $toOffice = optional($transferRequest->targetSchedule->office);

        // Cek apakah ada attendance dengan format baru
        $todayAttendance = Attendance::where('user_id', $transferRequest->user_id)
            ->where('date', Carbon::now()->toDateString())
            ->where('is_transfer_day', true)
            ->where('transfer_request_id', $transferRequest->id)
            ->first();
        
        if ($todayAttendance) {
            // Format transfer details menggunakan data dari struktur baru
            $details = [
                'id' => $transferRequest->id,
                'effective_date' => $transferRequest->effective_date,
                'reason' => $transferRequest->reason,
                'status_request' => $transferRequest->status, // 'approved', 'pending', etc.
                'from_office' => [
                    'id' => $fromOffice->id,
                    'name' => $fromOffice->name,
                ],
                'to_office' => [
                    'id' => $toOffice->id,
                    'name' => $toOffice->name,
                ],
                'current_stage' => $todayAttendance->transfer_status,
                'source_attendance' => [
                    'time_in' => $todayAttendance->source_time_in,
                    'time_out' => $todayAttendance->source_time_out,
                    'latlon_in' => $todayAttendance->source_latlon_in,
                    'latlon_out' => $todayAttendance->source_latlon_out,
                ],
                'destination_attendance' => [
                    'time_in' => $todayAttendance->destination_time_in,
                    'time_out' => $todayAttendance->destination_time_out, 
                    'latlon_in' => $todayAttendance->destination_latlon_in,
                    'latlon_out' => $todayAttendance->destination_latlon_out,
                ],
                'next_action_message' => '',
            ];
            
            // Generate next action message berdasarkan status
            switch ($todayAttendance->transfer_status) {
                case 'pending':
                    $details['next_action_message'] = "Silakan lakukan absensi (check-in lalu check-out) di kantor asal ({$fromOffice->name}) untuk memulai proses transfer.";
                    break;
                case 'checked_in_at_source':
                    $details['next_action_message'] = "Anda telah check-in di kantor asal ({$fromOffice->name}). Silakan lakukan check-out dari kantor asal untuk melanjutkan.";
                    break;
                case 'checked_out_from_source':
                    $details['next_action_message'] = "Anda telah check-out dari {$fromOffice->name}. Silakan lakukan absensi (check-in) di kantor tujuan ({$toOffice->name}).";
                    break;
                case 'checked_in_at_destination':
                    $details['next_action_message'] = "Anda telah check-in di kantor tujuan ({$toOffice->name}). Silakan lakukan check-out untuk menyelesaikan hari kerja.";
                    break;
                case 'completed':
                    $details['next_action_message'] = "Proses transfer ke {$toOffice->name} telah selesai.";
                    break;
                default:
                    $details['next_action_message'] = "Status transfer tidak diketahui.";
                    break;
            }
            
            return $details;
        }
        
        // Fallback ke format lama jika tidak ada attendance dengan format baru
        $details = [
            'id' => $transferRequest->id,
            'effective_date' => $transferRequest->effective_date,
            'reason' => $transferRequest->reason,
            'status_request' => $transferRequest->status, // 'approved', 'pending', etc.
            'from_office' => [
                'id' => $fromOffice->id,
                'name' => $fromOffice->name,
                // Add other office details as needed
            ],
            'to_office' => [
                'id' => $toOffice->id,
                'name' => $toOffice->name,
                // Add other office details as needed
            ],
            'current_stage' => $stage, // e.g., pending_source_action, checked_out_from_source, completed
            'source_attendance' => null, // Initialize
            'destination_attendance' => $transferInRecord ? $transferInRecord->toArray() : null, // Attendance record for clocking in/out at destination
            'transfer_out_attendance' => $transferOutRecord ? $transferOutRecord->toArray() : null, // Attendance record for clocking out from source
            'next_action_message' => '' // Placeholder for next action message based on stage
        ];

        // Populate source_attendance based on stage or if record is provided
        if ($sourceCheckInRecord) {
            $details['source_attendance'] = $sourceCheckInRecord->toArray();
        }

        // Customize next_action_message based on stage
        switch ($stage) {
            case 'pending_source_action':
                $details['next_action_message'] = "Silakan lakukan absensi (check-in lalu check-out) di kantor asal ({$fromOffice->name}) untuk memulai proses transfer.";
                break;
            case 'checked_in_at_source':
                $details['next_action_message'] = "Anda telah check-in di kantor asal ({$fromOffice->name}). Silakan lakukan check-out dari kantor asal untuk melanjutkan.";
                break;
            case 'checked_out_from_source':
                $details['next_action_message'] = "Anda telah check-out dari {$fromOffice->name}. Silakan lakukan absensi (check-in) di kantor tujuan ({$toOffice->name}).";
                break;
            case 'completed':
                $details['next_action_message'] = "Proses transfer ke {$toOffice->name} telah selesai.";
                break;
            default:
                $details['next_action_message'] = "Status transfer tidak diketahui.";
                break;
        }
        return $details;
    }

    /**
     * Special endpoint for destination office checkout during transfers.
     * This is a last resort when the regular checkout method fails.
     */
    public function transferDestinationCheckout(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::now()->toDateString();

        // Validate basic input
        $validator = Validator::make($request->all(), [
            'latlon_out' => 'required|string',
            'transfer_id' => 'required|exists:transfer_requests,id',
            'attendance_type' => 'required|string',
            'identity_data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the transfer request
        $transferRequest = TransferRequest::with(['currentSchedule.office', 'targetSchedule.office'])
            ->where('id', $request->transfer_id)
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('effective_date', $today)
            ->first();

        if (!$transferRequest) {
            return response()->json([
                'message' => 'Transfer request tidak ditemukan atau tidak aktif untuk hari ini.'
            ], 404);
        }

        // Get any existing attendance record for today that is a transfer
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->where(function($query) {
                $query->where('is_transfer_day', true)
                      ->orWhereNotNull('transfer_request_id');
            })
            ->first();

        // Emergency logic - We will update the existing record or create a new one
        DB::beginTransaction();
        try {
            if ($existingAttendance) {
                // Update existing record regardless of its current state
                $existingAttendance->destination_time_out = Carbon::now()->toTimeString();
                $existingAttendance->destination_latlon_out = $request->latlon_out;
                $existingAttendance->transfer_status = 'completed';
                $existingAttendance->is_transfer_day = true;
                $existingAttendance->transfer_request_id = $transferRequest->id;
                $existingAttendance->source_office_id = $transferRequest->currentSchedule->office_id;
                $existingAttendance->destination_office_id = $transferRequest->targetSchedule->office_id;
                $existingAttendance->status_attendance = 'checked_out'; // For backward compatibility
                
                // For backward compatibility
                $existingAttendance->time_out = Carbon::now()->toTimeString();
                $existingAttendance->latlon_out = $request->latlon_out;
                
                $existingAttendance->save();
                $attendance = $existingAttendance;
            } else {
                // Create a new attendance record with complete transfer data
                $attendance = new Attendance();
                $attendance->user_id = $user->id;
                $attendance->schedule_id = $transferRequest->target_schedule_id; // Use destination schedule
                $attendance->date = $today;
                $attendance->time_in = Carbon::now()->subHours(1)->toTimeString(); // Set a fake check-in time 1 hour ago
                $attendance->time_out = Carbon::now()->toTimeString();
                $attendance->latlon_in = $request->latlon_out; // Use checkout location as fallback
                $attendance->latlon_out = $request->latlon_out;
                $attendance->attendance_type = $request->attendance_type;
                $attendance->status_attendance = 'checked_out';
                
                // Set transfer data
                $attendance->is_transfer_day = true;
                $attendance->transfer_request_id = $transferRequest->id;
                $attendance->source_office_id = $transferRequest->currentSchedule->office_id;
                $attendance->destination_office_id = $transferRequest->targetSchedule->office_id;
                
                // Fake source times if needed
                $attendance->source_time_in = Carbon::now()->subHours(3)->toTimeString();
                $attendance->source_time_out = Carbon::now()->subHours(2)->toTimeString();
                $attendance->source_latlon_in = $request->latlon_out;
                $attendance->source_latlon_out = $request->latlon_out;
                
                // Set destination times
                $attendance->destination_time_in = Carbon::now()->subHours(1)->toTimeString();
                $attendance->destination_time_out = Carbon::now()->toTimeString();
                $attendance->destination_latlon_in = $request->latlon_out;
                $attendance->destination_latlon_out = $request->latlon_out;
                
                $attendance->transfer_status = 'completed';
                
                $attendance->save();
            }
            
            // Clear any cached attendance data
            Cache::forget("user_{$user->id}_attendance_{$today}");
            Cache::forget("user_{$user->id}_active_attendance_{$today}");
            Cache::forget("attendance_history_{$user->id}_{$today}");
            Cache::forget("attendance_status_user_{$user->id}_{$today}");
            
            DB::commit();
            
            return response()->json([
                'message' => 'Checkout darurat dari kantor tujuan berhasil',
                'attendance' => $attendance,
                'transfer_details' => [
                    'from_office' => $transferRequest->currentSchedule->office->name,
                    'to_office' => $transferRequest->targetSchedule->office->name,
                    'current_stage' => 'completed',
                    'next_step' => 'none'
                ]
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error executing emergency destination checkout: " . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat checkout darurat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Get monthly attendance status per day for a user
     * Params: user_id, month, year
     * Returns: [{date: 'YYYY-MM-DD', status: 'onsite|remote|leave|libur|alfa'}]
     */
    public function monthly(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            \Log::warning('API monthly() called with invalid token', [
                'token' => $request->bearerToken(),
                'headers' => $request->headers->all(),
            ]);
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        \Log::info('DEBUG: API monthly() called', [
            'request' => $request->all(),
            'headers' => $request->headers->all(),
            'user_id' => $user ? $user->id : null,
            'is_authenticated' => $user ? true : false,
            'token' => $request->bearerToken(),
        ]);

        try {
            $validator = \Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'month' => 'required|integer|between:1,12',
                'year' => 'required|integer|min:2000',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $userId = $request->input('user_id');
            $month = $request->input('month');
            $year = $request->input('year');

            \Log::info('DEBUG: Call getDetailedMonthlyAttendance', compact('userId', 'month', 'year'));
            $attendanceData = $this->getDetailedMonthlyAttendance($userId, $month, $year);

            // Rekap summary bulanan
            $summary = [
                'hadir' => 0,
                'transfer' => 0,
                'remote' => 0,
                'late' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alfa' => 0,
                'total_hadir' => 0,
            ];
            foreach ($attendanceData as $item) {
                switch ($item['status']) {
                    case 'onsite':
                        $summary['hadir']++;
                        break;
                    case 'transfer':
                        $summary['transfer']++;
                        break;
                    case 'remote':
                        $summary['remote']++;
                        break;
                    case 'late':
                        $summary['late']++;
                        break;
                    case 'leave':
                        $summary['izin']++;
                        break;
                    case 'sick':
                        $summary['sakit']++;
                        break;
                    case 'alfa':
                        $summary['alfa']++;
                        break;
                }
            }

            // Tambahkan total hadir (onsite + transfer + remote + late)
            $summary['total_hadir'] = $summary['hadir'] + $summary['transfer'] + $summary['remote'] + $summary['late'];

            // Perbaikan: wrap hasil dalam field 'data' dan 'summary'
            return response()->json([
                'data' => $attendanceData,
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            \Log::error('FATAL ERROR in monthly()', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Internal Server Error', 'detail' => $e->getMessage()], 500);
        }
    }

    public function getDetailedMonthlyAttendance($userId, $month, $year)
    {
        \Log::info('DEBUG: Mulai getDetailedMonthlyAttendance', compact('userId', 'month', 'year'));
        $user = User::findOrFail($userId);
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $results = [];

        // Ambil tanggal mulai aktif user (face_registered_at jika ada, jika tidak pakai created_at)
        $userActiveDate = $user->face_registered_at ? Carbon::parse($user->face_registered_at)->startOfDay() : $user->created_at->startOfDay();

        // DEBUG: Log raw attendances sebelum keyBy
        $rawAttendances = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
        \Log::info('DEBUG: RAW attendances', $rawAttendances->toArray());

        $attendances = $rawAttendances
            ->keyBy(function($item) {
                // Jika field date bertipe datetime, ambil hanya tanggal
                $dateStr = is_string($item->date) ? substr($item->date, 0, 10) : Carbon::parse($item->date)->toDateString();
                \Log::info('DEBUG: keyBy date', ['id' => $item->id, 'date' => $item->date, 'key' => $dateStr]);
                return $dateStr;
            });
        \Log::info('DEBUG: attendances after keyBy', $attendances->toArray());

        $leaveRequests = LeaveRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
            })
            ->get();

        $userDayOffs = \App\Models\UserDayOff::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn($date) => Carbon::parse($date)->toDateString())
            ->flip();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            \Log::info('DEBUG: Looping date', ['date' => $date->toDateString()]);
            try {
                $dateString = $date->toDateString();
                // Jika hari sebelum user aktif, skip (tidak dianggap alfa)
                if ($date->lt($userActiveDate)) {
                    continue;
                }
                // --- FIX: Prioritaskan LIBUR sebelum ALFA, bahkan untuk hari ini! ---
                if (isset($userDayOffs[$dateString]) || !\App\Services\WorkCalendarService::isWorkDay($user, $date)) {
                    $dayData = [
                        'date' => $dateString,
                        'status' => 'libur',
                        'time_in' => null,
                        'time_out' => null,
                        'total_hours' => null,
                        'location' => null,
                        'office_name' => null,
                        'is_transfer_day' => false,
                        'transfer_status' => null,
                        'source_time_in' => null,
                        'source_time_out' => null,
                        'destination_time_in' => null,
                        'destination_time_out' => null,
                        'transfer_request_id' => null,
                        'title' => 'Libur',
                        'description' => '',
                    ];
                    $dayData['status'] = strtolower($dayData['status']);
                    \Log::info('DEBUG: monthly attendance', $dayData);
                    $results[] = $dayData;
                    \Log::info('DEBUG: monthly attendance', ['date' => $dateString, 'status' => 'libur']);
                    continue; // Jangan proses ALFA/leave/attendance jika sudah libur
                }
                // Jika hari ini atau masa depan, jangan generate ALFA
                if ($date->isFuture()) {
                    continue;
                }
                // --- Tambahan: Cegah duplikasi tanggal di $results[] ---
                if (in_array($dateString, array_column($results, 'date'))) {
                    continue;
                }
                $dayData = [
                    'date' => $dateString,
                    'status' => 'alfa',
                    'time_in' => null,
                    'time_out' => null,
                    'total_hours' => null,
                    'location' => null,
                    'office_name' => null,
                    // Tambahan field transfer
                    'is_transfer_day' => false,
                    'transfer_status' => null,
                    'source_time_in' => null,
                    'source_time_out' => null,
                    'destination_time_in' => null,
                    'destination_time_out' => null,
                    'transfer_request_id' => null,
                    'title' => 'Alfa',
                    'description' => '',
                ];

                $isLeaveDay = $leaveRequests->first(function ($leave) use ($date) {
                    return $date->between(\Carbon\Carbon::parse($leave->start_date), \Carbon\Carbon::parse($leave->end_date));
                });

                if ($isLeaveDay) {
                    $dayData['status'] = 'leave';
                    $dayData['title'] = 'Leave';
                    $dayData['description'] = $isLeaveDay->reason ?? '';
                } elseif (isset($attendances[$dateString])) {
                    $attendance = $attendances[$dateString];
                    
                    // --- PRIORITASKAN TRANSFER ---
                    if (
                        $attendance->is_transfer_day ||
                        !empty($attendance->transfer_request_id)
                    ) {
                        $dayData['status'] = 'transfer';
                        $dayData['title'] = 'Transfer';
                        $dayData['description'] = 'Absensi transfer dari ' .
                            ($attendance->source_office_id ? (\App\Models\Office::find($attendance->source_office_id)?->name ?? 'Kantor Asal') : '-') .
                            ' ke ' .
                            ($attendance->destination_office_id ? (\App\Models\Office::find($attendance->destination_office_id)?->name ?? 'Kantor Tujuan') : '-');
                        $dayData['time_in'] = $attendance->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('H:i:s') : null;
                        $dayData['time_out'] = $attendance->time_out ? \Carbon\Carbon::parse($attendance->time_out)->format('H:i:s') : null;
                        $dayData['location'] = $attendance->schedule?->office?->name ?? 'Lokasi tidak diketahui';
                        $dayData['office_name'] = $attendance->schedule?->office?->name ?? null;
                        $dayData['is_transfer_day'] = true;
                        $dayData['transfer_status'] = $attendance->transfer_status;
                        $dayData['source_time_in'] = $attendance->source_time_in;
                        $dayData['source_time_out'] = $attendance->source_time_out;
                        $dayData['destination_time_in'] = $attendance->destination_time_in;
                        $dayData['destination_time_out'] = $attendance->destination_time_out;
                        $dayData['transfer_request_id'] = $attendance->transfer_request_id;
                        if ($attendance->time_in && $attendance->time_out) {
                            $timeIn = \Carbon\Carbon::parse($attendance->time_in);
                            $timeOut = \Carbon\Carbon::parse($attendance->time_out);
                            $dayData['total_hours'] = $timeOut->diff($timeIn)->format('%H:%I:%S');
                        } else {
                            $dayData['total_hours'] = null;
                        }
                        $dayData['status'] = strtolower($dayData['status']);
                        \Log::info('DEBUG: monthly attendance (TRANSFER)', $dayData);
                        $results[] = $dayData;
                        continue;
                    }
                    
                    // Normal attendance (non-transfer)
                    $dayData['status'] = $attendance->attendance_type === 'WFH' ? 'remote' : 'onsite';
                    $dayData['title'] = $dayData['status'] === 'remote' ? 'Remote' : 'Onsite';
                    $dayData['time_in'] = $attendance->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('H:i:s') : null;
                    $dayData['time_out'] = $attendance->time_out ? \Carbon\Carbon::parse($attendance->time_out)->format('H:i:s') : null;
                    $dayData['location'] = $attendance->schedule?->office?->name ?? 'Lokasi tidak diketahui';
                    $dayData['office_name'] = $attendance->schedule?->office?->name ?? null;
                    if ($attendance->time_in && $attendance->time_out) {
                        $timeIn = \Carbon\Carbon::parse($attendance->time_in);
                        $timeOut = \Carbon\Carbon::parse($attendance->time_out);
                        $dayData['total_hours'] = $timeOut->diff($timeIn)->format('%H:%I:%S');
                    } else {
                        $dayData['total_hours'] = null;
                    }
                    $dayData['description'] = '';
                    $dayData['status'] = strtolower($dayData['status']);
                    \Log::info('DEBUG: monthly attendance', $dayData);
                    $results[] = $dayData;
                    \Log::info('DEBUG: monthly attendance', ['date' => $dateString, 'status' => $dayData['status']]);
                    continue;
                }
            } catch (\Exception $e) {
                \Log::error('ERROR in getDetailedMonthlyAttendance', [
                    'date' => $date->toDateString(),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

        return $results;
    }

    /**
     * Export absensi ke Excel (sudah ada)
     */
    public function exportExcel(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = $request->input('user_id');
        $month = $request->input('month');
        $year = $request->input('year');

        $export = new \App\Exports\AttendanceExport($userId, $month, $year);
        $fileName = "laporan_absensi_{$userId}_{$month}_{$year}.xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
    }

    /**
     * Export absensi ke PDF (untuk API)
     */
    public function exportPdf(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = $request->input('user_id');
        $month = $request->input('month');
        $year = $request->input('year');

        $user = \App\Models\User::findOrFail($userId);
        // Ambil data harian lengkap (termasuk Alfa, Libur, dsb)
        $attendances = $this->getDetailedMonthlyAttendance($userId, $month, $year);

        $pdf = \PDF::loadView('exports.attendance-data-pdf', compact('user', 'attendances', 'month', 'year'));
        $fileName = 'absensi_' . $user->name . '_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($fileName);
    }
}
