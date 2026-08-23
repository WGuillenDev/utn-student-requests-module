<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Both prior status-enum migrations (2026_08_21_194439 and
 * 2026_08_22_190000) used raw `ALTER TABLE ... MODIFY ... ENUM(...)`
 * — MySQL-only syntax with no SQLite equivalent — guarded behind
 * `if (driver !== 'sqlite')`. That guard only avoided a syntax error;
 * it never actually updated the CHECK constraint SQLite uses to
 * emulate `enum()`. On SQLite, `status` was still stuck on the
 * original 2026_08_07_100008 enum ('Pending Review', 'In Review',
 * 'Approved', 'Denied'), which never included 'Verified by Registro'
 * — writing that status failed the CHECK constraint.
 *
 * Fixed here with Blueprint::change(), which Laravel translates
 * correctly per driver (MySQL ALTER MODIFY, and an actual table
 * rebuild on SQLite) instead of raw SQL — one migration that's
 * correct everywhere, rather than another driver-specific branch.
 * Re-running the equivalent change on MySQL (already correct there)
 * is harmless.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('status', ['Pending Review', 'In Review', 'Verified by Registro', 'Approved', 'Denied'])
                ->default('Pending Review')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('requests')->whereIn('status', ['In Review', 'Verified by Registro'])->update(['status' => 'Pending Review']);

        Schema::table('requests', function (Blueprint $table) {
            $table->enum('status', ['Pending Review', 'Approved', 'Denied'])
                ->default('Pending Review')
                ->change();
        });
    }
};
