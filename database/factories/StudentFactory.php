<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentModel>
 */
class StudentFactory extends Factory
{
    protected $model = StudentModel::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'cedula' => fake()->unique()->numerify('#-####-####'),
            'nombre' => fake()->firstName(),
            'primer_apellido' => fake()->lastName(),
            'segundo_apellido' => fake()->lastName(),
            'activo' => true,
        ];
    }

    /**
     * Student with a portal login account (User::factory()).
     */
    public function withAccount(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
        ]);
    }
}
