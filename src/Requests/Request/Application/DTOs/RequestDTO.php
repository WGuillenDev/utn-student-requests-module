<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\DTOs;

use Src\Requests\Request\Domain\ValueObjects\RequestAttachment;

final class RequestDTO
{
    /**
     * @param  array<int, RequestAttachment>  $attachments
     */
    public function __construct(
        public readonly int $studentId,
        public readonly string $type,
        public readonly int $courseId,
        public readonly ?int $requiredCourseId = null,
        public readonly ?string $waiverJustification = null,
        public readonly ?string $originInstitution = null,
        public readonly ?string $externalCourse = null,
        public readonly array $attachments = [],
    ) {}
}
