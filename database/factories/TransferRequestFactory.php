<?php

namespace Database\Factories;

use App\Models\TransferRequest;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransferRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TransferRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $currentOffice = Office::factory()->create();
        $targetOffice = Office::factory()->create();

        $currentSchedule = Schedule::factory()->create(['office_id' => $currentOffice->id]);
        $targetSchedule = Schedule::factory()->create(['office_id' => $targetOffice->id]);

        return [
            'user_id' => User::factory(),
            'current_schedule_id' => $currentSchedule->id,
            'target_schedule_id' => $targetSchedule->id,
            'reason' => $this->faker->sentence,
            'status' => 'approved', // Default to approved for testing purposes
            'effective_date' => Carbon::today()->toDateString(),
            'approved_by_user_id' => User::factory(),
            'approval_date' => Carbon::now(),
            'request_date' => Carbon::yesterday()->toDateString(),
        ];
    }
} 