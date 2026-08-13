<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Livewire\Forms;

use Livewire\Form;
use Src\Requests\Request\Application\DTOs\RequestDTO;
use Src\Requests\Request\Presentation\Livewire\Forms\Concerns\StoresRequestAttachments;

/**
 * Student self-service counterpart to RequestForm — models only the
 * "Validation" flow, with no studentId field (see WaiverRequestForm's
 * docblock for why this is a separate Form Object) and 3 mandatory
 * supporting documents, one per document_type expected by the
 * Docencia review inbox: external_program, grade_certification,
 * institution_proof.
 */
class ValidationRequestForm extends Form
{
    use StoresRequestAttachments;

    public ?int $courseId = null;

    public ?string $originInstitution = null;

    public ?string $externalCourse = null;

    public $externalProgramFile = null;

    public $gradeCertificationFile = null;

    public $institutionProofFile = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'originInstitution' => ['required', 'string', 'max:150'],
            'externalCourse' => ['required', 'string', 'max:150'],
            'externalProgramFile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'gradeCertificationFile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'institutionProofFile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function toDto(int $studentId): RequestDTO
    {
        return new RequestDTO(
            studentId: $studentId,
            type: 'Validation',
            courseId: (int) $this->courseId,
            originInstitution: $this->originInstitution,
            externalCourse: $this->externalCourse,
            attachments: [
                $this->storeAttachment($this->externalProgramFile, 'external_program'),
                $this->storeAttachment($this->gradeCertificationFile, 'grade_certification'),
                $this->storeAttachment($this->institutionProofFile, 'institution_proof'),
            ],
        );
    }
}
