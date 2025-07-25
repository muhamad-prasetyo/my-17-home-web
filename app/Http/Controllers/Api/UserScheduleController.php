<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Schedule; // Assuming Schedule model exists
use App\Models\User; // Import the User model
use App\Models\Office; // Import the Office model
use Illuminate\Support\Facades\Log;

class UserScheduleController extends Controller
{
    /**
     * Get the authenticated user's schedule for today.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTodaySchedule(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        \Log::info('DEBUG: user->id', ['id' => $user->id]);
        \Log::info('DEBUG: user->schedule_id', ['schedule_id' => $user->schedule_id]);
        \Log::info('DEBUG: user->schedule', ['schedule' => $user->schedule]);

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Eager load the schedule and its related office
        $user->load('schedule.office');

        if (!$user->schedule || !$user->schedule->office) {
            return response()->json([
                'message' => 'User does not have an assigned schedule or office data.',
                'data' => null
            ], 404);
        }

        $schedule = $user->schedule;
        $office = $schedule->office;

        // Determine 'Area Kantor' from the related Office model
        $officeAreaName = $office->name ?? 'Office Not Set';

        // Log the determined values before returning
        $determinedAttendanceType = $office->office_type ?? $schedule->default_attendance_type ?? 'Not Set';

        Log::info("UserScheduleController: getTodaySchedule - User ID: {$user->id}, Office Area: {$officeAreaName}, Determined Attendance Type: {$determinedAttendanceType}.");

        return response()->json([
            'message' => 'User schedule and office data retrieved successfully.',
            'data' => [
                'id' => $schedule->id,
                'office_area' => $office->name,
                'schedule_name' => $schedule->schedule_name,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'attendance_type' => $schedule->attendance_type ?? $office->office_type,
                'email' => $office->email,
                'address' => $office->address,
                'latitude' => $office->latitude,
                'longitude' => $office->longitude,
                'radius_meter' => (string) $office->radius_meter,
                'created_at' => $office->created_at,
                'updated_at' => $office->updated_at,
            ]
        ]);
    }
}
