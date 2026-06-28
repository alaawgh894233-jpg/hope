<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Review $review) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reviewerName = $this->review->is_anonymous
            ? 'مجهول'
            : ($this->review->reviewer?->name ?? 'مستخدم');

        $type = $this->review->type === 'applicant_to_company'
            ? 'متقدم → شركة'
            : 'شركة → متقدم';

        return (new MailMessage)
            ->subject('تقييم جديد بانتظار المراجعة #' . $this->review->id)
            ->greeting('مرحباً أدمن 👋')
            ->line("تم إرسال تقييم جديد ({$type}) من: {$reviewerName}")
            ->line('التقييم العام: ' . $this->review->overall_rating . ' / 5')
            ->line('العنوان: ' . ($this->review->title ?? '—'))
            ->action('مراجعة التقييم في لوحة الأدمن', route('admin.reviews.show', $this->review->id))
            ->line('يُرجى الموافقة على التقييم أو رفضه في أقرب وقت.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'review_submitted',
            'review_id'      => $this->review->id,
            'reviewer_id'    => $this->review->reviewer_id,
            'is_anonymous'   => $this->review->is_anonymous,
            'overall_rating' => $this->review->overall_rating,
            'review_type'    => $this->review->type,
            'title'          => $this->review->title,
            'action_url'     => route('admin.reviews.show', $this->review->id),
            'message'        => 'تقييم جديد بانتظار موافقتك',
        ];
    }
}
