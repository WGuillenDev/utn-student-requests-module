<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Presentation\Livewire\Forms;

use Livewire\Form;
use Src\Requests\ValidationPrecedent\Application\DTOs\ValidationPrecedentDTO;
use Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent;

/**
 * Livewire Form Object — the input boundary for the ValidationPrecedent
 * create/edit modal. Free editing (no state machine), so it does have a
 * fromEntity()/toDto() pair, same as WaiverRuleForm.
 *
 * No `Rule::unique` here on purpose — unlike Role/WaiverRule, nothing on
 * this entity is unique by design: the same (institution, external
 * course) pair can legitimately have several resolutions over time.
 * Don't add one without confirming with the rest of the team first (see
 * the CRUD guide for this entity).
 */
class ValidationPrecedentForm extends Form
{
    public string $institution = '';

    public string $externalCourse = '';

    public ?int $courseId = null;

    public string $result = 'Approved';

    public string $resolutionNumber = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'institution' => ['required', 'string', 'max:150'],
            'externalCourse' => ['required', 'string', 'max:150'],
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'result' => ['required', 'in:Approved,Denied'],
            'resolutionNumber' => ['required', 'string', 'max:60'],
        ];
    }

    /**
     * Hydrates the form from an existing ValidationPrecedent for the
     * edit modal. Named `fromEntity()` rather than `fill()` for the same
     * LSP reason documented on RoleForm::fromEntity().
     */
    public function fromEntity(ValidationPrecedent $validationPrecedent): void
    {
        $this->institution = $validationPrecedent->institution();
        $this->externalCourse = $validationPrecedent->externalCourse();
        $this->courseId = $validationPrecedent->courseId();
        $this->result = $validationPrecedent->result();
        $this->resolutionNumber = $validationPrecedent->resolutionNumber();
    }

    public function toDto(): ValidationPrecedentDTO
    {
        return new ValidationPrecedentDTO(
            institution: $this->institution,
            externalCourse: $this->externalCourse,
            courseId: (int) $this->courseId,
            result: $this->result,
            resolutionNumber: $this->resolutionNumber,
        );
    }
}
