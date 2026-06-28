<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Review $review) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('✅ تمت الموافقة على تقييمك')
            ->greeting('أهلاً ' . $notifiable->name . ' 🎉')
            ->line('تمت الموافقة على تقييمك وهو الآن مرئي للعموم.')
            ->line('التقييم العام الذي أعطيته: ' . $this->review->overall_rating . ' / 5 ⭐')
            ->action('عرض التقييم', route('reviews.show', $this->review->id))
            ->line('شكراً لمساهمتك في تحسين تجربة المجتمع.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'review_approved',
            'review_id'      => $this->review->id,
            'overall_rating' => $this->review->overall_rating,
            'action_url'     => route('reviews.show', $this->review->id),
            'message'        => 'تمت الموافقة على تقييمك وهو مرئي للعموم الآن',
        ];
    }
}
