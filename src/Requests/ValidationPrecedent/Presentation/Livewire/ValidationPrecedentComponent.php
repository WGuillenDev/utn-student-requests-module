<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Presentation\Livewire;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Livewire\Concerns\InteractsWithDataTable;
use App\Livewire\Concerns\InteractsWithExports;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Src\Requests\ValidationPrecedent\Application\UseCases\CreateValidationPrecedentUseCase;
use Src\Requests\ValidationPrecedent\Application\UseCases\DeleteValidationPrecedentUseCase;
use Src\Requests\ValidationPrecedent\Application\UseCases\FindValidationPrecedentUseCase;
use Src\Requests\ValidationPrecedent\Application\UseCases\ListValidationPrecedentsUseCase;
use Src\Requests\ValidationPrecedent\Application\UseCases\UpdateValidationPrecedentUseCase;
use Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent;
use Src\Requests\ValidationPrecedent\Presentation\Livewire\Forms\ValidationPrecedentForm;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ValidationPrecedentComponent extends Component
{
    use AuthorizesRequests;
    use InteractsWithDataTable;
    use InteractsWithExports;

    /**
     * Unlike WaiverRule's small per-course catalog, this is a historical
     * log that grows with every resolved validation over time — same
     * reasoning as RequestComponent — so it's server-paginated from the
     * start rather than shipped to the browser in one response.
     */
    protected string $tableMode = 'server';

    public bool $showModal = false;

    /**
     * Null while creating; the row's id while editing.
     */
    public ?int $editingId = null;

    public ValidationPrecedentForm $form;

    public function mount(): void
    {
        $this->authorize('viewAny', ValidationPrecedent::class);
        $this->sortKey = 'created_at';
        $this->sortDir = 'desc';
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', ValidationPrecedent::class);

        $this->editingId = null;
        $this->form->reset();
        $this->form->result = 'Approved';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEditModal(int $id, FindValidationPrecedentUseCase $useCase): void
    {
        $precedent = $useCase->handle($id);
        $this->authorize('update', $precedent);

        $this->editingId = $id;
        $this->form->fromEntity($precedent);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function save(
        CreateValidationPrecedentUseCase $createUseCase,
        UpdateValidationPrecedentUseCase $updateUseCase,
        ListValidationPrecedentsUseCase $listUseCase,
        FindValidationPrecedentUseCase $findUseCase,
    ): void {
        $this->form->validate();

        if ($this->editingId === null) {
            $this->authorize('create', ValidationPrecedent::class);
            $createUseCase->handle($this->form->toDto());
        } else {
            // ValidationPrecedentPolicy::update() needs an actual entity
            // instance as its 2nd parameter, same reasoning documented
            // on RoleComponent::save().
            $this->authorize('update', $findUseCase->handle($this->editingId));
            $updateUseCase->handle($this->editingId, $this->form->toDto());
        }

        $this->showModal = false;
        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: $this->editingId === null
            ? __('Validation precedent created.')
            : __('Validation precedent updated.'));
    }

    public function delete(int $id, DeleteValidationPrecedentUseCase $useCase, ListValidationPrecedentsUseCase $listUseCase, FindValidationPrecedentUseCase $findUseCase): void
    {
        $this->authorize('delete', $findUseCase->handle($id));

        try {
            $useCase->handle($id);
        } catch (QueryException $e) {
            // `course_id` is restrictOnDelete() on this table, but that
            // FK exists to protect `courses`, not this table directly —
            // this catch is a defensive net for any FK violation the DB
            // reports (see DeleteValidationPrecedentUseCase's docblock),
            // surfaced as a toast instead of a raw 500.
            $this->dispatch('toast', variant: 'danger', text: __('This precedent cannot be deleted because other records depend on it.'));

            return;
        }

        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: __('Validation precedent deleted.'));
    }

    public function exportPdf(PdfExporterInterface $exporter, ListValidationPrecedentsUseCase $useCase, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportPdf', ValidationPrecedent::class);

        return $this->streamPdf(
            __('Validation Precedents'),
            $this->exportHeaders(),
            $this->exportableRows($useCase, $search),
            Str::slug(__('Validation Precedents')) . '.pdf',
            $exporter,
            paperSize: 'letter',
        );
    }

    public function exportExcel(ExcelExporterInterface $exporter, ListValidationPrecedentsUseCase $useCase, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportExcel', ValidationPrecedent::class);

        return $this->streamExcel(
            $this->exportHeaders(),
            $this->exportableRows($useCase, $search),
            Str::slug(__('Validation Precedents')) . '.xlsx',
            $exporter,
        );
    }

    public function render(ListValidationPrecedentsUseCase $useCase): View
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

        /** @disregard P1013 Livewire registra ->layout() como macro en runtime sobre Illuminate\View\View */
        return view('requests.validation-precedent.livewire.validation-precedent-component', [
            'tableMode' => 'server',
            'validationPrecedents' => $paginator,
            'courseOptions' => $this->courseOptions(),
        ])->layout('components.layouts.dashboard', [
            'title' => __('Validation Precedents'),
            'subtitle' => __('Historical record of external validations used by the rules engine'),
        ]);
    }

    private function freshRows(ListValidationPrecedentsUseCase $useCase): array
    {
        // Server mode never calls refreshTable's Alpine path (see
        // InteractsWithDataTable::refreshTable — it's a no-op outside
        // 'client' mode), so this only exists to satisfy the shared
        // method signature used by save()/delete().
        return [];
    }

    private function authorizedSearch(): ?string
    {
        if (! Auth::user()->can('search', ValidationPrecedent::class)) {
            return null;
        }

        return $this->search !== '' ? $this->search : null;
    }

    /**
     * @return array<int, array{key: string, label: string, format?: callable}>
     */
    private function exportHeaders(): array
    {
        return [
            ['key' => 'institution', 'label' => __('Institution')],
            ['key' => 'externalCourse', 'label' => __('External course')],
            ['key' => 'result', 'label' => __('Result'), 'format' => fn (string $result): string => __($result)],
            ['key' => 'resolutionNumber', 'label' => __('Resolution number')],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function exportableRows(ListValidationPrecedentsUseCase $useCase, ?string $search): array
    {
        return array_map(
            fn (ValidationPrecedent $p) => [
                'institution' => $p->institution(),
                'externalCourse' => $p->externalCourse(),
                'result' => $p->result(),
                'resolutionNumber' => $p->resolutionNumber(),
            ],
            $useCase->all(search: $this->authorizedSearch($search), sortBy: $this->sortKey, sortDir: $this->sortDir),
        );
    }

    /**
     * Cross-context read (Academic), same reasoning already accepted for
     * RequestComponent::courseOptions() / WaiverRuleComponent::courseOptions().
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function courseOptions(): array
    {
        return CourseModel::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (CourseModel $c) => [
                'id' => $c->id,
                'label' => "{$c->code} — {$c->name}",
            ])
            ->all();
    }
}
