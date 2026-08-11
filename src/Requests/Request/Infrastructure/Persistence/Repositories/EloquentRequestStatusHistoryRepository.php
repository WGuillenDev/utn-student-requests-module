<?php

declare(strict_types=1);

namespace Src\Requests\Request\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Eloquent\Requests\Models\RequestStatusHistoryModel;
use Src\Requests\Request\Domain\Contracts\RequestStatusHistoryRepositoryInterface;

final class EloquentRequestStatusHistoryRepository implements RequestStatusHistoryRepositoryInterface
{
    public function record(
        int $requestId,
        ?string $previousStatus,
        string $newStatus,
        ?string $comment,
        ?int $userId,
    ): void {
        RequestStatusHistoryModel::query()->create([
            'request_id' => $requestId,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
            'user_id' => $userId,
        ]);
    }
}
