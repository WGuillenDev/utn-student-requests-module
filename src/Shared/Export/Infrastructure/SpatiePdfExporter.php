<?php

declare(strict_types=1);

namespace Src\Shared\Export\Infrastructure;

use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renders HTML to PDF via spatie/laravel-pdf, which drives headless
 * Chromium through Browsershot.
 *
 * Notable choices, each the result of a concrete failure during setup:
 *  - generatePdfContent() instead of the package's Responsable path,
 *    which returns base64 rather than a binary stream under Livewire
 *    (spatie/laravel-pdf discussion #120).
 *  - setNodeModulePath() resolves puppeteer explicitly; the implicit
 *    lookup fails when PHP spawns Node with public/ as its cwd.
 *    Requires `npm install puppeteer` at the project root.
 *  - timeout(15) stays under PHP's 30s max_execution_time so a hung
 *    Chrome raises a catchable exception instead of a fatal error.
 *  - showBackground() is required; Chrome's print engine drops
 *    background colors by default, which would strip the report styling.
 *  - The Chromium arguments disable startup networking and telemetry.
 *    They affect only startup cost and reliability, never the output.
 */
final class SpatiePdfExporter implements PdfExporterInterface
{
    public function fromHtml(string $html, string $filename, string $paperSize = 'a4'): StreamedResponse
    {
        $pdfBytes = Pdf::html($html)
            ->format($paperSize)
            ->withBrowsershot(function (Browsershot $browsershot): void {
                $browsershot
                    ->setNodeModulePath(base_path('node_modules'))
                    ->timeout(15)
                    ->showBackground()
                    ->addChromiumArguments([
                        'disable-extensions',
                        'disable-background-networking',
                        'disable-default-apps',
                        'disable-sync',
                        'disable-translate',
                        'metrics-recording-only',
                        'mute-audio',
                        'no-first-run',
                        'safebrowsing-disable-auto-update',
                        'disable-gpu',
                        'disable-dev-shm-usage',
                        'disable-software-rasterizer',
                    ]);
            })
            ->generatePdfContent();

        // The whole PDF is already in memory, so Content-Length can be set
        // exactly — the browser shows a real progress bar rather than an
        // unknown-size spinner.
        return response()->streamDownload(function () use ($pdfBytes): void {
            echo $pdfBytes;
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) strlen($pdfBytes),
        ]);
    }
}
