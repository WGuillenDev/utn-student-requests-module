<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Domain\Contracts\AcademicRecordRegistrarInterface;
use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Contracts\RequestStatusHistoryRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\RequestNotFoundException;

/**
 * The real "edit" action for this CRUD: Request fields are not freely
 * editable once created (see manual §Request), what actually changes
 * over a Request's life is its status. Every transition is recorded in
 * RequestStatusHistory — that write happens here, right after the
 * Domain invariant (Request::changeStatus()) has approved the move, so
 * an invalid transition never reaches the history table either.
 */
final class ChangeRequestStatusUseCase
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
        private readonly RequestStatusHistoryRepositoryInterface $historyRepository,
        private readonly AcademicRecordRegistrarInterface $academicRecordRegistrar,
    ) {}

    public function handle(
        int $requestId,
        string $newStatus,
        ?int $reviewerId = null,
        ?string $comment = null,
        ?string $estimatedResolutionDate = null,
    ): Request {
        $request = $this->repository->find($requestId) ?? throw RequestNotFoundException::withId($requestId);

        $previousStatus = $request->status();

        if ($estimatedResolutionDate !== null) {
            $request->assignEstimatedResolutionDate($estimatedResolutionDate);
        }

        $request->changeStatus($newStatus, $reviewerId);
        $saved = $this->repository->save($request);

        $this->historyRepository->record(
            requestId: $requestId,
            previousStatus: $previousStatus,
            newStatus: $newStatus,
            comment: $comment,
            userId: $reviewerId,
        );

        // 'Approved by Registro' is a final status (Request::changeStatus()
        // forbids transitioning out of it), so this can only fire once
        // per request — no need to guard against re-registering the
        // credit. Docencia's own 'Approved by Docencia' step does NOT
        // register a credit yet — only Registro's final closing does.
        if ($newStatus === 'Approved by Registro') {
            $this->academicRecordRegistrar->registerCredit($saved);
        }

        return $saved;
    }
}
