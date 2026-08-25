<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Requests\Models\RequestModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestModel>
 */
class RequestFactory extends Factory
{
    protected $model = RequestModel::class;

    public function definition(): array
    {
        return [
            'student_id' => StudentModel::factory(),
            'type' => 'Requirement Waiver',
            'course_id' => CourseModel::factory(),
            'required_course_id' => CourseModel::factory(),
            'origin_institution' => null,
            'external_course' => null,
            'validation_precedent_id' => null,
            'engine_result' => null,
            'violated_rule_id' => null,
            'status' => 'Pending Review',
            'estimated_resolution_date' => null,
            'reviewer_id' => null,
        ];
    }

    /** Validation request instead of a waiver request. */
    public function validation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Validation',
            'required_course_id' => null,
            'origin_institution' => fake()->company(),
            'external_course' => fake()->sentence(3),
        ]);
    }

    public function automaticallyApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'engine_result' => 'Automatically Approved',
            'status' => 'Approved by Registro',
        ]);
    }

    public function requiresManualReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'engine_result' => 'Requires Manual Review',
            'status' => 'Pending Review',
        ]);
    }
}
