<?php

declare(strict_types=1);

namespace Src\Requests\WaiverRule\Application\UseCases;

use Src\Requests\WaiverRule\Application\DTOs\WaiverRuleDTO;
use Src\Requests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;
use Src\Requests\WaiverRule\Domain\Entities\WaiverRule;

/**
 * Single-purpose orchestrator (SRP): turns a WaiverRuleDTO into a
 * persisted WaiverRule. No business logic beyond what the Domain
 * already encapsulates — same shape as CreateRoleUseCase.
 */
final class CreateWaiverRuleUseCase
{
    public function __construct(
        private readonly WaiverRuleRepositoryInterface $repository,
    ) {}

    public function handle(WaiverRuleDTO $dto): WaiverRule
    {
        $waiverRule = WaiverRule::create(
            courseId: $dto->courseId,
            order: $dto->order,
            type: $dto->type,
            requiredCourseId: $dto->requiredCourseId,
            minimumGrade: $dto->minimumGrade,
            minimumAccumulated: $dto->minimumAccumulated,
            active: $dto->active,
        );

        return $this->repository->save($waiverRule);
    }
}
