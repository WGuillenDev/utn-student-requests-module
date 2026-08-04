<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_academico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained('cursos')->restrictOnDelete();
            $table->foreignId('periodo_academico_id')->nullable()->constrained('periodos_academicos')->nullOnDelete();
            $table->enum('estado', [
                'Aprobado',
                'Reprobado',
                'Acreditado por equiparación',
                'Acreditado por convalidación',
                'Requisito levantado',
            ]);
            $table->decimal('nota', 5, 2)->nullable();
            // equiparacion_id: references `equiparaciones`, a table owned by the Curricular
            // Repository module (out of scope for Solicitudes). Left without a real FK until that module exists.
            $table->unsignedBigInteger('equiparacion_id')->nullable()->comment('Resolución de referencia de la acreditación');
            $table->timestamps();

            $table->index(['estudiante_id', 'curso_id']);
            $table->index(['curso_id', 'estado']);
            $table->index('equiparacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_academico');
    }
};
