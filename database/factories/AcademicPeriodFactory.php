<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\AcademicPeriodModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicPeriodModel>
 */
class AcademicPeriodFactory extends Factory
{
    protected $model = AcademicPeriodModel::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2020, 2026);
        $term = fake()->numberBetween(1, 3);

        return [
            'year' => $year,
            'term' => $term,
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-04-30",
        ];
    }
}
