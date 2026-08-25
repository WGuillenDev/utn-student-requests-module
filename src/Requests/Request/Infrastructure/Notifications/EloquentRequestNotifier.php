<?php

declare(strict_types=1);

namespace Src\Requests\Request\Infrastructure\Notifications;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use Src\Requests\Request\Domain\Contracts\RequestNotifierInterface;
use Src\Requests\Request\Domain\Entities\Request;

final class EloquentRequestNotifier implements RequestNotifierInterface
{
    public function notifyRequestSubmitted(Request $request): void
    {
        $student = StudentModel::query()->with('user')->find($request->studentId());

        // A student without a linked user account (or a soft-deleted
        // one) simply can't receive mail — this is not an error worth
        // failing the submission over, the request itself already
        // succeeded and was persisted.
        if ($student === null || $student->user === null) {
            return;
        }

        $course = CourseModel::query()->find($request->courseId());
        $courseLabel = $course ? "{$course->code} — {$course->name}" : (string) $request->courseId();

        $student->user->notify(
            new RequestSubmittedNotification($request, $courseLabel),
        );
    }
}
