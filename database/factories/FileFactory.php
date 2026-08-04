<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Documents\Models\FileModel;
use App\Infrastructure\Persistence\Eloquent\Requests\Models\RequestModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FileModel>
 */
class FileFactory extends Factory
{
    protected $model = FileModel::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'user_id' => null,
            'archivable_type' => RequestModel::class,
            'archivable_id' => RequestModel::factory(),
            'tipo_documento' => fake()->randomElement(['Certificación', 'Constancia', 'Programa de curso']),
            'nombre_original' => fake()->word().'.pdf',
            'disco' => 'local',
            'ruta' => 'solicitudes/'.fake()->unique()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'tamano_bytes' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'hash_sha256' => hash('sha256', fake()->uuid()),
        ];
    }
}
