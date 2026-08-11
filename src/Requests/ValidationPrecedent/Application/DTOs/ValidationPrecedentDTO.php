<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Application\DTOs;

/**
 * Immutable data boundary between Presentation and Application layers.
 * Carries primitives only — no Domain Entities and no Eloquent Models
 * leak across this line in either direction. The 5 fields of the
 * create/edit form.
 */
final class ValidationPrecedentDTO
{
    public function __construct(
        public readonly string $institution,
        public readonly string $externalCourse,
        public readonly int $courseId,
        public readonly string $result,
        public readonly string $resolutionNumber,
    ) {}
}
