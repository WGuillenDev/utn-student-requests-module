<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Domain\Contracts\RequestAttachmentRepositoryInterface;
use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Contracts\ResolutionDocumentGeneratorInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\InvalidStatusTransitionException;
use Src\Requests\Request\Domain\Exceptions\RequestNotFoundException;
use Src\Requests\Request\Domain\ValueObjects\RequestAttachment;
use Src\Requests\Request\Domain\ValueObjects\ResolutionData;

/**
 * Registro's "Publicar y Aprobar/Denegar la resolución" (RSREC-001): the
 * single action that closes a request for good. $decision is Registro's
 * own explicit call — 'Approved by Registro' or 'Denied by Registro' —
 * not derived from Docencia's verdict; the "Docencia resolvió: ..."
 * badge next to the panel is context only, Registro can still publish
 * the opposite outcome if that's its own decision. In order: the status
 * change goes through ChangeRequestStatusUseCase first (Domain invariant
 * + history row + academic-credit registration on approval, all
 * unchanged), then the resolution document is generated against the
 * now-final status, archived on the request's own attachment list along
 * with $additionalAttachment (Registro's own optional supporting file,
 * already moved to permanent storage by the Presentation layer before
 * this runs — see RequestComponent::issueResolution()). No email is sent
 * here: the student checks the outcome by logging into the system.
 */
final class IssueResolutionUseCase
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
        private readonly ChangeRequestStatusUseCase $changeStatus,
        private readonly ResolutionDocumentGeneratorInterface $documentGenerator,
        private readonly RequestAttachmentRepositoryInterface $attachmentRepository,
    ) {}

    public function handle(int $requestId, string $decision, ResolutionData $resolution, ?int $reviewerId = null, ?RequestAttachment $additionalAttachment = null): Request
    {
        $request = $this->repository->find($requestId) ?? throw RequestNotFoundException::withId($requestId);

        if (! in_array($request->status(), ['Approved by Docencia', 'Denied by Docencia'], true)) {
            throw new InvalidStatusTransitionException(
                __('A resolution can only be issued once Docencia has resolved the request.'),
            );
        }

        $saved = $this->changeStatus->handle(
            requestId: $requestId,
            newStatus: $decision,
            reviewerId: $reviewerId,
            comment: __('Resolution :number issued', ['number' => $resolution->resolutionNumber]),
        );

        $document = $this->documentGenerator->generate($saved, $resolution);
        $attachments = $additionalAttachment !== null ? [$document, $additionalAttachment] : [$document];
        $this->attachmentRepository->attach($requestId, $attachments);

        return $saved;
    }
}
