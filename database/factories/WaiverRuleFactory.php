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
            'curso_id' => CourseModel::factory(),
            'orden' => 1,
            'tipo' => 'Siempre revisión manual',
            'curso_requisito_id' => null,
            'nota_minima' => null,
            'minimo_acumulado' => null,
            'activo' => true,
        ];
    }

    /** Type (a): required course X approved with grade >= N. */
    public function requiredCourseWithMinimumGrade(?CourseModel $requiredCourse = null, float $minimumGrade = 70): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'Requisito aprobado con nota mínima',
            'curso_requisito_id' => $requiredCourse?->id ?? CourseModel::factory(),
            'nota_minima' => $minimumGrade,
        ]);
    }

    /** Type (b): accumulated credits or courses >= K. */
    public function accumulatedCredits(int $minimum = 60): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'Créditos o cursos acumulados',
            'minimo_acumulado' => $minimum,
        ]);
    }

    /** Type (c): student belongs to a terminal plan. */
    public function terminalPlan(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'Pertenencia a plan terminal',
        ]);
    }
}
