<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_id')->constrained('careers')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('name', 120)->comment('E.g.: Plan 2023, Plan 2025');
            $table->year('implementation_year');
            $table->enum('classification', ['Active', 'Terminal'])->default('Active');
            $table->date('enrollment_closing_date')->nullable()->comment('Required only for Terminal plans');
            $table->timestamps();

            $table->unique(['career_id', 'name']);
            $table->index('classification');
        });

        DB::statement("ALTER TABLE study_plans ADD CONSTRAINT chk_study_plans_terminal_date CHECK (classification = 'Active' OR enrollment_closing_date IS NOT NULL)");

        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained('study_plans')->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->timestamps();

            $table->unique(['study_plan_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('levels');
        Schema::dropIfExists('study_plans');
    }
};
