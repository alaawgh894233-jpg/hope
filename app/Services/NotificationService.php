<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\ApplicationTraining;
use App\Models\Notification;
use App\Models\Review;
use App\Models\ReviewFlag;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    // ===================================================================
    // 🟦 الجزء القديم — إيميلات (ما تغيّر فيه شي، نفس الكود يلي عندك)
    // ===================================================================

    public function notifyStatusChanged(JobApplication $application): void
    {
        $user     = $application->user;
        $jobTitle = $application->jobPost?->title ?? 'الوظيفة';
        $status   = $this->translateStatus($application->status);

        if ($user?->email) {
            Mail::send([], [], function ($m) use ($user, $jobTitle, $status, $application) {
                $m->to($user->email, $user->name)
                    ->subject("تحديث حالة طلبك على: {$jobTitle}")
                    ->html($this->statusChangedTemplate($user->name, $jobTitle, $status, $application->id));
            });
        }

        // 🆕 إشعار داخلي بالتطبيق بالإضافة للإيميل
        if ($user) {
            $this->push(
                userId: $user->id,
                type: 'application_status_changed',
                title: 'تحديث حالة طلبك',
                body: "تم تحديث حالة طلبك على \"{$jobTitle}\" إلى: {$status}",
                data: ['application_id' => $application->id]
            );
        }
    }

    public function notifyInterviewScheduled(Interview $interview): void
    {
        $user     = $interview->jobApplication?->user;
        $jobTitle = $interview->jobApplication?->jobPost?->title ?? 'الوظيفة';
        $date     = \Carbon\Carbon::parse($interview->scheduled_at)->format('Y-m-d الساعة H:i');
        $type     = match($interview->type) {
            'online' => 'أونلاين',
            'phone'  => 'هاتفياً',
            default  => 'حضورياً'
        };

        if ($user?->email) {
            Mail::send([], [], function ($m) use ($user, $jobTitle, $date, $type, $interview) {
                $m->to($user->email, $user->name)
                    ->subject("تمت جدولة مقابلتك: {$jobTitle}")
                    ->html($this->interviewScheduledTemplate($user->name, $jobTitle, $date, $type, $interview));
            });
        }

        if ($user) {
            $this->push(
                userId: $user->id,
                type: 'interview_scheduled',
                title: 'تمت جدولة مقابلتك',
                body: "موعد المقابلة على \"{$jobTitle}\": {$date} ({$type})",
                data: ['interview_id' => $interview->id]
            );
        }
    }

    public function notifyInterviewCancelled(Interview $interview): void
    {
        $user     = $interview->jobApplication?->user;
        $jobTitle = $interview->jobApplication?->jobPost?->title ?? 'الوظيفة';
        $reason   = $interview->feedback ?? 'لم يتم تحديد سبب';

        if ($user?->email) {
            Mail::send([], [], function ($m) use ($user, $jobTitle, $reason) {
                $m->to($user->email, $user->name)
                    ->subject("تم إلغاء مقابلتك: {$jobTitle}")
                    ->html("
                  <div dir='rtl' style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:8px'>
                      <h2 style='color:#dc2626'>تم إلغاء المقابلة ❌</h2>
                      <p>مرحباً <strong>{$user->name}</strong>،</p>
                      <p>نأسف لإبلاغك بأنه تم إلغاء مقابلتك على وظيفة <strong>\"{$jobTitle}\"</strong>.</p>
                      <div style='background:#fef2f2;border-right:4px solid #dc2626;padding:12px 16px;border-radius:4px;margin:16px 0'>
                          <p style='margin:0'>السبب: <strong>{$reason}</strong></p>
                      </div>
                      <p style='color:#6b7280;font-size:13px'>مع تحيات فريق المنصة</p>
                  </div>");
            });
        }

        if ($user) {
            $this->push(
                userId: $user->id,
                type: 'interview_cancelled',
                title: 'تم إلغاء مقابلتك',
                body: "تم إلغاء مقابلتك على \"{$jobTitle}\". السبب: {$reason}",
                data: ['interview_id' => $interview->id]
            );
        }
    }

    public function notifyTrainingResult(ApplicationTraining $training): void
    {
        $user     = $training->jobApplication?->user;
        $jobTitle = $training->jobApplication?->jobPost?->title ?? 'الوظيفة';
        $result   = $training->result === 'passed' ? 'اجتزت التدريب بنجاح ✅' : 'لم تجتز التدريب ❌';
        $score    = $training->score ?? 'لم يتم تحديد الدرجة';
        $color    = $training->result === 'passed' ? '#16a34a' : '#dc2626';
        $bg       = $training->result === 'passed' ? '#f0fdf4' : '#fef2f2';

        if ($user?->email) {
            Mail::send([], [], function ($m) use ($user, $jobTitle, $result, $score, $color, $bg) {
                $m->to($user->email, $user->name)
                    ->subject("نتيجة تدريبك على: {$jobTitle}")
                    ->html("
                  <div dir='rtl' style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:8px'>
                      <h2 style='color:{$color}'>نتيجة التدريب</h2>
                      <p>مرحباً <strong>{$user->name}</strong>،</p>
                      <p>نود إبلاغك بنتيجة تدريبك على وظيفة <strong>\"{$jobTitle}\"</strong>.</p>
                      <div style='background:{$bg};border-right:4px solid {$color};padding:12px 16px;border-radius:4px;margin:16px 0'>
                          <p style='margin:0 0 8px'>النتيجة: <strong>{$result}</strong></p>
                          <p style='margin:0'>الدرجة: <strong>{$score} / 100</strong></p>
                      </div>
                      <p style='color:#6b7280;font-size:13px'>مع تحيات فريق المنصة</p>
                  </div>");
            });
        }

        if ($user) {
            $this->push(
                userId: $user->id,
                type: 'training_result',
                title: 'نتيجة تدريبك',
                body: "نتيجتك على \"{$jobTitle}\": {$result} (الدرجة: {$score})",
                data: ['training_id' => $training->id]
            );
        }
    }

    public function notifyTrainingEnding(ApplicationTraining $training, bool $isLastDay): void
    {
        $user     = $training->jobApplication?->user;
        $jobTitle = $training->jobApplication?->jobPost?->title ?? 'الوظيفة';
        $endDate  = $training->end_date?->format('Y-m-d');
        $when     = $isLastDay ? 'اليوم' : 'غداً';

        if ($user?->email) {
            Mail::send([], [], function ($m) use ($user, $jobTitle, $endDate, $when) {
                $m->to($user->email, $user->name)
                    ->subject("تدريبك ينتهي {$when}: {$jobTitle}")
                    ->html($this->trainingEndingTemplate($user->name, $jobTitle, $endDate, $when));
            });
        }

        if ($user) {
            $this->push(
                userId: $user->id,
                type: 'training_ending',
                title: "تدريبك ينتهي {$when}",
                body: "فترة تدريبك على \"{$jobTitle}\" تنتهي {$when} بتاريخ {$endDate}",
                data: ['training_id' => $training->id]
            );
        }
    }

    // ===================================================================
    // 🟩 الجزء الجديد — إشعارات Reviews/Flags (للأدمن، اليوزر، الشركة)
    // ===================================================================

    public function notifyReviewPendingApproval(Review $review): void
    {
        $this->pushToAdmins(
            type: 'review_pending_approval',
            title: 'تقييم جديد يحتاج مراجعة',
            body: 'تم إضافة تقييم جديد وينتظر موافقتك.',
            data: ['review_id' => $review->id]
        );
    }

    public function notifyReviewApproved(Review $review): void
    {
        $this->push(
            userId: $review->reviewer_id,
            type: 'review_approved',
            title: 'تمت الموافقة على تقييمك',
            body: 'تقييمك أصبح ظاهرًا للعموم الآن.',
            data: ['review_id' => $review->id]
        );
    }

    public function notifyReviewRejected(Review $review, string $reason): void
    {
        $this->push(
            userId: $review->reviewer_id,
            type: 'review_rejected',
            title: 'تم رفض تقييمك',
            body: "سبب الرفض: {$reason}",
            data: ['review_id' => $review->id]
        );
    }

    public function notifyReviewFlagged(Review $review, ReviewFlag $flag): void
    {
        $this->pushToAdmins(
            type: 'review_flagged',
            title: 'تم الإبلاغ عن تقييم',
            body: "السبب: {$flag->reason}",
            data: ['review_id' => $review->id, 'flag_id' => $flag->id]
        );
    }

    public function notifyFlagApproved(ReviewFlag $flag): void
    {
        $this->push(
            userId: $flag->user_id,
            type: 'flag_approved',
            title: 'تم قبول بلاغك',
            body: 'تم رفض التقييم الذي قمت بالإبلاغ عنه.',
            data: ['review_id' => $flag->review_id, 'flag_id' => $flag->id]
        );
    }

    public function notifyFlagDismissed(ReviewFlag $flag): void
    {
        $this->push(
            userId: $flag->user_id,
            type: 'flag_dismissed',
            title: 'تم مراجعة بلاغك',
            body: 'تمت مراجعة البلاغ ولم يتم العثور على مخالفة.',
            data: ['review_id' => $flag->review_id, 'flag_id' => $flag->id]
        );
    }

    public function notifyReviewReacted(Review $review, string $reaction): void
    {
        $this->push(
            userId: $review->reviewer_id,
            type: 'review_reacted',
            title: 'تفاعل جديد عَ تقييمك',
            body: $reaction === 'helpful' ? 'أحدهم وجد تقييمك مفيدًا.' : 'أحدهم تفاعل مع تقييمك.',
            data: ['review_id' => $review->id]
        );
    }

    public function notifyReviewResponded(Review $review): void
    {
        $this->push(
            userId: $review->reviewer_id,
            type: 'review_responded',
            title: 'تم الرد على تقييمك',
            body: 'هناك رد جديد على التقييم الذي كتبته.',
            data: ['review_id' => $review->id]
        );
    }

    public function push(int $userId, string $type, string $title, string $body, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ]);

        // 🔌 لاحقًا: Push Notification (FCM) أو Broadcast (Pusher/WebSocket)
    }

    /**
     * إرسال لكل الأدمنز
     * ⚠️ عدّل الشرط حسب الـ column/role الحقيقي عندك (is_admin, role = 'admin'...)
     */
    public function pushToAdmins(string $type, string $title, string $body, array $data = []): void
    {
        $admins = User::where('role', 'admin')->get(); // ✔️ الصحيح

        foreach ($admins as $admin) {
            $this->push($admin->id, $type, $title, $body, $data);
        }
    }    public function markAsRead(int $notificationId, int $userId): bool
    {
        return Notification::where('id', $notificationId)
                ->where('user_id', $userId)
                ->update(['is_read' => true, 'read_at' => now()]) > 0;
    }

    public function markAllAsRead(int $userId): void
    {
        Notification::where('user_id', $userId)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function getUserNotifications(int $userId, int $perPage = 20)
    {
        return Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function unreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)->unread()->count();
    }

    // ─── Templates القديمة — بدون أي تغيير ──────────────────────────────

    private function statusChangedTemplate(string $name, string $jobTitle, string $status, int $appId): string
    {
        return "
        <div dir='rtl' style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:8px'>
            <h2 style='color:#1d4ed8'>تحديث حالة طلبك</h2>
            <p>مرحباً <strong>{$name}</strong>،</p>
            <p>تم تحديث حالة طلبك على وظيفة <strong>\"{$jobTitle}\"</strong>.</p>
            <div style='background:#f0f9ff;border-right:4px solid #1d4ed8;padding:12px 16px;margin:16px 0;border-radius:4px'>
                <p style='margin:0;font-size:16px'>الحالة الجديدة: <strong style='color:#1d4ed8'>{$status}</strong></p>
            </div>
            <p style='color:#6b7280;font-size:13px'>مع تحيات فريق المنصة</p>
        </div>";
    }

    private function interviewScheduledTemplate(string $name, string $jobTitle, string $date, string $type, Interview $interview): string
    {
        $extra = $interview->meeting_link
            ? "<p>رابط المقابلة: <a href='{$interview->meeting_link}'>{$interview->meeting_link}</a></p>"
            : ($interview->location ? "<p>المكان: <strong>{$interview->location}</strong></p>" : '');

        return "
        <div dir='rtl' style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:8px'>
            <h2 style='color:#1d4ed8'>تمت جدولة مقابلتك 🎉</h2>
            <p>مرحباً <strong>{$name}</strong>،</p>
            <p>تمت جدولة مقابلة عمل لطلبك على وظيفة <strong>\"{$jobTitle}\"</strong>.</p>
            <div style='background:#f0fdf4;border-right:4px solid #16a34a;padding:12px 16px;margin:16px 0;border-radius:4px'>
                <p style='margin:0 0 8px'>📅 الموعد: <strong>{$date}</strong></p>
                <p style='margin:0'>📋 النوع: <strong>{$type}</strong></p>
            </div>
            {$extra}
            <p style='color:#6b7280;font-size:13px'>مع تحيات فريق المنصة</p>
        </div>";
    }

    private function trainingEndingTemplate(string $name, string $jobTitle, string $endDate, string $when): string
    {
        return "
        <div dir='rtl' style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:8px'>
            <h2 style='color:#d97706'>تذكير: تدريبك ينتهي {$when} ⏰</h2>
            <p>مرحباً <strong>{$name}</strong>،</p>
            <p>نذكرك بأن فترة تدريبك على وظيفة <strong>\"{$jobTitle}\"</strong> ستنتهي <strong>{$when}</strong> بتاريخ <strong>{$endDate}</strong>.</p>
            <p>نتمنى لك التوفيق!</p>
            <p style='color:#6b7280;font-size:13px'>مع تحيات فريق المنصة</p>
        </div>";
    }

    private function translateStatus(string $status): string
    {
        return match($status) {
            'pending'   => 'قيد المراجعة',
            'interview' => 'مدعو للمقابلة 📋',
            'training'  => 'قيد التدريب 📚',
            'accepted'  => 'مقبول ✅',
            'rejected'  => 'مرفوض ❌',
            default     => $status
        };
    }
}
