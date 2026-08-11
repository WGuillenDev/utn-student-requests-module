<?php

declare(strict_types=1);

namespace Src\Requests\WaiverRule\Application\UseCases;

use Src\Requests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;
use Src\Requests\WaiverRule\Domain\Entities\WaiverRule;

final class ListWaiverRulesUseCase
{
    public function __construct(
        private readonly WaiverRuleRepositoryInterface $repository,
    ) {}

    /**
     * Full, unpaginated collection — used by client-side (Alpine) tables
     * that resolve search/sort/pagination in the browser without any
     * further round-trip to the server. Intended for small catalogs,
     * same reasoning as ListRolesUseCase::all().
     *
     * @return array<int, WaiverRule>
     */
    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc', ?int $courseId = null): array
    {
        return $this->repository->all($search, $sortBy, $sortDir, $courseId);
    }

    /**
     * Server-paginated collection — kept for parity with the rest of the
     * module in case this catalog ever needs to flip to 'server' mode.
     *
     * @return array{items: array<int, WaiverRule>, total: int}
     */
    public function paginate(
        ?string $search = null,
        int $perPage = 10,
        int $page = 1,
        ?string $sortBy = null,
        string $sortDir = 'asc',
        ?int $courseId = null,
    ): array {
        return $this->repository->paginate($search, $perPage, $page, $sortBy, $sortDir, $courseId);
    }
}
