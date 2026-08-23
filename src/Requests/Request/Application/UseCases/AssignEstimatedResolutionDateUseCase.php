<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\RequestNotFoundException;

/**
 * The review modal's "Guardar fecha" — split out from
 * ChangeRequestStatusUseCase so setting the estimated date on its own
 * (with no status change) doesn't write a same-status
 * RequestStatusHistory row or fire ES-03's "status changed" email.
 */
final class AssignEstimatedResolutionDateUseCase
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
    ) {}

    public function handle(int $requestId, string $date): Request
    {
        $request = $this->repository->find($requestId) ?? throw RequestNotFoundException::withId($requestId);

        $request->assignEstimatedResolutionDate($date);

        return $this->repository->save($request);
    }
}
