<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Schedule;
use App\Models\User;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
//        // Create a default schedule for each company
//        Company::all()->each(function (Company $company) {
//            $schedule = Schedule::create([
//                'company_id' => $company->id,
//                'schedule_name' => 'Default Schedule for ' . $company->name,
//                'time_in' => '08:00:00',
//                'time_out' => '17:00:00',
//                'is_active' => true,
//                'default_attendance_type' => 'ON_SITE',
//            ]);
//
//            // Assign this schedule to all users (adjust logic as needed)
//            User::all()->each(function (User $user) use ($schedule) {
//                $user->schedule_id = $schedule->id;
//                $user->save();
//            });
//        });
    }
} 