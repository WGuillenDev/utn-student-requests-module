<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Contracts;

use Src\Requests\Request\Domain\Entities\Request;

/**
 * Port (in the Hexagonal sense) for confirming to the student, by
 * email, that their request was received. Kept entity-only in its
 * signature — no Illuminate\Notifications, no Mailable, no
 * User/Student model — so this stays importable from Application
 * without pulling any framework type into that layer. Only the
 * Infrastructure adapter (see EloquentRequestNotifier) is allowed to
 * know how "notify" is actually carried out (mail today; could become
 * an in-app notification, SMS, etc. later without touching
 * CreateRequestUseCase at all).
 */
interface RequestNotifierInterface
{
    /**
     * @param  ?string  $batchCourseNames  Validation only: every UTN
     *   course name from the same submission — see RequestDTO's docblock.
     */
    public function notifyRequestSubmitted(Request $request, ?string $batchCourseNames = null): void;
}
