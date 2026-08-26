<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Contracts;

use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\ValueObjects\RequestAttachment;
use Src\Requests\Request\Domain\ValueObjects\ResolutionData;

/**
 * Port (in the Hexagonal sense) for producing the formal resolution
 * document (RSREC-001) as a stored file. Entity/VO-only signature — the
 * Infrastructure adapter (BrowsershotResolutionDocumentGenerator) is the
 * only place that knows the document is a Blade-rendered PDF written to
 * a Laravel storage disk.
 */
interface ResolutionDocumentGeneratorInterface
{
    /**
     * Renders and persists the resolution document for $request, returning
     * the attachment descriptor ready for
     * RequestAttachmentRepositoryInterface::attach().
     */
    public function generate(Request $request, ResolutionData $resolution): RequestAttachment;
}
