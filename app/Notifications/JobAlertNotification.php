<?php

namespace App\Notifications;

use App\Models\JobAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected JobAlert $alert,
        protected array    $jobs,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->alert->notify_email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $jobCount = count($this->jobs);
        $alertName = $this->alert->name;

        $mail = (new MailMessage)
            ->subject("🔔 {$jobCount} وظيفة جديدة تطابق تنبيه: {$alertName}")
            ->greeting("مرحباً {$notifiable->name}،")
            ->line("وجدنا {$jobCount} وظيفة جديدة تطابق تنبيهك **{$alertName}**:");

        foreach (array_slice($this->jobs, 0, 5) as $job) {
            $mail->line("• **{$job->title}** - {$job->company?->name} ({$job->location})");
        }

        if ($jobCount > 5) {
            $mail->line("و " . ($jobCount - 5) . " وظيفة أخرى...");
        }

        return $mail
            ->action('عرض كل الوظائف', url('/jobs?alert=' . $this->alert->id))
            ->line('يمكنك إدارة تنبيهاتك من إعدادات حسابك.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'job_alert',
            'alert_id'   => $this->alert->id,
            'alert_name' => $this->alert->name,
            'job_count'  => count($this->jobs),
            'jobs'       => collect($this->jobs)->take(3)->map(fn($j) => [
                'id'    => $j->id,
                'title' => $j->title,
            ])->toArray(),
            'message' => count($this->jobs) . " وظيفة جديدة تطابق تنبيهك: {$this->alert->name}",
        ];
    }
}
