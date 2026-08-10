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
            'institution' => fake()->company(),
            'external_course' => fake()->sentence(3),
            'course_id' => CourseModel::factory(),
            'result' => 'Approved',
            'resolution_number' => 'RES-'.fake()->unique()->numerify('####-##'),
        ];
    }

    public function denied(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => 'Denied',
        ]);
    }
}
