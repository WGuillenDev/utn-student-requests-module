<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Livewire\Forms;

use Livewire\Form;
use Src\Requests\Request\Application\DTOs\RequestDTO;
use Src\Requests\Request\Presentation\Livewire\Forms\Concerns\StoresRequestAttachments;

/**
 * Student self-service counterpart to RequestForm — models only the
 * "Requirement Waiver" flow, with no studentId field (the owning
 * student is resolved server-side from the logged-in user, see
 * StudentRequestComponent::studentId()) and a mandatory supporting
 * document. Kept as its own Form Object rather than reusing RequestForm
 * so this file-upload requirement never applies to the staff inbox
 * (RequestComponent), whose create modal has no file input at all.
 */
class WaiverRequestForm extends Form
{
    use StoresRequestAttachments;

    public ?int $courseId = null;

    public ?int $requiredCourseId = null;

    public $supportDocument = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'requiredCourseId' => ['required', 'integer', 'exists:courses,id'],
            'supportDocument' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function toDto(int $studentId): RequestDTO
    {
        return new RequestDTO(
            studentId: $studentId,
            type: 'Requirement Waiver',
            courseId: (int) $this->courseId,
            requiredCourseId: (int) $this->requiredCourseId,
            attachments: [
                $this->storeAttachment($this->supportDocument, 'support_document'),
            ],
        );
    }
}
