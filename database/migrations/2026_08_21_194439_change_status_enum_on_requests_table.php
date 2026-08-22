<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registro decided 'In Review' was redundant with 'Pending Review' — the
 * two never had any behavioral difference (no gated permissions, no
 * distinct transition rule), so this simply drops it from the 4-status
 * set down to 3. Any pre-existing 'In Review' row is remapped first so
 * the narrower enum never rejects existing data.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('requests')->where('status', 'In Review')->update(['status' => 'Pending Review']);

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE requests MODIFY status ENUM('Pending Review', 'Approved', 'Denied') NOT NULL DEFAULT 'Pending Review'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE requests MODIFY status ENUM('Pending Review', 'In Review', 'Approved', 'Denied') NOT NULL DEFAULT 'Pending Review'");
        }
    }
};
