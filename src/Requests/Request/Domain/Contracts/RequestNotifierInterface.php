<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Contracts;

use Src\Requests\Request\Domain\Entities\Request;

/**
 * Port (in the Hexagonal sense) for ES-03's "email notifications on
 * every status change." Kept entity-only in its signature — no
 * Illuminate\Notifications, no Mailable, no User/Student model — so
 * this stays importable from Application without pulling any framework
 * type into that layer. Only the Infrastructure adapter (see
 * EloquentRequestNotifier) is allowed to know how "notify" is actually
 * carried out (mail today; could become an in-app notification, SMS,
 * etc. later without touching ChangeRequestStatusUseCase at all).
 */
interface RequestNotifierInterface
{
    public function notifyStatusChanged(Request $request, string $previousStatus): void;
}
