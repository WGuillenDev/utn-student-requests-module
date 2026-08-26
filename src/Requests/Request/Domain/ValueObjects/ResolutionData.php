<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\ValueObjects;

/**
 * Pure PHP — zero framework coupling. The metadata Registro types into
 * the "Emitir resolución (RSREC-001)" panel: the formal identifiers of
 * the session where the resolution was taken. Carried as one VO so the
 * generator/notifier signatures don't grow four parallel string params.
 */
final class ResolutionData
{
    public function __construct(
        public readonly string $resolutionNumber,
        public readonly string $sessionNumber,
        public readonly string $actNumber,
        public readonly string $sessionDate,
    ) {}
}
