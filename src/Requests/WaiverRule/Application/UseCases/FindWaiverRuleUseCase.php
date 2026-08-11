<?php

declare(strict_types=1);

namespace Src\Requests\WaiverRule\Application\UseCases;

use Src\Requests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;
use Src\Requests\WaiverRule\Domain\Entities\WaiverRule;
use Src\Requests\WaiverRule\Domain\Exceptions\WaiverRuleNotFoundException;

final class FindWaiverRuleUseCase
{
    public function __construct(
        private readonly WaiverRuleRepositoryInterface $repository,
    ) {}

    public function handle(int $id): WaiverRule
    {
        return $this->repository->find($id) ?? throw WaiverRuleNotFoundException::withId($id);
    }
}
