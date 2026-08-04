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
            'plan_estudio_id' => StudyPlanModel::factory(),
            'numero' => fake()->numberBetween(1, 10),
        ];
    }
}
