<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Contracts;

use Src\Requests\Request\Domain\Entities\Request;

/**
 * Port (in the Hexagonal sense) that Infrastructure adapters must
 * implement. The Domain and Application layers depend only on this
 * abstraction — never on Eloquent, the database, or any concrete driver.
 */
interface RequestRepositoryInterface
{
    public function find(int $id): ?Request;

    /**
     * @return array<int, Request>
     */
    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array;

    /**
     * @return array{items: array<int, Request>, total: int}
     */
    public function paginate(
        ?string $search,
        int $perPage,
        int $page,
        ?string $sortBy = null,
        string $sortDir = 'asc',
    ): array;

    /**
     * Student self-service listing ("My requests") — scoped strictly to
     * one student, no free-text search (the owning student never has
     * more than a handful of requests to browse).
     *
     * @return array{items: array<int, Request>, total: int}
     */
    public function paginateForStudent(
        int $studentId,
        int $perPage,
        int $page,
        ?string $sortBy = null,
        string $sortDir = 'asc',
    ): array;

    public function save(Request $request): Request;

    public function delete(int $id): void;
}
