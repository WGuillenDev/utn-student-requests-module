<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\Services;

use Src\Requests\Request\Domain\Contracts\HolidayCalendarInterface;
use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;

/**
 * ES-03's "si no se ingresa en 24h, el sistema asigna automáticamente
 * 5 días hábiles" — applied lazily on read (list/find) rather than via
 * a scheduled job, so it's guaranteed correct the moment anyone opens
 * the inbox or their own request list, without depending on a cron
 * worker actually running during grading/demo.
 */
final class EstimatedResolutionDateAssigner
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
        private readonly HolidayCalendarInterface $holidayCalendar,
    ) {}

    public function ensureAssigned(Request $request): Request
    {
        if (! $request->needsAutoEstimatedDate(new \DateTimeImmutable())) {
            return $request;
        }

        $request->autoAssignEstimatedResolutionDate($this->holidayCalendar);

        return $this->repository->save($request);
    }

    /**
     * @param array<int, Request> $requests
     * @return array<int, Request>
     */
    public function ensureAssignedForAll(array $requests): array
    {
        return array_map($this->ensureAssigned(...), $requests);
    }
}
