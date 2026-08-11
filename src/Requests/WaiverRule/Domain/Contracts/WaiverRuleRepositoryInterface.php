<?php

declare(strict_types=1);

namespace Src\Requests\WaiverRule\Domain\Contracts;

use Src\Requests\WaiverRule\Domain\Entities\WaiverRule;

/**
 * Port (in the Hexagonal sense) that Infrastructure adapters must
 * implement. The Domain and Application layers depend only on this
 * abstraction — never on Eloquent, the database, or any concrete driver.
 */
interface WaiverRuleRepositoryInterface
{
    public function find(int $id): ?WaiverRule;

    /**
     * @return array<int, WaiverRule>
     */
    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc', ?int $courseId = null): array;

    /**
     * @return array{items: array<int, WaiverRule>, total: int}
     */
    public function paginate(
        ?string $search,
        int $perPage,
        int $page,
        ?string $sortBy = null,
        string $sortDir = 'asc',
        ?int $courseId = null,
    ): array;

    public function save(WaiverRule $waiverRule): WaiverRule;

    public function delete(int $id): void;
}
