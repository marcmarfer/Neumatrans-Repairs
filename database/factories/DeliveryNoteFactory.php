<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryNote>
 */
class DeliveryNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['generic', 'corrective']),
            'supplier' => $this->faker->company,
            'family' => $this->faker->word,
            'RRP' => $this->faker->randomFloat(2, 50, 500),
            'cost' => $this->faker->randomFloat(2, 50, 500),
            'margin' => $this->faker->randomFloat(2, 10, 100),
            'profit' => $this->faker->randomFloat(2, 10, 100),
            'added_at' => $this->faker->date(),
        ];
    }
}
