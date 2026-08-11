<?php

declare(strict_types=1);

namespace Src\Requests\WaiverRule\Domain\Entities;

/**
 * WaiverRule — Aggregate Root of the Requests bounded context.
 *
 * Pure PHP. Zero framework coupling — no Eloquent, no Illuminate imports.
 *
 * Unlike Request, this is a master-data entity: free create/edit/delete
 * by Docencia, with no state machine. The only real business rule —
 * uniqueness of (course_id, order) — is not a Domain invariant enforced
 * here; it lives in the Form Object as a `Rule::unique` validation rule,
 * because it depends on querying persistence (Application/Presentation
 * concern), not on data already held by this entity.
 */
final class WaiverRule
{
    private function __construct(
        private readonly ?int $id,
        private int $courseId,
        private int $order,
        private string $type,
        private ?int $requiredCourseId,
        private ?float $minimumGrade,
        private ?int $minimumAccumulated,
        private bool $active,
    ) {}

    public static function create(
        int $courseId,
        int $order,
        string $type,
        ?int $requiredCourseId = null,
        ?float $minimumGrade = null,
        ?int $minimumAccumulated = null,
        bool $active = true,
    ): self {
        return new self(
            id: null,
            courseId: $courseId,
            order: $order,
            type: $type,
            requiredCourseId: $requiredCourseId,
            minimumGrade: $minimumGrade,
            minimumAccumulated: $minimumAccumulated,
            active: $active,
        );
    }

    public static function reconstitute(
        int $id,
        int $courseId,
        int $order,
        string $type,
        ?int $requiredCourseId,
        ?float $minimumGrade,
        ?int $minimumAccumulated,
        bool $active,
    ): self {
        return new self(
            id: $id,
            courseId: $courseId,
            order: $order,
            type: $type,
            requiredCourseId: $requiredCourseId,
            minimumGrade: $minimumGrade,
            minimumAccumulated: $minimumAccumulated,
            active: $active,
        );
    }

    /**
     * Free edit — no invariant beyond what the Form Object already
     * validated (composite uniqueness, conditional-by-type fields).
     * `course_id` is intentionally not editable here: the record is
     * scoped to a course from creation onward, same way RoleForm never
     * lets you "move" a role's identity, only its attributes.
     */
    public function update(
        int $order,
        string $type,
        ?int $requiredCourseId,
        ?float $minimumGrade,
        ?int $minimumAccumulated,
        bool $active,
    ): void {
        $this->order = $order;
        $this->type = $type;
        $this->requiredCourseId = $requiredCourseId;
        $this->minimumGrade = $minimumGrade;
        $this->minimumAccumulated = $minimumAccumulated;
        $this->active = $active;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function courseId(): int
    {
        return $this->courseId;
    }

    public function order(): int
    {
        return $this->order;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function requiredCourseId(): ?int
    {
        return $this->requiredCourseId;
    }

    public function minimumGrade(): ?float
    {
        return $this->minimumGrade;
    }

    public function minimumAccumulated(): ?int
    {
        return $this->minimumAccumulated;
    }

    public function active(): bool
    {
        return $this->active;
    }
}
