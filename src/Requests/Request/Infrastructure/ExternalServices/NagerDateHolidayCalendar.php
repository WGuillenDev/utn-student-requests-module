<?php

declare(strict_types=1);

namespace Src\Requests\Request\Infrastructure\ExternalServices;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\Requests\Request\Domain\Contracts\HolidayCalendarInterface;

/**
 * Consumes the Nager.Date public holidays REST API
 * (https://date.nager.at) for Costa Rica ('CR'), so ES-03's "5 días
 * hábiles" skips national holidays instead of just weekends — the
 * limitation the Domain entity's docblock used to call out of scope.
 *
 * One HTTP call per calendar year, cached for 24h: a year's holidays
 * never change mid-day, and this keeps a burst of status changes from
 * hammering a third-party API on every single request review.
 *
 * Resilience: if the API is unreachable, times out, or returns a
 * non-2xx response, this fails OPEN (treats the date as not a
 * holiday) rather than blocking the reviewer's workflow — ES-03 has
 * no acceptance criterion that depends on holidays being excluded,
 * only that the fallback of "5 business days" gets assigned at all.
 * The failure is logged so a persistent outage is still visible.
 */
final class NagerDateHolidayCalendar implements HolidayCalendarInterface
{
    private const BASE_URL = 'https://date.nager.at/api/v3/PublicHolidays';

    private const COUNTRY_CODE = 'CR';

    public function isHoliday(\DateTimeImmutable $date): bool
    {
        $holidays = $this->holidaysForYear((int) $date->format('Y'));

        return in_array($date->format('Y-m-d'), $holidays, true);
    }

    /**
     * @return array<int, string> Dates in 'Y-m-d' format.
     */
    private function holidaysForYear(int $year): array
    {
        return Cache::remember(
            "holiday-calendar:cr:{$year}",
            now()->addDay(),
            function () use ($year): array {
                try {
                    $response = Http::timeout(3)->get(self::BASE_URL.'/'.$year.'/'.self::COUNTRY_CODE);

                    if (! $response->successful()) {
                        Log::warning('Holiday calendar API returned a non-successful response.', [
                            'year' => $year,
                            'status' => $response->status(),
                        ]);

                        return [];
                    }

                    return collect($response->json())
                        ->pluck('date')
                        ->all();
                } catch (\Throwable $e) {
                    Log::warning('Holiday calendar API call failed; treating the year as having no holidays.', [
                        'year' => $year,
                        'error' => $e->getMessage(),
                    ]);

                    return [];
                }
            },
        );
    }
}
