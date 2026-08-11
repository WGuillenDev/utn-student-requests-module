<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Entities;

use Src\Requests\Request\Domain\Exceptions\InvalidStatusTransitionException;

/**
 * Request — Aggregate Root of the Requests bounded context.
 *
 * Pure PHP. Zero framework coupling — no Eloquent, no Illuminate imports.
 * Covers both request types stored in the same table (`type` column):
 * "Requirement Waiver" and "Validation". The distinguishing fields for
 * each type (originInstitution/externalCourse for Validation,
 * requiredCourseId for Waiver) are simply nullable here — validating
 * which ones are mandatory per type is an Application-layer concern
 * (the DTO/UseCase knows which flow created it), not a Domain invariant.
 */
final class Request
{
    /**
     * A request in either of these statuses is closed. It cannot be
     * moved to any other status — reopening a resolved request is not
     * supported; a new request must be filed instead.
     */
    private const FINAL_STATUSES = ['Approved', 'Denied'];

    private function __construct(
        private readonly ?int $id,
        private readonly int $studentId,
        private readonly string $type,
        private readonly int $courseId,
        private ?int $requiredCourseId,
        private ?string $originInstitution,
        private ?string $externalCourse,
        private ?int $validationPrecedentId,
        private ?string $engineResult,
        private ?int $violatedRuleId,
        private string $status,
        private ?string $estimatedResolutionDate,
        private ?int $reviewerId,
    ) {}

    public static function create(
        int $studentId,
        string $type,
        int $courseId,
        ?int $requiredCourseId = null,
        ?string $originInstitution = null,
        ?string $externalCourse = null,
    ): self {
        return new self(
            id: null,
            studentId: $studentId,
            type: $type,
            courseId: $courseId,
            requiredCourseId: $requiredCourseId,
            originInstitution: $originInstitution,
            externalCourse: $externalCourse,
            validationPrecedentId: null,
            engineResult: null,
            violatedRuleId: null,
            status: 'Pending Review',
            estimatedResolutionDate: null,
            reviewerId: null,
        );
    }

    public static function reconstitute(
        int $id,
        int $studentId,
        string $type,
        int $courseId,
        ?int $requiredCourseId,
        ?string $originInstitution,
        ?string $externalCourse,
        ?int $validationPrecedentId,
        ?string $engineResult,
        ?int $violatedRuleId,
        string $status,
        ?string $estimatedResolutionDate,
        ?int $reviewerId,
    ): self {
        return new self(
            id: $id,
            studentId: $studentId,
            type: $type,
            courseId: $courseId,
            requiredCourseId: $requiredCourseId,
            originInstitution: $originInstitution,
            externalCourse: $externalCourse,
            validationPrecedentId: $validationPrecedentId,
            engineResult: $engineResult,
            violatedRuleId: $violatedRuleId,
            status: $status,
            estimatedResolutionDate: $estimatedResolutionDate,
            reviewerId: $reviewerId,
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

    public function originInstitution(): ?string
    {
        return $this->originInstitution;
    }

    public function externalCourse(): ?string
    {
        return $this->externalCourse;
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

    public function reviewerId(): ?int
    {
        return $this->reviewerId;
    }
}
