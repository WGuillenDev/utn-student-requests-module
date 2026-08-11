<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Exceptions\RequestNotFoundException;

final class DeleteRequestUseCase
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
    ) {}

    public function handle(int $id): void
    {
        $this->repository->find($id) ?? throw RequestNotFoundException::withId($id);

        $this->repository->delete($id);
    }
}
