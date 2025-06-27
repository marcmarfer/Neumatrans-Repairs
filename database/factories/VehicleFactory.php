<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Client;
use App\Models\Brand;
use App\Models\VehicleModel;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::inRandomOrder()->value('id') ?? Client::factory(),
            'plate_number' => strtoupper($this->faker->unique()->bothify('??###??')),
            'brand_id' => Brand::inRandomOrder()->value('id') ?? Brand::factory(),
            'model_id' => VehicleModel::inRandomOrder()->value('id') ?? VehicleModel::factory(),
            'VIN' => strtoupper($this->faker->unique()->bothify('?????????????????')),
            'motor_type' => $this->faker->word,
            'added_at' => $this->faker->date(),
        ];
    }
}
