<?php

namespace App\Services;

use App\Models\JobAlert;
use App\Models\JobPost;
use App\Models\User;
use App\Notifications\JobAlertNotification;
use Illuminate\Support\Facades\Notification;

class JobAlertService
{
    /**
     * إنشاء تنبيه جديد
     */
    public function create(User $user, array $data): JobAlert
    {
        return JobAlert::create([
            'user_id'      => $user->id,
            'name'         => $data['name'],
            'criteria'     => $data['criteria'],
            'frequency'    => $data['frequency'] ?? 'daily',
            'notify_email' => $data['notify_email'] ?? true,
            'notify_push'  => $data['notify_push'] ?? true,
            'is_active'    => true,
        ]);
    }

    /**
     * تحديث تنبيه
     */
    public function update(JobAlert $alert, array $data): JobAlert
    {
        $alert->update($data);
        return $alert->fresh();
    }

    /**
     * تفعيل/إيقاف تنبيه
     */
    public function toggle(JobAlert $alert): JobAlert
    {
        $alert->update(['is_active' => !$alert->is_active]);
        return $alert->fresh();
    }

    /**
     * حذف تنبيه
     */
    public function delete(JobAlert $alert): void
    {
        $alert->delete();
    }

    /**
     * إرسال تنبيهات فورية عند نشر وظيفة جديدة
     */
    public function notifyMatchingAlerts(JobPost $jobPost): void
    {
        $jobData = [
            'title'       => $jobPost->title,
            'description' => $jobPost->description,
            'location'    => $jobPost->location,
            'job_type'    => $jobPost->job_type,
            'salary_min'  => $jobPost->salary_min,
            'salary_max'  => $jobPost->salary_max,
            'remote'      => $jobPost->is_remote,
        ];

        // جلب التنبيهات الفورية النشطة
        JobAlert::active()
            ->instant()
            ->with('user')
            ->chunk(100, function ($alerts) use ($jobPost, $jobData) {
                foreach ($alerts as $alert) {
                    if ($alert->matchesJob($jobData)) {
                        $this->sendNotification($alert, [$jobPost]);
                    }
                }
            });
    }

    /**
     * إرسال التنبيهات اليومية
     */
    public function sendDailyAlerts(): void
    {
        $yesterday = now()->subDay();

        JobAlert::active()
            ->daily()
            ->with('user')
            ->chunk(100, function ($alerts) use ($yesterday) {
                foreach ($alerts as $alert) {
                    $matchingJobs = $this->findMatchingJobs($alert, $yesterday);

                    if ($matchingJobs->isNotEmpty()) {
                        $this->sendNotification($alert, $matchingJobs->all());
                        $alert->update([
                            'last_sent_at' => now(),
                            'total_sent'   => $alert->total_sent + $matchingJobs->count(),
                        ]);
                    }
                }
            });
    }

    /**
     * إرسال التنبيهات الأسبوعية
     */
    public function sendWeeklyAlerts(): void
    {
        $lastWeek = now()->subWeek();

        JobAlert::active()
            ->weekly()
            ->with('user')
            ->chunk(100, function ($alerts) use ($lastWeek) {
                foreach ($alerts as $alert) {
                    $matchingJobs = $this->findMatchingJobs($alert, $lastWeek);

                    if ($matchingJobs->isNotEmpty()) {
                        $this->sendNotification($alert, $matchingJobs->all());
                        $alert->update([
                            'last_sent_at' => now(),
                            'total_sent'   => $alert->total_sent + $matchingJobs->count(),
                        ]);
                    }
                }
            });
    }

    /**
     * إيجاد الوظائف المطابقة للتنبيه
     */
    protected function findMatchingJobs(JobAlert $alert, \Carbon\Carbon $since)
    {
        $criteria = $alert->criteria;

        $query = JobPost::query()->where('created_at', '>=', $since)->active();

        // تطبيق معايير البحث
        if (!empty($criteria['keywords'])) {
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria['keywords'] as $keyword) {
                    $q->orWhere('title', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                }
            });
        }

        if (!empty($criteria['location'])) {
            $query->where('location', 'like', "%{$criteria['location']}%");
        }

        if (!empty($criteria['job_type'])) {
            $query->whereIn('job_type', $criteria['job_type']);
        }

        if (!empty($criteria['salary_min'])) {
            $query->where('salary_min', '>=', $criteria['salary_min']);
        }

        if (isset($criteria['remote']) && $criteria['remote']) {
            $query->where('is_remote', true);
        }

        if (!empty($criteria['categories'])) {
            $query->whereIn('category_id', $criteria['categories']);
        }

        return $query->limit(10)->get();
    }

    /**
     * إرسال الإشعار
     */
    protected function sendNotification(JobAlert $alert, array $jobs): void
    {
        try {
            $alert->user->notify(new JobAlertNotification($alert, $jobs));
        } catch (\Exception $e) {
            logger()->error("Failed to send job alert #{$alert->id}: " . $e->getMessage());
        }
    }
}
