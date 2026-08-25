<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Contracts;

use Src\Requests\Request\Domain\Entities\Request;

/**
 * Port for turning an Approved request into academic progress the
 * student's record (and the "Approved/Failed/Credited/Credits"
 * summary Docencia sees in the request detail modal) actually reflects.
 * Kept entity-only in its signature for the same reason as
 * RequestNotifierInterface — no Eloquent/AcademicRecordModel leaking
 * into Application.
 */
interface AcademicRecordRegistrarInterface
{
    public function registerCredit(Request $request): void;
}
