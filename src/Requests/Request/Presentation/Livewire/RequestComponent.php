<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Livewire;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CareerModel;
use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use App\Livewire\Concerns\InteractsWithDataTable;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Src\Requests\Request\Application\UseCases\ChangeRequestStatusUseCase;
use Src\Requests\Request\Application\UseCases\CreateRequestUseCase;
use Src\Requests\Request\Application\UseCases\DeleteRequestUseCase;
use Src\Requests\Request\Application\UseCases\FindRequestUseCase;
use Src\Requests\Request\Application\UseCases\ListRequestsUseCase;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\InvalidStatusTransitionException;
use Src\Requests\Request\Presentation\Livewire\Forms\RequestForm;

class RequestComponent extends Component
{
    use AuthorizesRequests;
    use InteractsWithDataTable;

    /**
     * Requests grow continuously over time (unlike Role/Permission's
     * small reference catalog), so this table is server-paginated from
     * the start rather than shipped to the browser in one response.
     */
    protected string $tableMode = 'server';

    public bool $showCreateModal = false;

    public bool $showReviewModal = false;

    public ?int $reviewingId = null;

    public string $reviewStatus = '';

    public string $reviewComment = '';

    public string $reviewEstimatedDate = '';

    public RequestForm $form;

    /**
     * ES-04: "filterable and sortable by type, program, status, and
     * received date". Sorting was already covered by InteractsWithDataTable
     * (type/status/created_at are in EloquentRequestRepository's
     * SORTABLE_COLUMNS); these four add the missing filter half.
     */
    public string $filterType = '';

    public string $filterStatus = '';

    public string $filterCareerId = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Request::class);
        $this->sortKey = 'created_at';
        $this->sortDir = 'desc';
    }

    /**
     * Any filter change starts back at page 1 — same reasoning as
     * InteractsWithDataTable::updatingSearch(), a stale $page could
     * otherwise point past the end of a newly-narrowed result set.
     */
    public function updatingFilterType(): void
    {
        $this->page = 1;
    }

    public function updatingFilterStatus(): void
    {
        $this->page = 1;
    }

    public function updatingFilterCareerId(): void
    {
        $this->page = 1;
    }

    public function updatingFilterDateFrom(): void
    {
        $this->page = 1;
    }

    public function updatingFilterDateTo(): void
    {
        $this->page = 1;
    }

    public function clearFilters(): void
    {
        $this->filterType = '';
        $this->filterStatus = '';
        $this->filterCareerId = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->page = 1;
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Request::class);

        $this->form->reset();
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    public function save(CreateRequestUseCase $useCase, ListRequestsUseCase $listUseCase): void
    {
        $this->authorize('create', Request::class);

        $this->form->validate();
        $useCase->handle($this->form->toDto());

        $this->showCreateModal = false;
        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: __('Request created.'));
    }

    public function openReviewModal(int $id, FindRequestUseCase $useCase): void
    {
        $request = $useCase->handle($id);
        $this->authorize('review', $request);

        $this->reviewingId = $id;
        $this->reviewStatus = $request->status();
        $this->reviewComment = '';
        $this->reviewEstimatedDate = $request->estimatedResolutionDate() ?? '';
        $this->resetValidation();
        $this->showReviewModal = true;
    }

    public function closeReviewModal(): void
    {
        $this->showReviewModal = false;
    }

    public function changeStatus(ChangeRequestStatusUseCase $useCase, ListRequestsUseCase $listUseCase, FindRequestUseCase $findUseCase): void
    {
        $request = $findUseCase->handle($this->reviewingId);
        $this->authorize('review', $request);

        if ($this->reviewStatus === 'Denied' && trim($this->reviewComment) === '') {
            $this->addError('reviewComment', __('A comment is required to deny a request.'));

            return;
        }

        try {
            $useCase->handle(
                requestId: $this->reviewingId,
                newStatus: $this->reviewStatus,
                reviewerId: Auth::id(),
                comment: $this->reviewComment !== '' ? $this->reviewComment : null,
                estimatedResolutionDate: $this->reviewEstimatedDate !== '' ? $this->reviewEstimatedDate : null,
            );
        } catch (InvalidStatusTransitionException $e) {
            $this->dispatch('toast', variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->showReviewModal = false;
        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: __('Request status updated.'));
    }

    public function delete(int $id, DeleteRequestUseCase $useCase, ListRequestsUseCase $listUseCase, FindRequestUseCase $findUseCase): void
    {
        $this->authorize('delete', $findUseCase->handle($id));

        $useCase->handle($id);

        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: __('Request deleted.'));
    }

    public function exportPdf(): void
    {
        $this->authorize('exportPdf', Request::class);

        $this->dispatch('toast', variant: 'info', text: __('Export coming soon.'));
    }

    public function exportExcel(): void
    {
        $this->authorize('exportExcel', Request::class);

        $this->dispatch('toast', variant: 'info', text: __('Export coming soon.'));
    }

    public function render(ListRequestsUseCase $useCase): View
    {
        $result = $useCase->paginate(
            search: $this->authorizedSearch(),
            perPage: $this->perPage,
            page: $this->page,
            sortBy: $this->sortKey,
            sortDir: $this->sortDir,
            filters: $this->activeFilters(),
        );

        $paginator = new LengthAwarePaginator(
            items: $result['items'],
            total: $result['total'],
            perPage: $this->perPage,
            currentPage: $this->page,
        );

        /** @disregard P1013 Livewire registra ->layout() como macro en runtime sobre Illuminate\View\View */
        return view('requests.request.livewire.request-component', [
            'tableMode' => 'server',
            'requests' => $paginator,
            'studentOptions' => $this->studentOptions(),
            'courseOptions' => $this->courseOptions(),
            'careerOptions' => $this->careerOptions(),
        ])->layout('components.layouts.dashboard', [
            'title' => __('Requests'),
            'subtitle' => __('Requirement waivers and course validations submitted by students'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function activeFilters(): array
    {
        return array_filter([
            'type' => $this->filterType,
            'status' => $this->filterStatus,
            'careerId' => $this->filterCareerId,
            'dateFrom' => $this->filterDateFrom,
            'dateTo' => $this->filterDateTo,
        ], fn (string $value): bool => $value !== '');
    }

    private function freshRows(ListRequestsUseCase $useCase): array
    {
        // Server mode never calls refreshTable's Alpine path (see
        // InteractsWithDataTable::refreshTable — it's a no-op outside
        // 'client' mode), so this only exists to satisfy the shared
        // method signature used by save()/changeStatus()/delete().
        return [];
    }

    private function authorizedSearch(): ?string
    {
        if (! Auth::user()->can('search', Request::class)) {
            return null;
        }

        return $this->search !== '' ? $this->search : null;
    }

    /**
     * Cross-context read (Students), same pattern already accepted for
     * Role reading Permission's catalog: this component needs the list
     * to populate the "student" dropdown but does not own that data.
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function studentOptions(): array
    {
        return StudentModel::query()
            ->orderBy('last_name')
            ->get(['id', 'name', 'last_name', 'national_id'])
            ->map(fn (StudentModel $s) => [
                'id' => $s->id,
                'label' => "{$s->name} {$s->last_name} ({$s->national_id})",
            ])
            ->all();
    }

    /**
     * Cross-context read (Academic), same reasoning as studentOptions().
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

    /**
     * ES-04's "program" filter — populates the career dropdown. Same
     * cross-context read pattern as studentOptions()/courseOptions().
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function careerOptions(): array
    {
        return CareerModel::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (CareerModel $c) => [
                'id' => $c->id,
                'label' => $c->name,
            ])
            ->all();
    }
}
