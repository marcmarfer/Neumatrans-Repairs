<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'email' => $this->faker->unique()->safeEmail,
            'telephone' => $this->faker->phoneNumber,
            'city' => $this->faker->city,
            'postal_code' => $this->faker->postcode,
            'DNI' => strtoupper($this->faker->unique()->bothify('########??')),
            'registered_at' => $this->faker->date(),
        ];
    }
}
