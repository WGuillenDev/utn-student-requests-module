<?php

declare(strict_types=1);

namespace Tests\Unit\Requests\Request\Domain;

use PHPUnit\Framework\TestCase;
use Src\Requests\Request\Domain\Contracts\HolidayCalendarInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\InvalidStatusTransitionException;

/**
 * Request is pure PHP (see the entity's own docblock: "Zero framework
 * coupling"), so this suite extends PHPUnit\Framework\TestCase directly
 * — no RefreshDatabase, no Laravel bootstrap, no database needed to
 * exercise its invariants.
 */
final class RequestTest extends TestCase
{
    public function test_create_always_starts_pending_review_regardless_of_engine_result(): void
    {
        $request = Request::create(
            studentId: 1,
            type: 'Requirement Waiver',
            courseId: 10,
            requiredCourseId: 5,
            engineResult: 'Automatically Approved',
        );

        // By design (see Request::create()'s docblock): even an
        // auto-resolved waiver still requires a human reviewer at
        // Docencia to close it, so status never starts pre-approved.
        $this->assertSame('Pending Review', $request->status());
        $this->assertSame('Automatically Approved', $request->engineResult());
        $this->assertFalse($request->isFinal());
    }

    public function test_change_status_updates_status_and_reviewer(): void
    {
        $request = Request::create(studentId: 1, type: 'Requirement Waiver', courseId: 10);

        $request->changeStatus('Approved by Registro', reviewerId: 99);

        $this->assertSame('Approved by Registro', $request->status());
        $this->assertSame(99, $request->reviewerId());
        $this->assertTrue($request->isFinal());
    }

    public function test_change_status_without_reviewer_id_leaves_reviewer_untouched(): void
    {
        $request = Request::reconstitute(
            id: 1,
            studentId: 1,
            type: 'Requirement Waiver',
            courseId: 10,
            requiredCourseId: null,
            waiverJustification: null,
            originInstitution: null,
            externalCourse: null,
            validationPrecedentId: null,
            engineResult: null,
            violatedRuleId: null,
            status: 'Pending Review',
            estimatedResolutionDate: null,
            reviewerId: 42,
        );

        $request->changeStatus('Approved by Registro');

        // reviewerId is only overwritten when a new one is explicitly
        // passed — an editor re-saving without changing the assigned
        // reviewer should not accidentally clear it.
        $this->assertSame(42, $request->reviewerId());
    }

    public function test_pending_review_is_not_final(): void
    {
        $pending = Request::create(studentId: 1, type: 'Requirement Waiver', courseId: 10);

        $this->assertFalse($pending->isFinal());
    }

    /**
     * @dataProvider finalStatusProvider
     */
    public function test_a_request_in_a_final_status_cannot_change_status_again(string $finalStatus): void
    {
        $request = Request::reconstitute(
            id: 1, studentId: 1, type: 'Requirement Waiver', courseId: 10,
            requiredCourseId: null, waiverJustification: null, originInstitution: null, externalCourse: null,
            validationPrecedentId: null, engineResult: null, violatedRuleId: null,
            status: $finalStatus, estimatedResolutionDate: null, reviewerId: null,
        );

        $this->expectException(InvalidStatusTransitionException::class);

        // Resolved requests cannot be reopened — the invariant applies
        // even when the "new" status is the same as the current one.
        $request->changeStatus($finalStatus === 'Approved by Registro' ? 'Denied by Registro' : 'Approved by Registro');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function finalStatusProvider(): array
    {
        return [
            'Approved by Registro' => ['Approved by Registro'],
            'Denied by Registro' => ['Denied by Registro'],
        ];
    }

    public function test_assign_estimated_resolution_date_works_regardless_of_final_status(): void
    {
        // Deliberately not gated by isFinal() per the method's own
        // docblock: the reviewer may set this while still deciding.
        $request = Request::reconstitute(
            id: 1, studentId: 1, type: 'Requirement Waiver', courseId: 10,
            requiredCourseId: null, waiverJustification: null, originInstitution: null, externalCourse: null,
            validationPrecedentId: null, engineResult: null, violatedRuleId: null,
            status: 'Approved by Registro', estimatedResolutionDate: null, reviewerId: null,
        );

        $request->assignEstimatedResolutionDate('2026-09-01');

        $this->assertSame('2026-09-01', $request->estimatedResolutionDate());
    }

    public function test_needs_auto_estimated_date_is_false_before_24_hours_have_passed(): void
    {
        $createdAt = new \DateTimeImmutable('2026-08-10 09:00:00');
        $request = $this->reconstituteOpenRequest(createdAt: $createdAt->format('Y-m-d H:i:s'));

        $justUnder24h = $createdAt->modify('+23 hours +59 minutes');

        $this->assertFalse($request->needsAutoEstimatedDate($justUnder24h));
    }

    public function test_needs_auto_estimated_date_is_true_once_24_hours_have_passed(): void
    {
        $createdAt = new \DateTimeImmutable('2026-08-10 09:00:00');
        $request = $this->reconstituteOpenRequest(createdAt: $createdAt->format('Y-m-d H:i:s'));

        $exactly24h = $createdAt->modify('+24 hours');

        $this->assertTrue($request->needsAutoEstimatedDate($exactly24h));
    }

    public function test_needs_auto_estimated_date_is_false_when_a_date_was_already_assigned(): void
    {
        $createdAt = new \DateTimeImmutable('2026-08-01 09:00:00');
        $request = $this->reconstituteOpenRequest(
            createdAt: $createdAt->format('Y-m-d H:i:s'),
            estimatedResolutionDate: '2026-08-15',
        );

        $this->assertFalse($request->needsAutoEstimatedDate($createdAt->modify('+3 days')));
    }

    public function test_needs_auto_estimated_date_is_false_for_a_final_request(): void
    {
        $createdAt = new \DateTimeImmutable('2026-08-01 09:00:00');
        $request = Request::reconstitute(
            id: 1, studentId: 1, type: 'Requirement Waiver', courseId: 10,
            requiredCourseId: null, waiverJustification: null, originInstitution: null, externalCourse: null,
            validationPrecedentId: null, engineResult: null, violatedRuleId: null,
            status: 'Approved by Registro', estimatedResolutionDate: null, reviewerId: null,
            createdAt: $createdAt->format('Y-m-d H:i:s'),
        );

        // A closed request no longer needs an estimate, no matter how
        // much time has passed since it was created.
        $this->assertFalse($request->needsAutoEstimatedDate($createdAt->modify('+30 days')));
    }

    public function test_auto_assign_estimated_resolution_date_skips_weekends(): void
    {
        // Monday 2026-08-10 + 5 business days = Monday 2026-08-17,
        // skipping the Sat/Sun in between — this is the concrete
        // regression case for the "5 días hábiles" business rule.
        $request = $this->reconstituteOpenRequest(createdAt: '2026-08-10 09:00:00');

        $request->autoAssignEstimatedResolutionDate($this->noHolidays());

        $this->assertSame('2026-08-17', $request->estimatedResolutionDate());
    }

    public function test_auto_assign_estimated_resolution_date_from_a_friday(): void
    {
        // Friday 2026-08-14 + 5 business days: Mon 17, Tue 18, Wed 19,
        // Thu 20, Fri 21 — two full weekends never enter the count.
        $request = $this->reconstituteOpenRequest(createdAt: '2026-08-14 09:00:00');

        $request->autoAssignEstimatedResolutionDate($this->noHolidays());

        $this->assertSame('2026-08-21', $request->estimatedResolutionDate());
    }

    public function test_auto_assign_estimated_resolution_date_skips_a_holiday(): void
    {
        // Monday 2026-08-10 + 5 business days is normally 2026-08-17
        // (see the sibling test above); with 2026-08-14 (Friday)
        // reported as a holiday by the calendar port, that day no
        // longer counts and the estimate pushes to 2026-08-18.
        $request = $this->reconstituteOpenRequest(createdAt: '2026-08-10 09:00:00');

        $calendar = new class implements HolidayCalendarInterface
        {
            public function isHoliday(\DateTimeImmutable $date): bool
            {
                return $date->format('Y-m-d') === '2026-08-14';
            }
        };

        $request->autoAssignEstimatedResolutionDate($calendar);

        $this->assertSame('2026-08-18', $request->estimatedResolutionDate());
    }

    /**
     * Test double for tests that only care about the weekend logic —
     * every date is reported as a normal working day.
     */
    private function noHolidays(): HolidayCalendarInterface
    {
        return new class implements HolidayCalendarInterface
        {
            public function isHoliday(\DateTimeImmutable $date): bool
            {
                return false;
            }
        };
    }

    private function reconstituteOpenRequest(string $createdAt, ?string $estimatedResolutionDate = null): Request
    {
        return Request::reconstitute(
            id: 1,
            studentId: 1,
            type: 'Requirement Waiver',
            courseId: 10,
            requiredCourseId: null,
            waiverJustification: null,
            originInstitution: null,
            externalCourse: null,
            validationPrecedentId: null,
            engineResult: null,
            violatedRuleId: null,
            status: 'Pending Review',
            estimatedResolutionDate: $estimatedResolutionDate,
            reviewerId: null,
            createdAt: $createdAt,
        );
    }
}
