<?php

declare(strict_types=1);

namespace Src\Requests\WaiverRule\Presentation\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;
use Src\Requests\WaiverRule\Application\DTOs\WaiverRuleDTO;
use Src\Requests\WaiverRule\Domain\Entities\WaiverRule;
use Src\Requests\WaiverRule\Presentation\Livewire\WaiverRuleComponent;

/**
 * Livewire Form Object — the input boundary for the WaiverRule
 * create/edit modal. This entity has free editing (no state machine),
 * so — unlike RequestForm — it does have a fromEntity()/toDto() pair
 * used for both create and update.
 */
class WaiverRuleForm extends Form
{
    public ?int $courseId = null;

    public ?int $order = null;

    public string $type = 'Always manual review';

    public ?int $requiredCourseId = null;

    public ?float $minimumGrade = null;

    public ?int $minimumAccumulated = null;

    public bool $active = true;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var WaiverRuleComponent $component */
        $component = $this->component;

        return [
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'order' => [
                'required', 'integer', 'min:1',
                Rule::unique('waiver_rules', 'order')
                    ->where('course_id', $this->courseId)
                    ->ignore($component->editingId),
            ],
            'type' => ['required', 'in:Approved requirement with minimum grade,Accumulated credits or courses,Terminal plan membership,Always manual review'],
            'requiredCourseId' => ['required_if:type,Approved requirement with minimum grade', 'nullable', 'integer', 'exists:courses,id'],
            'minimumGrade' => ['required_if:type,Approved requirement with minimum grade', 'nullable', 'numeric', 'between:0,100'],
            'minimumAccumulated' => ['required_if:type,Accumulated credits or courses', 'nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
        ];
    }

    /**
     * Hydrates the form from an existing WaiverRule for the edit modal.
     * Named `fromEntity()` rather than `fill()` for the same LSP reason
     * documented on RoleForm::fromEntity().
     */
    public function fromEntity(WaiverRule $waiverRule): void
    {
        $this->courseId = $waiverRule->courseId();
        $this->order = $waiverRule->order();
        $this->type = $waiverRule->type();
        $this->requiredCourseId = $waiverRule->requiredCourseId();
        $this->minimumGrade = $waiverRule->minimumGrade();
        $this->minimumAccumulated = $waiverRule->minimumAccumulated();
        $this->active = $waiverRule->active();
    }

    public function toDto(): WaiverRuleDTO
    {
        return new WaiverRuleDTO(
            courseId: (int) $this->courseId,
            order: (int) $this->order,
            type: $this->type,
            requiredCourseId: $this->type === 'Approved requirement with minimum grade' ? $this->requiredCourseId : null,
            minimumGrade: $this->type === 'Approved requirement with minimum grade' ? $this->minimumGrade : null,
            minimumAccumulated: $this->type === 'Accumulated credits or courses' ? $this->minimumAccumulated : null,
            active: $this->active,
        );
    }
}
