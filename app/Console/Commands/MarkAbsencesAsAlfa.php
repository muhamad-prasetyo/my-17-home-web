<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\UserDayOff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MarkAbsencesAsAlfa extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-alfa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark users who were absent without leave on the previous day as "Alfa"';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $yesterday = Carbon::yesterday()->toDateString();
        $this->info("Checking for absences on: {$yesterday}");
        Log::info("Starting attendance:mark-alfa job for date: {$yesterday}");

        // Get all active users who should have an attendance record
        $users = User::where('role', '!=', 'super_admin')->where('is_wfa', false)->get(); // Example: Exclude super admins and WFA users

        foreach ($users as $user) {
            // 1. Check if there is already an attendance record for the user yesterday
            $hasAttendance = Attendance::where('user_id', $user->id)
                ->where('date', $yesterday)
                ->exists();

            if ($hasAttendance) {
                continue; // Skip to the next user
            }

            // 2. Check if the user had an approved leave
            $hasLeave = LeaveRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('start_date', '<=', $yesterday)
                ->where('end_date', '>=', $yesterday)
                ->exists();

            if ($hasLeave) {
                continue; // Skip to the next user
            }

            // 3. Check if it was a personal day off for the user
            $isDayOff = UserDayOff::where('user_id', $user->id)
                ->where('date', $yesterday)
                ->exists();

            if ($isDayOff) {
                continue; // Skip to the next user
            }

            // 4. (Optional) Check for general company holidays
            // Example: $isCompanyHoliday = CompanyHoliday::where('date', $yesterday)->exists();
            // if ($isCompanyHoliday) {
            //     continue;
            // }

            // If all checks pass, the user is considered "Alfa"
            $this->warn("User {$user->name} (ID: {$user->id}) is marked as Alfa for {$yesterday}.");
            Log::info("User {$user->name} (ID: {$user->id}) is marked as Alfa for {$yesterday}.");

            Attendance::create([
                'user_id' => $user->id,
                'schedule_id' => $user->schedule_id,
                'date' => $yesterday,
                'status_attendance' => 'alpha',
            ]);
        }

        $this->info('Successfully marked all applicable absences as Alfa.');
        Log::info('Finished attendance:mark-alfa job.');
        return Command::SUCCESS;
    }
}
