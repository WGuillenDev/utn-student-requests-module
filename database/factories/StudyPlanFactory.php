<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CareerModel;
use App\Infrastructure\Persistence\Eloquent\Academic\Models\StudyPlanModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyPlanModel>
 */
class StudyPlanFactory extends Factory
{
    protected $model = StudyPlanModel::class;

    public function definition(): array
    {
        return [
            'career_id' => CareerModel::factory(),
            'name' => 'Plan '.fake()->year(),
            'implementation_year' => fake()->year(),
            'classification' => 'Active',
            'enrollment_closing_date' => null,
        ];
    }

    /**
     * A Terminal plan always requires an enrollment closing date (chk_study_plans_terminal_date).
     */
    public function terminal(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => 'Terminal',
            'enrollment_closing_date' => fake()->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
        ]);
    }
}
