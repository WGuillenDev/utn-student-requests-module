<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CareerModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerModel>
 */
class CareerFactory extends Factory
{
    protected $model = CareerModel::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(4, true),
            'activa' => true,
        ];
    }
}
