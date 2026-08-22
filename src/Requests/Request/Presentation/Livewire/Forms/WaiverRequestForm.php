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

    /**
     * One of these 5 fixed categories, per SLR-002 — Docencia's paper
     * form for this request type. Kept as English enum values (see
     * the `requests.waiver_justification` migration) with the Spanish
     * wording only in the label translations, same as `type`/`status`.
     *
     * @var array<int, string>
     */
    public const JUSTIFICATIONS = [
        'Only Pending Requirement',
        'Final Level Parallel Enrollment',
        'Delayed Course Offering',
        'Minimum Academic Load',
        'Prior Knowledge Sufficiency',
    ];

    public ?int $courseId = null;

    public ?int $requiredCourseId = null;

    public ?string $justification = null;

    public $supportDocument = null;

    /**
     * Gate only — never persisted. The two "Notas importantes" paragraphs
     * come from Directriz Administrativa DA-VDOC-01-2020; requiring this
     * checkbox just proves the student saw them before submitting.
     */
    public bool $noticeAccepted = false;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'requiredCourseId' => ['required', 'integer', 'exists:courses,id'],
            'justification' => ['required', 'string', 'in:'.implode(',', self::JUSTIFICATIONS)],
            'supportDocument' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'noticeAccepted' => ['accepted'],
        ];
    }

    public function toDto(int $studentId): RequestDTO
    {
        return new RequestDTO(
            studentId: $studentId,
            type: 'Requirement Waiver',
            courseId: (int) $this->courseId,
            requiredCourseId: (int) $this->requiredCourseId,
            waiverJustification: $this->justification,
            attachments: [
                $this->storeAttachment($this->supportDocument, 'support_document'),
            ],
        );
    }
}
