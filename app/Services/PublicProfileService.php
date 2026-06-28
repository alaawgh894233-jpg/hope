<?php

namespace App\Services;

use App\Models\PublicProfile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PublicProfileService
{
    /**
     * إنشاء أو جلب البروفايل العام
     */
    public function findOrCreate(User $user): PublicProfile
    {
        return PublicProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'slug'             => PublicProfile::generateSlug($user->name, $user->id),
                'is_public'        => true,
                'visible_sections' => (new PublicProfile())->getDefaultVisibleSections(),
                'theme_color'      => '#3B82F6',
            ]
        );
    }

    /**
     * تحديث إعدادات البروفايل العام
     */
    public function updateSettings(PublicProfile $publicProfile, array $data): PublicProfile
    {
        $publicProfile->update([
            'is_public'        => $data['is_public'] ?? $publicProfile->is_public,
            'visible_sections' => $data['visible_sections'] ?? $publicProfile->visible_sections,
            'theme_color'      => $data['theme_color'] ?? $publicProfile->theme_color,
            'meta_title'       => $data['meta_title'] ?? $publicProfile->meta_title,
            'meta_description' => $data['meta_description'] ?? $publicProfile->meta_description,
        ]);

        // مسح الكاش
        Cache::forget("public_profile_{$publicProfile->slug}");

        return $publicProfile->fresh();
    }

    /**
     * جلب البروفايل العام بالـ slug
     */
    public function getBySlug(string $slug): ?PublicProfile
    {
        return Cache::remember("public_profile_{$slug}", 300, function () use ($slug) {
            return PublicProfile::with([
                'user.profile',
                'user.experiences',
                'user.educations',
                'user.skills',
                'user.projects',
                'user.certifications',
                'user.trainings',
            ])
                ->where('slug', $slug)
                ->where('is_public', true)
                ->first();
        });
    }

    /**
     * تسجيل مشاهدة
     */
    public function recordView(PublicProfile $publicProfile, ?int $viewerId, string $ip): void
    {
        // منع تكرار المشاهدة من نفس المستخدم/IP في 24 ساعة
        $cacheKey = "profile_view_{$publicProfile->id}_{$viewerId}_{$ip}";

        if (!Cache::has($cacheKey)) {
            $publicProfile->recordView($viewerId, $ip);
            Cache::put($cacheKey, true, 86400); // 24 ساعة
        }
    }

    /**
     * تغيير الـ slug
     */
    public function changeSlug(PublicProfile $publicProfile, string $newSlug): PublicProfile
    {
        $newSlug = \Illuminate\Support\Str::slug($newSlug);

        // التحقق من التفرد
        if (PublicProfile::where('slug', $newSlug)->where('id', '!=', $publicProfile->id)->exists()) {
            throw new \Exception('هذا الرابط مستخدم بالفعل.');
        }

        Cache::forget("public_profile_{$publicProfile->slug}");

        $publicProfile->update(['slug' => $newSlug]);

        return $publicProfile->fresh();
    }

    /**
     * إحصائيات البروفايل
     */
    public function getStats(PublicProfile $publicProfile): array
    {
        return [
            'total_views'     => $publicProfile->total_views,
            'views_this_week' => $publicProfile->views()
                ->where('viewed_at', '>=', now()->subWeek())
                ->count(),
            'views_this_month' => $publicProfile->views()
                ->where('viewed_at', '>=', now()->startOfMonth())
                ->count(),
            'last_viewed_at' => $publicProfile->last_viewed_at?->diffForHumans(),
        ];
    }

    /**
     * بناء بيانات البروفايل العام الكاملة
     */
    public function buildProfileData(PublicProfile $publicProfile): array
    {
        $user    = $publicProfile->user;
        $profile = $user->profile;

        $data = [
            'user' => [
                'id'       => $user->id,
                'name'     => $publicProfile->isSectionVisible('contact_info') ? $user->name : null,
                'headline' => $profile?->headline,
                'summary'  => $profile?->summary,
                'country'  => $profile?->country,
                'city'     => $profile?->city,
                'photo'    => $profile?->profile_image,
                'linkedin' => $profile?->linkedin,
                'github'   => $profile?->github,
                'portfolio'=> $profile?->portfolio,
            ],
            'public_url'  => $publicProfile->getPublicUrl(),
            'theme_color' => $publicProfile->theme_color,
            'slug'        => $publicProfile->slug,
            'stats'       => $this->getStats($publicProfile),
        ];

        // إضافة الأقسام المرئية فقط
        if ($publicProfile->isSectionVisible('experience')) {
            $data['experiences'] = $user->experiences;
        }

        if ($publicProfile->isSectionVisible('education')) {
            $data['educations'] = $user->educations;
        }

        if ($publicProfile->isSectionVisible('skills')) {
            $data['skills'] = $user->skills;
        }

        if ($publicProfile->isSectionVisible('projects')) {
            $data['projects'] = $user->projects;
        }

        if ($publicProfile->isSectionVisible('certifications')) {
            $data['certifications'] = $user->certifications;
        }

        return $data;
    }
}
