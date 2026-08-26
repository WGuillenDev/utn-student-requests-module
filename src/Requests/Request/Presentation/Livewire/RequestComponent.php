<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Livewire;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Documents\Models\FileModel;
use App\Infrastructure\Persistence\Eloquent\Requests\Models\RequestModel;
use App\Infrastructure\Persistence\Eloquent\Requests\Models\RequestStatusHistoryModel;
use App\Infrastructure\Persistence\Eloquent\Requests\Models\ValidationPrecedentModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\AcademicRecordModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use App\Livewire\Concerns\InteractsWithDataTable;
use App\Livewire\Concerns\InteractsWithExports;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Src\Requests\Request\Application\UseCases\AssignEstimatedResolutionDateUseCase;
use Src\Requests\Request\Application\UseCases\ChangeRequestStatusUseCase;
use Src\Requests\Request\Application\UseCases\CreateRequestUseCase;
use Src\Requests\Request\Application\UseCases\DeleteRequestUseCase;
use Src\Requests\Request\Application\UseCases\FindRequestUseCase;
use Src\Requests\Request\Application\UseCases\IssueResolutionUseCase;
use Src\Requests\Request\Application\UseCases\ListRequestsUseCase;
use Src\Requests\Request\Application\UseCases\SaveExternalCourseDataUseCase;
use Src\Requests\Request\Domain\Contracts\RequestAttachmentRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\InvalidStatusTransitionException;
use Src\Requests\Request\Domain\ValueObjects\RequestAttachment;
use Src\Requests\Request\Domain\ValueObjects\ResolutionData;
use Src\Requests\Request\Presentation\Livewire\Forms\Concerns\StoresRequestAttachments;
use Src\Requests\Request\Presentation\Livewire\Forms\RequestForm;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestComponent extends Component
{
    use AuthorizesRequests;
    use InteractsWithDataTable;
    use InteractsWithExports;
    use StoresRequestAttachments;
    use WithFileUploads;

    /**
     * Requests grow continuously over time (unlike Role/Permission's
     * small reference catalog), so this table is server-paginated from
     * the start rather than shipped to the browser in one response.
     */
    protected string $tableMode = 'server';

    /**
     * The only accepted values of the URL-bound $activeTab. Anything else
     * arriving through ?tab= is discarded in favour of the default —
     * see normalizeActiveTab().
     *
     * @var array<int, string>
     */
    private const TABS = ['waiver', 'validation', 'history'];

    private const DEFAULT_TAB = 'waiver';

    /**
     * Which inbox tab is open, bound to ?tab= so each sidebar link is a
     * plain href to the tab it names and reloading or bookmarking keeps
     * you on it (see sidebar.blade.php).
     *
     * Being URL-bound makes this user-supplied input: it is normalized
     * against self::TABS on mount and on every update, so a hand-edited
     * query string can never put the component into an unlisted tab.
     */
    #[Url(as: 'tab', history: true)]
    public string $activeTab = self::DEFAULT_TAB;

    public bool $showCreateModal = false;

    /**
     * The single modal for both viewing and reviewing a request. Open for
     * any request regardless of status; the review controls inside are
     * gated by $viewingRequest['canReview'].
     */
    public bool $showViewModal = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $viewingRequest = null;

    /**
     * Bound to the "Código"/"Créditos"/"Nota" inputs in the "Cursos a
     * convalidar" table — kept separate from $viewingRequest (a plain
     * read array rebuilt on every open/refresh) since these are live
     * form inputs.
     */
    public string $viewingExternalCourseCode = '';

    public string $viewingExternalCourseCredits = '';

    public string $viewingExternalCourseGrade = '';

    /**
     * Document Docencia attaches on top of the student's own files. Free
     * text rather than one of the four fixed student-form document types,
     * since staff may need categories those do not cover.
     */
    public string $reviewDocumentType = '';

    public $reviewDocumentFile = null;

    public ?int $reviewingId = null;

    /**
     * Seeded by openViewModal(). A Validation reaches 'Approved by
     * Docencia' through the Reconocer action in the courses table, so it
     * does not render the separate approve button a Waiver does.
     */
    public string $reviewingType = '';

    public string $reviewStatus = '';

    public string $reviewComment = '';

    public string $reviewEstimatedDate = '';

    /**
     * Registro's "Emitir resolución (RSREC-001)" panel — the formal
     * identifiers of the session where the resolution was taken. Reset
     * in openViewModal(); consumed by issueResolution().
     */
    public string $resolutionNumber = '';

    public string $resolutionSessionNumber = '';

    public string $resolutionActNumber = '';

    public string $resolutionSessionDate = '';

    /**
     * Whether the resolution-date cell is showing its editable input
     * rather than the read-only value. Reset in openViewModal(), so it
     * collapses again after a successful save; a failed save returns
     * early and leaves the input open with its error message.
     */
    public bool $editingResolutionDate = false;

    /**
     * A Reconocer/No reconocer decision staged by markValidationDecision()
     * so the courses table can preview it. Nothing is persisted until
     * "Resolver y enviar a Registro" runs changeStatus(). Reset to null in
     * openViewModal().
     */
    public ?string $stagedValidationDecision = null;

    /**
     * Success confirmation shown instead of the generic toast when
     * changeStatus() hands a request to Registro, for either type.
     */
    public bool $showSentToRegistroModal = false;

    /**
     * Same success-modal pattern as $showSentToRegistroModal, for the
     * closing end of the flow: set right after issueResolution() (RSREC-001)
     * publishes the resolution, once the warning dialog has been confirmed.
     */
    public bool $showResolutionPublishedModal = false;

    public RequestForm $form;

    public function mount(): void
    {
        $this->authorize('viewAny', Request::class);
        $this->normalizeActiveTab();
        $this->sortKey = 'created_at';
        $this->sortDir = 'desc';
    }

    /**
     * Livewire hook: fires whenever $activeTab changes after mount,
     * including when the browser's back/forward navigation replays a
     * ?tab= value. Re-runs the same allow-list check as mount().
     */
    public function updatedActiveTab(): void
    {
        $this->normalizeActiveTab();
    }

    private function normalizeActiveTab(): void
    {
        if (! in_array($this->activeTab, self::TABS, true)) {
            $this->activeTab = self::DEFAULT_TAB;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function activeTypeFilter(): array
    {
        // 'history' is both types together, limited to the two statuses
        // Registro closes a request with. The default arm is unreachable
        // while normalizeActiveTab() guards $activeTab, and falls back to
        // the narrowest filter rather than an unfiltered list.
        $filters = match ($this->activeTab) {
            'validation' => ['type' => 'Validation'],
            'history' => ['statusIn' => ['Approved by Registro', 'Denied by Registro']],
            default => ['type' => 'Requirement Waiver'],
        };

        // Both bandejas are worklists, not archives: each role sees only
        // what still needs its action, and everything already closed
        // lives in the history tab.
        //  - Registro (holds 'requests.finalize'): Docencia has decided
        //    but the resolution is not issued yet.
        //  - Docencia (everyone else): not yet decided.
        if ($this->activeTab !== 'history') {
            $filters['statusIn'] = Auth::user()->hasPermissionTo('requests.finalize')
                ? ['Approved by Docencia', 'Denied by Docencia']
                : ['Pending Review', 'In Review'];
        }

        return $filters;
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

    /**
     * @var array<int, string>
     */
    private const FILE_FIELDS = [
        'form.supportDocument',
        'form.externalProgramFile',
        'form.gradeCertificationFile',
        'form.institutionProofFile',
    ];

    /**
     * Real-time validation for file inputs — same rationale as
     * StudentRequestComponent::updated(): without this, "max 5MB"/wrong
     * type errors only surface on full submit.
     */
    public function updated(string $property): void
    {
        if (in_array($property, self::FILE_FIELDS, true)) {
            $this->validateOnly($property);
        }
    }

    /**
     * Lets Docencia discard an already-attached file and go back to the
     * empty dropzone. $field is "form.property" as used by wire:model,
     * e.g. "form.supportDocument".
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

    public function save(CreateRequestUseCase $useCase, ListRequestsUseCase $listUseCase): void
    {
        $this->authorize('create', Request::class);

        $this->form->validate();
        $useCase->handle($this->form->toDto());

        $this->showCreateModal = false;
        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: __('Request created.'));
    }

    /**
     * Builds a plain array rather than passing the domain entity, since
     * the view also needs cross-context labels (student name, course
     * label, attachments) that do not belong on the entity.
     *
     * Also the single place every reviewXxx property is initialized.
     */
    public function openViewModal(int $id, FindRequestUseCase $useCase): void
    {
        $request = $useCase->handle($id);
        $this->authorize('view', $request);

        $this->editingResolutionDate = false;
        $this->showSentToRegistroModal = false;
        $this->showResolutionPublishedModal = false;
        $this->stagedValidationDecision = null;

        $students = $this->studentLabelsById();
        $courses = $this->courseLabelsById();

        $this->viewingRequest = [
            'id' => $request->id(),
            'student' => $students[$request->studentId()] ?? (string) $request->studentId(),
            'type' => $request->type(),
            'course' => $courses[$request->courseId()] ?? (string) $request->courseId(),
            'requiredCourse' => $request->requiredCourseId() !== null
                ? ($courses[$request->requiredCourseId()] ?? (string) $request->requiredCourseId())
                : null,
            'waiverJustification' => $request->waiverJustification(),
            'originInstitution' => $request->originInstitution(),
            'externalCourse' => $request->externalCourse(),
            'externalCourseCode' => $request->externalCourseCode(),
            'externalCourseCredits' => $request->externalCourseCredits(),
            'externalCourseGrade' => $request->externalCourseGrade(),
            'engineResult' => $request->engineResult(),
            'status' => $request->status(),
            'estimatedResolutionDate' => $request->displayEstimatedResolutionDate(),
            'submittedAt' => $request->createdAt(),
            'precedentResolution' => $request->validationPrecedentId() !== null
                ? ValidationPrecedentModel::query()->find($request->validationPrecedentId())?->resolution_number
                : null,
            'documents' => $this->documentsFor($request->id()),
            'studentRecord' => $this->studentRecord($request->studentId()),
            'statusHistory' => $this->statusHistoryFor($request->id()),
            'canReview' => Auth::user()->can('review', $request) && ! $request->isFinal(),
            'canFinalize' => Auth::user()->can('finalize', $request) && ! $request->isFinal(),
        ];

        $this->viewingExternalCourseCode = $request->externalCourseCode() ?? '';
        $this->viewingExternalCourseCredits = $request->externalCourseCredits() !== null
            ? (string) $request->externalCourseCredits()
            : '';
        $this->viewingExternalCourseGrade = $request->externalCourseGrade() !== null
            ? (string) $request->externalCourseGrade()
            : '';

        $this->reviewingId = $id;
        $this->reviewingType = $request->type();
        $this->reviewStatus = $request->status();
        $this->reviewComment = '';
        $this->reviewEstimatedDate = $request->displayEstimatedResolutionDate() ?? '';
        $this->reviewDocumentType = '';
        $this->reviewDocumentFile = null;
        $this->resolutionNumber = '';
        $this->resolutionSessionNumber = '';
        $this->resolutionActNumber = '';
        $this->resolutionSessionDate = '';
        $this->resetValidation();

        $this->showViewModal = true;
    }

    /**
     * Shared by openViewModal()/uploadReviewDocument() so the
     * attachment list is read from a single query instead of being
     * duplicated.
     *
     * @return array<int, array{id: int, documentType: string, originalName: string, sizeKb: int}>
     */
    private function documentsFor(int $requestId): array
    {
        return FileModel::query()
            ->where('fileable_type', RequestModel::class)
            ->where('fileable_id', $requestId)
            ->get(['id', 'document_type', 'original_name', 'size_bytes'])
            ->map(fn (FileModel $file) => [
                'id' => $file->id,
                'documentType' => $file->document_type,
                'originalName' => $file->original_name,
                'sizeKb' => (int) round($file->size_bytes / 1024),
            ])
            ->all();
    }

    /**
     * Every transition recorded for this request, oldest first. The first
     * row is the 'Received by Docencia' marker written at creation time
     * (CreateRequestUseCase), which is the one case where previousStatus
     * is null and the view renders "(nueva)".
     *
     * @return array<int, array{previousStatus: ?string, newStatus: string, comment: ?string, changedBy: string, createdAt: ?string}>
     */
    private function statusHistoryFor(int $requestId): array
    {
        return RequestStatusHistoryModel::query()
            ->where('request_id', $requestId)
            ->with('user:id,name')
            // created_at has whole-second precision, so the 3 rows a
            // single Docencia decision can now produce (see
            // ChangeRequestStatusUseCase) may tie on that column — id
            // (insertion order) breaks the tie deterministically instead
            // of leaving it to the database's default ordering.
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (RequestStatusHistoryModel $entry) => [
                'previousStatus' => $entry->previous_status,
                'newStatus' => $entry->new_status,
                'comment' => $entry->comment,
                'changedBy' => $entry->user_id === null ? __('System') : $entry->user->name,
                'createdAt' => $entry->created_at?->format('d/m/Y H:i'),
            ])
            ->all();
    }

    /**
     * Stages a Reconocer/No reconocer decision without persisting it.
     * Still authorized like any review action, so the UI state cannot be
     * toggled by an unauthorized direct Livewire call.
     *
     * Requires the course fields first, and a reason for "No reconocer".
     * That reason is flagged under its own 'viewingCourseReason' key so
     * the unrelated "Comentario adicional" textarea — which shares the
     * $reviewComment property — is not marked as required too.
     */
    public function markValidationDecision(FindRequestUseCase $findUseCase, string $decision): void
    {
        if (! in_array($decision, ['Approved by Docencia', 'Denied by Docencia'], true)) {
            return;
        }

        $request = $findUseCase->handle($this->reviewingId);
        $this->authorize('review', $request);

        $this->validateExternalCourseFields();

        if ($decision === 'Denied by Docencia' && trim($this->reviewComment) === '') {
            $this->addError('viewingCourseReason', __('A reason is required to not recognize this course.'));

            return;
        }

        $this->stagedValidationDecision = $decision;
        $this->resetErrorBag(['viewingCourseReason', 'reviewComment']);
    }

    /**
     * Código/Créditos/Nota are required before either staging a Reconocer/
     * No reconocer decision (markValidationDecision()) or actually
     * resolving and sending to Registro (changeStatus()) — Registro's
     * resolution document is generated from these same three fields, so
     * neither action makes sense without them.
     */
    private function validateExternalCourseFields(): void
    {
        $this->validate(
            rules: [
                'viewingExternalCourseCode' => ['required', 'string', 'max:50'],
                'viewingExternalCourseCredits' => ['required', 'integer', 'min:0', 'max:255'],
                'viewingExternalCourseGrade' => ['required', 'numeric', 'min:0', 'max:100'],
            ],
            attributes: [
                'viewingExternalCourseCode' => __('Code'),
                'viewingExternalCourseCredits' => __('Credits'),
                'viewingExternalCourseGrade' => __('Grade'),
            ],
        );
    }

    /**
     * Statuses that count as completed progress toward the plan. Kept in
     * sync with EloquentStudentAcademicProfileRepository by duplication
     * rather than sharing, since Presentation should not read an
     * Infrastructure constant.
     *
     * @var array<int, string>
     */
    private const PROGRESS_STATUSES = ['Approved', 'Credited by Equivalence', 'Credited by Validation', 'Requirement Waived'];

    /**
     * @var array<int, string>
     */
    private const CREDITED_STATUSES = ['Credited by Equivalence', 'Credited by Validation', 'Requirement Waived'];

    /**
     * academic_periods.term (1-3) as the roman numeral used everywhere
     * this university prints a period ("II - 2026"), for the student
     * academic record table.
     *
     * @var array<int, string>
     */
    private const TERM_NUMERALS = [1 => 'I', 2 => 'II', 3 => 'III'];

    /**
     * The student's academic record: identification, career/plan, an
     * aggregate summary and the per-course history. Read-only, so a
     * reviewer can see the record behind an engine result without
     * leaving the request detail modal.
     *
     * @return array{
     *     fullName: string, nationalId: string, email: ?string, active: bool,
     *     studyPlans: array<int, array{career: ?string, currentLevel: int, planLabel: string}>,
     *     courses: array<int, array{course: string, status: string, grade: string|null, period: string, planLevel: int|null}>,
     *     summary: array{approved: int, failed: int, credited: int, averageGrade: float|null, earnedCredits: int, totalCredits: int},
     * }
     */
    private function studentRecord(int $studentId): array
    {
        $student = StudentModel::query()
            ->whereKey($studentId)
            ->with([
                'user',
                'studyPlans.career',
                'studyPlans.levels.courses',
                'academicRecords.course',
                'academicRecords.academicPeriod',
            ])
            ->first();

        if ($student === null) {
            return [
                'fullName' => '', 'nationalId' => '', 'email' => null, 'active' => false,
                'studyPlans' => [], 'courses' => [],
                'summary' => ['approved' => 0, 'failed' => 0, 'credited' => 0, 'averageGrade' => null, 'earnedCredits' => 0, 'totalCredits' => 0],
            ];
        }

        // courseId -> plan level number + credits, resolved through the
        // student's own study plan(s) (course_level, via LevelModel::courses()).
        // A course could in principle sit in more than one plan; the last
        // one wins, which is harmless while students only carry one plan.
        $courseLevelInfo = [];
        $totalPlanCredits = 0;

        foreach ($student->studyPlans as $plan) {
            foreach ($plan->levels as $level) {
                foreach ($level->courses as $course) {
                    $credits = (int) $course->pivot->credits;
                    $totalPlanCredits += $credits;
                    $courseLevelInfo[$course->id] = ['levelNumber' => $level->number, 'credits' => $credits];
                }
            }
        }

        $approvedCount = 0;
        $failedCount = 0;
        $creditedCount = 0;
        $earnedCredits = 0;
        $gradeSum = 0.0;
        $gradeWeight = 0;

        $courses = $student->academicRecords->map(function (AcademicRecordModel $record) use (
            &$approvedCount, &$failedCount, &$creditedCount, &$earnedCredits, &$gradeSum, &$gradeWeight, $courseLevelInfo
        ) {
            $info = $courseLevelInfo[$record->course_id] ?? null;

            match (true) {
                $record->status === 'Approved' => $approvedCount++,
                $record->status === 'Failed' => $failedCount++,
                in_array($record->status, self::CREDITED_STATUSES, true) => $creditedCount++,
                default => null,
            };

            if (in_array($record->status, self::PROGRESS_STATUSES, true) && $info !== null) {
                $earnedCredits += $info['credits'];
            }

            if ($record->grade !== null) {
                // Weighted by plan credits when resolvable; a course
                // outside the student's own plan (e.g. an elective from
                // another plan) still counts, just unweighted (weight 1),
                // rather than being silently dropped from the average.
                $weight = $info['credits'] ?? 1;
                $gradeSum += (float) $record->grade * $weight;
                $gradeWeight += $weight;
            }

            return [
                'code' => $record->course?->code ?? (string) $record->course_id,
                'name' => $record->course?->name ?? '—',
                'status' => $record->status,
                'grade' => $record->grade !== null ? (string) $record->grade : null,
                'period' => $record->academicPeriod !== null
                    ? self::TERM_NUMERALS[$record->academicPeriod->term].' - '.$record->academicPeriod->year
                    : '—',
                'credits' => $info['credits'] ?? null,
            ];
        })->all();

        return [
            'fullName' => implode(' ', array_filter([$student->name, $student->last_name, $student->second_last_name])),
            'nationalId' => $student->national_id,
            'email' => $student->user?->email,
            'active' => $student->active,
            'studyPlans' => $student->studyPlans
                ->map(fn ($plan) => [
                    'career' => $plan->career?->name,
                    'currentLevel' => $plan->pivot->current_level,
                    'planLabel' => trim("{$plan->name} {$plan->implementation_year}"),
                ])
                ->all(),
            'courses' => $courses,
            'summary' => [
                'approved' => $approvedCount,
                'failed' => $failedCount,
                'credited' => $creditedCount,
                'averageGrade' => $gradeWeight > 0 ? round($gradeSum / $gradeWeight, 2) : null,
                'earnedCredits' => $earnedCredits,
                'totalCredits' => $totalPlanCredits,
            ],
        ];
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewingRequest = null;
    }

    /**
     * Statuses gated behind 'finalize' on top of 'review', so Docencia
     * cannot reach them even through a crafted Livewire call.
     *
     * @var array<int, string>
     */
    private const REGISTRAR_ONLY_STATUSES = ['Verified by Registro', 'Approved by Registro', 'Denied by Registro'];

    /**
     * $status lets the courses table's Reconocer/No reconocer buttons
     * drive this same method: they are Approved/Denied under another
     * name, the module keeping one status field rather than a separate
     * per-course resolution field.
     */
    public function changeStatus(ChangeRequestStatusUseCase $useCase, ListRequestsUseCase $listUseCase, FindRequestUseCase $findUseCase, SaveExternalCourseDataUseCase $externalCourseUseCase, ?string $status = null): void
    {
        if ($status !== null) {
            $this->reviewStatus = $status;
        }

        $request = $findUseCase->handle($this->reviewingId);
        $this->authorize('review', $request);

        // The Registro-named steps need the extra 'finalize' ability on
        // top of 'review' — Docencia holds 'review' but not 'finalize',
        // so it can reach every other status but not these.
        if (in_array($this->reviewStatus, self::REGISTRAR_ONLY_STATUSES, true)) {
            $this->authorize('finalize', $request);
        }

        if (in_array($this->reviewStatus, ['Denied by Docencia', 'Denied by Registro'], true) && trim($this->reviewComment) === '') {
            $this->addError('reviewComment', __('A comment is required to deny a request.'));

            return;
        }

        // Server-side backstop for the Validation flow: the button is
        // already disabled without a staged decision, and the course fields
        // were validated when it was staged — re-checked here in case they
        // were emptied afterward. Scoped to Validation because a Waiver's
        // own buttons reach this method with the same status values.
        if ($this->reviewingType === 'Validation' && in_array($this->reviewStatus, ['Approved by Docencia', 'Denied by Docencia'], true)) {
            if ($this->stagedValidationDecision === null) {
                $this->dispatch('toast', variant: 'danger', text: __('Mark Recognize or Do not recognize before resolving.'));

                return;
            }

            $this->validateExternalCourseFields();
        }

        // Validation has no separate "Guardar datos externos" step any
        // more — whatever is currently typed into Código/Créditos/Nota
        // is persisted together with the Reconocer/No reconocer decision,
        // right here, the moment "Resolver y enviar a Registro" is
        // confirmed.
        if ($this->reviewingType === 'Validation') {
            $externalCourseUseCase->handle(
                requestId: $this->reviewingId,
                code: $this->viewingExternalCourseCode !== '' ? $this->viewingExternalCourseCode : null,
                credits: $this->viewingExternalCourseCredits !== '' ? (int) $this->viewingExternalCourseCredits : null,
                grade: $this->viewingExternalCourseGrade !== '' ? (float) $this->viewingExternalCourseGrade : null,
            );
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

        $sentToRegistro = in_array($this->reviewingType, ['Requirement Waiver', 'Validation'], true)
            && in_array($this->reviewStatus, ['Approved by Docencia', 'Denied by Docencia'], true);

        $this->refreshTable($this->freshRows($listUseCase));
        $this->openViewModal($this->reviewingId, $findUseCase);

        if ($sentToRegistro) {
            $this->showSentToRegistroModal = true;
        } else {
            $this->dispatch('toast', variant: 'success', text: __('Request status updated.'));
        }
    }

    public function closeSentToRegistroModal(): void
    {
        $this->showSentToRegistroModal = false;
    }

    /**
     * Registro's closing action (RSREC-001), confirmed in the blade
     * before it runs. $decision is Registro's own call, independent of
     * Docencia's. Generates and archives the resolution document and,
     * on approval, registers the academic credit through
     * ChangeRequestStatusUseCase. No email is sent: the student checks
     * the outcome in the system. The request then leaves this bandeja
     * for the history tab.
     */
    public function issueResolution(IssueResolutionUseCase $useCase, ListRequestsUseCase $listUseCase, FindRequestUseCase $findUseCase, ?string $decision = null): void
    {
        if (! in_array($decision, ['Approved by Registro', 'Denied by Registro'], true)) {
            return;
        }

        $request = $findUseCase->handle($this->reviewingId);
        $this->authorize('finalize', $request);

        $this->validate(
            rules: [
                'resolutionNumber' => ['required', 'string', 'max:50'],
                'resolutionSessionNumber' => ['required', 'string', 'max:50'],
                'resolutionActNumber' => ['required', 'string', 'max:50'],
                'resolutionSessionDate' => ['required', 'date'],
                // Same allow-list as uploadReviewDocument() — optional
                // here: Registro isn't required to attach anything extra
                // beyond the resolution document itself.
                'reviewDocumentFile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            ],
            attributes: [
                'resolutionNumber' => __('Resolution number'),
                'resolutionSessionNumber' => __('Session No.'),
                'resolutionActNumber' => __('Act No.'),
                'resolutionSessionDate' => __('Session date'),
            ],
        );

        // Moved to permanent storage only now, at publish time — not
        // when the file was picked — same "stage first, persist on the
        // real action" pattern as Validation's external course fields.
        $registroAttachment = $this->reviewDocumentFile
            ? $this->storeAttachment($this->reviewDocumentFile, Request::REGISTRO_ATTACHMENT_DOCUMENT_TYPE)
            : null;

        try {
            $useCase->handle(
                requestId: $this->reviewingId,
                decision: $decision,
                resolution: new ResolutionData(
                    resolutionNumber: $this->resolutionNumber,
                    sessionNumber: $this->resolutionSessionNumber,
                    actNumber: $this->resolutionActNumber,
                    sessionDate: $this->resolutionSessionDate,
                ),
                reviewerId: Auth::id(),
                additionalAttachment: $registroAttachment,
            );
        } catch (InvalidStatusTransitionException $e) {
            $this->dispatch('toast', variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->reviewDocumentFile = null;
        $this->refreshTable($this->freshRows($listUseCase));
        $this->openViewModal($this->reviewingId, $findUseCase);
        $this->showResolutionPublishedModal = true;
    }

    public function closeResolutionPublishedModal(): void
    {
        $this->showResolutionPublishedModal = false;
    }

    /**
     * Saves the resolution date on its own, so a reviewer can record or
     * correct it without also picking a status. Goes through
     * AssignEstimatedResolutionDateUseCase rather than
     * ChangeRequestStatusUseCase to avoid writing a same-status history
     * row for something that is not a status change.
     */
    public function saveEstimatedDate(AssignEstimatedResolutionDateUseCase $useCase, ListRequestsUseCase $listUseCase, FindRequestUseCase $findUseCase): void
    {
        $request = $findUseCase->handle($this->reviewingId);
        $this->authorize('review', $request);

        if ($this->reviewEstimatedDate === '') {
            $this->addError('reviewEstimatedDate', __('Enter a date first.'));

            return;
        }

        $useCase->handle($this->reviewingId, $this->reviewEstimatedDate);

        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: __('Estimated resolution date saved.'));
        $this->openViewModal($this->reviewingId, $findUseCase);
    }

    /**
     * Attaches a staff document through the same storage pipeline the
     * student forms use, just triggered from the detail modal. Gated by
     * 'review' rather than a new permission, since any role that can
     * review a request should be able to attach documents to it.
     */
    public function uploadReviewDocument(RequestAttachmentRepositoryInterface $attachmentRepository, FindRequestUseCase $findUseCase): void
    {
        $request = $findUseCase->handle($this->reviewingId);
        $this->authorize('review', $request);

        $this->validate([
            'reviewDocumentType' => ['required', 'string', 'max:150'],
            // Same allow-list as the student forms, at the more generous
            // of their two size limits: staff attachments are just as
            // likely to be scanned multi-page documents.
            'reviewDocumentFile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $attachment = $this->storeAttachment($this->reviewDocumentFile, $this->reviewDocumentType);
        $attachmentRepository->attach($this->reviewingId, [$attachment]);

        $this->reviewDocumentType = '';
        $this->reviewDocumentFile = null;
        $this->dispatch('toast', variant: 'success', text: __('Document uploaded.'));
        $this->openViewModal($this->reviewingId, $findUseCase);
    }

    public function delete(int $id, DeleteRequestUseCase $useCase, ListRequestsUseCase $listUseCase, FindRequestUseCase $findUseCase): void
    {
        $this->authorize('delete', $findUseCase->handle($id));

        $useCase->handle($id);

        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: __('Request deleted.'));
    }

    /**
     * Exports honor the inbox's current search + sort — same
     * "what you see is what you export" expectation as WaiverRuleComponent/
     * ValidationPrecedentComponent, just unpaginated (the full matching
     * set, not only the current page).
     */
    public function exportPdf(PdfExporterInterface $exporter, ListRequestsUseCase $useCase): StreamedResponse
    {
        $this->authorize('exportPdf', Request::class);

        return $this->streamPdf(
            __('Requests'),
            $this->exportHeaders(),
            $this->exportableRows($useCase),
            Str::slug(__('Requests')).'.pdf',
            $exporter,
            paperSize: 'letter',
        );
    }

    public function exportExcel(ExcelExporterInterface $exporter, ListRequestsUseCase $useCase): StreamedResponse
    {
        $this->authorize('exportExcel', Request::class);

        return $this->streamExcel(
            $this->exportHeaders(),
            $this->exportableRows($useCase),
            Str::slug(__('Requests')).'.xlsx',
            $exporter,
        );
    }

    public function render(ListRequestsUseCase $useCase): View
    {
        $result = $useCase->paginate(
            search: $this->authorizedSearch(),
            perPage: $this->perPage,
            page: $this->page,
            sortBy: $this->sortKey,
            sortDir: $this->sortDir,
            filters: $this->activeTypeFilter(),
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
            'studentLabels' => $this->studentNamesById(),
            'courseLabels' => $this->courseLabelsById(),
            'studentOptions' => $this->studentOptions(),
            'courseOptions' => $this->courseOptions(),
        ])->layout('components.layouts.dashboard', [
            'title' => __('Requests'),
            'subtitle' => __('Requirement waivers and course validations submitted by students'),
        ]);
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function exportHeaders(): array
    {
        return [
            ['key' => 'student', 'label' => __('Student')],
            ['key' => 'type', 'label' => __('Type')],
            ['key' => 'course', 'label' => __('Course')],
            ['key' => 'status', 'label' => __('Status')],
            ['key' => 'estimatedDate', 'label' => __('Estimated date')],
            ['key' => 'submittedAt', 'label' => __('Submitted')],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function exportableRows(ListRequestsUseCase $useCase): array
    {
        $requests = $useCase->all(
            search: $this->authorizedSearch(),
            sortBy: $this->sortKey,
            sortDir: $this->sortDir,
            filters: $this->activeTypeFilter(),
        );

        $courses = $this->courseLabelsById();
        $students = $this->studentLabelsById();

        return array_map(fn (Request $request) => [
            'student' => $students[$request->studentId()] ?? (string) $request->studentId(),
            'type' => match ($request->type()) {
                'Requirement Waiver' => __('Requirement Waiver'),
                'Validation' => __('Course Validation'),
                default => $request->type(),
            },
            'course' => $courses[$request->courseId()] ?? (string) $request->courseId(),
            'status' => __($request->status()),
            'estimatedDate' => $request->estimatedResolutionDate() ?? '',
            'submittedAt' => $request->createdAt() ?? '',
        ], $requests);
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

    /**
     * @return array<int, string>
     */
    private function studentLabelsById(): array
    {
        return StudentModel::query()
            ->get(['id', 'name', 'last_name', 'national_id'])
            ->mapWithKeys(fn (StudentModel $s) => [$s->id => "{$s->name} {$s->last_name} ({$s->national_id})"])
            ->all();
    }

    /**
     * Name-only variant of studentLabelsById() for the inbox table's
     * "Student" column — the national ID stays available in the detail
     * modal and exports (which use studentLabelsById()), but is dropped
     * here to keep the row scannable; search still matches national_id
     * server-side regardless (EloquentRequestRepository::baseQuery()).
     *
     * @return array<int, string>
     */
    private function studentNamesById(): array
    {
        return StudentModel::query()
            ->get(['id', 'name', 'last_name'])
            ->mapWithKeys(fn (StudentModel $s) => [$s->id => "{$s->name} {$s->last_name}"])
            ->all();
    }

    private function freshRows(ListRequestsUseCase $useCase): array
    {
        // refreshTable() is a no-op outside client mode, so this exists
        // only to satisfy the shared signature its callers use.
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
}
