<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CareerModel;
use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseModel>
 */
class CourseFactory extends Factory
{
    protected $model = CourseModel::class;

    public function definition(): array
    {
        return [
            'carrera_id' => CareerModel::factory(),
            'codigo' => strtoupper(fake()->unique()->bothify('???-###')),
            'nombre' => fake()->sentence(3),
            'es_servicio' => false,
            'es_cuello_botella' => false,
            'requiere_laboratorio' => false,
            'tipo_laboratorio' => null,
            'activo' => true,
        ];
    }

    /**
     * Cross-cutting service course: may have no career (chk_cursos_servicio_carrera).
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'carrera_id' => null,
            'es_servicio' => true,
        ]);
    }
}
