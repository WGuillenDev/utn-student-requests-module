<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Livewire;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use App\Livewire\Concerns\InteractsWithDataTable;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Src\Requests\Request\Application\UseCases\CreateRequestUseCase;
use Src\Requests\Request\Application\UseCases\FindRequestUseCase;
use Src\Requests\Request\Application\UseCases\ListRequestsUseCase;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\DuplicateWaiverRequestException;
use Src\Requests\Request\Presentation\Livewire\Forms\ValidationRequestForm;
use Src\Requests\Request\Presentation\Livewire\Forms\WaiverRequestForm;

/**
 * Student self-service screen (ES-01/ES-02/ES-03): 3 tabs — new waiver
 * request, new validation request, and a read-only "my requests" list.
 * Deliberately separate from RequestComponent (the staff inbox): the
 * two screens have opposite authorization shapes (RequestPolicy::
 * viewAny() denies the Estudiante role outright, see that policy's
 * docblock) and this one has no student picker, no edit/delete, and no
 * review action — only create + read-your-own.
 */
class StudentRequestComponent extends Component
{
    use AuthorizesRequests;
    use InteractsWithDataTable;
    use WithFileUploads;

    protected string $tableMode = 'server';

    public string $activeTab = 'waiver';

    public WaiverRequestForm $waiverForm;

    public ValidationRequestForm $validationForm;

    public bool $showSuccessModal = false;

    public string $successType = '';

    public string $successCourse = '';

    public ?string $successEngineResult = null;

    public bool $showViewModal = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $viewingRequest = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasRole('Estudiante'), 403);

        $this->studentId();

        $this->sortKey = 'created_at';
        $this->sortDir = 'desc';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['waiver', 'validation', 'my-requests'], true) ? $tab : 'waiver';
    }

    /**
     * @var array<int, string>
     */
    private const FILE_FIELDS = [
        'waiverForm.supportDocument',
    ];

    /**
     * Real-time validation for file inputs: without this hook, a chosen
     * file (oversized, wrong type, or a valid replacement) is only
     * checked when the whole form is submitted, so the "max 5MB" error
     * never shows until then, and a stale "required" error for an
     * already-attached file lingers until the next full submit attempt.
     */
    public function updated(string $property): void
    {
        if (in_array($property, self::FILE_FIELDS, true)) {
            $this->validateOnly($property);
        }

        // Same idea for "requisito no cumplido" == "curso a matricular":
        // check as soon as both selects have a value, instead of waiting
        // for submitWaiver(). requiredCourseId carries the `different`
        // rule, so re-checking it covers both edit orders (course picked
        // first, or the requirement picked first). Skipped while
        // requiredCourseId is still empty so a bare courseId pick doesn't
        // trigger a premature "required" error on the other field.
        if ($property === 'waiverForm.requiredCourseId'
            || ($property === 'waiverForm.courseId' && $this->waiverForm->requiredCourseId !== null)) {
            $this->validateOnly('waiverForm.requiredCourseId');
        }

        // Validation's course rows carry a `distinct` rule (no picking
        // the same UTN course twice across rows), but checked by hand
        // here instead of via validateOnly(): Livewire's Form::
        // validateOnly() narrows a wildcarded field's sibling data down
        // to just the one row being checked (see HandlesValidation::
        // filterCollectionDataDownToSpecificKeys()) before handing it to
        // the Validator, which leaves nothing for `distinct` to compare
        // against — it always passes. submitValidation()'s full
        // $this->validationForm->validate() call doesn't narrow
        // anything, so the real `distinct` rule still catches this at
        // submit time regardless; this is only needed for the live,
        // as-you-pick feedback.
        if (preg_match('/^validationForm\.courses\.\d+\.courseId$/', $property) === 1) {
            $this->flagDuplicateCourses();
        }
    }

    private function flagDuplicateCourses(): void
    {
        $counts = collect($this->validationForm->courses)->pluck('courseId')->filter()->countBy();

        foreach (array_keys($this->validationForm->courses) as $index) {
            $field = "validationForm.courses.{$index}.courseId";
            $courseId = $this->validationForm->courses[$index]['courseId'];

            if ($courseId !== null && ($counts[$courseId] ?? 0) > 1) {
                $this->addError($field, __('You already selected this UTN course.'));
            } else {
                $this->resetErrorBag($field);
            }
        }
    }

    /**
     * Lets the student discard an already-attached file (e.g. picked the
     * wrong one) and go back to the empty dropzone, instead of having to
     * submit with it or reload the page. $field is "form.property" as
     * used by wire:model, e.g. "waiverForm.supportDocument".
     */
    public function removeFile(string $field): void
    {
        if (! in_array($field, self::FILE_FIELDS, true)) {
            return;
        }

        [$form, $property] = explode('.', $field, 2);

        $this->{$form}->{$property} = null;

        $this->resetErrorBag($field);
    }

    public function submitWaiver(CreateRequestUseCase $useCase): void
    {
        $this->waiverForm->validate();

        $course = $this->courseLabel($this->waiverForm->courseId);

        try {
            $request = $useCase->handle($this->waiverForm->toDto($this->studentId()));
        } catch (DuplicateWaiverRequestException) {
            $this->addError('waiverForm.requiredCourseId', __('This waiver has already been processed previously.'));

            return;
        }

        $this->waiverForm->reset();
        $this->openSuccessModal('Requirement Waiver', $course, $request->engineResult());
    }

    public function addValidationCourse(): void
    {
        if (count($this->validationForm->courses) >= ValidationRequestForm::MAX_COURSES) {
            return;
        }

        $this->validationForm->courses[] = ['courseId' => null, 'externalCourse' => null, 'originInstitution' => null];
    }

    public function removeValidationCourse(int $index): void
    {
        if (count($this->validationForm->courses) <= 1) {
            return;
        }

        $oldLastIndex = count($this->validationForm->courses) - 1;

        unset($this->validationForm->courses[$index]);
        $this->validationForm->courses = array_values($this->validationForm->courses);

        // Removing a row re-indexes the rest, and may have just deleted
        // the other half of a duplicate pair — re-run the check so a
        // remaining row's error clears immediately instead of only on
        // its next courseId change. The trailing index the array no
        // longer has (courses shrank by one) needs an explicit reset
        // too: flagDuplicateCourses() only touches indices still in the
        // array, so its error would otherwise linger in the bag and
        // resurface if a later addValidationCourse() reuses that index.
        $this->resetErrorBag("validationForm.courses.{$oldLastIndex}.courseId");
        $this->flagDuplicateCourses();
    }

    public function removeValidationDocument(int $index): void
    {
        $documents = $this->validationForm->documents;
        unset($documents[$index]);
        $this->validationForm->documents = array_values($documents);
    }

    public function submitValidation(CreateRequestUseCase $useCase): void
    {
        $this->validationForm->validate();

        $courseLabels = $this->courseLabelsById();
        $courseNames = collect($this->validationForm->courses)
            ->map(fn (array $course) => $courseLabels[(int) $course['courseId']] ?? (string) $course['courseId'])
            ->implode(', ');

        foreach ($this->validationForm->toDtos($this->studentId(), $courseLabels) as $dto) {
            $useCase->handle($dto);
        }

        $this->validationForm->reset();
        $this->openSuccessModal('Validation', $courseNames);
    }

    public function closeSuccessModal(): void
    {
        $this->showSuccessModal = false;
    }

    public function viewRequest(int $id, FindRequestUseCase $useCase): void
    {
        $request = $useCase->handle($id);
        $this->authorize('view', $request);

        $courseLabels = $this->courseLabelsById();
        $this->viewingRequest = $this->toRow($request, $courseLabels);

        // Each course line from a Validation submission is its own
        // independent Request — reviewed and resolved on its own by
        // Docencia/Registro, potentially with a different outcome per
        // course (see ValidationRequestForm's docblock). So "Ver" on one
        // of them shows only that one course, not its siblings from the
        // same submission — unlike the confirmation email, which does
        // list every course together (see RequestSubmittedNotification).
        // Table format kept (not the old stacked fields) for visual
        // consistency even with a single row.
        if ($request->type() === 'Validation') {
            $this->viewingRequest['batchCourses'] = [[
                'course' => $courseLabels[$request->courseId()] ?? (string) $request->courseId(),
                'externalCourse' => $request->externalCourse(),
                'originInstitution' => $request->originInstitution(),
            ]];
        }

        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
    }

    public function render(ListRequestsUseCase $useCase): View
    {
        $paginator = null;

        if ($this->activeTab === 'my-requests') {
            $result = $useCase->paginateForStudent(
                studentId: $this->studentId(),
                perPage: $this->perPage,
                page: $this->page,
                sortBy: $this->sortKey,
                sortDir: $this->sortDir,
                search: $this->search,
            );

            $courseLabels = $this->courseLabelsById();

            $paginator = new LengthAwarePaginator(
                items: array_map(fn (Request $r) => $this->toRow($r, $courseLabels), $result['items']),
                total: $result['total'],
                perPage: $this->perPage,
                currentPage: $this->page,
            );
        }

        return view('requests.request.livewire.student-request-component', [
            'requests' => $paginator,
            'courseOptions' => $this->courseOptions(),
        ])->layout('components.layouts.dashboard', [
            'title' => __('My requests'),
            'subtitle' => __('Submit and track your requirement waivers and course validations'),
        ]);
    }

    private function openSuccessModal(string $type, string $course, ?string $engineResult = null): void
    {
        $this->successType = $type;
        $this->successCourse = $course;
        $this->successEngineResult = $engineResult;
        $this->showSuccessModal = true;
    }

    /**
     * Resolved fresh on every call rather than cached on the component:
     * Livewire only rehydrates public properties between requests, so a
     * value stashed on a private property in mount() would be gone by
     * the time an action method (submitWaiver, etc.) runs.
     */
    private function studentId(): int
    {
        $studentId = StudentModel::query()->where('user_id', Auth::id())->value('id');

        abort_if($studentId === null, 403, __('No student record is linked to your account.'));

        return $studentId;
    }

    /**
     * Scoped to the courses of the career(s) the student is enrolled in
     * (via student_study_plan → study_plans.career_id), plus any
     * cross-cutting course with no career_id (is_service = true) — so a
     * Salud Ocupacional student never sees Ingeniería del Software
     * courses in this dropdown, and vice versa. Falls back to the full
     * catalog if the student has no enrollment on file, so the form
     * never renders an empty dropdown.
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function courseOptions(): array
    {
        $careerIds = $this->studentCareerIds();

        return CourseModel::query()
            ->when($careerIds !== [], function ($query) use ($careerIds): void {
                $query->where(function ($q) use ($careerIds): void {
                    $q->whereIn('career_id', $careerIds)->orWhereNull('career_id');
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (CourseModel $c) => [
                'id' => $c->id,
                'label' => "{$c->code} — {$c->name}",
            ])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function studentCareerIds(): array
    {
        return DB::table('student_study_plan')
            ->join('study_plans', 'study_plans.id', '=', 'student_study_plan.study_plan_id')
            ->where('student_study_plan.student_id', $this->studentId())
            ->pluck('study_plans.career_id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function courseLabelsById(): array
    {
        return CourseModel::query()
            ->get(['id', 'name', 'code'])
            ->mapWithKeys(fn (CourseModel $c) => [$c->id => "{$c->code} — {$c->name}"])
            ->all();
    }

    private function courseLabel(?int $courseId): string
    {
        return $this->courseLabelsById()[$courseId] ?? (string) $courseId;
    }

    /**
     * @param  array<int, string>  $courseLabels
     * @return array<string, mixed>
     */
    private function toRow(Request $request, array $courseLabels): array
    {
        return [
            'id' => $request->id(),
            'type' => $request->type(),
            'course' => $courseLabels[$request->courseId()] ?? (string) $request->courseId(),
            'requiredCourse' => $request->requiredCourseId()
                ? ($courseLabels[$request->requiredCourseId()] ?? (string) $request->requiredCourseId())
                : null,
            'waiverJustification' => $request->waiverJustification(),
            'status' => $request->status(),
            'statusVariant' => $this->statusVariant($request->status()),
            'result' => $request->engineResult(),
            'submittedAt' => $request->createdAt(),
            'estimatedDate' => $request->estimatedResolutionDate(),
            'originInstitution' => $request->originInstitution(),
            'externalCourse' => $request->externalCourse(),
        ];
    }

    private function statusVariant(string $status): string
    {
        return match (true) {
            in_array($status, ['Approved by Docencia', 'Approved by Registro'], true) => 'positive',
            in_array($status, ['Denied by Docencia', 'Denied by Registro'], true) => 'negative',
            $status === 'Pending Review' => 'pending',
            default => '',
        };
    }
}
