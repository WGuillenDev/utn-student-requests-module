<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Exceptions;

use DomainException;

/**
 * Raised when an attempt is made to change the status of a request that
 * is already in a final status (Approved / Denied).
 */
final class InvalidStatusTransitionException extends DomainException
{
    public static function fromFinalStatus(string $currentStatus, string $attemptedStatus): self
    {
        return new self(
            "Request is already [{$currentStatus}] and cannot be moved to [{$attemptedStatus}]. ".
            'Resolved requests cannot be reopened; a new request must be filed instead.'
        );
    }
}
