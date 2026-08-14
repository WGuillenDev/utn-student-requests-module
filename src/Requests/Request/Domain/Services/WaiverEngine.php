<?php

declare(strict_types=1);

namespace Src\Requests\Request\Domain\Services;

use Src\Requests\Request\Domain\Contracts\StudentAcademicProfileRepositoryInterface;
use Src\Requests\Request\Domain\ValueObjects\WaiverEngineDecision;
use Src\Requests\WaiverRule\Domain\Entities\WaiverRule;

/**
 * ES-01's rules engine. Pure PHP — no Illuminate, no Livewire — takes
 * the course's configured rules and the student's academic profile
 * port, and returns one of three conclusive outcomes.
 *
 * Design decision (documented in the diario): each active rule, when
 * evaluated, is always conclusive — it either grants the waiver or
 * denies it against that specific rule's criterion. This means the
 * first *active* rule in configured order is authoritative; `order`
 * exists so Docencia can pick which single criterion applies, and so
 * an inactive rule can be skipped in favor of the next one without
 * deleting it. "Always manual review" is the deliberate escape hatch:
 * placing it first forces manual review regardless of other rules.
 * A course with no active rules falls through to manual review, per
 * ES-01's "curso sin criterios configurados" acceptance criterion.
 */
final class WaiverEngine
{
    public function __construct(
        private readonly StudentAcademicProfileRepositoryInterface $profile,
    ) {}

    /**
     * @param array<int, WaiverRule> $rules Rules for the target course,
     *   in configured order — fetching and sorting is the caller's job.
     */
    public function evaluate(int $studentId, array $rules): WaiverEngineDecision
    {
        foreach ($rules as $rule) {
            if (! $rule->active()) {
                continue;
            }

            $decision = match ($rule->type()) {
                'Approved requirement with minimum grade' => $this->evaluateMinimumGrade($studentId, $rule),
                'Accumulated credits or courses' => $this->evaluateAccumulated($studentId, $rule),
                'Terminal plan membership' => $this->evaluateTerminalPlan($studentId, $rule),
                'Always manual review' => WaiverEngineDecision::manualReview(),
                default => null,
            };

            if ($decision !== null) {
                return $decision;
            }
        }

        return WaiverEngineDecision::manualReview();
    }

    private function evaluateMinimumGrade(int $studentId, WaiverRule $rule): WaiverEngineDecision
    {
        $met = $rule->requiredCourseId() !== null
            && $rule->minimumGrade() !== null
            && $this->profile->hasApprovedCourseWithMinimumGrade($studentId, $rule->requiredCourseId(), $rule->minimumGrade());

        return $met ? WaiverEngineDecision::approved() : WaiverEngineDecision::denied((int) $rule->id());
    }

    private function evaluateAccumulated(int $studentId, WaiverRule $rule): WaiverEngineDecision
    {
        $met = $rule->minimumAccumulated() !== null
            && $this->profile->countApprovedCourses($studentId) >= $rule->minimumAccumulated();

        return $met ? WaiverEngineDecision::approved() : WaiverEngineDecision::denied((int) $rule->id());
    }

    private function evaluateTerminalPlan(int $studentId, WaiverRule $rule): WaiverEngineDecision
    {
        return $this->profile->belongsToTerminalPlan($studentId)
            ? WaiverEngineDecision::approved()
            : WaiverEngineDecision::denied((int) $rule->id());
    }
}
