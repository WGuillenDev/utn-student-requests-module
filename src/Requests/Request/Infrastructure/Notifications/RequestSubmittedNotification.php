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

        return (new MailMessage())
            ->subject(__('We received your :type', ['type' => $type]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('We successfully received your :type for :course.', [
                'type' => $type,
                'course' => $this->courseLabel,
            ]))
            ->line(__('Current status: :status', ['status' => __($this->request->status())]))
            ->line(__('You can review the full detail from the "My requests" section of the portal.'));
    }
}
