<?php

declare(strict_types=1);

namespace Src\Requests\Request\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Src\Requests\Request\Domain\Entities\Request;

/**
 * ES-03's "email notifications on every status change." Queued — a
 * status change in RequestComponent::changeStatus() is a synchronous
 * Livewire action; sending mail inline would make the reviewer's click
 * wait on SMTP. Requires a working QUEUE_CONNECTION (or `sync`, which
 * behaves like an inline send — see .env's default) to actually go out.
 *
 * Deliberately takes only the two facts it needs (the Request and its
 * previous status) rather than the whole review context (comment,
 * reviewer name, etc.) — the email confirms outcome and next steps,
 * not every audit detail already visible in the student's own
 * "My requests" screen (StudentRequestComponent).
 */
final class RequestStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Request $request,
        private readonly string $previousStatus,
        private readonly string $courseLabel,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $type = $this->request->type() === 'Requirement Waiver'
            ? __('requirement waiver request')
            : __('course validation request');

        $mail = (new MailMessage())
            ->subject(__('Your :type is now :status', ['type' => $type, 'status' => __($this->request->status())]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('The status of your :type for :course has changed.', [
                'type' => $type,
                'course' => $this->courseLabel,
            ]))
            ->line(__('Previous status: :status', ['status' => __($this->previousStatus)]))
            ->line(__('New status: :status', ['status' => __($this->request->status())]));

        if ($this->request->estimatedResolutionDate() !== null) {
            $mail->line(__('Estimated resolution date: :date', ['date' => $this->request->estimatedResolutionDate()]));
        }

        return $mail->line(__('You can review the full detail from the "My requests" section of the portal.'));
    }
}
