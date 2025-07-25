<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\UserDayOff;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    /**
     * Get all calendar events for the authenticated user within a date range.
     * Events include attendances, leaves, and days off.
     */
    public function getEvents(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $user = Auth::user();
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // 1. Fetch all relevant data in bulk queries for efficiency
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
        Log::info('Fetched attendances for user ' . $user->id . ': ' . $attendances->count());

        $leaveRequests = LeaveRequest::with('leaveType')
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
            })
            ->get();
        Log::info('Fetched leave requests for user ' . $user->id . ': ' . $leaveRequests->count());

        $dayOffs = UserDayOff::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
        Log::info('Fetched day offs for user ' . $user->id . ': ' . $dayOffs->count());

        // Create maps for O(1) lookups
        $attendanceMap = $attendances->keyBy(fn($item) => Carbon::parse($item->date)->toDateString());
        $dayOffMap = $dayOffs->keyBy(fn($item) => Carbon::parse($item->date)->toDateString());
        
        // 2. Process all dates in the range to create a unified event list
        $events = [];
        
        // Create a map of leave dates for quick checking
        $leaveDates = [];
        foreach ($leaveRequests as $leave) {
            $leavePeriod = CarbonPeriod::create($leave->start_date, $leave->end_date);
            foreach ($leavePeriod as $date) {
                $leaveDates[$date->toDateString()] = $leave;
            }
        }
        
        // Iterate through each day in the requested period
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $dateString = $date->toDateString();
            
            // Priority check: Day Off > Leave Request > Attendance
            if ($dayOffMap->has($dateString)) {
                $events[] = [
                    'date' => $dateString,
                    'title' => 'Libur',
                    'description' => $dayOffMap->get($dateString)->note ?? 'Hari Libur Pribadi',
                    'status' => 'libur', 
                ];
                continue; // Move to the next day
            }
            
            if (isset($leaveDates[$dateString])) {
                $leave = $leaveDates[$dateString];
                $events[] = [
                    'date' => $dateString,
                    'title' => 'Cuti',
                    'description' => $leave->leaveType->name ?? 'Cuti Disetujui',
                    'status' => 'leave',
                ];
                continue; // Move to the next day
            }
            
            if ($attendanceMap->has($dateString)) {
                $attendance = $attendanceMap->get($dateString);
                $event = null;
                switch ($attendance->status_attendance) {
                    case 'present':
                    case 'checked_in':
                    case 'checked_out':
                        $lateLabel = $attendance->is_late ? ' (Terlambat)' : '';
                        $event = [
                            'title' => 'Onsite' . $lateLabel,
                            'description' => ($attendance->time_in ? Carbon::parse($attendance->time_in)->format('H:i') : '') . ' - ' . ($attendance->time_out ? Carbon::parse($attendance->time_out)->format('H:i') : '...'),
                            'status' => 'onsite',
                        ];
                        break;
                    case 'wfh':
                    case 'checked_in_wfa':
                        $event = [
                            'title' => 'WFH',
                            'description' => ($attendance->time_in ? Carbon::parse($attendance->time_in)->format('H:i') : '') . ' - ' . ($attendance->time_out ? Carbon::parse($attendance->time_out)->format('H:i') : '...'),
                            'status' => 'wfh',
                        ];
                        break;
                    case 'alpha':
                        $event = [
                            'title' => 'Alfa',
                            'description' => 'Tidak ada keterangan',
                            'status' => 'alfa',
                        ];
                        break;
                }
                if ($event) {
                    $event['date'] = $dateString;
                    $events[] = $event;
                }
            }
        }
        
        Log::info('Returning ' . count($events) . ' events for user ' . $user->id);

        return response()->json(['data' => $events]);
    }
}
