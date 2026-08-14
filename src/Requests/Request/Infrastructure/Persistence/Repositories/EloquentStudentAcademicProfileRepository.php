<?php

declare(strict_types=1);

namespace Src\Requests\Request\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Eloquent\Students\Models\AcademicRecordModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use Src\Requests\Request\Domain\Contracts\StudentAcademicProfileRepositoryInterface;

final class EloquentStudentAcademicProfileRepository implements StudentAcademicProfileRepositoryInterface
{
    /**
     * Statuses that represent completed academic progress for the
     * "accumulated courses" count — not just literally-passed-with-a-
     * grade, since credited/waived courses still move the student
     * forward in their plan.
     *
     * @var array<int, string>
     */
    private const PROGRESS_STATUSES = [
        'Approved',
        'Credited by Equivalence',
        'Credited by Validation',
        'Requirement Waived',
    ];

    public function hasApprovedCourseWithMinimumGrade(int $studentId, int $courseId, float $minimumGrade): bool
    {
        return AcademicRecordModel::query()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('status', 'Approved')
            ->where('grade', '>=', $minimumGrade)
            ->exists();
    }

    public function countApprovedCourses(int $studentId): int
    {
        return AcademicRecordModel::query()
            ->where('student_id', $studentId)
            ->whereIn('status', self::PROGRESS_STATUSES)
            ->count();
    }

    public function belongsToTerminalPlan(int $studentId): bool
    {
        return StudentModel::query()
            ->whereKey($studentId)
            ->whereHas('studyPlans', fn ($query) => $query->where('classification', 'Terminal'))
            ->exists();
    }
}
