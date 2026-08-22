<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->enum('waiver_justification', [
                'Only Pending Requirement',
                'Final Level Parallel Enrollment',
                'Delayed Course Offering',
                'Minimum Academic Load',
                'Prior Knowledge Sufficiency',
            ])->nullable()->after('required_course_id')
                ->comment('Requirement Waiver only — one of 5 fixed institutional categories, Directriz Administrativa DA-VDOC-01-2020');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('waiver_justification');
        });
    }
};
