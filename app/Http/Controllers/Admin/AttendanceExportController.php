<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use PDF; // barryvdh/laravel-dompdf

class AttendanceExportController extends Controller
{
    // Export data absensi (tabel) ke PDF
    public function exportData(Request $request)
    {
        $userId = $request->user_id;
        $month = $request->month;
        $year = $request->year;

        $user = User::findOrFail($userId);
        $query = Attendance::where('user_id', $userId);
        if ($month && $year) {
            $query->whereMonth('date', $month)->whereYear('date', $year);
        }
        $attendances = $query->orderBy('date')->get();

        $pdf = PDF::loadView('exports.attendance-data-pdf', compact('user', 'attendances', 'month', 'year'));
        return $pdf->download('absensi_'.$user->name.'_'.now()->format('Ymd_His').'.pdf');
    }

    // Export gambar kalender ke PDF
    public function exportCalendarImage(Request $request)
    {
        $imageData = $request->input('calendar_image'); // base64 string
        $userId = $request->user_id;
        $user = User::findOrFail($userId);

        $pdf = PDF::loadView('exports.attendance-calendar-image-pdf', compact('imageData', 'user'));
        return $pdf->download('kalender_'.$user->name.'_'.now()->format('Ymd_His').'.pdf');
    }

    // Export seluruh data absensi ke PDF (tabel lengkap)
    public function exportAllToPdf(Request $request)
    {
        $attendances = \App\Models\Attendance::with(['user', 'sourceOffice', 'destinationOffice'])->orderBy('date')->get();
        $pdf = \PDF::loadView('exports.attendances_pdf', compact('attendances'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('attendances_'.now()->format('Ymd_His').'.pdf');
    }
} 