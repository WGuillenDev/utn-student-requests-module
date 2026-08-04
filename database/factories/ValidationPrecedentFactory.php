<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Requests\Models\ValidationPrecedentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ValidationPrecedentModel>
 */
class ValidationPrecedentFactory extends Factory
{
    protected $model = ValidationPrecedentModel::class;

    public function definition(): array
    {
        return [
            'institucion' => fake()->company(),
            'curso_externo' => fake()->sentence(3),
            'curso_id' => CourseModel::factory(),
            'resultado' => 'Aprobada',
            'numero_resolucion' => 'RES-'.fake()->unique()->numerify('####-##'),
        ];
    }

    public function denied(): static
    {
        return $this->state(fn (array $attributes) => [
            'resultado' => 'Denegada',
        ]);
    }
}
