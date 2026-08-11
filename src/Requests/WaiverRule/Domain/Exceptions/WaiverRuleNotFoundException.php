<?php

declare(strict_types=1);

namespace Src\Requests\WaiverRule\Domain\Exceptions;

use DomainException;

final class WaiverRuleNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("WaiverRule with id [{$id}] was not found.");
    }
}
