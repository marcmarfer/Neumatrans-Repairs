<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\VehicleModel;
use App\Models\Brand;

class VehicleModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VehicleModel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::inRandomOrder()->value('id') ?? Brand::factory(),
            'name' => $this->faker->unique()->word(),
        ];
    }
} 