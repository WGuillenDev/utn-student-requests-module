<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\RequestNotFoundException;

/**
 * Persists the external course's own code/credits/grade for a Validation
 * request — called from RequestComponent::changeStatus() right alongside
 * the Reconocer/No reconocer decision (there's no separate "Guardar datos
 * externos" step any more), but kept as its own use case rather than
 * folded into ChangeRequestStatusUseCase since it's conceptually a
 * different write: this data isn't itself a status decision, and never
 * touches RequestStatusHistory.
 */
final class SaveExternalCourseDataUseCase
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
    ) {}

    public function handle(int $requestId, ?string $code, ?int $credits, ?float $grade): Request
    {
        $request = $this->repository->find($requestId) ?? throw RequestNotFoundException::withId($requestId);

        $request->setExternalCourseData($code, $credits, $grade);

        return $this->repository->save($request);
    }
}
