<?php

namespace App\Listeners;

use App\Events\LeaveRequestApproved;
use App\Models\Attendance;
use Carbon\Carbon;

class GenerateLeaveAttendanceEntries
{
    /**
     * Handle the event.
     */
    public function handle(LeaveRequestApproved $event): void
    {
        $leave = $event->leaveRequest;
        $userId = $leave->user_id;
        $leaveTypeName = optional($leave->leaveType)->name;
        // Determine attendance type and status based on leave type
        $attendanceType = $leaveTypeName === 'Izin Karyawan' ? 'permission' : 'leave';
        $statusAttendance = $leaveTypeName === 'Izin Karyawan' ? 'on_permission' : 'on_leave';

        $startDate = Carbon::parse($leave->start_date);
        $endDate = Carbon::parse($leave->end_date);

        // Loop through each date in the leave period
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->toDateString();

            // Skip if an attendance record already exists for this user and date
            $existingAttendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $dateString)
                ->first();
            if ($existingAttendance) {
                continue;
            }

            // Create a new attendance record for the leave day
            Attendance::create([
                'user_id'           => $userId,
                'date'              => $dateString,
                'attendance_type'   => $attendanceType,
                'status_attendance' => $statusAttendance,
            ]);
        }
    }
} 