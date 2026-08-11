<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Exceptions;

use DomainException;

final class RequestNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Request with id [{$id}] was not found.");
    }
}
