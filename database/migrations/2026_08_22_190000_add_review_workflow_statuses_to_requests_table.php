<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Docencia asked for two intermediate review steps back — 'In Review'
 * (removed on 2026-08-21 for being redundant with 'Pending Review' at
 * the time) and a new 'Verified by Registro', both sitting between
 * 'Pending Review' and the final Approved/Denied. Purely additive
 * (widening, not narrowing), so no data remap is needed — every
 * existing row's status is still a valid value in the new set.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE requests MODIFY status ENUM('Pending Review', 'In Review', 'Verified by Registro', 'Approved', 'Denied') NOT NULL DEFAULT 'Pending Review'");
        }
    }

    public function down(): void
    {
        DB::table('requests')->whereIn('status', ['In Review', 'Verified by Registro'])->update(['status' => 'Pending Review']);

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE requests MODIFY status ENUM('Pending Review', 'Approved', 'Denied') NOT NULL DEFAULT 'Pending Review'");
        }
    }
};
