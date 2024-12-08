<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\RepairType;

class RepairTypeStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'repair_type_id' => RepairType::inRandomOrder()->value('id') ?? RepairType::factory(),
            'step_name' => $this->faker->sentence(3),
            'step_order' => $this->faker->numberBetween(1, 100)
        ];
    }
}
