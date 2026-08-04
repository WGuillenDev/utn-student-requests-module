<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicPeriodSeeder extends Seeder
{
    /**
     * The academic period covered by the sample offering (official SQL, Section 9.3).
     */
    public function run(): void
    {
        DB::table('periodos_academicos')->insertOrIgnore([
            'anio' => 2025,
            'cuatrimestre' => 3,
            'fecha_inicio' => '2025-09-01',
            'fecha_fin' => '2025-12-19',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
