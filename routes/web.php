<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\CompanyController; // Dikomentari
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\QrAbsenController;
use App\Http\Controllers\Admin\AttendanceExportController;

// Redirect to Filament admin panel instead of a custom login
Route::redirect('/', '/admin');

Route::middleware(['auth'])->group(function () {
    Route::get('home', function () {
        // total user
        $total_user = \App\Models\User::count();
        return view('pages.dashboard', ['type_menu' => 'home'], compact('total_user'));
    })->name('home');

    Route::resource('users', UserController::class);
    // Route::resource('companies', CompanyController::class); // Dikomentari
    Route::resource('attendances', AttendanceController::class);
    // Route::resource('permissions', PermissionController::class);
    Route::resource('qr_absens', QrAbsenController::class);
    Route::get('/qr-absens/{id}/download', [QrAbsenController::class, 'downloadPDF'])->name('qr_absens.download');

    Route::get('/admin/export-attendance-data', [AttendanceExportController::class, 'exportData'])->name('admin.export-attendance-data');
    Route::post('/admin/export-attendance-calendar-image', [AttendanceExportController::class, 'exportCalendarImage'])->name('admin.export-attendance-calendar-image');
    Route::get('/admin/attendance/export-pdf', [AttendanceExportController::class, 'exportAllToPdf'])->name('attendance.exportAllToPdf');
});
