<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\AcademicRecordModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicRecordModel>
 */
class AcademicRecordFactory extends Factory
{
    protected $model = AcademicRecordModel::class;

    public function definition(): array
    {
        return [
            'student_id' => StudentModel::factory(),
            'course_id' => CourseModel::factory(),
            'academic_period_id' => null,
            'status' => 'Approved',
            'grade' => fake()->randomFloat(2, 70, 100),
            'equivalence_id' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Failed',
            'grade' => fake()->randomFloat(2, 0, 69),
        ]);
    }
}
