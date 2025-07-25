<?php

namespace Database\Factories;

use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Office::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'radius_meter' => $this->faker->numberBetween(50, 200),
            // 'office_area' => $this->faker->city, // REMOVED: Column does not exist
            // Add other necessary fields with default fake data that ARE in the Office model
            'start_time' => '08:00:00', // Example default
            'end_time' => '17:00:00',   // Example default
            'type' => $this->faker->randomElement(['face_recognition', 'qr_code']), // MODIFIED: Use valid enum values
            'office_type' => $this->faker->randomElement(['Main Office', 'Branch Office', 'Client Site']), // Correctly uses office_type
        ];
    }
} 