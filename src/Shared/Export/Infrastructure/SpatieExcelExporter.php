<?php

declare(strict_types=1);

namespace Src\Shared\Export\Infrastructure;

use Spatie\SimpleExcel\SimpleExcelWriter;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams rows via spatie/simple-excel, keeping memory flat regardless
 * of row count.
 *
 * Two deliberate choices:
 *  - SimpleExcelWriter::streamDownload() rather than create('php://output'),
 *    because create() picks its writer from the path's file extension and
 *    'php://output' has none, raising UnsupportedTypeException.
 *  - addRow()/close() driven from our own streamDownload() callback rather
 *    than the package's toBrowser(), which calls exit() internally and
 *    would kill the Livewire request mid-flight (spatie/simple-excel #143).
 */
final class SpatieExcelExporter implements ExcelExporterInterface
{
    /**
     * Flush interval, so bytes reach the browser progressively and a large
     * export starts downloading immediately.
     */
    private const FLUSH_EVERY = 1000;

    public function streamDownload(iterable $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows, $filename): void {
            $writer = SimpleExcelWriter::streamDownload($filename);

            $count = 0;

            foreach ($rows as $row) {
                $writer->addRow($row);

                if (++$count % self::FLUSH_EVERY === 0) {
                    flush();
                }
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
