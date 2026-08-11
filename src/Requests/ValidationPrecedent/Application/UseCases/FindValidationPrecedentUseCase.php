<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Application\UseCases;

use Src\Requests\ValidationPrecedent\Domain\Contracts\ValidationPrecedentRepositoryInterface;
use Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent;
use Src\Requests\ValidationPrecedent\Domain\Exceptions\ValidationPrecedentNotFoundException;

final class FindValidationPrecedentUseCase
{
    public function __construct(
        private readonly ValidationPrecedentRepositoryInterface $repository,
    ) {}

    public function handle(int $id): ValidationPrecedent
    {
        return $this->repository->find($id) ?? throw ValidationPrecedentNotFoundException::withId($id);
    }
}
