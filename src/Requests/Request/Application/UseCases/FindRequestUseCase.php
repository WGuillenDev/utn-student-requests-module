<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Application\Services\EstimatedResolutionDateAssigner;
use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\RequestNotFoundException;

final class FindRequestUseCase
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
        private readonly EstimatedResolutionDateAssigner $estimatedResolutionDateAssigner,
    ) {}

    public function handle(int $id): Request
    {
        $request = $this->repository->find($id) ?? throw RequestNotFoundException::withId($id);

        return $this->estimatedResolutionDateAssigner->ensureAssigned($request);
    }
}
