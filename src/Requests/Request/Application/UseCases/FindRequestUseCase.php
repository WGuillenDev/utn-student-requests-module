<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\RequestNotFoundException;

final class FindRequestUseCase
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
    ) {}

    public function handle(int $id): Request
    {
        return $this->repository->find($id) ?? throw RequestNotFoundException::withId($id);
    }
}
