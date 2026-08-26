<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Validation-only, alongside external_course_code/external_course_credits
 * (see 2026_08_22_180000): the grade Docencia reads off the external
 * institution's transcript while reviewing, manually entered — same
 * decimal(5,2) scale as academic_records.grade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->decimal('external_course_grade', 5, 2)->nullable()->after('external_course_credits');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('external_course_grade');
        });
    }
};
