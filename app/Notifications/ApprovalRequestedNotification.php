<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $approvableType,
        public readonly int $approvableId,
        public readonly string $approvableName,
        public readonly string $requiredLevel,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Approval Required — {$this->approvableName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A {$this->approvableType} requires your approval at the {$this->requiredLevel} level.")
            ->line("Item: {$this->approvableName}")
            ->action('Review', url('/dashboard'))
            ->line('Please review and approve or reject.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'approval',
            'title'           => 'Approval Required',
            'body'            => "{$this->approvableType} '{$this->approvableName}' needs your {$this->requiredLevel} approval.",
            'approvable_type' => $this->approvableType,
            'approvable_id'   => $this->approvableId,
            'required_level'  => $this->requiredLevel,
        ];
    }
}
