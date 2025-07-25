<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\LeaveTypeController;
use App\Http\Controllers\Api\TransferRequestController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\QrAbsenController;
use App\Http\Controllers\Api\UserScheduleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\CalendarController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Test endpoint untuk koneksi Flutter
Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'API Laravel berjalan dengan baik!',
        'timestamp' => now(),
        'server' => request()->getHost(),
        'app_name' => config('app.name'),
    ]);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Public announcement routes
Route::get('/announcements', [AnnouncementController::class, 'index']);
Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);

// Office locations (public)
Route::get('/offices', [OfficeController::class, 'index']);
Route::get('/offices/{id}', [OfficeController::class, 'show']);

// Email verification routes
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return response(['message' => 'Email verified!']);
})->middleware(['auth:sanctum', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return response(['message' => 'Verification link sent!']);
})->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');

// Routes yang memerlukan autentikasi
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/update-fcm-token', [AuthController::class, 'updateFcmToken']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);
    
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/me/schedule', [UserController::class, 'mySchedule']);
    Route::get('/api-me-schedule', [UserController::class, 'mySchedule']); // Alias for Flutter
    Route::get('/user/{id}', [UserController::class, 'getUserId']);
    Route::get('/api-user/{id}', [UserController::class, 'getUserId']); // Alias untuk Flutter
    Route::post('/updateProfile', [UserController::class, 'updateProfile']);
    Route::post('/api-user/edit', [UserController::class, 'updateProfile']); // Alias untuk Flutter
    Route::middleware(['auth:sanctum', 'is_admin'])->post('/user/{id}/approve', [App\Http\Controllers\Api\UserController::class, 'approveUser']); // route approve user hanya admin
    
    // Company routes
    
    // Attendance routes
    Route::prefix('attendance')->group(function () {
        Route::post('/clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('/clock-out', [AttendanceController::class, 'clockOut']);
        Route::get('/history', [AttendanceController::class, 'history']);
        Route::get('/today-status', [AttendanceController::class, 'todayStatus']);
        Route::post('/transfer-destination-checkout', [AttendanceController::class, 'transferDestinationCheckout']);
        Route::get('/monthly', [AttendanceController::class, 'monthly']);
        Route::get('/export/excel', [AttendanceController::class, 'exportExcel']);
        Route::get('/export/pdf', [AttendanceController::class, 'exportPdf']);
    });
    
    // Permission routes
    Route::post('/permissions', [PermissionController::class, 'store']);
    Route::post('/api-permissions', [PermissionController::class, 'store']); // Alias untuk Flutter
    Route::get('/api-permissions', [PermissionController::class, 'index']); // List user permissions
    Route::get('/api-permissions/{id}', [PermissionController::class, 'show']); // Get permission detail
    
    // Note routes
    Route::get('/notes', [NoteController::class, 'index']);
    Route::post('/notes', [NoteController::class, 'store']);
    Route::get('/api-notes', [NoteController::class, 'index']); // Alias untuk Flutter
    Route::post('/api-notes', [NoteController::class, 'store']); // Alias untuk Flutter

    // Device Token routes:
    Route::get('/device-tokens', [DeviceTokenController::class, 'index']);
    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens/{id}', [DeviceTokenController::class, 'destroy']);
    
    // QR routes
    Route::post('/check-qr', [QrAbsenController::class, 'checkQR']);

    // Leave Request routes
    Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    Route::get('/api-leave-requests', [LeaveRequestController::class, 'index']); // Alias for Flutter
    Route::post('/api-leave-requests', [LeaveRequestController::class, 'store']); // Alias for Flutter
    Route::get('/leave_requests/user/{userId}', [LeaveRequestController::class, 'indexByUser']);

    // Transfer Request routes
    Route::get('/transfer-requests', [TransferRequestController::class, 'index']);
    Route::post('/transfer-requests', [TransferRequestController::class, 'store']);
    Route::get('/transfer-requests/{id}', [TransferRequestController::class, 'getTransferDetails']);
    Route::get('/transfer-history', [TransferRequestController::class, 'getTransferHistory']);

    // Schedule routes for transfer UI
    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::get('/api-schedules', [ScheduleController::class, 'index']); // Alias for Flutter

    Route::get('/leave-types', [LeaveTypeController::class, 'index']);
    Route::get('/api-leave-types', [LeaveTypeController::class, 'index']); // Alias untuk Flutter

    // Route for fetching user's today schedule
    Route::get('/user-schedule/today', [UserScheduleController::class, 'getTodaySchedule']);

    // Rute API untuk mendapatkan data user dan kehadiran berdasarkan ID user dan tanggal
    Route::get('/users/{userId}/attendance', [UserController::class, 'getUserAttendanceByDate']);

    Route::get('user-devices', [UserController::class, 'getUserDevices']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    
    // Permissions
    Route::apiResource('permissions', PermissionController::class);

    // Transfer Requests
    Route::apiResource('transfer-requests', TransferRequestController::class);
    Route::get('transfer-requests/details/{id}', [TransferRequestController::class, 'getTransferDetails']);
    Route::get('transfer-history', [TransferRequestController::class, 'getTransferHistory']);

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'getEvents']);

    // Transfer schedule routes
    Route::post('/attendance/transfer/destination-check-in', [AttendanceController::class, 'transferDestinationCheckin']);

    // Tambahkan endpoint report fake GPS
    Route::post('/report-fake-gps', [\App\Http\Controllers\Api\ReportFakeGpsController::class, 'report']);
});
