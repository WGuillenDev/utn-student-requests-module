<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Livewire\Forms;

use Livewire\Form;
use Src\Requests\Request\Application\DTOs\RequestDTO;

/**
 * Livewire Form Object for the "New request" modal. There is no edit
 * counterpart (no fromEntity()/toDto() hydration-for-update pair): once
 * created, a Request's fields don't change — what changes is its status,
 * handled separately by RequestComponent::changeStatus() and
 * ChangeRequestStatusUseCase, not through this form.
 */
class RequestForm extends Form
{
    public ?int $studentId = null;

    public string $type = 'Requirement Waiver';

    public ?int $courseId = null;

    public ?int $requiredCourseId = null;

    public ?string $originInstitution = null;

    public ?string $externalCourse = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'studentId' => ['required', 'integer', 'exists:students,id'],
            'type' => ['required', 'in:Requirement Waiver,Validation'],
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'requiredCourseId' => ['required_if:type,Requirement Waiver', 'nullable', 'integer', 'exists:courses,id'],
            'originInstitution' => ['required_if:type,Validation', 'nullable', 'string', 'max:150'],
            'externalCourse' => ['required_if:type,Validation', 'nullable', 'string', 'max:150'],
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
        );
    }
}
