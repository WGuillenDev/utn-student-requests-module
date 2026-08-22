<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Livewire\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;
use Src\Requests\Request\Application\DTOs\RequestDTO;
use Src\Requests\Request\Presentation\Livewire\Forms\Concerns\StoresRequestAttachments;

/**
 * Student self-service counterpart to RequestForm — models only the
 * "Validation" flow, with no studentId field (see WaiverRequestForm's
 * docblock for why this is a separate Form Object).
 *
 * Decided with Docencia and Registro: one submission bundles up to
 * MAX_COURSES course lines (internal course + external course name +
 * origin institution) sharing a single pool of supporting documents,
 * instead of one course per submission with 3 separately-named
 * required documents. There is still no shared "batch" aggregate in
 * the Domain — StudentRequestComponent::submitValidation() turns each
 * course line into its own independent Request, so Docencia reviews
 * and resolves every one on its own, same as before.
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
            'courses.*.courseId' => ['required', 'integer', 'exists:courses,id'],
            'courses.*.externalCourse' => ['required', 'string', 'max:150'],
            'courses.*.originInstitution' => ['required', 'string', 'max:150'],
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * One Request per course line, each with its own copy of every
     * attached document — the `files` table's (disk, path) unique
     * constraint means the same stored file can't be pointed to by
     * more than one row, so each course line gets a fresh copy of the
     * shared uploads rather than reusing a single stored File.
     *
     * @return array<int, RequestDTO>
     */
    public function toDtos(int $studentId): array
    {
        return array_map(
            fn (array $course): RequestDTO => new RequestDTO(
                studentId: $studentId,
                type: 'Validation',
                courseId: (int) $course['courseId'],
                originInstitution: $course['originInstitution'],
                externalCourse: $course['externalCourse'],
                attachments: array_map(
                    fn (TemporaryUploadedFile $file) => $this->storeAttachment($file, self::DOCUMENT_TYPE),
                    $this->documents,
                ),
            ),
            $this->courses,
        );
    }
}
