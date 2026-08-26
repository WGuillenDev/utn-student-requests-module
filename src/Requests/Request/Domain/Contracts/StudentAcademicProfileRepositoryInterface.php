<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Contracts;

/**
 * Port to the student academic record the WaiverEngine evaluates rules
 * against. Separate from RequestRepositoryInterface because it reads an
 * aggregate owned by another bounded context, and only ever asks yes/no
 * questions about it — never loads or mutates those records.
 */
interface StudentAcademicProfileRepositoryInterface
{
    /**
     * Type (a): "Requisito X aprobado con nota mínima N".
     */
    public function hasApprovedCourseWithMinimumGrade(int $studentId, int $courseId, float $minimumGrade): bool;

    /**
     * Type (b): "Créditos o cursos acumulados ≥ K". Counts courses, not
     * credits — the course catalog has no credits column in this module's
     * scope. Includes the credited/waived statuses, which still count as
     * completed progress.
     */
    public function countApprovedCourses(int $studentId): int;

    /**
     * Type (c): "Pertenencia del estudiante a un plan terminal".
     */
    public function belongsToTerminalPlan(int $studentId): bool;
}
