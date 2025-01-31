<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Vehicle;
use App\Models\RepairType;

class RepairFactory extends Factory
{
    public function definition(): array
    {
        return [
            'repair_type_id' => RepairType::inRandomOrder()->value('id') ?? RepairType::factory(),
            'vehicle_id' => Vehicle::inRandomOrder()->value('id') ?? Vehicle::factory(),
            'observations' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'started_at' => $this->faker->date(),
        ];
    }
}
