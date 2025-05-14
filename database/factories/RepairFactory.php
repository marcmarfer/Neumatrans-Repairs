<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Vehicle;
use App\Models\RepairType;
use App\Models\RepairOrder;
use Illuminate\Support\Str;

class RepairFactory extends Factory
{
    public function definition(): array
    {
        $repairType = RepairType::inRandomOrder()->first() ?? RepairType::factory()->create();
        $repairOrder = RepairOrder::inRandomOrder()->first();
        
        if (!$repairOrder) {
            $user = \App\Models\User::first() ?? \App\Models\User::factory()->create();
            $client = \App\Models\Client::inRandomOrder()->first() ?? \App\Models\Client::factory()->create();
            
            $repairOrder = RepairOrder::create([
                'client_id' => $client->id,
                'observations' => $this->faker->paragraph,
                'status' => $this->faker->randomElement(['reception', 'in_repair', 'finished']),
                'created_by' => $user->id
            ]);
        }
        
        $vehicle = Vehicle::where('client_id', $repairOrder->client_id)->inRandomOrder()->first();
        if (!$vehicle) {
            $vehicle = Vehicle::inRandomOrder()->first() ?? Vehicle::factory()->create([
                'client_id' => $repairOrder->client_id
            ]);
        }
        
        return [
            'repair_type_id' => $repairType->id,
            'vehicle_id' => $vehicle->id,
            'repair_order_id' => $repairOrder->id,
            'observations' => $this->faker->paragraph,
            'tracking_token' => Str::uuid(),
            'started_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'completed_at' => $repairOrder->status === 'finished' ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
        ];
    }
}
