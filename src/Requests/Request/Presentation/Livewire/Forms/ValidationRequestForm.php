<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Livewire\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;
use Src\Requests\Request\Application\DTOs\RequestDTO;
use Src\Requests\Request\Presentation\Livewire\Forms\Concerns\StoresRequestAttachments;

/**
 * Student self-service form for the Validation flow.
 *
 * One submission bundles up to MAX_COURSES course lines sharing a single
 * pool of supporting documents. There is no batch aggregate in the
 * Domain: each line becomes an independent Request that Docencia
 * reviews and resolves on its own.
 */
class ValidationRequestForm extends Form
{
    use StoresRequestAttachments;

    public const MAX_COURSES = 8;

    private const DOCUMENT_TYPE = 'supporting_document';

    /**
     * @var array<int, array{courseId: int|string|null, externalCourse: string|null, originInstitution: string|null}>
     */
    public array $courses = [
        ['courseId' => null, 'externalCourse' => null, 'originInstitution' => null],
    ];

    /**
     * @var array<int, TemporaryUploadedFile>
     */
    public array $documents = [];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'courses' => ['required', 'array', 'min:1', 'max:'.self::MAX_COURSES],
            'courses.*.courseId' => ['required', 'integer', 'exists:courses,id', 'distinct'],
            'courses.*.externalCourse' => ['required', 'string', 'max:150'],
            'courses.*.originInstitution' => ['required', 'string', 'max:150'],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * One DTO per course line, each with its own copy of every attached
     * document: the files table's (disk, path) unique constraint forbids
     * two rows pointing at one stored file.
     *
     * Only the first DTO sets notify, so the batch produces a single
     * confirmation email. See RequestDTO.
     *
     * @param  array<int, string>  $courseLabels  Course id => label, used
     *   only to build batchCourseNames; never persisted.
     * @return array<int, RequestDTO>
     */
    public function toDtos(int $studentId, array $courseLabels = []): array
    {
        $batchCourseNames = collect($this->courses)
            ->map(fn (array $course) => $courseLabels[(int) $course['courseId']] ?? (string) $course['courseId'])
            ->implode(', ');

        return collect($this->courses)
            ->values()
            ->map(fn (array $course, int $index): RequestDTO => new RequestDTO(
                studentId: $studentId,
                type: 'Validation',
                courseId: (int) $course['courseId'],
                originInstitution: $course['originInstitution'],
                externalCourse: $course['externalCourse'],
                attachments: array_map(
                    fn (TemporaryUploadedFile $file) => $this->storeAttachment($file, self::DOCUMENT_TYPE),
                    $this->documents,
                ),
                batchCourseNames: $batchCourseNames,
                notify: $index === 0,
            ))
            ->all();
    }
}
