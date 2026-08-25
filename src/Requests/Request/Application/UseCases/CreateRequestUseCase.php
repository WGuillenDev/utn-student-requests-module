<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Application\DTOs\RequestDTO;
use Src\Requests\Request\Domain\Contracts\RequestAttachmentRepositoryInterface;
use Src\Requests\Request\Domain\Contracts\RequestNotifierInterface;
use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\DuplicateWaiverRequestException;
use Src\Requests\Request\Domain\Services\WaiverEngine;
use Src\Requests\ValidationPrecedent\Domain\Contracts\ValidationPrecedentRepositoryInterface;
use Src\Requests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;

/**
 * Single-purpose orchestrator (SRP): turns a RequestDTO into a persisted
 * Request in 'Pending Review'. Depends on repository abstractions only —
 * the concrete adapters are wired in by the container (see
 * App\Providers\DomainServiceProvider).
 *
 * For waiver requests, runs the WaiverEngine (ES-01) to compute the
 * immediate `engineResult`/`violatedRuleId` shown to the student. This
 * never changes `status`: every request — auto-resolved or not — still
 * starts 'Pending Review' and requires a human reviewer at Docencia to
 * close it (see Request::create()'s docblock for why).
 *
 * The ValidationPrecedent lookup is a separate, unrelated concern: it
 * only links a pre-existing catalog resolution (if any) to a new
 * Validation request for the Docencia inbox to see later.
 */
final class CreateRequestUseCase
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
        private readonly RequestAttachmentRepositoryInterface $attachmentRepository,
        private readonly ValidationPrecedentRepositoryInterface $validationPrecedentRepository,
        private readonly WaiverRuleRepositoryInterface $waiverRuleRepository,
        private readonly WaiverEngine $waiverEngine,
        private readonly RequestNotifierInterface $notifier,
    ) {}

    public function handle(RequestDTO $dto): Request
    {
        [$engineResult, $violatedRuleId] = $dto->type === 'Requirement Waiver'
            ? $this->runWaiverEngine($dto)
            : [null, null];

        $request = Request::create(
            studentId: $dto->studentId,
            type: $dto->type,
            courseId: $dto->courseId,
            requiredCourseId: $dto->requiredCourseId,
            waiverJustification: $dto->waiverJustification,
            originInstitution: $dto->originInstitution,
            externalCourse: $dto->externalCourse,
            validationPrecedentId: $this->resolveValidationPrecedentId($dto),
            engineResult: $engineResult,
            violatedRuleId: $violatedRuleId,
        );

        $saved = $this->repository->save($request);

        if ($dto->attachments !== []) {
            $this->attachmentRepository->attach($saved->id(), $dto->attachments);
        }

        $this->notifier->notifyRequestSubmitted($saved);

        return $saved;
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private function runWaiverEngine(RequestDTO $dto): array
    {
        if (
            $dto->requiredCourseId !== null
            && $this->repository->existsApprovedWaiver($dto->studentId, $dto->courseId, $dto->requiredCourseId)
        ) {
            throw DuplicateWaiverRequestException::create();
        }

        $rules = $this->waiverRuleRepository->all(sortBy: 'order', courseId: $dto->courseId);

        $decision = $this->waiverEngine->evaluate($dto->studentId, $rules);

        return [$decision->result(), $decision->violatedRuleId()];
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
