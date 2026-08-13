<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = Role::query()->firstOrCreate(['name' => 'Superadmin']);
        $superadmin->permissions()->sync(Permission::query()->pluck('id'));
        Role::query()->firstOrCreate(['name' => 'Admin']);

        $studentRole = Role::query()->firstOrCreate(['name' => 'Estudiante']);
        $studentRole->permissions()->sync(
            Permission::query()->whereIn('name', [
                'requests.create',
                'requests.view',
            ])->pluck('id')
        );

        $teachingCoordinatorRole = Role::query()->firstOrCreate(['name' => 'Coordinadora de Docencia']);
        $teachingCoordinatorRole->permissions()->sync(
            Permission::query()->whereIn('name', [
                'requests.view',
                'requests.search',
                'requests.review',
                'requests.export_pdf',
                'requests.export_excel',
                'waiver_rules.create',
                'waiver_rules.view',
                'waiver_rules.edit',
                'waiver_rules.delete',
            ])->pluck('id')
        );
    }
}
