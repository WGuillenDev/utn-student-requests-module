<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Entities;

use Src\Requests\Request\Domain\Contracts\HolidayCalendarInterface;
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
     *
     * 'Approved by Docencia'/'Denied by Docencia' are deliberately NOT
     * final: they're Docencia's substantive decision, but the request
     * only truly closes once Registro applies the matching final status
     * (see RequestPolicy::finalize(), gated to the Registro role only).
     */
    private const FINAL_STATUSES = ['Approved by Registro', 'Denied by Registro'];

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
        private ?int $validationPrecedentId,
        private ?string $engineResult,
        private ?int $violatedRuleId,
        private string $status,
        private ?string $estimatedResolutionDate,
        private ?int $reviewerId,
        private readonly ?string $createdAt,
    ) {}

    /**
     * `engineResult`/`violatedRuleId` may be populated at creation by
     * the WaiverEngine (ES-01's immediate result shown to the student).
     * `status` always starts 'Pending Review' regardless — by design,
     * every request (auto-resolved or not) still requires a human
     * reviewer at Docencia to close it, so it stays visible in the
     * ES-04 inbox and gets a proper user+date audit trail (ES-03).
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
     * Validation-only: the external course's own code/credit count,
     * captured by Docencia while reviewing (never at submission time),
     * to compare against the equivalent UTN course before deciding
     * Reconocer/No reconocer. Deliberately not gated by isFinal() — a
     * reviewer may correct these while still deciding, same rationale
     * as assignEstimatedResolutionDate().
     */
    public function setExternalCourseData(?string $code, ?int $credits): void
    {
        $this->externalCourseCode = $code;
        $this->externalCourseCredits = $credits;
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
     * "5 días hábiles a partir de la fecha de recepción" — Monday
     * through Friday, excluding national holidays as reported by the
     * injected HolidayCalendarInterface port (see
     * NagerDateHolidayCalendar for the concrete adapter).
     */
    public function autoAssignEstimatedResolutionDate(HolidayCalendarInterface $calendar): void
    {
        $date = new \DateTimeImmutable($this->createdAt);
        $businessDaysAdded = 0;

        while ($businessDaysAdded < 5) {
            $date = $date->modify('+1 day');

            if ((int) $date->format('N') < 6 && ! $calendar->isHoliday($date)) {
                $businessDaysAdded++;
            }
        }

        $this->estimatedResolutionDate = $date->format('Y-m-d');
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

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }
}
