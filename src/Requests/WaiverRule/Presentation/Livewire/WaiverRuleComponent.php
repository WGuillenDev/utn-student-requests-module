<?php

declare(strict_types=1);

namespace Src\Requests\WaiverRule\Presentation\Livewire;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CareerModel;
use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Livewire\Concerns\InteractsWithDataTable;
use App\Livewire\Concerns\InteractsWithExports;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Src\Requests\WaiverRule\Application\UseCases\CreateWaiverRuleUseCase;
use Src\Requests\WaiverRule\Application\UseCases\DeleteWaiverRuleUseCase;
use Src\Requests\WaiverRule\Application\UseCases\FindWaiverRuleUseCase;
use Src\Requests\WaiverRule\Application\UseCases\ListWaiverRulesUseCase;
use Src\Requests\WaiverRule\Application\UseCases\UpdateWaiverRuleUseCase;
use Src\Requests\WaiverRule\Domain\Entities\WaiverRule;
use Src\Requests\WaiverRule\Presentation\Livewire\Forms\WaiverRuleForm;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WaiverRuleComponent extends Component
{
    use AuthorizesRequests;
    use InteractsWithDataTable;
    use InteractsWithExports;

    /**
     * Waiver rules are a small, reference-style catalog scoped per course
     * (a handful of rows per course) — same reasoning as RoleComponent:
     * client-side is the right default, ships once and Alpine resolves
     * search/sort/pagination with zero further server round-trips. Flip
     * to 'server' only if this catalog is ever expected to grow into the
     * hundreds/thousands across all courses.
     */
    protected string $tableMode = 'client';

    public bool $showModal = false;

    /**
     * Null while creating; the row's id while editing. Also read by
     * WaiverRuleForm::rules() to scope the (course_id, order) uniqueness
     * check correctly.
     */
    public ?int $editingId = null;

    public WaiverRuleForm $form;

    /**
     * Client-side filter only — narrows the "Course"/"Required course"
     * selects in the form, same reasoning as
     * ValidationPrecedentComponent::$filterCareerId. Not part of
     * WaiverRuleDTO: the entity only stores course_id, never the career.
     */
    public ?int $filterCareerId = null;

    public bool $showViewModal = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $viewing = null;

    public function mount(): void
    {
        $this->authorize('viewAny', WaiverRule::class);
        $this->sortKey = 'order';
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', WaiverRule::class);

        $this->editingId = null;
        $this->filterCareerId = null;
        $this->form->reset();
        $this->form->type = 'Always manual review';
        $this->form->active = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEditModal(int $id, FindWaiverRuleUseCase $useCase): void
    {
        $waiverRule = $useCase->handle($id);
        $this->authorize('update', $waiverRule);

        $this->editingId = $id;
        $this->filterCareerId = CourseModel::query()->whereKey($waiverRule->courseId())->value('career_id');
        $this->form->fromEntity($waiverRule);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function openViewModal(int $id, FindWaiverRuleUseCase $useCase): void
    {
        $waiverRule = $useCase->handle($id);
        $this->authorize('view', $waiverRule);

        $this->viewing = $this->toRow($waiverRule);
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewing = null;
    }

    /**
     * Switching the "Carrera" filter narrows courseOptions() — clear
     * whichever of the two course selects no longer belongs to that
     * career, so the form can't submit a mismatched pair silently. Same
     * reasoning as ValidationPrecedentComponent::updatedFilterCareerId().
     */
    public function updatedFilterCareerId(): void
    {
        if ($this->filterCareerId === null) {
            return;
        }

        foreach (['courseId', 'requiredCourseId'] as $field) {
            if ($this->form->{$field} === null) {
                continue;
            }

            $stillValid = CourseModel::query()
                ->whereKey($this->form->{$field})
                ->where('career_id', $this->filterCareerId)
                ->exists();

            if (! $stillValid) {
                $this->form->{$field} = null;
            }
        }
    }

    public function save(
        CreateWaiverRuleUseCase $createUseCase,
        UpdateWaiverRuleUseCase $updateUseCase,
        ListWaiverRulesUseCase $listUseCase,
        FindWaiverRuleUseCase $findUseCase,
    ): void {
        $this->form->validate();

        if ($this->editingId === null) {
            $this->authorize('create', WaiverRule::class);
            $createUseCase->handle($this->form->toDto());
        } else {
            // WaiverRulePolicy::update() needs an actual WaiverRule
            // instance as its 2nd parameter, same reasoning documented
            // on RoleComponent::save().
            $this->authorize('update', $findUseCase->handle($this->editingId));
            $updateUseCase->handle($this->editingId, $this->form->toDto());
        }

        $this->showModal = false;
        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: $this->editingId === null
            ? __('Waiver rule created.')
            : __('Waiver rule updated.'));
    }

    public function delete(int $id, DeleteWaiverRuleUseCase $useCase, ListWaiverRulesUseCase $listUseCase, FindWaiverRuleUseCase $findUseCase): void
    {
        $this->authorize('delete', $findUseCase->handle($id));

        $useCase->handle($id);

        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: __('Waiver rule deleted.'));
    }

    public function exportPdf(PdfExporterInterface $exporter, ListWaiverRulesUseCase $useCase, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportPdf', WaiverRule::class);

        return $this->streamPdf(
            __('Waiver Rules'),
            $this->exportHeaders(),
            $this->exportableRows($useCase, $search),
            Str::slug(__('Waiver Rules')).'.pdf',
            $exporter,
            paperSize: 'letter',
        );
    }

    public function exportExcel(ExcelExporterInterface $exporter, ListWaiverRulesUseCase $useCase, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportExcel', WaiverRule::class);

        return $this->streamExcel(
            $this->exportHeaders(),
            $this->exportableRows($useCase, $search),
            Str::slug(__('Waiver Rules')).'.xlsx',
            $exporter,
        );
    }

    public function render(ListWaiverRulesUseCase $useCase): View
    {
        $view = $this->isServerMode()
            ? $this->renderServerMode($useCase)
            : $this->renderClientMode($useCase);

        $view = $view
            ->with('careerOptions', $this->careerOptions())
            ->with('courseOptions', $this->courseOptions($this->filterCareerId));

        /** @disregard P1013 Livewire registra ->layout() como macro en runtime sobre Illuminate\View\View */
        return $view->layout('components.layouts.dashboard', [
            'title' => __('Waiver Rules'),
            'subtitle' => __('Rules engine configuration for automatic requirement waivers'),
        ]);
    }

    /**
     * Client mode: the full, unpaginated collection is fetched once and
     * projected into plain arrays for Alpine (resources/js/data-table.js).
     * No Domain Entity ever reaches the browser.
     */
    private function renderClientMode(ListWaiverRulesUseCase $useCase): View
    {
        return view('requests.waiver-rule.livewire.waiver-rule-component', [
            'tableMode' => 'client',
            'rows' => $this->freshRows($useCase),
        ]);
    }

    /**
     * Server mode: unchanged Livewire-driven pagination, preserved for
     * any future catalog too large to ship to the client in one response.
     */
    private function renderServerMode(ListWaiverRulesUseCase $useCase): View
    {
        $result = $useCase->paginate(
            search: $this->authorizedSearch(),
            perPage: $this->perPage,
            page: $this->page,
            sortBy: $this->sortKey,
            sortDir: $this->sortDir,
        );

        $paginator = new LengthAwarePaginator(
            items: $result['items'],
            total: $result['total'],
            perPage: $this->perPage,
            currentPage: $this->page,
        );

        return view('requests.waiver-rule.livewire.waiver-rule-component', [
            'tableMode' => 'server',
            'waiverRules' => $paginator,
        ]);
    }

    /**
     * Plain-array projection handed to Alpine as JSON — keeps the Domain
     * Entity from ever leaking past the Presentation boundary.
     *
     * @return array<string, mixed>
     */
    private function toRow(WaiverRule $waiverRule): array
    {
        $courses = $this->courseOptionsById();

        return [
            'id' => $waiverRule->id(),
            'courseId' => $waiverRule->courseId(),
            'courseLabel' => $courses[$waiverRule->courseId()] ?? (string) $waiverRule->courseId(),
            'order' => $waiverRule->order(),
            'type' => $waiverRule->type(),
            'requiredCourseId' => $waiverRule->requiredCourseId(),
            'requiredCourseLabel' => $waiverRule->requiredCourseId() !== null
                ? ($courses[$waiverRule->requiredCourseId()] ?? (string) $waiverRule->requiredCourseId())
                : null,
            'minimumGrade' => $waiverRule->minimumGrade(),
            'minimumAccumulated' => $waiverRule->minimumAccumulated(),
            'active' => $waiverRule->active(),
        ];
    }

    /**
     * Fetches and projects the full collection in one call — shared by
     * renderClientMode() (initial/normal render) and save()/delete()
     * (post-mutation refreshTable() dispatch), so there's exactly one
     * place that knows how to build a row array for this entity.
     *
     * @return array<int, array<string, mixed>>
     */
    private function freshRows(ListWaiverRulesUseCase $useCase): array
    {
        return array_map($this->toRow(...), $useCase->all(sortBy: $this->sortKey, sortDir: $this->sortDir));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function exportableRows(ListWaiverRulesUseCase $useCase, ?string $search): array
    {
        return array_map(
            $this->toRow(...),
            $useCase->all(search: $this->authorizedSearch($search), sortBy: $this->sortKey, sortDir: $this->sortDir),
        );
    }

    private function authorizedSearch(?string $explicit = null): ?string
    {
        if (! Auth::user()->can('search', WaiverRule::class)) {
            return null;
        }

        $candidate = filled($explicit) ? $explicit : $this->search;

        return $candidate !== '' ? $candidate : null;
    }

    /**
     * @return array<int, array{key: string, label: string, format?: callable}>
     */
    private function exportHeaders(): array
    {
        return [
            ['key' => 'courseLabel', 'label' => __('Course')],
            ['key' => 'order', 'label' => __('Order')],
            ['key' => 'type', 'label' => __('Type'), 'format' => fn (string $type): string => __($type)],
            ['key' => 'active', 'label' => __('Active'), 'format' => fn (bool $active): string => $active ? __('Active') : __('Inactive')],
        ];
    }

    /**
     * Cross-context read (Academic), same reasoning already accepted for
     * RequestComponent::courseOptions() — used both for the "course" and
     * "required course" selects of the create/edit modal. Requires a
     * career to be picked first, same reasoning as
     * ValidationPrecedentComponent::courseOptions().
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function courseOptions(?int $careerId = null): array
    {
        if ($careerId === null) {
            return [];
        }

        return CourseModel::query()
            ->where('career_id', $careerId)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (CourseModel $c) => [
                'id' => $c->id,
                'label' => "{$c->code} — {$c->name}",
            ])
            ->all();
    }

    /**
     * Only careers with at least one course loaded — same reasoning as
     * ValidationPrecedentComponent::careerOptions().
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function careerOptions(): array
    {
        return CareerModel::query()
            ->whereHas('courses')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (CareerModel $c) => ['id' => $c->id, 'label' => $c->name])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function courseOptionsById(): array
    {
        return CourseModel::query()
            ->get(['id', 'name', 'code'])
            ->mapWithKeys(fn (CourseModel $c) => [$c->id => "{$c->code} — {$c->name}"])
            ->all();
    }
}
