<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Actions available for every manageable module.
     *
     * @var array<int, string>
     */
    private const ACTIONS = [
        'create',
        'view',
        'edit',
        'delete',
        'search',
        'export_pdf',
        'export_excel',
    ];

    /**
     * Modules that currently expose the actions above.
     * Extend this list as new manageable modules are added.
     *
     * @var array<int, string>
     */
    private const MODULES = ['roles', 'permissions', 'requests', 'waiver_rules', 'validation_precedents'];

    public function run(): void
    {
        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                Permission::query()->firstOrCreate(
                    ['name' => "{$module}.{$action}"],
                    ['module' => $module, 'action' => $action],
                );
            }
        }

        // Custom action outside the standard 7 — reviewing (changing the
        // status of) a request. See Src\Requests\Request\Presentation\
        // Policies\RequestPolicy::review().
        Permission::query()->firstOrCreate(
            ['name' => 'requests.review'],
            ['module' => 'requests', 'action' => 'review'],
        );

        // Custom action, separate from 'requests.review': closing a
        // request for good ('Approved by Registro'/'Denied by Registro').
        // See RequestPolicy::finalize() — only the Registro role holds
        // this one, unlike 'requests.review' which Docencia also has.
        Permission::query()->firstOrCreate(
            ['name' => 'requests.finalize'],
            ['module' => 'requests', 'action' => 'finalize'],
        );
    }
}
