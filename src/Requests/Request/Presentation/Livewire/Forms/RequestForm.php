<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Livewire\Forms;

use Livewire\Form;
use Src\Requests\Request\Application\DTOs\RequestDTO;
use Src\Requests\Request\Presentation\Livewire\Forms\Concerns\StoresRequestAttachments;

/**
 * Livewire Form Object for the "New request" modal. There is no edit
 * counterpart (no fromEntity()/toDto() hydration-for-update pair): once
 * created, a Request's fields don't change — what changes is its status,
 * handled separately by RequestComponent::changeStatus() and
 * ChangeRequestStatusUseCase, not through this form.
 *
 * Unlike WaiverRequestForm/ValidationRequestForm (one per flow), this
 * form models both flows behind a single $type switch — same file-upload
 * requirements as those two, only $type-gated instead of split by class.
 */
class RequestForm extends Form
{
    use StoresRequestAttachments;

    public ?int $studentId = null;

    public string $type = 'Requirement Waiver';

    public ?int $courseId = null;

    public ?int $requiredCourseId = null;

    public ?string $originInstitution = null;

    public ?string $externalCourse = null;

    public $supportDocument = null;

    public $externalProgramFile = null;

    public $gradeCertificationFile = null;

    public $institutionProofFile = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'studentId' => ['required', 'integer', 'exists:students,id'],
            'type' => ['required', 'in:Requirement Waiver,Validation'],
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'requiredCourseId' => ['required_if:type,Requirement Waiver', 'nullable', 'integer', 'exists:courses,id', 'different:courseId'],
            'originInstitution' => ['required_if:type,Validation', 'nullable', 'string', 'max:150'],
            'externalCourse' => ['required_if:type,Validation', 'nullable', 'string', 'max:150'],
            'supportDocument' => ['required_if:type,Requirement Waiver', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'externalProgramFile' => ['required_if:type,Validation', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'gradeCertificationFile' => ['required_if:type,Validation', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'institutionProofFile' => ['required_if:type,Validation', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function toDto(): RequestDTO
    {
        return new RequestDTO(
            studentId: $this->studentId,
            type: $this->type,
            courseId: $this->courseId,
            requiredCourseId: $this->type === 'Requirement Waiver' ? $this->requiredCourseId : null,
            originInstitution: $this->type === 'Validation' ? $this->originInstitution : null,
            externalCourse: $this->type === 'Validation' ? $this->externalCourse : null,
            attachments: $this->type === 'Requirement Waiver'
                ? [$this->storeAttachment($this->supportDocument, 'support_document')]
                : [
                    $this->storeAttachment($this->externalProgramFile, 'external_program'),
                    $this->storeAttachment($this->gradeCertificationFile, 'grade_certification'),
                    $this->storeAttachment($this->institutionProofFile, 'institution_proof'),
                ],
        );
    }
}
