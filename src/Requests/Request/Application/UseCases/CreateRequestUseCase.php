<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Application\DTOs\RequestDTO;
use Src\Requests\Request\Domain\Contracts\RequestAttachmentRepositoryInterface;
use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\ValidationPrecedent\Domain\Contracts\ValidationPrecedentRepositoryInterface;

/**
 * Single-purpose orchestrator (SRP): turns a RequestDTO into a persisted
 * Request in 'Pending Review'. Depends on repository abstractions only —
 * the concrete adapters are wired in by the container (see
 * App\Providers\DomainServiceProvider).
 *
 * Running the waiver/validation engine to auto-resolve the request is
 * intentionally NOT done here — that belongs to a dedicated Domain
 * Service invoked from this UseCase once it exists. For now every
 * request is created in 'Pending Review' and resolved manually via
 * ChangeRequestStatusUseCase.
 *
 * The ValidationPrecedent lookup below is NOT that engine: it only
 * links a pre-existing catalog resolution (if any) to the new request
 * for the Docencia inbox to see later — it never changes `status` or
 * `engineResult`, both of which stay untouched until the engine exists.
 */
final class CreateRequestUseCase
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
        private readonly RequestAttachmentRepositoryInterface $attachmentRepository,
        private readonly ValidationPrecedentRepositoryInterface $validationPrecedentRepository,
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
            validationPrecedentId: $this->resolveValidationPrecedentId($dto),
        );

        $saved = $this->repository->save($request);

        if ($dto->attachments !== []) {
            $this->attachmentRepository->attach($saved->id(), $dto->attachments);
        }

        return $saved;
    }

    private function resolveValidationPrecedentId(RequestDTO $dto): ?int
    {
        if ($dto->type !== 'Validation' || $dto->originInstitution === null || $dto->externalCourse === null) {
            return null;
        }

        $precedent = $this->validationPrecedentRepository->findByInstitutionAndExternalCourse(
            $dto->originInstitution,
            $dto->externalCourse,
        );

        return $precedent?->result() === 'Approved' ? $precedent->id() : null;
    }
}
