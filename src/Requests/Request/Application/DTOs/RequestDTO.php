<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\DTOs;

use Src\Requests\Request\Domain\ValueObjects\RequestAttachment;

final class RequestDTO
{
    /**
     * @param  array<int, RequestAttachment>  $attachments
     * @param  ?string  $batchCourseNames  Validation only: every UTN
     *   course name from the same submission (this one included) —
     *   ValidationRequestForm::toDtos() turns one submission into
     *   several independent Request rows, but the confirmation email
     *   should still tell the student everything they just submitted,
     *   not just the one course this particular Request is for.
     * @param  bool  $notify  Whether CreateRequestUseCase should send the
     *   "request submitted" email for this DTO. Validation submits
     *   several DTOs (one Request per course) from a single click, but
     *   the student should get exactly one confirmation email for that
     *   whole submission — not one per course — so
     *   ValidationRequestForm::toDtos() sets this true on only the first
     *   of the batch; that single email's body still lists every course
     *   via batchCourseNames.
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
