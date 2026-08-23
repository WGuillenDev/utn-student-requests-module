<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Validation-only fields Docencia fills in while reviewing (not
 * captured from the student at submission): the external course's own
 * code and credit count, used to compare against the equivalent UTN
 * course before deciding Reconocer/No reconocer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('external_course_code')->nullable()->after('external_course');
            $table->unsignedTinyInteger('external_course_credits')->nullable()->after('external_course_code');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['external_course_code', 'external_course_credits']);
        });
    }
};
