<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Application\Services\EstimatedResolutionDateAssigner;
use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;

final class ListRequestsUseCase
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
        private readonly EstimatedResolutionDateAssigner $estimatedResolutionDateAssigner,
    ) {}

    /**
     * @param array<string, mixed> $filters ES-04's inbox filters — see
     *   RequestRepositoryInterface for recognized keys.
     * @return array<int, Request>
     */
    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc', array $filters = []): array
    {
        return $this->estimatedResolutionDateAssigner->ensureAssignedForAll(
            $this->repository->all($search, $sortBy, $sortDir, $filters),
        );
    }

    /**
     * @param array<string, mixed> $filters ES-04's inbox filters — see
     *   RequestRepositoryInterface for recognized keys.
     * @return array{items: array<int, Request>, total: int}
     */
    public function paginate(
        ?string $search = null,
        int $perPage = 10,
        int $page = 1,
        ?string $sortBy = null,
        string $sortDir = 'asc',
        array $filters = [],
    ): array {
        $result = $this->repository->paginate($search, $perPage, $page, $sortBy, $sortDir, $filters);

        $result['items'] = $this->estimatedResolutionDateAssigner->ensureAssignedForAll($result['items']);

        return $result;
    }

    /**
     * @return array{items: array<int, Request>, total: int}
     */
    public function paginateForStudent(
        int $studentId,
        int $perPage = 10,
        int $page = 1,
        ?string $sortBy = null,
        string $sortDir = 'asc',
    ): array {
        $result = $this->repository->paginateForStudent($studentId, $perPage, $page, $sortBy, $sortDir);

        $result['items'] = $this->estimatedResolutionDateAssigner->ensureAssignedForAll($result['items']);

        return $result;
    }
}
