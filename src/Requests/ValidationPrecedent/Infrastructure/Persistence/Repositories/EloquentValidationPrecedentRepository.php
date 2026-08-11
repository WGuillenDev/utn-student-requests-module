<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Eloquent\Requests\Models\ValidationPrecedentModel;
use Illuminate\Database\Eloquent\Builder;
use Src\Requests\ValidationPrecedent\Domain\Contracts\ValidationPrecedentRepositoryInterface;
use Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent;

final class EloquentValidationPrecedentRepository implements ValidationPrecedentRepositoryInterface
{
    /**
     * Explicit allow-list — $sortBy ultimately reaches raw SQL via orderBy(),
     * and Livewire action arguments are client-controllable, so this is not
     * optional hardening.
     *
     * @var array<int, string>
     */
    private const SORTABLE_COLUMNS = ['institution', 'external_course', 'result', 'created_at'];

    public function find(int $id): ?ValidationPrecedent
    {
        $model = ValidationPrecedentModel::query()->with('course')->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        $query = $this->baseQuery($search);

        $column = in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'created_at';
        $direction = $sortDir === 'desc' ? 'desc' : 'asc';

        /** @var \Illuminate\Database\Eloquent\Collection<int, ValidationPrecedentModel> $models */
        $models = $query->orderBy($column, $direction)->get();

        return $models->map($this->toDomain(...))->all();
    }

    public function paginate(?string $search, int $perPage, int $page, ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        $query = $this->baseQuery($search);

        $column = in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'created_at';
        $direction = $sortDir === 'desc' ? 'desc' : 'asc';

        $paginator = $query->orderBy($column, $direction)->paginate(perPage: $perPage, page: $page);

        return [
            'items' => array_map($this->toDomain(...), $paginator->items()),
            'total' => $paginator->total(),
        ];
    }

    public function save(ValidationPrecedent $validationPrecedent): ValidationPrecedent
    {
        $model = $validationPrecedent->id()
            ? ValidationPrecedentModel::query()->findOrFail($validationPrecedent->id())
            : new ValidationPrecedentModel();

        $model->institution = $validationPrecedent->institution();
        $model->external_course = $validationPrecedent->externalCourse();
        $model->course_id = $validationPrecedent->courseId();
        $model->result = $validationPrecedent->result();
        $model->resolution_number = $validationPrecedent->resolutionNumber();
        $model->save();

        return $this->toDomain($model->fresh('course'));
    }

    public function delete(int $id): void
    {
        ValidationPrecedentModel::query()->whereKey($id)->delete();
    }

    /**
     * Simpler than EloquentRequestRepository's baseQuery(): no whereHas
     * against a related table — institution/external_course/
     * resolution_number all live on this model directly, matching the
     * (institution, external_course) index already in the migration.
     */
    private function baseQuery(?string $search): Builder
    {
        $query = ValidationPrecedentModel::query()->with('course');

        if (filled($search)) {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery->where('institution', 'like', "%{$search}%")
                    ->orWhere('external_course', 'like', "%{$search}%")
                    ->orWhere('resolution_number', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function toDomain(ValidationPrecedentModel $model): ValidationPrecedent
    {
        return ValidationPrecedent::reconstitute(
            id: $model->id,
            institution: $model->institution,
            externalCourse: $model->external_course,
            courseId: $model->course_id,
            result: $model->result,
            resolutionNumber: $model->resolution_number,
        );
    }
}
