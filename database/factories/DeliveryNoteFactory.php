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
        $quantity = $this->faker->numberBetween(1, 4);
        $unitaryPrice = $this->faker->randomFloat(2, 10, 100);
        $rrp = $quantity * $unitaryPrice;
        $cost = $this->faker->randomFloat(2, $unitaryPrice * 0.6, $unitaryPrice * 0.9);
        $profit = $rrp - $cost;
        $margin = ($profit / $rrp) * 100;
        
        return [
            'type' => $this->faker->randomElement(['generic', 'corrective']),
            'supplier' => $this->faker->company,
            'family' => $this->faker->word,
            'quantity' => $quantity,
            'unitary_price' => $unitaryPrice,
            'RRP' => $rrp,
            'cost' => $cost,
            'margin' => $margin,
            'profit' => $profit,
            'added_at' => $this->faker->date(),
        ];
    }
}
