<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Base roles (official SQL, Section 9.4).
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrador', 'description' => 'Gestión total: catálogo de atinencias, usuarios y configuración'],
            ['name' => 'Coordinadora de Docencia', 'description' => 'Registra atestados, consolida y gestiona asignaciones docentes'],
            ['name' => 'Docente', 'description' => 'Consulta su perfil, atestados y asignaciones'],
            ['name' => 'Consulta', 'description' => 'Acceso de solo lectura a la oferta académica'],
            ['name' => 'Director de Carrera', 'description' => 'Registra la oferta, planes y resoluciones de su propia carrera'],
            ['name' => 'Coordinador CONTA', 'description' => 'Consolida la oferta de las carreras de su área'],
            ['name' => 'Recursos Humanos', 'description' => 'Lectura de la oferta consolidada; sin acceso a atinencias'],
            ['name' => 'Estudiante', 'description' => 'Presenta y da seguimiento a sus propias solicitudes'],
            ['name' => 'Comisión Técnica', 'description' => 'Revisa y resuelve solicitudes de convalidación'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore([
                ...$role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
