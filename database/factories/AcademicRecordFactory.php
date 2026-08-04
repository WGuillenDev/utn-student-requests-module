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
            'estudiante_id' => StudentModel::factory(),
            'curso_id' => CourseModel::factory(),
            'periodo_academico_id' => null,
            'estado' => 'Aprobado',
            'nota' => fake()->randomFloat(2, 70, 100),
            'equiparacion_id' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'Reprobado',
            'nota' => fake()->randomFloat(2, 0, 69),
        ]);
    }
}
