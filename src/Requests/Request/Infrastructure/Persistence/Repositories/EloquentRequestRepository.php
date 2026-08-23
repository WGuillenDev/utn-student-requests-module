<?php

declare(strict_types=1);

namespace Src\Requests\Request\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Eloquent\Requests\Models\RequestModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;

final class EloquentRequestRepository implements RequestRepositoryInterface
{
    /**
     * Explicit allow-list — $sortBy ultimately reaches raw SQL via orderBy(),
     * and Livewire action arguments are client-controllable, so this is not
     * optional hardening.
     *
     * @var array<int, string>
     */
    private const SORTABLE_COLUMNS = ['type', 'status', 'created_at', 'estimated_resolution_date'];

    public function find(int $id): ?Request
    {
        $model = RequestModel::query()->with('student')->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc', array $filters = []): array
    {
        $query = $this->baseQuery($search, $filters);

        $column = in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'created_at';
        $direction = $sortDir === 'desc' ? 'desc' : 'asc';

        /** @var Collection<int, RequestModel> $models */
        $models = $query->orderBy($column, $direction)->get();

        return $models->map($this->toDomain(...))->all();
    }

    public function paginate(
        ?string $search,
        int $perPage,
        int $page,
        ?string $sortBy = null,
        string $sortDir = 'asc',
        array $filters = [],
    ): array {
        $query = $this->baseQuery($search, $filters);

        $column = in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'created_at';
        $direction = $sortDir === 'desc' ? 'desc' : 'asc';

        $paginator = $query->orderBy($column, $direction)->paginate(perPage: $perPage, page: $page);

        return [
            'items' => array_map($this->toDomain(...), $paginator->items()),
            'total' => $paginator->total(),
        ];
    }

    /**
     * @return array{items: array<int, Request>, total: int}
     */
    public function paginateForStudent(
        int $studentId,
        int $perPage,
        int $page,
        ?string $sortBy = null,
        string $sortDir = 'asc',
    ): array {
        $query = RequestModel::query()->with('student')->where('student_id', $studentId);

        $column = in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'created_at';
        $direction = $sortDir === 'desc' ? 'desc' : 'asc';

        $paginator = $query->orderBy($column, $direction)->paginate(perPage: $perPage, page: $page);

        return [
            'items' => array_map($this->toDomain(...), $paginator->items()),
            'total' => $paginator->total(),
        ];
    }

    public function save(Request $request): Request
    {
        $model = $request->id()
            ? RequestModel::query()->findOrFail($request->id())
            : new RequestModel;

        $model->student_id = $request->studentId();
        $model->type = $request->type();
        $model->course_id = $request->courseId();
        $model->required_course_id = $request->requiredCourseId();
        $model->waiver_justification = $request->waiverJustification();
        $model->origin_institution = $request->originInstitution();
        $model->external_course = $request->externalCourse();
        $model->external_course_code = $request->externalCourseCode();
        $model->external_course_credits = $request->externalCourseCredits();
        $model->validation_precedent_id = $request->validationPrecedentId();
        $model->engine_result = $request->engineResult();
        $model->violated_rule_id = $request->violatedRuleId();
        $model->status = $request->status();
        $model->estimated_resolution_date = $request->estimatedResolutionDate();
        $model->reviewer_id = $request->reviewerId();
        $model->save();

        return $this->toDomain($model->fresh('student'));
    }

    public function delete(int $id): void
    {
        RequestModel::query()->whereKey($id)->delete();
    }

    public function existsApprovedWaiver(int $studentId, int $courseId, int $requiredCourseId): bool
    {
        return RequestModel::query()
            ->where('student_id', $studentId)
            ->where('type', 'Requirement Waiver')
            ->where('course_id', $courseId)
            ->where('required_course_id', $requiredCourseId)
            ->where('status', 'Approved')
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $filters  See interface docblock for
     *                                         recognized keys — every one is optional and simply skipped when
     *                                         absent or empty, so callers can pass a sparse array freely.
     */
    private function baseQuery(?string $search, array $filters = []): Builder
    {
        $query = RequestModel::query()->with('student');

        if (filled($search)) {
            // Docencia's inbox has no dedicated filter panel — the single
            // search box doubles as the ES-04 filters, matching type and
            // status against their *translated* Spanish labels (what's
            // actually shown on screen) rather than the English enum
            // values stored in the column, plus an exact received-date
            // match when the term parses as YYYY-MM-DD.
            $typeMatches = collect([
                'Requirement Waiver' => __('Requirement Waiver'),
                'Validation' => __('Course Validation'),
            ])->filter(fn (string $label): bool => stripos($label, $search) !== false)->keys()->all();

            $statusMatches = collect([
                'Pending Review' => __('Pending Review'),
                'Approved' => __('Approved'),
                'Denied' => __('Denied'),
            ])->filter(fn (string $label): bool => stripos($label, $search) !== false)->keys()->all();

            $searchDate = \DateTime::createFromFormat('Y-m-d', $search);
            $isExactDate = $searchDate !== false && $searchDate->format('Y-m-d') === $search;

            $query->where(function ($outer) use ($search, $typeMatches, $statusMatches, $isExactDate): void {
                $outer->whereHas('student', function ($studentQuery) use ($search): void {
                    $studentQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%");
                })->orWhereHas('course', function ($courseQuery) use ($search): void {
                    $courseQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });

                if ($typeMatches !== []) {
                    $outer->orWhereIn('type', $typeMatches);
                }

                if ($statusMatches !== []) {
                    $outer->orWhereIn('status', $statusMatches);
                }

                if ($isExactDate) {
                    $outer->orWhereDate('created_at', $search);
                }
            });
        }

        if (filled($filters['type'] ?? null)) {
            $query->where('type', $filters['type']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        // "Program" (ES-04) is the requested course's owning career — a
        // request has no direct career column, so this reaches it through
        // the course relation rather than duplicating the FK on `requests`.
        if (filled($filters['careerId'] ?? null)) {
            $query->whereHas('course', function ($courseQuery) use ($filters): void {
                $courseQuery->where('career_id', $filters['careerId']);
            });
        }

        if (filled($filters['dateFrom'] ?? null)) {
            $query->whereDate('created_at', '>=', $filters['dateFrom']);
        }

        if (filled($filters['dateTo'] ?? null)) {
            $query->whereDate('created_at', '<=', $filters['dateTo']);
        }

        return $query;
    }

    private function toDomain(RequestModel $model): Request
    {
        return Request::reconstitute(
            id: $model->id,
            studentId: $model->student_id,
            type: $model->type,
            courseId: $model->course_id,
            requiredCourseId: $model->required_course_id,
            waiverJustification: $model->waiver_justification,
            originInstitution: $model->origin_institution,
            externalCourse: $model->external_course,
            validationPrecedentId: $model->validation_precedent_id,
            engineResult: $model->engine_result,
            violatedRuleId: $model->violated_rule_id,
            status: $model->status,
            estimatedResolutionDate: $model->estimated_resolution_date?->toDateString(),
            reviewerId: $model->reviewer_id,
            createdAt: $model->created_at?->toDateTimeString(),
            externalCourseCode: $model->external_course_code,
            externalCourseCredits: $model->external_course_credits,
        );
    }
}
