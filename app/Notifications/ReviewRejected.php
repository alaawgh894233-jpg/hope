<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Review $review,
        public readonly string $reason
    )
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('❌ تم رفض تقييمك')
            ->greeting('أهلاً ' . $notifiable->name)
            ->line('نأسف لإبلاغك بأن تقييمك لم يتم قبوله.')
            ->line('سبب الرفض: ' . $this->reason)
            ->line('يمكنك تعديله وإرساله مجدداً بعد مراجعة السبب.')
            ->line('شكراً لتفهمك.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'review_rejected',
            'review_id' => $this->review->id,
            'reason' => $this->reason,
            'message' => 'تم رفض تقييمك: ' . $this->reason,
        ];
    }
}
