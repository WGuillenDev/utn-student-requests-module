<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\Request\Domain;

use PHPUnit\Framework\TestCase;
use Src\Requests\Request\Domain\Contracts\StudentAcademicProfileRepositoryInterface;
use Src\Requests\Request\Domain\Services\WaiverEngine;
use Src\Requests\WaiverRule\Domain\Entities\WaiverRule;

/**
 * WaiverEngine is pure PHP with a single Domain-level dependency
 * (StudentAcademicProfileRepositoryInterface), so it's exercised here
 * against an in-memory fake of that port rather than an Eloquent
 * implementation — no database needed to prove ES-01's rule ordering
 * and per-type evaluation logic.
 */
final class WaiverEngineTest extends TestCase
{
    public function test_a_course_with_no_rules_falls_through_to_manual_review(): void
    {
        // ES-01's "curso sin criterios configurados" acceptance
        // criterion.
        $engine = new WaiverEngine(new InMemoryStudentAcademicProfile());

        $decision = $engine->evaluate(studentId: 1, rules: []);

        $this->assertSame('Requires Manual Review', $decision->result());
        $this->assertNull($decision->violatedRuleId());
    }

    public function test_an_inactive_rule_is_skipped_in_favor_of_the_next_active_one(): void
    {
        $profile = new InMemoryStudentAcademicProfile(approvedCoursesCount: 20);
        $engine = new WaiverEngine($profile);

        $inactiveRule = WaiverRule::reconstitute(
            id: 1, courseId: 10, order: 1, type: 'Terminal plan membership',
            requiredCourseId: null, minimumGrade: null, minimumAccumulated: null, active: false,
        );
        $activeRule = WaiverRule::reconstitute(
            id: 2, courseId: 10, order: 2, type: 'Accumulated credits or courses',
            requiredCourseId: null, minimumGrade: null, minimumAccumulated: 15, active: true,
        );

        $decision = $engine->evaluate(studentId: 1, rules: [$inactiveRule, $activeRule]);

        // The inactive Terminal-plan rule (which the fake profile would
        // deny — belongsToTerminalPlan defaults false) is skipped
        // entirely; the active Accumulated-credits rule is authoritative.
        $this->assertSame('Automatically Approved', $decision->result());
    }

    public function test_minimum_grade_rule_approves_when_the_profile_meets_it(): void
    {
        $profile = new InMemoryStudentAcademicProfile(
            approvedWithMinimumGrade: [10 => true],
        );
        $engine = new WaiverEngine($profile);

        $rule = WaiverRule::reconstitute(
            id: 1, courseId: 20, order: 1, type: 'Approved requirement with minimum grade',
            requiredCourseId: 10, minimumGrade: 80.0, minimumAccumulated: null, active: true,
        );

        $decision = $engine->evaluate(studentId: 1, rules: [$rule]);

        $this->assertSame('Automatically Approved', $decision->result());
        $this->assertNull($decision->violatedRuleId());
    }

    public function test_minimum_grade_rule_denies_and_names_the_violated_rule_when_not_met(): void
    {
        $profile = new InMemoryStudentAcademicProfile(
            approvedWithMinimumGrade: [10 => false],
        );
        $engine = new WaiverEngine($profile);

        $rule = WaiverRule::reconstitute(
            id: 7, courseId: 20, order: 1, type: 'Approved requirement with minimum grade',
            requiredCourseId: 10, minimumGrade: 90.0, minimumAccumulated: null, active: true,
        );

        $decision = $engine->evaluate(studentId: 1, rules: [$rule]);

        $this->assertSame('Not Approved', $decision->result());
        $this->assertSame(7, $decision->violatedRuleId());
    }

    public function test_minimum_grade_rule_is_denied_when_required_course_or_grade_is_missing(): void
    {
        // A malformed rule (missing its own required fields) must fail
        // safe — deny, never silently approve.
        $engine = new WaiverEngine(new InMemoryStudentAcademicProfile());

        $rule = WaiverRule::reconstitute(
            id: 3, courseId: 20, order: 1, type: 'Approved requirement with minimum grade',
            requiredCourseId: null, minimumGrade: null, minimumAccumulated: null, active: true,
        );

        $decision = $engine->evaluate(studentId: 1, rules: [$rule]);

        $this->assertSame('Not Approved', $decision->result());
        $this->assertSame(3, $decision->violatedRuleId());
    }

    public function test_accumulated_credits_rule_approves_at_exactly_the_threshold(): void
    {
        $profile = new InMemoryStudentAcademicProfile(approvedCoursesCount: 12);
        $engine = new WaiverEngine($profile);

        $rule = WaiverRule::reconstitute(
            id: 1, courseId: 20, order: 1, type: 'Accumulated credits or courses',
            requiredCourseId: null, minimumGrade: null, minimumAccumulated: 12, active: true,
        );

        $decision = $engine->evaluate(studentId: 1, rules: [$rule]);

        $this->assertSame('Automatically Approved', $decision->result());
    }

    public function test_accumulated_credits_rule_denies_below_the_threshold(): void
    {
        $profile = new InMemoryStudentAcademicProfile(approvedCoursesCount: 5);
        $engine = new WaiverEngine($profile);

        $rule = WaiverRule::reconstitute(
            id: 4, courseId: 20, order: 1, type: 'Accumulated credits or courses',
            requiredCourseId: null, minimumGrade: null, minimumAccumulated: 12, active: true,
        );

        $decision = $engine->evaluate(studentId: 1, rules: [$rule]);

        $this->assertSame('Not Approved', $decision->result());
        $this->assertSame(4, $decision->violatedRuleId());
    }

    public function test_terminal_plan_rule_approves_a_member_and_denies_a_non_member(): void
    {
        $member = new WaiverEngine(new InMemoryStudentAcademicProfile(belongsToTerminalPlan: true));
        $nonMember = new WaiverEngine(new InMemoryStudentAcademicProfile(belongsToTerminalPlan: false));

        $rule = WaiverRule::reconstitute(
            id: 9, courseId: 20, order: 1, type: 'Terminal plan membership',
            requiredCourseId: null, minimumGrade: null, minimumAccumulated: null, active: true,
        );

        $this->assertSame('Automatically Approved', $member->evaluate(1, [$rule])->result());
        $this->assertSame('Not Approved', $nonMember->evaluate(1, [$rule])->result());
        $this->assertSame(9, $nonMember->evaluate(1, [$rule])->violatedRuleId());
    }

    public function test_always_manual_review_short_circuits_earlier_rules_when_ordered_first(): void
    {
        // "Placing it first forces manual review regardless of other
        // rules" — the engine's own docblock. A rule that would
        // otherwise auto-approve never gets evaluated.
        $profile = new InMemoryStudentAcademicProfile(belongsToTerminalPlan: true);
        $engine = new WaiverEngine($profile);

        $manualFirst = WaiverRule::reconstitute(
            id: 1, courseId: 20, order: 1, type: 'Always manual review',
            requiredCourseId: null, minimumGrade: null, minimumAccumulated: null, active: true,
        );
        $wouldAutoApprove = WaiverRule::reconstitute(
            id: 2, courseId: 20, order: 2, type: 'Terminal plan membership',
            requiredCourseId: null, minimumGrade: null, minimumAccumulated: null, active: true,
        );

        $decision = $engine->evaluate(studentId: 1, rules: [$manualFirst, $wouldAutoApprove]);

        $this->assertSame('Requires Manual Review', $decision->result());
    }

    public function test_the_first_active_rule_in_order_is_authoritative_over_later_ones(): void
    {
        // Two active rules that would each resolve differently — the
        // engine must stop at the first one, per its own docblock:
        // "the first active rule in configured order is authoritative".
        $profile = new InMemoryStudentAcademicProfile(approvedCoursesCount: 0, belongsToTerminalPlan: true);
        $engine = new WaiverEngine($profile);

        $firstDenies = WaiverRule::reconstitute(
            id: 1, courseId: 20, order: 1, type: 'Accumulated credits or courses',
            requiredCourseId: null, minimumGrade: null, minimumAccumulated: 5, active: true,
        );
        $secondApproves = WaiverRule::reconstitute(
            id: 2, courseId: 20, order: 2, type: 'Terminal plan membership',
            requiredCourseId: null, minimumGrade: null, minimumAccumulated: null, active: true,
        );

        $decision = $engine->evaluate(studentId: 1, rules: [$firstDenies, $secondApproves]);

        $this->assertSame('Not Approved', $decision->result());
        $this->assertSame(1, $decision->violatedRuleId());
    }
}

/**
 * In-memory test double for StudentAcademicProfileRepositoryInterface.
 * Every constructor argument defaults to the "denies everything" case,
 * so each test only wires the one signal it actually needs.
 */
final class InMemoryStudentAcademicProfile implements StudentAcademicProfileRepositoryInterface
{
    /**
     * @param array<int, bool> $approvedWithMinimumGrade Keyed by courseId.
     */
    public function __construct(
        private readonly array $approvedWithMinimumGrade = [],
        private readonly int $approvedCoursesCount = 0,
        private readonly bool $belongsToTerminalPlan = false,
    ) {}

    public function hasApprovedCourseWithMinimumGrade(int $studentId, int $courseId, float $minimumGrade): bool
    {
        return $this->approvedWithMinimumGrade[$courseId] ?? false;
    }

    public function countApprovedCourses(int $studentId): int
    {
        return $this->approvedCoursesCount;
    }

    public function belongsToTerminalPlan(int $studentId): bool
    {
        return $this->belongsToTerminalPlan;
    }
}
