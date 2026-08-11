<?php

declare(strict_types=1);

namespace Src\Requests\WaiverRule\Application\DTOs;

/**
 * Immutable data boundary between Presentation and Application layers.
 * Carries primitives only — no Domain Entities and no Eloquent Models
 * leak across this line in either direction. Mirrors the 7 fields of
 * the create/edit form.
 */
final class WaiverRuleDTO
{
    public function __construct(
        public readonly int $courseId,
        public readonly int $order,
        public readonly string $type,
        public readonly ?int $requiredCourseId = null,
        public readonly ?float $minimumGrade = null,
        public readonly ?int $minimumAccumulated = null,
        public readonly bool $active = true,
    ) {}
}
