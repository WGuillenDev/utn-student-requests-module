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
            'national_id' => fake()->unique()->numerify('#-####-####'),
            'name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'second_last_name' => fake()->lastName(),
            'active' => true,
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
