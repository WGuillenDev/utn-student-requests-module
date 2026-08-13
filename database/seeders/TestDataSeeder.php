<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CareerModel;
use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;

/**
 * Manual QA fixtures for the Requests module: enough students and courses
 * to exercise the "New request" dropdowns end to end. Not part of the
 * official SRS seed data (Section 9) — local/demo only.
 */
class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $career = CareerModel::query()
            ->where('name', 'Ingeniería del Software - Tecnologías Informáticas')
            ->first() ?? CareerModel::query()->firstOrFail();

        $courses = collect([
            ['code' => 'ISW-521', 'name' => 'Programación en Ambiente Web I'],
            ['code' => 'ISW-401', 'name' => 'Estructuras de Datos'],
            ['code' => 'ISW-315', 'name' => 'Bases de Datos'],
            ['code' => 'ISW-210', 'name' => 'Programación Orientada a Objetos'],
            ['code' => 'ISW-102', 'name' => 'Introducción a la Programación'],
        ])->map(fn (array $data) => CourseModel::query()->firstOrCreate(
            ['code' => $data['code']],
            [
                'career_id' => $career->id,
                'name' => $data['name'],
                'is_service' => false,
                'is_bottleneck' => false,
                'requires_lab' => false,
                'lab_type' => null,
                'active' => true,
            ],
        ));

        // Spanish locale for test names/national IDs: these are seed
        // *data* (like the career names above), not code — the project's
        // English-only convention applies to identifiers, not fixtures.
        $faker = FakerFactory::create('es_ES');

        for ($i = 0; $i < 10; $i++) {
            StudentModel::query()->create([
                'national_id' => $faker->unique()->numerify('#-####-####'),
                'name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'second_last_name' => $faker->lastName(),
                'active' => true,
            ]);
        }
    }
}
