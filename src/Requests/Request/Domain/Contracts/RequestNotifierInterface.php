<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Contracts;

use Src\Requests\Request\Domain\Entities\Request;

/**
 * Port (in the Hexagonal sense) for the submission-confirmation email the
 * module sends the student. Kept entity-only in its signature — no
 * Illuminate\Notifications, no Mailable, no User/Student model — so this
 * stays importable from Application without pulling any framework type
 * into that layer. Only the Infrastructure adapter (see
 * EloquentRequestNotifier) is allowed to know how "notify" is actually
 * carried out (mail today; could become an in-app notification, SMS,
 * etc. later without touching the use cases at all). The final
 * resolution outcome is not emailed — the student checks it by logging
 * into the system.
 */
interface RequestNotifierInterface
{
    /**
     * @param  ?string  $batchCourseNames  Validation only: every UTN
     *   course name from the same submission — see RequestDTO's docblock.
     */
    public function notifyRequestSubmitted(Request $request, ?string $batchCourseNames = null): void;
}
