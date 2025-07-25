<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\User;
use App\Models\UserDayOff;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Services\WorkCalendarService;

class AttendanceCalendarWidget extends FullCalendarWidget
{
    public ?int $userId = null;
    public Model | string | null $model = null;
    public Model|int|string|null $record = null;

    // Nonaktifkan caching untuk memastikan data selalu fresh
    protected static bool $isLazy = false;
    
    // Cache hanya untuk 0 detik (tidak cache)
    public function cacheFor()
    {
        return now()->addSeconds(0);
    }

    public function mount()
    {
        $this->userId = request('user_id') ?? (Auth::user() ? Auth::user()->id : null);
    }

    // Unique key berdasarkan user yang dipilih untuk force refresh widget
    public function getWidgetKey(): string
    {
        $userId = $this->userId ?? 'default';
        return 'attendance_calendar_' . $userId;
    }

    /**
     * FullCalendar event click action.
     *
     * @param array $event
     * @return void
     */
    public function onEventClick(array $event): void
    {
        // Do nothing to prevent the default modal action.
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $userId = $this->userId;
        $user = $userId ? User::find($userId) : Auth::user();
        \Log::info('[AttendanceCalendarWidget] fetchEvents for user_id: ' . ($userId ?? 'NULL') . ' - ' . ($user ? ($user->id . ' - ' . $user->name) : 'NULL'));

        if (!$user) {
            return [];
        }

        $startDate = $fetchInfo['start'];
        $endDate = $fetchInfo['end'];

        // 1. Fetch Personal Holidays (Libur)
        $dayOffs = UserDayOff::where('user_id', $user->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get();

        // 2. Fetch Approved Leave Requests (Cuti/Izin)
        $leaves = \App\Models\LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
            })
            ->get();

        // Create a set of dates for day-offs and leaves to check against attendance
        $nonWorkingDates = [];
        foreach ($dayOffs as $off) {
            $nonWorkingDates[] = Carbon::parse($off->date)->format('Y-m-d');
        }
        foreach ($leaves as $leave) {
            $period = \Carbon\CarbonPeriod::create($leave->start_date, $leave->end_date);
            foreach ($period as $date) {
                if ($date->between($startDate, $endDate)) {
                    $nonWorkingDates[] = $date->format('Y-m-d');
                }
            }
        }
        $nonWorkingDates = array_unique($nonWorkingDates);

        // 3. Fetch Attendances
        $attendances = Attendance::where('user_id', $user->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get();

        $events = [];

        // Process Attendances
        foreach ($attendances as $a) {
            $attendanceDate = Carbon::parse($a->date)->format('Y-m-d');

            // If the user is marked 'alpha' on a non-working day, skip creating the event.
            // This prevents the black 'alpha' dot from overriding the holiday/leave color.
            if ($a->status_attendance === 'alpha' && in_array($attendanceDate, $nonWorkingDates)) {
                continue;
            }

            $title = '';
            $color = '';

            // Set title
            if ($a->time_in && $a->time_out) {
                $timeIn = \Carbon\Carbon::parse($a->time_in)->format('H:i');
                $timeOut = \Carbon\Carbon::parse($a->time_out)->format('H:i');
                $title = "{$timeIn} - {$timeOut}";
            } else {
                $title = strtoupper($a->status_attendance);
            }

            if ($a->is_late) {
                $title .= ' (Terlambat)';
            }

            // Determine Color based on legend
            if ($a->attendance_type === 'WFH') {
                $color = '#3b82f6'; // Remote (Blue)
            } elseif ($a->status_attendance === 'alpha') {
                $color = '#000000'; // Alfa (Black)
            } else {
                $color = '#22c55e'; // Onsite (Green)
            }

            $date = \Carbon\Carbon::parse($a->date)->format('Y-m-d');
            $events[] = [
                'title' => $title,
                'start' => $date,
                'color' => $color,
            ];
            $eventDates[$date] = true;

            $events[] = [
                'title' => $title,
                'start' => Carbon::parse($a->date)->format('Y-m-d'),
                'color' => $color,
            ];
        }

        // Process Leaves
        foreach ($leaves as $leave) {
            $period = \Carbon\CarbonPeriod::create($leave->start_date, $leave->end_date);
            foreach ($period as $date) {
                if ($date->between($fetchInfo['start'], $fetchInfo['end'])) {
                    $d = $date->format('Y-m-d');
                    $events[] = [
                        'title' => 'LEAVE',
                        'start' => $d,
                        'color' => '#facc15', // Yellow
                        'allDay' => true,
                    ];
                    $eventDates[$d] = true;

                    $events[] = [
                        'title' => 'Cuti',
                        'start' => $date->format('Y-m-d'),
                        'color' => '#facc15', // Yellow
                        'allDay' => true,
                        'extendedProps' => [
                            'description' => $leave->reason
                        ]
                    ];
                }
            }
        }

        // Process Personal Holidays
        foreach ($dayOffs as $libur) {
            $d = \Carbon\Carbon::parse($libur->date)->format('Y-m-d');
            $events[] = [
                'title' => 'LIBUR',
                'start' => $d,
                'color' => '#ef4444', // Red
                'allDay' => true,
            ];
            $eventDates[$d] = true;
        }

        // Tambahkan event Alfa otomatis untuk hari kerja yang tidak ada event apapun
        $start = \Carbon\Carbon::parse($fetchInfo['start']);
        $end = \Carbon\Carbon::parse($fetchInfo['end']);
        $faceRegisteredAt = $user->face_registered_at ? \Carbon\Carbon::parse($user->face_registered_at) : null;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            if ($date->isToday() || $date->isFuture()) {
                continue;
            }
            if (
                !isset($eventDates[$dateStr]) &&
                WorkCalendarService::isWorkDay($user, $date) &&
                $date->lte(now()) &&
                ($faceRegisteredAt && $date->gte($faceRegisteredAt))
            ) {
                $events[] = [
                    'title' => 'ALFA',
                    'start' => $dateStr,
                    'color' => '#000000', // Black
                    'allDay' => true,
                ];
            }
        }

        // Debug: Log jumlah events yang dihasilkan
        \Log::info('[AttendanceCalendarWidget] Total events generated: ' . count($events) . ' for user: ' . $user->id);

        return $events;
    }

    /**
     * Rekap bulanan untuk user tertentu.
     * @param int $userId
     * @param int $month
     * @param int $year
     * @return array
     */
    public static function getMonthlyRecap($userId, $month, $year)
    {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return [
                'hadir' => 0,
                'remote' => 0,
                'leave' => 0,
                'libur' => 0,
                'alfa' => 0,
                'total' => 0,
            ];
        }
        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $attendances = \App\Models\Attendance::where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();
        $leaves = \App\Models\LeaveRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end->toDateString())
                      ->where('end_date', '>=', $start->toDateString());
            })
            ->get();
        $dayOffs = \App\Models\UserDayOff::where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();
        $workDays = [];
        $faceRegisteredAt = $user->face_registered_at ? \Carbon\Carbon::parse($user->face_registered_at) : null;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (\App\Services\WorkCalendarService::isWorkDay($user, $date) &&
                $date->lte(now()) &&
                ($faceRegisteredAt && $date->gte($faceRegisteredAt))) {
                $workDays[] = $date->format('Y-m-d');
            }
        }
        $rekap = [
            'hadir' => 0,
            'remote' => 0,
            'leave' => 0,
            'libur' => 0,
            'alfa' => 0,
            'total' => count($workDays),
        ];
        $hadirDates = [];
        foreach ($attendances as $a) {
            if ($a->attendance_type === 'WFH') {
                $rekap['remote']++;
            } elseif ($a->status_attendance === 'alpha') {
                $rekap['alfa']++;
            } else {
                $rekap['hadir']++;
            }
            $hadirDates[] = \Carbon\Carbon::parse($a->date)->format('Y-m-d');
        }
        foreach ($leaves as $leave) {
            $period = \Carbon\CarbonPeriod::create($leave->start_date, $leave->end_date);
            foreach ($period as $date) {
                $d = $date->format('Y-m-d');
                if (in_array($d, $workDays)) {
                    $rekap['leave']++;
                }
            }
        }
        foreach ($dayOffs as $libur) {
            $d = \Carbon\Carbon::parse($libur->date)->format('Y-m-d');
            if (in_array($d, $workDays)) {
                $rekap['libur']++;
            }
        }
        // Hitung alfa otomatis (hari kerja yang tidak ada hadir, remote, leave, libur)
        $rekap['alfa'] = $rekap['total'] - ($rekap['hadir'] + $rekap['remote'] + $rekap['leave'] + $rekap['libur']);
        if ($rekap['alfa'] < 0) $rekap['alfa'] = 0;
        return $rekap;
    }
}
