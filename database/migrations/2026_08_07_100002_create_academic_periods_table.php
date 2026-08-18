<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('term')->comment('1, 2 or 3');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->unique(['year', 'term']);
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
    DB::statement('ALTER TABLE academic_periods ADD CONSTRAINT chk_academic_periods_term CHECK (term BETWEEN 1 AND 3)');
}
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_periods');
    }
};
