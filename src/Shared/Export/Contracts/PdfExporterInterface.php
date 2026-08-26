<?php

declare(strict_types=1);

namespace Src\Shared\Export\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Port for rendering HTML to a downloadable PDF.
 *
 * Takes raw HTML rather than a view name, so the contract does not
 * assume Blade; rendering the view to a string is Presentation's job.
 * Shared across entities for the same reason as ExcelExporterInterface.
 */
interface PdfExporterInterface
{
    /**
     * @param string $paperSize Any size the renderer accepts ('a4',
     *        'letter', ...). The call site passes it explicitly when its
     *        template was designed for a specific size.
     */
    public function fromHtml(string $html, string $filename, string $paperSize = 'a4'): StreamedResponse;
}
