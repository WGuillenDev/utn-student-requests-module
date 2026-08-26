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

        // Admin never got its own scoped-down permission list anywhere in
        // this codebase (see Role::PROTECTED_NAMES — both are treated as
        // the same tier of structural role) — it's meant to have every
        // business permission Superadmin has, just without Superadmin's
        // Gate::before bypass (DomainServiceProvider::registerSuperAdminBypass()).
        // Synced the same way, for the same reason: so a permission added
        // later (like 'requests.finalize', added after Admin's original
        // one-time setup) doesn't silently leave Admin unable to act.
        $admin = Role::query()->firstOrCreate(['name' => 'Admin']);
        $admin->permissions()->sync(Permission::query()->pluck('id'));

        $studentRole = Role::query()->firstOrCreate(['name' => 'Estudiante']);
        $studentRole->permissions()->sync(
            Permission::query()->whereIn('name', [
                'requests.create',
                'requests.view',
            ])->pluck('id')
        );

        $teachingCoordinatorPermissions = [
            'requests.view',
            'requests.search',
            'requests.review',
            'requests.export_pdf',
            'requests.export_excel',
            'waiver_rules.create',
            'waiver_rules.view',
            'waiver_rules.edit',
            'waiver_rules.delete',
            'validation_precedents.create',
            'validation_precedents.view',
            'validation_precedents.edit',
            'validation_precedents.delete',
        ];

        $teachingCoordinatorRole = Role::query()->firstOrCreate(['name' => 'Coordinadora de Docencia']);
        $teachingCoordinatorRole->permissions()->sync(
            Permission::query()->whereIn('name', $teachingCoordinatorPermissions)->pluck('id')
        );

        // Same permission set as Docencia today — kept as its own role
        // (not a duplicate user under the Docencia role) so it lines up
        // with the 'Verified by Registro' status already in the
        // requests pipeline, and so it can be scoped down later without
        // touching Docencia's own permissions. Plus 'requests.finalize',
        // which Docencia deliberately does NOT get: only Registro can
        // apply the final 'Approved by Registro'/'Denied by Registro'
        // status (see RequestPolicy::finalize()).
        $registrarRole = Role::query()->firstOrCreate(['name' => 'Registro']);
        $registrarRole->permissions()->sync(
            Permission::query()->whereIn('name', [...$teachingCoordinatorPermissions, 'requests.finalize'])->pluck('id')
        );
    }
}
