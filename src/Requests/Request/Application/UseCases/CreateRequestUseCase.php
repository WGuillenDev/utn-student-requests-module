<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Application\DTOs\RequestDTO;
use Src\Requests\Request\Domain\Contracts\RequestAttachmentRepositoryInterface;
use Src\Requests\Request\Domain\Contracts\RequestNotifierInterface;
use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Contracts\RequestStatusHistoryRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\DuplicateWaiverRequestException;
use Src\Requests\Request\Domain\Services\WaiverEngine;
use Src\Requests\ValidationPrecedent\Domain\Contracts\ValidationPrecedentRepositoryInterface;
use Src\Requests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;

/**
 * Turns a RequestDTO into a persisted Request in 'Pending Review'.
 *
 * For waiver requests it runs the WaiverEngine (ES-01) to compute the
 * immediate result shown to the student. That never affects status: every
 * request still starts 'Pending Review' and needs a human reviewer.
 *
 * The ValidationPrecedent lookup is unrelated — it only links an existing
 * catalog resolution to a new Validation request for Docencia to see.
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
        private readonly RequestStatusHistoryRepositoryInterface $historyRepository,
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

        // Opens the status history with a narrative marker — never a real
        // requests.status value, same convention as the markers in
        // ChangeRequestStatusUseCase — so the timeline covers the whole
        // lifecycle rather than starting at the first decision.
        $this->historyRepository->record(
            requestId: $saved->id(),
            previousStatus: null,
            newStatus: 'Received by Docencia',
            comment: null,
            userId: null,
        );

        if ($dto->attachments !== []) {
            $this->attachmentRepository->attach($saved->id(), $dto->attachments);
        }

        if ($dto->notify) {
            $this->notifier->notifyRequestSubmitted($saved, $dto->batchCourseNames);
        }

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
