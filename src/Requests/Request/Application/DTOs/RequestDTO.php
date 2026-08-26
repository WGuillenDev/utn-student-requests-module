<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\DTOs;

use Src\Requests\Request\Domain\ValueObjects\RequestAttachment;

final class RequestDTO
{
    /**
     * A Validation submission becomes one DTO per course, so the two
     * fields below keep the confirmation email whole: only the first DTO
     * of a batch carries $notify, and it lists every course of the
     * submission through $batchCourseNames.
     *
     * @param  array<int, RequestAttachment>  $attachments
     * @param  ?string  $batchCourseNames  Validation only: every course name
     *   from the same submission, this one included.
     * @param  bool  $notify  Whether to send the submission email for this DTO.
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
        public readonly ?string $batchCourseNames = null,
        public readonly bool $notify = true,
    ) {}
}
