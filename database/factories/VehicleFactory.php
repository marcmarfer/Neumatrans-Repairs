<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'plate_number' => strtoupper($this->faker->unique()->bothify('??###??')),
            'brand' => $this->faker->company,
            'model' => $this->faker->word,
            'year' => $this->faker->year
        ];
    }
}
