<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Application\DTOs\RequestDTO;
use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;

/**
 * Single-purpose orchestrator (SRP): turns a RequestDTO into a persisted
 * Request in 'Pending Review'. Depends on the repository abstraction
 * only — the concrete EloquentRequestRepository is wired in by the
 * container (see App\Providers\DomainServiceProvider).
 *
 * Running the waiver/validation engine to auto-resolve the request is
 * intentionally NOT done here — that belongs to a dedicated Domain
 * Service invoked from this UseCase once it exists. For now every
 * request is created in 'Pending Review' and resolved manually via
 * ChangeRequestStatusUseCase.
 */
final class CreateRequestUseCase
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
    ) {}

    public function handle(RequestDTO $dto): Request
    {
        $request = Request::create(
            studentId: $dto->studentId,
            type: $dto->type,
            courseId: $dto->courseId,
            requiredCourseId: $dto->requiredCourseId,
            originInstitution: $dto->originInstitution,
            externalCourse: $dto->externalCourse,
        );

        return $this->repository->save($request);
    }
}
