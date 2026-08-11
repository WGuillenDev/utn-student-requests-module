<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Domain\Entities;

/**
 * ValidationPrecedent — Aggregate Root of the Requests bounded context.
 *
 * Pure PHP. Zero framework coupling — no Eloquent, no Illuminate imports.
 *
 * Master-data entity, same family as WaiverRule: free create/edit/delete,
 * no state machine. Simpler than WaiverRule — not even a composite
 * uniqueness rule; the same (institution, external_course) pair can
 * legitimately repeat across different resolutions over time, so this
 * entity carries no invariant of its own beyond primitive types, which
 * the Form Object already validates.
 */
final class ValidationPrecedent
{
    private function __construct(
        private readonly ?int $id,
        private string $institution,
        private string $externalCourse,
        private int $courseId,
        private string $result,
        private string $resolutionNumber,
    ) {}

    public static function create(
        string $institution,
        string $externalCourse,
        int $courseId,
        string $result,
        string $resolutionNumber,
    ): self {
        return new self(
            id: null,
            institution: $institution,
            externalCourse: $externalCourse,
            courseId: $courseId,
            result: $result,
            resolutionNumber: $resolutionNumber,
        );
    }

    public static function reconstitute(
        int $id,
        string $institution,
        string $externalCourse,
        int $courseId,
        string $result,
        string $resolutionNumber,
    ): self {
        return new self(
            id: $id,
            institution: $institution,
            externalCourse: $externalCourse,
            courseId: $courseId,
            result: $result,
            resolutionNumber: $resolutionNumber,
        );
    }

    public function update(
        string $institution,
        string $externalCourse,
        int $courseId,
        string $result,
        string $resolutionNumber,
    ): void {
        $this->institution = $institution;
        $this->externalCourse = $externalCourse;
        $this->courseId = $courseId;
        $this->result = $result;
        $this->resolutionNumber = $resolutionNumber;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function institution(): string
    {
        return $this->institution;
    }

    public function externalCourse(): string
    {
        return $this->externalCourse;
    }

    public function courseId(): int
    {
        return $this->courseId;
    }

    public function result(): string
    {
        return $this->result;
    }

    public function resolutionNumber(): string
    {
        return $this->resolutionNumber;
    }
}
