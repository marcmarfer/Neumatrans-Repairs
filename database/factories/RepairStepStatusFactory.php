<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Repair;
use App\Models\RepairTypeStep;

class RepairStepStatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'repair_id' => Repair::inRandomOrder()->value('id') ?? Repair::factory(),
            'repair_type_step_id' => RepairTypeStep::inRandomOrder()->value('id') ?? RepairTypeStep::factory(),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
        ];
    }
}
