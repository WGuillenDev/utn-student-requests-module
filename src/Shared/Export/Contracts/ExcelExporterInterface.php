<?php

declare(strict_types=1);

namespace Src\Shared\Export\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Port for streaming tabular data to the browser as a spreadsheet.
 *
 * Entity-agnostic on purpose: exporting rows needs no domain knowledge,
 * so every CRUD shares this one contract rather than each declaring its
 * own. The Symfony HttpFoundation return type is Laravel's own base, not
 * an infrastructure leak; export-library types stay in the adapter.
 */
interface ExcelExporterInterface
{
    /**
     * @param iterable<array<string, scalar|null>> $rows One row per element;
     *        the first row's keys become the header. Any iterable is
     *        accepted so a lazy source never has to be fully materialized.
     */
    public function streamDownload(iterable $rows, string $filename): StreamedResponse;
}
