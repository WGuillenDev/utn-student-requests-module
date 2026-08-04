<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('cedula', 12)->unique();
            $table->string('nombre', 60);
            $table->string('primer_apellido', 60);
            $table->string('segundo_apellido', 60)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['primer_apellido', 'segundo_apellido']);
        });

        Schema::create('estudiante_plan', function (Blueprint $table) {
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('plan_estudio_id')->constrained('planes_estudio')->cascadeOnDelete();
            $table->unsignedTinyInteger('nivel_actual')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->primary(['estudiante_id', 'plan_estudio_id']);
            $table->index(['plan_estudio_id', 'nivel_actual']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_plan');
        Schema::dropIfExists('estudiantes');
    }
};
