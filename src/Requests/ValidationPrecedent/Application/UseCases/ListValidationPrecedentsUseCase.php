<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Application\UseCases;

use Src\Requests\ValidationPrecedent\Domain\Contracts\ValidationPrecedentRepositoryInterface;
use Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent;

final class ListValidationPrecedentsUseCase
{
    public function __construct(
        private readonly ValidationPrecedentRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, ValidationPrecedent>
     */
    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        return $this->repository->all($search, $sortBy, $sortDir);
    }

    /**
     * @return array{items: array<int, ValidationPrecedent>, total: int}
     */
    public function paginate(
        ?string $search = null,
        int $perPage = 10,
        int $page = 1,
        ?string $sortBy = null,
        string $sortDir = 'asc',
    ): array {
        return $this->repository->paginate($search, $perPage, $page, $sortBy, $sortDir);
    }
}
