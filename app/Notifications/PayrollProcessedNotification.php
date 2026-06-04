<?php

namespace App\Notifications;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayrollProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Payroll $payroll,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payroll Processed — {$this->payroll->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Payroll '{$this->payroll->name}' has been processed.")
            ->line("Period: {$this->payroll->period_start->format('d M Y')} — {$this->payroll->period_end->format('d M Y')}")
            ->line("Employees processed: {$this->payroll->total_employees}")
            ->action('View Payroll', route('payroll.show', $this->payroll->id))
            ->line('Pending approval review.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'payroll',
            'title'      => 'Payroll Processed',
            'body'       => "Payroll '{$this->payroll->name}' is ready for approval.",
            'payroll_id' => $this->payroll->id,
        ];
    }
}
