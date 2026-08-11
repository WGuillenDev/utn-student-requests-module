<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\DTOs;

final class RequestDTO
{
    public function __construct(
        public readonly int $studentId,
        public readonly string $type,
        public readonly int $courseId,
        public readonly ?int $requiredCourseId = null,
        public readonly ?string $originInstitution = null,
        public readonly ?string $externalCourse = null,
    ) {}
}
