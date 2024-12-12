<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Client;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::inRandomOrder()->value('id') ?? Client::factory(),
            'plate_number' => strtoupper($this->faker->unique()->bothify('??###??')),
            'brand' => $this->faker->company,
            'model' => $this->faker->word,
            'year' => $this->faker->year
        ];
    }
}
