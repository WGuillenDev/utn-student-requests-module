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
            'estudiante_id' => StudentModel::factory(),
            'tipo' => 'Levantamiento de requisito',
            'curso_id' => CourseModel::factory(),
            'curso_requisito_id' => CourseModel::factory(),
            'institucion_origen' => null,
            'curso_externo' => null,
            'convalidacion_historica_id' => null,
            'resultado_motor' => null,
            'regla_incumplida_id' => null,
            'estado' => 'Pendiente de revisión',
            'fecha_estimada_resolucion' => null,
            'revisor_id' => null,
        ];
    }

    /** Validation request instead of a waiver request. */
    public function validation(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'Convalidación',
            'curso_requisito_id' => null,
            'institucion_origen' => fake()->company(),
            'curso_externo' => fake()->sentence(3),
        ]);
    }

    public function automaticallyApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resultado_motor' => 'Procede automáticamente',
            'estado' => 'Aprobada',
        ]);
    }

    public function requiresManualReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'resultado_motor' => 'Requiere revisión manual',
            'estado' => 'Pendiente de revisión',
        ]);
    }
}
