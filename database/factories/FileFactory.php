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
            'fileable_type' => RequestModel::class,
            'fileable_id' => RequestModel::factory(),
            'document_type' => fake()->randomElement(['Certification', 'Proof of Enrollment', 'Course Syllabus']),
            'original_name' => fake()->word().'.pdf',
            'disk' => 'local',
            'path' => 'requests/'.fake()->unique()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'hash_sha256' => hash('sha256', fake()->uuid()),
        ];
    }
}
