<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CareerModel;
use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseModel>
 */
class CourseFactory extends Factory
{
    protected $model = CourseModel::class;

    public function definition(): array
    {
        return [
            'career_id' => CareerModel::factory(),
            'code' => strtoupper(fake()->unique()->bothify('???-###')),
            'name' => fake()->sentence(3),
            'is_service' => false,
            'is_bottleneck' => false,
            'requires_lab' => false,
            'lab_type' => null,
            'active' => true,
        ];
    }

    /**
     * Cross-cutting service course: may have no career (chk_courses_service_career).
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'career_id' => null,
            'is_service' => true,
        ]);
    }
}
