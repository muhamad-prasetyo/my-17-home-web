<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Schedule::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'office_id' => Office::factory(), // Creates an Office if not provided
            'schedule_name' => $this->faker->words(3, true),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'working_days' => 'Mon,Tue,Wed,Thu,Fri', // Example working days
            'is_active' => true,
        ];
    }
} 