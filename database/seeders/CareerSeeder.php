<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CareerSeeder extends Seeder
{
    /**
     * Careers in scope (official SQL, Section 9.1).
     */
    public function run(): void
    {
        $careers = [
            'Administración y Gestión de Recursos Humanos',
            'Administración Aduanera',
            'Ingeniería en Tecnologías de Información - Tecnologías de Información',
            'Ingeniería del Software - Tecnologías Informáticas',
            'Contabilidad y Finanzas - Contaduría Pública',
            'Asistencia Administrativa',
            'Inglés como Lengua Extranjera',
            'Administración Agroindustrial',
            'Gestión de Centros de Servicios Compartidos',
            'Ingeniería en Mantenimiento Agroindustrial Sostenible - Mantenimiento Agroindustrial Sostenible',
            'Ingeniería en Gestión Ambiental',
            'Ingeniería en Salud Ocupacional y Ambiente - Salud Ocupacional',
            'Ingeniería en Tecnología de Alimentos - Tecnología de Alimentos',
            'Administración del Comercio Exterior',
        ];

        foreach ($careers as $name) {
            DB::table('carreras')->insertOrIgnore([
                'nombre' => $name,
                'activa' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
