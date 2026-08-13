<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\ValueObjects;

/**
 * Pure PHP — zero framework coupling. Describes a single file already
 * written to disk (Presentation layer resolved the path/hash/mime via
 * Livewire's upload handling) waiting to be persisted as a `files` row
 * once the owning Request has an id.
 */
final class RequestAttachment
{
    public function __construct(
        public readonly string $documentType,
        public readonly string $originalName,
        public readonly string $disk,
        public readonly string $path,
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly string $hashSha256,
    ) {}
}
