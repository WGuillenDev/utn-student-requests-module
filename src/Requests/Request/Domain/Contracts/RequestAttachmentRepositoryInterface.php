<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Contracts;

use Src\Requests\Request\Domain\ValueObjects\RequestAttachment;

/**
 * Port (in the Hexagonal sense) that Infrastructure adapters must
 * implement. The Domain and Application layers depend only on this
 * abstraction — never on Eloquent, the database, or any concrete driver.
 */
interface RequestAttachmentRepositoryInterface
{
    /**
     * @param array<int, RequestAttachment> $attachments
     */
    public function attach(int $requestId, array $attachments): void;
}
