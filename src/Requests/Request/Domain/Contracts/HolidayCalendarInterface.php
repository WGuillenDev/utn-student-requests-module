<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Contracts;

/**
 * Port for ES-03's "5 días hábiles" calculation. The Domain only needs
 * to ask "is this date a non-working day?" — it has no idea (and must
 * not know) that the answer comes from an external REST API. The
 * concrete adapter (Infrastructure) is free to call a third-party
 * service, read a static table, or return false always; Request::
 * autoAssignEstimatedResolutionDate() behaves identically either way.
 */
interface HolidayCalendarInterface
{
    public function isHoliday(\DateTimeImmutable $date): bool;
}
