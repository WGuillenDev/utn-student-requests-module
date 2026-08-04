<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Base permissions (official SQL, Section 9.4).
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'atestados.gestionar', 'description' => 'Crear y editar atestados de docentes'],
            ['name' => 'catalogo.gestionar', 'description' => 'Crear versiones del catálogo de atinencias'],
            ['name' => 'oferta.gestionar', 'description' => 'Crear grupos, horarios y asignaciones'],
            ['name' => 'atinencia.verificar', 'description' => 'Ejecutar verificaciones de atinencia'],
            ['name' => 'nota_tecnica.aprobar', 'description' => 'Aprobar la vía excepcional de Nota Técnica'],
            ['name' => 'oferta.consultar', 'description' => 'Consultar la oferta académica'],
            ['name' => 'usuarios.gestionar', 'description' => 'Administrar usuarios, roles y permisos'],
            ['name' => 'archivos.subir', 'description' => 'Adjuntar documentos a los módulos'],
            ['name' => 'archivos.descargar', 'description' => 'Descargar documentos adjuntos y reportes'],
            ['name' => 'resoluciones.gestionar', 'description' => 'Registrar resoluciones de modalidad por curso'],
            ['name' => 'reservas.gestionar', 'description' => 'Registrar y aprobar préstamos de aulas'],
            ['name' => 'oferta.consolidar', 'description' => 'Consolidar la oferta y mover grupos de estado'],
            ['name' => 'planes.gestionar', 'description' => 'Administrar planes de estudio, niveles y requisitos'],
            ['name' => 'equiparaciones.gestionar', 'description' => 'Registrar equiparaciones entre planes'],
            ['name' => 'solicitudes.crear', 'description' => 'Presentar solicitudes estudiantiles'],
            ['name' => 'solicitudes.revisar', 'description' => 'Revisar y resolver solicitudes estudiantiles'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                ...$permission,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
