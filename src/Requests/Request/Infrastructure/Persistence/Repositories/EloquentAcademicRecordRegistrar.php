<?php

declare(strict_types=1);

namespace Src\Requests\Request\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Eloquent\Students\Models\AcademicRecordModel;
use Src\Requests\Request\Domain\Contracts\AcademicRecordRegistrarInterface;
use Src\Requests\Request\Domain\Entities\Request;

final class EloquentAcademicRecordRegistrar implements AcademicRecordRegistrarInterface
{
    public function registerCredit(Request $request): void
    {
        $status = $request->type() === 'Requirement Waiver'
            ? 'Requirement Waived'
            : 'Credited by Validation';

        AcademicRecordModel::query()->updateOrCreate(
            ['student_id' => $request->studentId(), 'course_id' => $request->courseId()],
            ['status' => $status, 'equivalence_id' => $request->id()],
        );
    }
}
