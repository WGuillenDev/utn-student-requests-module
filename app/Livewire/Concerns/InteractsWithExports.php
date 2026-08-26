<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel/PDF export helpers for any CRUD Livewire component.
 *
 * Each component supplies its own columns as {key, label, format?} pairs
 * — the same shape as <x-ui.data-table>'s headers prop, so on-screen and
 * exported columns share one source of truth.
 *
 * Authorization is not handled here: call $this->authorize(...) in the
 * component's own export action first. See RoleComponent for an example.
 *
 * Every method accepts an `iterable`, so the pipeline stays constant-
 * memory when a repository feeds it a lazy source such as a DB cursor.
 */
trait InteractsWithExports
{
    /**
     * @param array<int, array{key: string, label: string, format?: callable}> $headers
     * @param iterable<array<string, mixed>> $rows
     */
    protected function streamExcel(array $headers, iterable $rows, string $filename, ExcelExporterInterface $exporter): StreamedResponse
    {
        return $exporter->streamDownload($this->mapRowsForExport($headers, $rows), $filename);
    }

    /**
     * @param array<int, array{key: string, label: string, format?: callable}> $headers
     * @param iterable<array<string, mixed>> $rows
     * @param string $paperSize Passed through to PdfExporterInterface.
     */
    protected function streamPdf(string $title, array $headers, iterable $rows, string $filename, PdfExporterInterface $exporter, string $paperSize = 'a4'): StreamedResponse
    {
        $html = view('exports.table-pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $this->mapRowsForExport($headers, $rows),
        ])->render();

        return $exporter->fromHtml($html, $filename, $paperSize);
    }

    /**
     * Projects each row down to the exportable columns, keyed by label so
     * the header row reads "Nombre" rather than "name". A generator, to
     * keep the pipeline lazy when the caller supplies a lazy source.
     *
     * @param array<int, array{key: string, label: string, format?: callable}> $headers
     * @param iterable<array<string, mixed>> $rows
     * @return \Generator<int, array<string, mixed>>
     */
    private function mapRowsForExport(array $headers, iterable $rows): \Generator
    {
        foreach ($rows as $row) {
            $mapped = [];

            foreach ($headers as $header) {
                $value = $row[$header['key']] ?? '';
                $mapped[$header['label']] = isset($header['format']) ? ($header['format'])($value) : $value;
            }

            yield $mapped;
        }
    }
}
