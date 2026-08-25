<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            CareerSeeder::class,
            AcademicPeriodSeeder::class,
        ]);

        $superadminUser = User::factory()->create([
            'name' => 'prueba ISW-521',
            'email' => 'prueba@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $superadminUser->roles()->sync(
            Role::query()->where('name', 'Superadmin')->pluck('id')
        );

        $adminRole = Role::query()->where('name', 'Admin')->firstOrFail();
        $adminRole->permissions()->sync(Permission::query()->pluck('id'));

        $adminUser = User::factory()->create([
            'name' => 'admin prueba ISW-521',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $adminUser->roles()->sync([$adminRole->id]);

        $studentRole = Role::query()->where('name', 'Estudiante')->firstOrFail();

        $studentUser = User::factory()->create([
            'name' => 'estudiante prueba ISW-521',
            'email' => 'estudiante@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $studentUser->roles()->sync([$studentRole->id]);

        StudentModel::query()->create([
            'user_id' => $studentUser->id,
            'national_id' => '0-0000-0000',
            'name' => 'estudiante',
            'last_name' => 'prueba',
            'second_last_name' => 'ISW-521',
            'active' => true,
        ]);

        $teachingCoordinatorRole = Role::query()->where('name', 'Coordinadora de Docencia')->firstOrFail();

        $teachingCoordinatorUser = User::factory()->create([
            'name' => 'docencia prueba ISW-521',
            'email' => 'docencia@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $teachingCoordinatorUser->roles()->sync([$teachingCoordinatorRole->id]);

        $registrarRole = Role::query()->where('name', 'Registro')->firstOrFail();

        $registrarUser = User::factory()->create([
            'name' => 'registro prueba ISW-521',
            'email' => 'registro@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $registrarUser->roles()->sync([$registrarRole->id]);

        // Runs last, not in the $this->call() block above: its waiver-
        // engine fixtures are keyed to the estudiante@gmail.com user
        // created just above, and would silently no-op if seeded first.
        $this->call(TestDataSeeder::class);
    }
}
