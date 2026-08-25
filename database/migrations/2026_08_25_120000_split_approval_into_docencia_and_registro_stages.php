<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the single-step 'Approved'/'Denied' decision into two stages:
 * Docencia's substantive review ('Approved by Docencia'/'Denied by
 * Docencia') and Registro's final closing step ('Approved by Registro'/
 * 'Denied by Registro') — only the Registro stage is a true final
 * status now (see Request::FINAL_STATUSES). Existing rows already at
 * 'Approved'/'Denied' are remapped to the Docencia-stage equivalent:
 * that decision already happened, but under the old single-step flow
 * Registro's closing step didn't exist yet to have been applied.
 *
 * Widened first (old values + new values coexist) so the UPDATE below
 * is valid against the enum/CHECK constraint, then narrowed once no row
 * references the old 'Approved'/'Denied' values anymore — same lesson
 * as 2026_08_23_000000_fix_status_enum_on_sqlite.php: doing the ALTER
 * and the data remap in the wrong order trips the CHECK constraint
 * (MySQL) or truncates silently. Blueprint::change() is used throughout
 * rather than raw per-driver SQL, for the same reason as that migration.
 */
return new class extends Migration
{
    private const OLD_STATUSES = ['Pending Review', 'In Review', 'Verified by Registro', 'Approved', 'Denied'];

    private const NEW_STATUSES = [
        'Pending Review', 'In Review', 'Verified by Registro',
        'Approved by Docencia', 'Denied by Docencia',
        'Approved by Registro', 'Denied by Registro',
    ];

    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('status', [...self::OLD_STATUSES, 'Approved by Docencia', 'Denied by Docencia', 'Approved by Registro', 'Denied by Registro'])
                ->default('Pending Review')
                ->change();
        });

        DB::table('requests')->where('status', 'Approved')->update(['status' => 'Approved by Docencia']);
        DB::table('requests')->where('status', 'Denied')->update(['status' => 'Denied by Docencia']);

        Schema::table('requests', function (Blueprint $table) {
            $table->enum('status', self::NEW_STATUSES)
                ->default('Pending Review')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('status', [...self::NEW_STATUSES, 'Approved', 'Denied'])
                ->default('Pending Review')
                ->change();
        });

        DB::table('requests')->whereIn('status', ['Approved by Docencia', 'Approved by Registro'])->update(['status' => 'Approved']);
        DB::table('requests')->whereIn('status', ['Denied by Docencia', 'Denied by Registro'])->update(['status' => 'Denied']);

        Schema::table('requests', function (Blueprint $table) {
            $table->enum('status', self::OLD_STATUSES)
                ->default('Pending Review')
                ->change();
        });
    }
};
