<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\LevelModel;
use App\Infrastructure\Persistence\Eloquent\Academic\Models\StudyPlanModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LevelModel>
 */
class LevelFactory extends Factory
{
    protected $model = LevelModel::class;

    public function definition(): array
    {
        return [
            'study_plan_id' => StudyPlanModel::factory(),
            'number' => fake()->numberBetween(1, 10),
        ];
    }
}
