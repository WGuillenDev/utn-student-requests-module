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
use Livewire\Component;
use Livewire\WithFileUploads;
use Src\Requests\Request\Application\UseCases\AssignEstimatedResolutionDateUseCase;
use Src\Requests\Request\Application\UseCases\ChangeRequestStatusUseCase;
use Src\Requests\Request\Application\UseCases\CreateRequestUseCase;
use Src\Requests\Request\Application\UseCases\DeleteRequestUseCase;
use Src\Requests\Request\Application\UseCases\FindRequestUseCase;
use Src\Requests\Request\Application\UseCases\ListRequestsUseCase;
use Src\Requests\Request\Application\UseCases\SaveExternalCourseDataUseCase;
use Src\Requests\Request\Domain\Contracts\RequestAttachmentRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\InvalidStatusTransitionException;
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

    public bool $showCreateModal = false;

    public bool $showReviewModal = false;

    /**
     * Detail view — available for EVERY request regardless of status,
     * so Docencia can always look back at a closed request's full data
     * and attached documents. For an open Validation request it is no
     * longer strictly read-only: the "Cursos a convalidar" section
     * embeds the Reconocer/No reconocer decision inline (via the same
     * changeStatus() used by showReviewModal, see below) instead of
     * requiring a trip to the separate review modal.
     */
    public bool $showViewModal = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $viewingRequest = null;

    /**
     * Bound to the "Código"/"Créditos" inputs in the "Cursos a
     * convalidar" table — kept separate from $viewingRequest (a plain
     * read array rebuilt on every open/refresh) since these are live
     * form inputs.
     */
    public string $viewingExternalCourseCode = '';

    public string $viewingExternalCourseCredits = '';

    /**
     * The review modal's "Tipo de documento" + file picker — Docencia
     * attaching its own supporting document to a request, on top of
     * whatever the student already submitted. Free text rather than
     * one of the 4 fixed student-form document types (support_document/
     * external_program/grade_certification/institution_proof): Docencia
     * may need to attach things those categories don't cover (e.g. a
     * signed resolution or a Registro memo).
     */
    public string $reviewDocumentType = '';

    public $reviewDocumentFile = null;

    /**
     * The review modal's own read-only "Adjuntos" list — same shape as
     * $viewingRequest['documents'], populated by openReviewModal() so
     * Docencia can see what's already attached before deciding whether
     * to add another document, without needing the separate detail
     * modal open at the same time.
     *
     * @var array<int, array{id: int, documentType: string, originalName: string, sizeKb: int}>
     */
    public array $reviewingDocuments = [];

    public ?int $reviewingId = null;

    /**
     * The request's type, seeded by openReviewModal() — the status
     * button row needs it to decide whether "Aprobada" gets its own
     * button. Course Validation requests reach Approved through
     * Reconocer in the "Cursos a convalidar" table instead, so the
     * reference design's 4-button row (no Aprobada) applies there;
     * Requirement Waiver has no such alternate path, so it keeps a
     * 5th button.
     */
    public string $reviewingType = '';

    public string $reviewStatus = '';

    public string $reviewComment = '';

    public string $reviewEstimatedDate = '';

    /**
     * ES-02's precedent indicator: the reference resolution number of an
     * approved catalog precedent linked to the request being reviewed.
     * Null when the request has no linked precedent (waiver requests,
     * or validations without an approved precedent match).
     */
    public ?string $reviewPrecedentResolution = null;

    public RequestForm $form;

    public function mount(): void
    {
        $this->authorize('viewAny', Request::class);
        $this->sortKey = 'created_at';
        $this->sortDir = 'desc';
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
     * Detail — available for any request the user is authorized to
     * 'view' (RequestPolicy::view()), open or closed. Builds a plain
     * array (not the domain entity) because the blade also needs
     * cross-context labels (student name, course label, precedent
     * resolution, attached documents) that don't belong on the Request
     * entity itself.
     *
     * Also seeds $reviewingId/$reviewComment (normally openReviewModal's
     * job) so the "Cursos a convalidar" table's inline Reconocer/No
     * reconocer buttons have a valid target to call changeStatus()
     * against without opening the separate review modal.
     */
    public function openViewModal(int $id, FindRequestUseCase $useCase): void
    {
        $request = $useCase->handle($id);
        $this->authorize('view', $request);

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
            'engineResult' => $request->engineResult(),
            'status' => $request->status(),
            'estimatedResolutionDate' => $request->estimatedResolutionDate(),
            'submittedAt' => $request->createdAt(),
            'precedentResolution' => $request->validationPrecedentId() !== null
                ? ValidationPrecedentModel::query()->find($request->validationPrecedentId())?->resolution_number
                : null,
            'documents' => $this->documentsFor($request->id()),
            'studentRecord' => $this->studentRecord($request->studentId()),
            'statusHistory' => $this->statusHistoryFor($request->id()),
            'canReview' => Auth::user()->can('review', $request) && ! $request->isFinal(),
        ];

        $this->viewingExternalCourseCode = $request->externalCourseCode() ?? '';
        $this->viewingExternalCourseCredits = $request->externalCourseCredits() !== null
            ? (string) $request->externalCourseCredits()
            : '';

        $this->reviewingId = $id;
        $this->reviewStatus = $request->status();
        $this->reviewComment = '';
        $this->resetValidation();

        $this->showViewModal = true;
    }

    /**
     * Shared by openViewModal()/openReviewModal()/uploadReviewDocument()
     * — both modals now show the same attachment list (the review modal
     * gained its own read-only "Adjuntos" section alongside the new
     * upload form), so this stays a single query instead of two copies
     * drifting apart.
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
     * "Historial de estados" — every transition ChangeRequestStatusUseCase
     * has ever recorded for this request, oldest first. `previousStatus`
     * is null only for a row that doesn't exist yet today: nothing in
     * this module writes a status-history row at creation time (only
     * Docencia's later reviews go through ChangeRequestStatusUseCase),
     * so in practice every row here has both a previous and a new
     * status — the null case is handled in the view purely so this
     * doesn't silently misrender if/when that changes.
     *
     * @return array<int, array{previousStatus: ?string, newStatus: string, comment: ?string, changedBy: string, createdAt: ?string}>
     */
    private function statusHistoryFor(int $requestId): array
    {
        return RequestStatusHistoryModel::query()
            ->where('request_id', $requestId)
            ->with('user:id,name')
            ->orderBy('created_at')
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
     * "Guardar datos externos" — persists the external course's code/
     * credits without touching status, so Docencia can record what the
     * foreign transcript says before deciding Reconocer/No reconocer.
     */
    public function saveExternalCourseData(SaveExternalCourseDataUseCase $useCase, FindRequestUseCase $findUseCase): void
    {
        $request = $findUseCase->handle($this->reviewingId);
        $this->authorize('review', $request);

        $credits = $this->viewingExternalCourseCredits !== ''
            ? (int) $this->viewingExternalCourseCredits
            : null;

        $useCase->handle(
            requestId: $this->reviewingId,
            code: $this->viewingExternalCourseCode !== '' ? $this->viewingExternalCourseCode : null,
            credits: $credits,
        );

        $this->viewingRequest['externalCourseCode'] = $this->viewingExternalCourseCode !== '' ? $this->viewingExternalCourseCode : null;
        $this->viewingRequest['externalCourseCredits'] = $credits;

        $this->dispatch('toast', variant: 'success', text: __('External course data saved.'));
    }

    /**
     * Courses whose academic_records status still counts as completed
     * progress toward the plan — same set as
     * EloquentStudentAcademicProfileRepository::PROGRESS_STATUSES,
     * duplicated here (Presentation has no business reading a
     * Domain-adjacent Infrastructure class's private constant) rather
     * than shared, since it's a two-line list unlikely to drift.
     *
     * @var array<int, string>
     */
    private const PROGRESS_STATUSES = ['Approved', 'Credited by Equivalence', 'Credited by Validation', 'Requirement Waived'];

    /**
     * @var array<int, string>
     */
    private const CREDITED_STATUSES = ['Credited by Equivalence', 'Credited by Validation', 'Requirement Waived'];

    /**
     * Docencia's "expediente del estudiante": identification, current
     * career/plan, an aggregate summary (approved/failed/credited
     * counts, weighted average grade, earned/total plan credits), and
     * the full per-course academic record — the same academic_records
     * rows the WaiverEngine reads via
     * StudentAcademicProfileRepositoryInterface, surfaced here
     * read-only so a reviewer can see the record behind an engine
     * result without leaving the request detail modal.
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
                'course' => $record->course !== null ? "{$record->course->code} — {$record->course->name}" : (string) $record->course_id,
                'status' => $record->status,
                'grade' => $record->grade !== null ? (string) $record->grade : null,
                'period' => $record->academicPeriod !== null ? "{$record->academicPeriod->term} {$record->academicPeriod->year}" : '—',
                'planLevel' => $info['levelNumber'] ?? null,
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

    public function openReviewModal(int $id, FindRequestUseCase $useCase): void
    {
        $request = $useCase->handle($id);
        $this->authorize('review', $request);

        $this->reviewingId = $id;
        $this->reviewingType = $request->type();
        $this->reviewStatus = $request->status();
        $this->reviewComment = '';
        $this->reviewEstimatedDate = $request->estimatedResolutionDate() ?? '';
        $this->reviewPrecedentResolution = $request->validationPrecedentId() !== null
            ? ValidationPrecedentModel::query()->find($request->validationPrecedentId())?->resolution_number
            : null;
        $this->reviewDocumentType = '';
        $this->reviewDocumentFile = null;
        $this->reviewingDocuments = $this->documentsFor($id);
        $this->resetValidation();
        $this->showReviewModal = true;
    }

    public function closeReviewModal(): void
    {
        $this->showReviewModal = false;
        $this->reviewPrecedentResolution = null;
    }

    /**
     * $status lets the "Cursos a convalidar" table's Reconocer/No
     * reconocer buttons drive this same method directly (with
     * $reviewingId seeded by openViewModal()) instead of going through
     * the separate review modal's status dropdown — Reconocer/No
     * reconocer are just Approved/Denied under another name, per the
     * team's decision to keep a single status field rather than add a
     * distinct per-course resolution field.
     */
    public function changeStatus(ChangeRequestStatusUseCase $useCase, ListRequestsUseCase $listUseCase, FindRequestUseCase $findUseCase, ?string $status = null): void
    {
        if ($status !== null) {
            $this->reviewStatus = $status;
        }

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

        if ($this->showViewModal) {
            $this->openViewModal($this->reviewingId, $findUseCase);
        }
    }

    /**
     * "Guardar fecha" — the estimated resolution date's own save action,
     * independent from changeStatus()/"Confirmar" so a reviewer can
     * record/correct the date without also having to pick and confirm
     * a status. Uses AssignEstimatedResolutionDateUseCase rather than
     * ChangeRequestStatusUseCase precisely to avoid writing a same-
     * status RequestStatusHistory row or firing a status-changed email
     * for what isn't actually a status change.
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

        if ($this->showViewModal) {
            $this->openViewModal($this->reviewingId, $findUseCase);
        }
    }

    /**
     * Docencia attaching its own document to a request — same storage
     * pipeline the student-facing forms use (StoresRequestAttachments,
     * RequestAttachmentRepositoryInterface::attach()), just triggered
     * from the review modal instead of at creation time. Gated by the
     * same 'review' ability as the rest of this modal rather than a new
     * permission string, since every role that can review a request is
     * exactly the role that should be able to attach supporting
     * documents to it.
     */
    public function uploadReviewDocument(RequestAttachmentRepositoryInterface $attachmentRepository, FindRequestUseCase $findUseCase): void
    {
        $request = $findUseCase->handle($this->reviewingId);
        $this->authorize('review', $request);

        $this->validate([
            'reviewDocumentType' => ['required', 'string', 'max:150'],
            // Same mime allow-list as everywhere else in this module
            // (WaiverRequestForm/ValidationRequestForm) — bumped to
            // 10MB to match the more generous of the two existing
            // limits (the student Validation form's), since Docencia's
            // own attachments are just as likely to be scanned multi-
            // page documents.
            'reviewDocumentFile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $attachment = $this->storeAttachment($this->reviewDocumentFile, $this->reviewDocumentType);
        $attachmentRepository->attach($this->reviewingId, [$attachment]);

        $this->reviewDocumentType = '';
        $this->reviewDocumentFile = null;
        $this->reviewingDocuments = $this->documentsFor($this->reviewingId);
        $this->dispatch('toast', variant: 'success', text: __('Document uploaded.'));

        if ($this->showViewModal) {
            $this->openViewModal($this->reviewingId, $findUseCase);
        }
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
}
