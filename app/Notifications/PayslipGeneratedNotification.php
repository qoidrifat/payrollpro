<?php

namespace App\Notifications;

use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayslipGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Payslip $payslip,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payslip Ready — {$this->payslip->payslip_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your payslip {$this->payslip->payslip_number} has been generated.")
            ->line("You can view and download it from your employee portal.")
            ->action('View Payslip', route('payslips.preview', $this->payslip->payroll_item_id))
            ->line('Thank you!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'payslip',
            'title'   => 'Payslip Ready',
            'body'    => "Payslip {$this->payslip->payslip_number} is available.",
            'payslip_id' => $this->payslip->id,
        ];
    }
}
