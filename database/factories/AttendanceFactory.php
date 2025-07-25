<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'schedule_id' => Schedule::factory(), // Assuming you have a ScheduleFactory
            'date' => Carbon::today()->toDateString(),
            'time_in' => null,
            'time_out' => null,
            'latlon_in' => null,
            'latlon_out' => null,
            'attendance_type' => 'normal', // default, can be overridden
            'status_attendance' => 'present', // default, can be overridden
            'is_late' => false,
            'late_duration' => null,
            'original_schedule_id' => null,
            'related_attendance_id' => null,
        ];
    }
}
