<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CareerModel;
use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Academic\Models\StudyPlanModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\AcademicRecordModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        ))->keyBy('code');

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

        $this->seedWaiverEngineFixtures($career, $courses);
    }

    /**
     * Fixtures wired specifically to the `estudiante@gmail.com` demo user
     * (see RoleSeeder/DatabaseSeeder) and the 3 rule-engine outcomes
     * (ES-01): ISW-521 is configured to auto-Approve, ISW-401 to
     * auto-Deny, and ISW-315 is left with no rules at all so it falls
     * through to manual review — exercising all three in the UI without
     * the reviewer having to fabricate data by hand before the demo.
     *
     * @param \Illuminate\Support\Collection<string, CourseModel> $courses
     */
    private function seedWaiverEngineFixtures(CareerModel $career, \Illuminate\Support\Collection $courses): void
    {
        $demoUser = User::query()->where('email', 'estudiante@gmail.com')->first();

        if ($demoUser === null) {
            return;
        }

        $demoStudent = StudentModel::query()->firstOrCreate(
            ['user_id' => $demoUser->id],
            [
                'national_id' => '0-0000-0000',
                'name' => 'Estudiante',
                'last_name' => 'Demo',
                'second_last_name' => 'UTN',
                'active' => true,
            ],
        );

        // ISW-102 approved with a high grade — satisfies the ISW-521
        // waiver rule below (min grade 70).
        AcademicRecordModel::query()->firstOrCreate(
            ['student_id' => $demoStudent->id, 'course_id' => $courses['ISW-102']->id],
            ['status' => 'Approved', 'grade' => 85],
        );

        // ISW-210 approved but with a grade below the ISW-401 waiver
        // rule's threshold (min grade 90) — deliberately fails it.
        AcademicRecordModel::query()->firstOrCreate(
            ['student_id' => $demoStudent->id, 'course_id' => $courses['ISW-210']->id],
            ['status' => 'Approved', 'grade' => 60],
        );

        DB::table('waiver_rules')->updateOrInsert(
            ['course_id' => $courses['ISW-521']->id, 'order' => 1],
            [
                'type' => 'Approved requirement with minimum grade',
                'required_course_id' => $courses['ISW-102']->id,
                'minimum_grade' => 70,
                'minimum_accumulated' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('waiver_rules')->updateOrInsert(
            ['course_id' => $courses['ISW-401']->id, 'order' => 1],
            [
                'type' => 'Approved requirement with minimum grade',
                'required_course_id' => $courses['ISW-210']->id,
                'minimum_grade' => 90,
                'minimum_accumulated' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        // ISW-315 intentionally left without any waiver_rules row —
        // exercises the "curso sin criterios configurados" acceptance
        // criterion (falls through to manual review).
    }
}
