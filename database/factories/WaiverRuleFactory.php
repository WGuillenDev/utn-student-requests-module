<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Requests\Models\WaiverRuleModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaiverRuleModel>
 */
class WaiverRuleFactory extends Factory
{
    protected $model = WaiverRuleModel::class;

    public function definition(): array
    {
        return [
            'course_id' => CourseModel::factory(),
            'order' => 1,
            'type' => 'Always manual review',
            'required_course_id' => null,
            'minimum_grade' => null,
            'minimum_accumulated' => null,
            'active' => true,
        ];
    }

    /** Type (a): required course X approved with grade >= N. */
    public function requiredCourseWithMinimumGrade(?CourseModel $requiredCourse = null, float $minimumGrade = 70): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Approved requirement with minimum grade',
            'required_course_id' => $requiredCourse?->id ?? CourseModel::factory(),
            'minimum_grade' => $minimumGrade,
        ]);
    }

    /** Type (b): accumulated credits or courses >= K. */
    public function accumulatedCredits(int $minimum = 60): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Accumulated credits or courses',
            'minimum_accumulated' => $minimum,
        ]);
    }

    /** Type (c): student belongs to a terminal plan. */
    public function terminalPlan(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Terminal plan membership',
        ]);
    }
}
