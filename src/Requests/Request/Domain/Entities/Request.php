<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Entities;

use Src\Requests\Request\Domain\Exceptions\InvalidStatusTransitionException;

/**
 * Aggregate Root of the Requests bounded context. Pure PHP, no framework
 * coupling.
 *
 * Covers both types stored in the same table: "Requirement Waiver" and
 * "Validation". Each type's distinguishing fields are nullable here —
 * which ones are mandatory per type is an Application concern, since the
 * use case knows which flow created the request.
 */
final class Request
{
    /**
     * Closed statuses: a request here cannot move again, and reopening is
     * not supported — a new request must be filed.
     *
     * Docencia's own decisions are deliberately not final. They are its
     * substantive verdict, but the request only closes once Registro
     * applies the matching final status.
     */
    private const FINAL_STATUSES = ['Approved by Registro', 'Denied by Registro'];

    /**
     * files.document_type for the optional file Registro attaches when
     * issuing a resolution. A fixed marker rather than free text, so both
     * the staff and student components can find that file reliably.
     * Distinct from 'resolution', the generated PDF's own type.
     */
    public const REGISTRO_ATTACHMENT_DOCUMENT_TYPE = 'registro_attachment';

    private function __construct(
        private readonly ?int $id,
        private readonly int $studentId,
        private readonly string $type,
        private readonly int $courseId,
        private ?int $requiredCourseId,
        private readonly ?string $waiverJustification,
        private ?string $originInstitution,
        private ?string $externalCourse,
        private ?string $externalCourseCode,
        private ?int $externalCourseCredits,
        private ?float $externalCourseGrade,
        private ?int $validationPrecedentId,
        private ?string $engineResult,
        private ?int $violatedRuleId,
        private string $status,
        private ?string $estimatedResolutionDate,
        private ?int $reviewerId,
        private readonly ?string $createdAt,
    ) {}

    /**
     * The WaiverEngine may set engineResult/violatedRuleId here (ES-01's
     * immediate feedback), but status always starts 'Pending Review':
     * every request still needs a human reviewer to close it, so it stays
     * in the inbox and keeps a full audit trail (ES-03/ES-04).
     */
    public static function create(
        int $studentId,
        string $type,
        int $courseId,
        ?int $requiredCourseId = null,
        ?string $waiverJustification = null,
        ?string $originInstitution = null,
        ?string $externalCourse = null,
        ?int $validationPrecedentId = null,
        ?string $engineResult = null,
        ?int $violatedRuleId = null,
    ): self {
        return new self(
            id: null,
            studentId: $studentId,
            type: $type,
            courseId: $courseId,
            requiredCourseId: $requiredCourseId,
            waiverJustification: $waiverJustification,
            originInstitution: $originInstitution,
            externalCourse: $externalCourse,
            externalCourseCode: null,
            externalCourseCredits: null,
            externalCourseGrade: null,
            validationPrecedentId: $validationPrecedentId,
            engineResult: $engineResult,
            violatedRuleId: $violatedRuleId,
            status: 'Pending Review',
            estimatedResolutionDate: null,
            reviewerId: null,
            createdAt: null,
        );
    }

    public static function reconstitute(
        int $id,
        int $studentId,
        string $type,
        int $courseId,
        ?int $requiredCourseId,
        ?string $waiverJustification,
        ?string $originInstitution,
        ?string $externalCourse,
        ?int $validationPrecedentId,
        ?string $engineResult,
        ?int $violatedRuleId,
        string $status,
        ?string $estimatedResolutionDate,
        ?int $reviewerId,
        ?string $createdAt = null,
        ?string $externalCourseCode = null,
        ?int $externalCourseCredits = null,
        ?float $externalCourseGrade = null,
    ): self {
        return new self(
            id: $id,
            studentId: $studentId,
            type: $type,
            courseId: $courseId,
            requiredCourseId: $requiredCourseId,
            waiverJustification: $waiverJustification,
            originInstitution: $originInstitution,
            externalCourse: $externalCourse,
            externalCourseCode: $externalCourseCode,
            externalCourseCredits: $externalCourseCredits,
            externalCourseGrade: $externalCourseGrade,
            validationPrecedentId: $validationPrecedentId,
            engineResult: $engineResult,
            violatedRuleId: $violatedRuleId,
            status: $status,
            estimatedResolutionDate: $estimatedResolutionDate,
            reviewerId: $reviewerId,
            createdAt: $createdAt,
        );
    }

    /**
     * Central invariant: a request already in a final status can never
     * transition again. The "denial requires a comment" rule is enforced
     * one layer up (ChangeRequestStatusUseCase), because the comment is
     * persisted in RequestStatusHistory, not on the Request itself.
     */
    public function changeStatus(string $newStatus, ?int $reviewerId = null): void
    {
        if (in_array($this->status, self::FINAL_STATUSES, true)) {
            throw InvalidStatusTransitionException::fromFinalStatus($this->status, $newStatus);
        }

        $this->status = $newStatus;

        if ($reviewerId !== null) {
            $this->reviewerId = $reviewerId;
        }
    }

    public function isFinal(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true);
    }

    /**
     * Validation only: the external course's code, credits and grade,
     * captured by Docencia during review to compare against the UTN
     * equivalent. Not gated by isFinal(), since a reviewer may correct
     * them while still deciding.
     */
    public function setExternalCourseData(?string $code, ?int $credits, ?float $grade): void
    {
        $this->externalCourseCode = $code;
        $this->externalCourseCredits = $credits;
        $this->externalCourseGrade = $grade;
    }

    /**
     * Manual entry: "la fecha estimada de resolución la ingresa el
     * revisor al abrir la solicitud" (ES-03). Deliberately not gated by
     * `isFinal()` — the reviewer sets this while still deciding, before
     * or independently of a status change.
     */
    public function assignEstimatedResolutionDate(string $date): void
    {
        $this->estimatedResolutionDate = $date;
    }

    /**
     * ES-03's automatic fallback: true once 24h have passed since
     * receipt with no date entered by a reviewer, for a request still
     * open (a closed request no longer needs an estimate).
     */
    public function needsAutoEstimatedDate(\DateTimeImmutable $now): bool
    {
        if ($this->estimatedResolutionDate !== null || $this->createdAt === null || $this->isFinal()) {
            return false;
        }

        return $now->getTimestamp() - (new \DateTimeImmutable($this->createdAt))->getTimestamp() >= 86400;
    }

    /**
     * The resolution date is receipt + 24h for every request type. Since
     * needsAutoEstimatedDate() only allows this once that same period has
     * elapsed, the resulting deadline is effectively now, flagging the
     * request as already due.
     */
    public function autoAssignEstimatedResolutionDate(): void
    {
        $this->estimatedResolutionDate = (new \DateTimeImmutable($this->createdAt))
            ->modify('+24 hours')
            ->format('Y-m-d');
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function studentId(): int
    {
        return $this->studentId;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function courseId(): int
    {
        return $this->courseId;
    }

    public function requiredCourseId(): ?int
    {
        return $this->requiredCourseId;
    }

    public function waiverJustification(): ?string
    {
        return $this->waiverJustification;
    }

    public function originInstitution(): ?string
    {
        return $this->originInstitution;
    }

    public function externalCourse(): ?string
    {
        return $this->externalCourse;
    }

    public function externalCourseCode(): ?string
    {
        return $this->externalCourseCode;
    }

    public function externalCourseCredits(): ?int
    {
        return $this->externalCourseCredits;
    }

    public function externalCourseGrade(): ?float
    {
        return $this->externalCourseGrade;
    }

    public function validationPrecedentId(): ?int
    {
        return $this->validationPrecedentId;
    }

    public function engineResult(): ?string
    {
        return $this->engineResult;
    }

    public function violatedRuleId(): ?int
    {
        return $this->violatedRuleId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function estimatedResolutionDate(): ?string
    {
        return $this->estimatedResolutionDate;
    }

    /**
     * The date shown in the request detail: the stored value once set,
     * or the same receipt + 24h computed on the fly during the first day,
     * so the view never shows a blank for a date that is already known.
     *
     * Read-only — unlike autoAssignEstimatedResolutionDate(), this never
     * mutates or persists anything.
     */
    public function displayEstimatedResolutionDate(): ?string
    {
        if ($this->estimatedResolutionDate !== null || $this->createdAt === null) {
            return $this->estimatedResolutionDate;
        }

        return (new \DateTimeImmutable($this->createdAt))
            ->modify('+24 hours')
            ->format('Y-m-d');
    }

    public function reviewerId(): ?int
    {
        return $this->reviewerId;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }
}
