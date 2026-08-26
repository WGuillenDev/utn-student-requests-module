<?php

declare(strict_types=1);

namespace Src\Requests\Request\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Src\Requests\Request\Domain\Entities\Request;

/**
 * Confirms to the student, right after submission, that their request
 * was received — replaces the earlier "notify on every status change"
 * behavior (see git history for RequestStatusChangedNotification).
 * Queued for the same reason as before: a request submission is a
 * synchronous Livewire action, and mail shouldn't block it on SMTP.
 */
final class RequestSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Request $request,
        private readonly string $courseLabel,
        private readonly ?string $documentName = null,
        private readonly ?string $batchCourseNames = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Same structure for both request types: confirmation line, then
     * whatever's relevant (justification only exists for Requirement
     * Waiver, so that line is skipped for Validation), attached
     * document(s), the Docencia-then-Registro review order, and the
     * "check My requests" recommendation.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $isWaiver = $this->request->type() === 'Requirement Waiver';

        // A Validation submission bundles several UTN courses into one
        // or more independent Request rows (see RequestDTO's docblock on
        // batchCourseNames) — the confirmation should list all of them,
        // not just the single course this particular Request/email is
        // for, so the student sees everything they just submitted.
        $courseLabel = $isWaiver ? $this->courseLabel : ($this->batchCourseNames ?? $this->courseLabel);

        $message = (new MailMessage())
            ->subject($isWaiver
                ? __('Requirement waiver request received')
                : __('Course validation request received'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line($isWaiver
                ? __('The UTN confirms your requirement waiver request for :course was received.', ['course' => $courseLabel])
                : __('The UTN confirms your course validation request for :course was received.', ['course' => $courseLabel]));

        if ($this->request->createdAt() !== null) {
            $message->line(__('Submission date: :date', [
                'date' => date('Y-m-d', strtotime($this->request->createdAt())),
            ]));
        }

        if ($this->request->waiverJustification() !== null) {
            $message->line(__('Justification you selected: :justification', [
                'justification' => __($this->request->waiverJustification()),
            ]));
        }

        if ($this->documentName !== null) {
            $message->line(__('Attached document: :document', ['document' => $this->documentName]));
        }

        return $message
            ->line(__('Your request will be reviewed by Docencia and then by Registro of the UTN. You will be informed once it is ready.'))
            ->line(__('Recommendation: keep track of your request status in the "My requests" inbox.'))
            ->salutation(__('Regards,')."  \nUniversidad Técnica Nacional");
    }
}
