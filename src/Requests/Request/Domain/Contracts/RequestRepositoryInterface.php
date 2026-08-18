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
     * @param array<string, mixed> $filters ES-04's inbox filters. Recognized
     *   keys: 'type' (Request::type()), 'status' (Request::status()),
     *   'careerId' (the requested course's owning career), 'dateFrom' and
     *   'dateTo' (received-date range, inclusive, 'Y-m-d'). All optional —
     *   an absent or empty value is not applied.
     * @return array<int, Request>
     */
    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc', array $filters = []): array;

    /**
     * @param array<string, mixed> $filters See all()'s $filters.
     * @return array{items: array<int, Request>, total: int}
     */
    public function paginate(
        ?string $search,
        int $perPage,
        int $page,
        ?string $sortBy = null,
        string $sortDir = 'asc',
        array $filters = [],
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

    /**
     * ES-01's duplicate check: whether this student already has an
     * Approved waiver for the exact same course + unmet requirement.
     */
    public function existsApprovedWaiver(int $studentId, int $courseId, int $requiredCourseId): bool;
}
