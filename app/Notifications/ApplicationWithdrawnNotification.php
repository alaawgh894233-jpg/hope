<?php

namespace App\Notifications;

use App\Models\ApplicationWithdrawal;
use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationWithdrawnNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected JobApplication     $application,
        protected ApplicationWithdrawal $withdrawal,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $applicantName = $this->application->user->name;
        $jobTitle      = $this->application->jobPost->title;
        $reasons       = ApplicationWithdrawal::getReasonCategories();
        $reason        = $reasons[$this->withdrawal->reason_category] ?? 'أخرى';

        return (new MailMessage)
            ->subject("انسحب المتقدم من طلبه - {$jobTitle}")
            ->greeting("مرحباً،")
            ->line("قام {$applicantName} بسحب طلبه على وظيفة: **{$jobTitle}**")
            ->line("**سبب الانسحاب:** {$reason}")
            ->when($this->withdrawal->reason_details, fn($mail) =>
            $mail->line("**تفاصيل إضافية:** {$this->withdrawal->reason_details}")
            )
            ->action('عرض تفاصيل الطلب', url("/company/applications/{$this->application->id}"))
            ->line('يمكنك مراجعة طلبات أخرى أو فتح الوظيفة لمتقدمين آخرين.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'application_withdrawn',
            'application_id' => $this->application->id,
            'applicant_name' => $this->application->user->name,
            'job_title'      => $this->application->jobPost->title,
            'reason'         => $this->withdrawal->reason_category,
            'message'        => "سحب {$this->application->user->name} طلبه على وظيفة {$this->application->jobPost->title}",
        ];
    }
}
