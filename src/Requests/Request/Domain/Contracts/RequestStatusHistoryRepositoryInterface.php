<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Contracts;

/**
 * Deliberately minimal: RequestStatusHistory is an append-only audit
 * log written automatically whenever a Request's status changes (see
 * ChangeRequestStatusUseCase). It never gets its own CRUD/UI — no
 * find/update/delete here, only the one write it actually needs.
 */
interface RequestStatusHistoryRepositoryInterface
{
    public function record(
        int $requestId,
        ?string $previousStatus,
        string $newStatus,
        ?string $comment,
        ?int $userId,
    ): void;
}
