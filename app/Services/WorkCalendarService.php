<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class WorkCalendarService
{
    /**
     * Determine if the given date is a work day for the user.
     * Returns false if it's a holiday or not in the company's work_days.
     */
    public static function isWorkDay(User $user, Carbon $date): bool
    {
        // Determine company via user's schedule
        $schedule = $user->schedule;
        $company = $schedule ? $schedule->company : null;

        // Check standard work_days pattern on company
        if ($company && $company->work_days) {
            $days = explode(',', $company->work_days);
            // isoWeekday: 1 (Mon) .. 7 (Sun)
            if (!in_array((string)$date->isoWeekday(), $days, true)) {
                return false;
            }
        }

        return true;
    }
} 