<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Domain\Contracts\AcademicRecordRegistrarInterface;
use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Contracts\RequestStatusHistoryRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\RequestNotFoundException;

/**
 * The edit action for this CRUD: a Request's fields are fixed once
 * created, and what changes over its life is the status.
 *
 * Every transition is written to RequestStatusHistory here, after the
 * Domain invariant has approved the move, so an invalid transition never
 * reaches the history table either.
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

        // Docencia's decision is the same click that hands the request to
        // Registro — this synchronous system has no separate "receive"
        // action — so both milestones are logged as system rows
        // (userId: null) alongside the real status change.
        //
        // 'Sent to Registro' and 'Received by Registro' are narrative
        // markers, never values of requests.status, so nothing matching
        // against the real enum downstream ever sees them.
        if (in_array($saved->type(), ['Requirement Waiver', 'Validation'], true) && in_array($newStatus, ['Approved by Docencia', 'Denied by Docencia'], true)) {
            $this->historyRepository->record(
                requestId: $requestId,
                previousStatus: $newStatus,
                newStatus: 'Sent to Registro',
                comment: null,
                userId: null,
            );

            $this->historyRepository->record(
                requestId: $requestId,
                previousStatus: 'Sent to Registro',
                newStatus: 'Received by Registro',
                comment: null,
                userId: null,
            );
        }

        // The closing mirror of the block above, so the timeline reads
        // through to publication instead of stopping at Registro's own
        // decision. Same narrative-marker convention.
        if (in_array($newStatus, ['Approved by Registro', 'Denied by Registro'], true)) {
            $this->historyRepository->record(
                requestId: $requestId,
                previousStatus: $newStatus,
                newStatus: 'Published by Registro',
                comment: null,
                userId: null,
            );
        }

        // Only Registro's final approval registers the credit, never
        // Docencia's. Being a final status, it can fire only once per
        // request, so no guard against double-registering is needed.
        if ($newStatus === 'Approved by Registro') {
            $this->academicRecordRegistrar->registerCredit($saved);
        }

        return $saved;
    }
}
