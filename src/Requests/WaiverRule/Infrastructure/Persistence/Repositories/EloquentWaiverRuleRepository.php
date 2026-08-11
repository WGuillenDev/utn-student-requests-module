<?php

declare(strict_types=1);

namespace Src\Requests\WaiverRule\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Eloquent\Requests\Models\WaiverRuleModel;
use Illuminate\Database\Eloquent\Builder;
use Src\Requests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;
use Src\Requests\WaiverRule\Domain\Entities\WaiverRule;

final class EloquentWaiverRuleRepository implements WaiverRuleRepositoryInterface
{
    /**
     * Explicit allow-list — $sortBy ultimately reaches raw SQL via orderBy(),
     * and Livewire action arguments are client-controllable, so this is not
     * optional hardening.
     *
     * @var array<int, string>
     */
    private const SORTABLE_COLUMNS = ['order', 'type', 'active'];

    public function find(int $id): ?WaiverRule
    {
        $model = WaiverRuleModel::query()->with(['course', 'requiredCourse'])->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc', ?int $courseId = null): array
    {
        $query = $this->baseQuery($search, $courseId);

        $column = in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'order';
        $direction = $sortDir === 'desc' ? 'desc' : 'asc';

        /** @var \Illuminate\Database\Eloquent\Collection<int, WaiverRuleModel> $models */
        $models = $query->orderBy($column, $direction)->get();

        return $models->map($this->toDomain(...))->all();
    }

    public function paginate(
        ?string $search,
        int $perPage,
        int $page,
        ?string $sortBy = null,
        string $sortDir = 'asc',
        ?int $courseId = null,
    ): array {
        $query = $this->baseQuery($search, $courseId);

        $column = in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'order';
        $direction = $sortDir === 'desc' ? 'desc' : 'asc';

        $paginator = $query->orderBy($column, $direction)->paginate(perPage: $perPage, page: $page);

        return [
            'items' => array_map($this->toDomain(...), $paginator->items()),
            'total' => $paginator->total(),
        ];
    }

    public function save(WaiverRule $waiverRule): WaiverRule
    {
        $model = $waiverRule->id()
            ? WaiverRuleModel::query()->findOrFail($waiverRule->id())
            : new WaiverRuleModel();

        $model->course_id = $waiverRule->courseId();
        $model->order = $waiverRule->order();
        $model->type = $waiverRule->type();
        $model->required_course_id = $waiverRule->requiredCourseId();
        $model->minimum_grade = $waiverRule->minimumGrade();
        $model->minimum_accumulated = $waiverRule->minimumAccumulated();
        $model->active = $waiverRule->active();
        $model->save();

        return $this->toDomain($model->fresh(['course', 'requiredCourse']));
    }

    public function delete(int $id): void
    {
        WaiverRuleModel::query()->whereKey($id)->delete();
    }

    private function baseQuery(?string $search, ?int $courseId = null): Builder
    {
        $query = WaiverRuleModel::query()->with(['course', 'requiredCourse']);

        if ($courseId !== null) {
            $query->where('course_id', $courseId);
        }

        if (filled($search)) {
            $query->whereHas('course', function (Builder $courseQuery) use ($search): void {
                $courseQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function toDomain(WaiverRuleModel $model): WaiverRule
    {
        return WaiverRule::reconstitute(
            id: $model->id,
            courseId: $model->course_id,
            order: $model->order,
            type: $model->type,
            requiredCourseId: $model->required_course_id,
            minimumGrade: $model->minimum_grade !== null ? (float) $model->minimum_grade : null,
            minimumAccumulated: $model->minimum_accumulated,
            active: $model->active,
        );
    }
}
