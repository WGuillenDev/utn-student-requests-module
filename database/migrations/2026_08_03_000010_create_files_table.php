<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('Identificador público para URL de descarga firmada');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('archivable');
            $table->string('tipo_documento', 60)->comment('Criterio Técnico, Resolución, Certificación, Reporte, ...');
            $table->string('nombre_original');
            $table->string('disco', 30)->default('local');
            $table->string('ruta');
            $table->string('mime_type', 100);
            $table->unsignedInteger('tamano_bytes');
            $table->char('hash_sha256', 64)->comment('Integridad y detección de duplicados');
            $table->timestamps();

            $table->unique(['disco', 'ruta']);
            $table->index('tipo_documento');
        });

        DB::statement('ALTER TABLE archivos ADD CONSTRAINT chk_archivos_tamano CHECK (tamano_bytes > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};
