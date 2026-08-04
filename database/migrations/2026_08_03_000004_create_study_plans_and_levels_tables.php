<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_estudio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carreras')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('nombre', 120)->comment('Ej.: Plan 2023, Plan 2025');
            $table->year('anio_implementacion');
            $table->enum('clasificacion', ['Vigente', 'Terminal'])->default('Vigente');
            $table->date('fecha_cierre_matricula')->nullable()->comment('Obligatoria solo para planes Terminal');
            $table->timestamps();

            $table->unique(['carrera_id', 'nombre']);
            $table->index('clasificacion');
        });

        DB::statement("ALTER TABLE planes_estudio ADD CONSTRAINT chk_planes_terminal_fecha CHECK (clasificacion = 'Vigente' OR fecha_cierre_matricula IS NOT NULL)");

        Schema::create('niveles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_estudio_id')->constrained('planes_estudio')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->timestamps();

            $table->unique(['plan_estudio_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('niveles');
        Schema::dropIfExists('planes_estudio');
    }
};
