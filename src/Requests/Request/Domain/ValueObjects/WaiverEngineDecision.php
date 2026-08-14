<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\ValueObjects;

/**
 * Immutable outcome of WaiverEngine::evaluate(). The three possible
 * results mirror the `requests.engine_result` ENUM exactly and the
 * three outcomes literally named in ES-01's acceptance criteria.
 */
final class WaiverEngineDecision
{
    private function __construct(
        private readonly string $result,
        private readonly ?int $violatedRuleId,
    ) {}

    public static function approved(): self
    {
        return new self('Automatically Approved', null);
    }

    public static function denied(int $violatedRuleId): self
    {
        return new self('Not Approved', $violatedRuleId);
    }

    public static function manualReview(): self
    {
        return new self('Requires Manual Review', null);
    }

    public function result(): string
    {
        return $this->result;
    }

    public function violatedRuleId(): ?int
    {
        return $this->violatedRuleId;
    }
}
