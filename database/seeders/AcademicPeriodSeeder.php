<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicPeriodSeeder extends Seeder
{
    /**
     * The academic period covered by the sample offering (SRS v1.2, Section 9.3).
     */
    public function run(): void
    {
        DB::table('academic_periods')->insertOrIgnore([
            'year' => 2025,
            'term' => 3,
            'start_date' => '2025-09-01',
            'end_date' => '2025-12-19',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
