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
            'anio' => $year,
            'cuatrimestre' => $term,
            'fecha_inicio' => "{$year}-01-01",
            'fecha_fin' => "{$year}-04-30",
        ];
    }
}
