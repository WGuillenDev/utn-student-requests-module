<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Domain\Contracts;

use Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent;

/**
 * Port (in the Hexagonal sense) that Infrastructure adapters must
 * implement. The Domain and Application layers depend only on this
 * abstraction — never on Eloquent, the database, or any concrete driver.
 */
interface ValidationPrecedentRepositoryInterface
{
    public function find(int $id): ?ValidationPrecedent;

    /**
     * Exact-match catalog lookup used by the Request context to
     * auto-link a precedent when a student files a Validation request —
     * distinct from the free-text `search` used by all()/paginate().
     * Returns the most recent match when more than one resolution
     * exists for the same (institution, external_course) pair.
     */
    public function findByInstitutionAndExternalCourse(string $institution, string $externalCourse): ?ValidationPrecedent;

    /**
     * @return array<int, ValidationPrecedent>
     */
    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array;

    /**
     * @return array{items: array<int, ValidationPrecedent>, total: int}
     */
    public function paginate(?string $search, int $perPage, int $page, ?string $sortBy = null, string $sortDir = 'asc'): array;

    public function save(ValidationPrecedent $validationPrecedent): ValidationPrecedent;

    public function delete(int $id): void;
}
