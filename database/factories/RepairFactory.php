<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Vehicle;
use App\Models\RepairType;
use App\Models\RepairTypeStep;

class RepairFactory extends Factory
{
    public function definition(): array
    {
        $repairType = RepairType::inRandomOrder()->first() ?? RepairType::factory()->create();
        $step = RepairTypeStep::where('repair_type_id', $repairType->id)->inRandomOrder()->first();
        
        if (!$step) {
            $step = RepairTypeStep::factory()->create(['repair_type_id' => $repairType->id]);
        }
        
        return [
            'repair_type_id' => $repairType->id,
            'vehicle_id' => Vehicle::inRandomOrder()->value('id') ?? Vehicle::factory(),
            'observations' => $this->faker->paragraph,
            'step' => $step->id,
            'started_at' => $this->faker->date(),
        ];
    }
}
