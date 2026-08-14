<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Exceptions;

use DomainException;

/**
 * Thrown by CreateRequestUseCase when the student already has an
 * Approved waiver for the same course + unmet requirement. Mirrors
 * ES-01's acceptance criterion literally: "Este levantamiento ya fue
 * procesado previamente" — no duplicate request is created.
 */
final class DuplicateWaiverRequestException extends DomainException
{
    public static function create(): self
    {
        return new self('This waiver has already been processed previously.');
    }
}
