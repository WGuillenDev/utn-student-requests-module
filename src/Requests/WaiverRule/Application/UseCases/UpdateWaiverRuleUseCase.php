<?php

declare(strict_types=1);

namespace Src\Requests\WaiverRule\Application\UseCases;

use Src\Requests\WaiverRule\Application\DTOs\WaiverRuleDTO;
use Src\Requests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;
use Src\Requests\WaiverRule\Domain\Entities\WaiverRule;
use Src\Requests\WaiverRule\Domain\Exceptions\WaiverRuleNotFoundException;

final class UpdateWaiverRuleUseCase
{
    public function __construct(
        private readonly WaiverRuleRepositoryInterface $repository,
    ) {}

    public function handle(int $id, WaiverRuleDTO $dto): WaiverRule
    {
        $waiverRule = $this->repository->find($id) ?? throw WaiverRuleNotFoundException::withId($id);

        $waiverRule->update(
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
