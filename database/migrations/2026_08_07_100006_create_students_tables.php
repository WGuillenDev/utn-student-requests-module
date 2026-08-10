<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('national_id', 12)->unique();
            $table->string('name', 60);
            $table->string('last_name', 60);
            $table->string('second_last_name', 60)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['last_name', 'second_last_name']);
        });

        Schema::create('student_study_plan', function (Blueprint $table) {
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('study_plan_id')->constrained('study_plans')->cascadeOnDelete();
            $table->unsignedTinyInteger('current_level')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->primary(['student_id', 'study_plan_id']);
            $table->index(['study_plan_id', 'current_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_study_plan');
        Schema::dropIfExists('students');
    }
};
