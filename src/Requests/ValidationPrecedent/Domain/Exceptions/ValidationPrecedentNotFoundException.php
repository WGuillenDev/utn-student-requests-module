<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Domain\Exceptions;

use DomainException;

final class ValidationPrecedentNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("ValidationPrecedent with id [{$id}] was not found.");
    }
}
