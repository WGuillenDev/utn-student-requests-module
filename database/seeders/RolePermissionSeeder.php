<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Role -> permissions assignment (official SQL, Section 9.4).
     *
     * Looked up by `name` instead of hardcoding numeric IDs: MySQL assigns
     * IDs based on actual insertion order, and depending on fixed numbers
     * (like the original .sql does with `(1, 1, NOW())`) is fragile if a
     * seeder ever runs in a different order.
     */
    public function run(): void
    {
        $assignments = [
            'Administrador' => [
                'atestados.gestionar', 'catalogo.gestionar', 'oferta.gestionar', 'atinencia.verificar',
                'nota_tecnica.aprobar', 'oferta.consultar', 'usuarios.gestionar', 'archivos.subir',
                'archivos.descargar', 'resoluciones.gestionar', 'reservas.gestionar', 'oferta.consolidar',
                'planes.gestionar', 'equiparaciones.gestionar', 'solicitudes.crear', 'solicitudes.revisar',
            ],
            'Coordinadora de Docencia' => [
                'atestados.gestionar', 'oferta.gestionar', 'atinencia.verificar', 'oferta.consultar',
                'archivos.subir', 'archivos.descargar', 'resoluciones.gestionar', 'reservas.gestionar',
                'oferta.consolidar', 'planes.gestionar', 'equiparaciones.gestionar', 'solicitudes.revisar',
            ],
            'Docente' => ['oferta.consultar', 'archivos.descargar'],
            'Consulta' => ['oferta.consultar'],
            'Director de Carrera' => [
                'oferta.gestionar', 'oferta.consultar', 'archivos.subir', 'archivos.descargar',
                'resoluciones.gestionar', 'planes.gestionar', 'equiparaciones.gestionar',
            ],
            'Coordinador CONTA' => ['oferta.consultar', 'archivos.descargar', 'oferta.consolidar'],
            'Recursos Humanos' => ['oferta.consultar', 'archivos.descargar'],
            'Estudiante' => ['solicitudes.crear', 'archivos.subir'],
            'Comisión Técnica' => ['solicitudes.revisar', 'archivos.descargar'],
        ];

        $roleIds = DB::table('roles')->pluck('id', 'name');
        $permissionIds = DB::table('permissions')->pluck('id', 'name');

        foreach ($assignments as $roleName => $permissionNames) {
            foreach ($permissionNames as $permissionName) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $roleIds[$roleName],
                    'permission_id' => $permissionIds[$permissionName],
                    'created_at' => now(),
                ]);
            }
        }
    }
}
