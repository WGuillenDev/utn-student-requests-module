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
            'carrera_id' => CareerModel::factory(),
            'nombre' => 'Plan '.fake()->year(),
            'anio_implementacion' => fake()->year(),
            'clasificacion' => 'Vigente',
            'fecha_cierre_matricula' => null,
        ];
    }

    /**
     * A Terminal plan always requires an enrollment closing date (chk_planes_terminal_fecha).
     */
    public function terminal(): static
    {
        return $this->state(fn (array $attributes) => [
            'clasificacion' => 'Terminal',
            'fecha_cierre_matricula' => fake()->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
        ]);
    }
}
