<?php

declare(strict_types=1);

namespace Src\Requests\Request\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Eloquent\Requests\Models\RequestModel;
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

    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        $query = $this->baseQuery($search);

        $column = in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'created_at';
        $direction = $sortDir === 'desc' ? 'desc' : 'asc';

        /** @var \Illuminate\Database\Eloquent\Collection<int, RequestModel> $models */
        $models = $query->orderBy($column, $direction)->get();

        return $models->map($this->toDomain(...))->all();
    }

    public function paginate(
        ?string $search,
        int $perPage,
        int $page,
        ?string $sortBy = null,
        string $sortDir = 'asc',
        ?string $type = null,
        ?string $status = null,
    ): array {
        $query = $this->baseQuery($search, $type, $status);

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
            : new RequestModel();

        $model->student_id = $request->studentId();
        $model->type = $request->type();
        $model->course_id = $request->courseId();
        $model->required_course_id = $request->requiredCourseId();
        $model->origin_institution = $request->originInstitution();
        $model->external_course = $request->externalCourse();
        $model->validation_precedent_id = $request->validationPrecedentId();
        $model->engine_result = $request->engineResult();
        $model->violated_rule_id = $request->violatedRuleId();
        $model->status = $request->status();
        $model->reviewer_id = $request->reviewerId();
        $model->save();

        return $this->toDomain($model->fresh('student'));
    }

    public function delete(int $id): void
    {
        RequestModel::query()->whereKey($id)->delete();
    }

    private function baseQuery(?string $search, ?string $type = null, ?string $status = null): \Illuminate\Database\Eloquent\Builder
    {
        $query = RequestModel::query()->with('student');

        if (filled($type)) {
            $query->where('type', $type);
        }

        if (filled($status)) {
            $query->where('status', $status);
        }

        if (filled($search)) {
            $query->whereHas('student', function ($studentQuery) use ($search): void {
                $studentQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%");
            });
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
            originInstitution: $model->origin_institution,
            externalCourse: $model->external_course,
            validationPrecedentId: $model->validation_precedent_id,
            engineResult: $model->engine_result,
            violatedRuleId: $model->violated_rule_id,
            status: $model->status,
            estimatedResolutionDate: $model->estimated_resolution_date?->toDateString(),
            reviewerId: $model->reviewer_id,
        );
    }
}
