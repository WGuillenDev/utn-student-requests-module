<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Contracts;

/**
 * Port to the student's "expediente simulado" (simulated academic
 * record) that the WaiverEngine evaluates rules against. Kept separate
 * from RequestRepositoryInterface because it reads a different
 * aggregate (Students/AcademicRecords, owned by another bounded
 * context) — the engine only needs to ask yes/no questions about a
 * student's academic history, never to load or mutate those records.
 */
interface StudentAcademicProfileRepositoryInterface
{
    /**
     * Type (a): "Requisito X aprobado con nota mínima N".
     */
    public function hasApprovedCourseWithMinimumGrade(int $studentId, int $courseId, float $minimumGrade): bool;

    /**
     * Type (b): "Créditos o cursos acumulados ≥ K". No `credits` column
     * exists in this module's course catalog (out of scope per the
     * migration's documented scope cuts), so this counts approved
     * courses — Approved, plus the three "credited/waived" statuses,
     * which still represent completed academic progress even though
     * they weren't literally passed with a grade.
     */
    public function countApprovedCourses(int $studentId): int;

    /**
     * Type (c): "Pertenencia del estudiante a un plan terminal".
     */
    public function belongsToTerminalPlan(int $studentId): bool;
}
