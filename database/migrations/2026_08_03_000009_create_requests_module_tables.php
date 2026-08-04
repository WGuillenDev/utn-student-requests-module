<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas_levantamiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->unsignedTinyInteger('orden')->comment('Orden de evaluación del motor');
            $table->enum('tipo', [
                'Requisito aprobado con nota mínima',
                'Créditos o cursos acumulados',
                'Pertenencia a plan terminal',
                'Siempre revisión manual',
            ]);
            $table->foreignId('curso_requisito_id')->nullable()->constrained('cursos')->cascadeOnDelete();
            $table->decimal('nota_minima', 5, 2)->nullable()->comment('Parámetro N del tipo (a)');
            $table->unsignedSmallInteger('minimo_acumulado')->nullable()->comment('Parámetro K del tipo (b)');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['curso_id', 'orden']);
        });

        Schema::create('convalidaciones_historicas', function (Blueprint $table) {
            $table->id();
            $table->string('institucion', 150);
            $table->string('curso_externo', 150);
            $table->foreignId('curso_id')->comment('Curso interno UTN equivalente')->constrained('cursos')->restrictOnDelete();
            $table->enum('resultado', ['Aprobada', 'Denegada']);
            $table->string('numero_resolucion', 60)->comment('Resolución de referencia del precedente');
            $table->timestamps();

            $table->index(['institucion', 'curso_externo']);
        });

        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->enum('tipo', ['Levantamiento de requisito', 'Convalidación']);
            $table->foreignId('curso_id')->comment('Curso a matricular / curso interno al que aspira')->constrained('cursos')->restrictOnDelete();
            $table->foreignId('curso_requisito_id')->nullable()->comment('Requisito no cumplido')->constrained('cursos')->nullOnDelete();
            $table->string('institucion_origen', 150)->nullable()->comment('Solo convalidación');
            $table->string('curso_externo', 150)->nullable()->comment('Solo convalidación');
            $table->foreignId('convalidacion_historica_id')->nullable()->comment('Precedente encontrado')
                ->constrained('convalidaciones_historicas')->nullOnDelete();
            $table->enum('resultado_motor', ['Procede automáticamente', 'No procede', 'Requiere revisión manual'])
                ->nullable()->comment('Primer resultado concluyente del motor');
            $table->foreignId('regla_incumplida_id')->nullable()->comment('Regla que produjo No procede')
                ->constrained('reglas_levantamiento')->nullOnDelete();
            $table->enum('estado', ['Pendiente de revisión', 'En revisión', 'Aprobada', 'Denegada'])
                ->default('Pendiente de revisión');
            $table->date('fecha_estimada_resolucion')->nullable()->comment('Si no se ingresa en 24h la app asigna 5 días hábiles');
            $table->foreignId('revisor_id')->nullable()->comment('Usuario revisor (Docencia/Comisión)')
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tipo', 'estado', 'created_at']);
            $table->index(['estudiante_id', 'estado']);
        });

        Schema::create('solicitud_estados_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->cascadeOnDelete();
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30);
            $table->string('comentario', 255)->nullable()->comment('Justificación del cambio; obligatoria en denegaciones (capa app)');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('notificado_at')->nullable()->comment('Momento del correo al estudiante');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['solicitud_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_estados_historial');
        Schema::dropIfExists('solicitudes');
        Schema::dropIfExists('convalidaciones_historicas');
        Schema::dropIfExists('reglas_levantamiento');
    }
};
