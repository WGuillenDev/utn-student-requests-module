<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
                Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_id')->nullable()->constrained('careers')->restrictOnDelete();
            $table->string('code', 30)->unique()->comment('E.g.: ITI-224, ITIEL-13');
            $table->string('name', 150);
            $table->boolean('is_service')->default(false)->comment('1 = cross-cutting course administered by Docencia');
            $table->boolean('is_bottleneck')->default(false)->comment('1 = pinned course: scheduling and classroom priority');
            $table->boolean('requires_lab')->default(false);
            $table->enum('lab_type', [
                'Computer Lab',
                'Science Lab',
                'Language Lab',
            ])->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE courses ADD CONSTRAINT chk_courses_service_career CHECK (is_service = 1 OR career_id IS NOT NULL)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};

