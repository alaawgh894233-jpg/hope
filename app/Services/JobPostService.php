<?php

namespace App\Services;

use App\Models\JobPost;

class JobPostService
{
    public function create($companyId, $userId, array $data)
    {
        $job = JobPost::create([
            'company_id'   => $companyId,
            'created_by'   => $userId,
            'title'        => $data['title'],
            'description'  => $data['description'],
            'location'     => $data['location'] ?? null,
            'is_remote'    => $data['is_remote'] ?? false,
            'salary_range' => $data['salary_range'] ?? null,
            'type'         => $data['type'],
            'status'       => $data['status'] ?? 'published',
            'skills'       => $this->formatSkills($data['skills'] ?? []),
            'tags'         => $this->formatTags($data['tags'] ?? []),
            'expires_at'   => $data['expires_at'] ?? null,
        ]);

        // ✅ ربط الفئات
        if (!empty($data['category_ids'])) {
            $job->categories()->sync($data['category_ids']);
        }

        return $job->load('categories');
    }

    // ✅ Skills — بدون # (PHP, Laravel, Python...)
    private function formatSkills(array $skills): array
    {
        return array_values(array_filter(array_map(function ($skill) {
            $skill = trim($skill);
            if ($skill === '') return null;

            // ✅ نشيل الـ # لو حطها المستخدم بالغلط
            return strtolower(ltrim($skill, '#'));

        }, $skills)));
    }

    // ✅ Tags — مع # (#it, #damas, #syria...)
    private function formatTags(array $tags): array
    {
        return array_values(array_filter(array_map(function ($tag) {
            $tag = trim($tag);
            if ($tag === '') return null;

            // ✅ نضيف # لو ما حطها المستخدم
            return str_starts_with($tag, '#')
                ? strtolower($tag)
                : '#' . strtolower($tag);

        }, $tags)));
    }

    public function update(JobPost $job, array $data)
    {
        if (isset($data['skills'])) {
            $data['skills'] = $this->formatSkills($data['skills']);
        }

        if (isset($data['tags'])) {
            $data['tags'] = $this->formatTags($data['tags']);
        }

        // ✅ تحديث الفئات
        if (isset($data['category_ids'])) {
            $job->categories()->sync($data['category_ids']);
            unset($data['category_ids']);
        }

        $job->update($data);
        return $job->fresh()->load('categories');
    }

    public function delete(JobPost $job)
    {
        return $job->delete();
    }

    public function getById($id)
    {
        $user = auth()->user();

        $job = JobPost::with([
            'company:id,company_name,logo,website_url,local_address,category,status',
            'creator:id,name',
            'categories:id,name,name_ar,slug'
        ])
            ->withCount([
                'comments',
                'reactions',
                'applications'
            ])
            ->findOrFail($id);

        // 📈 views
        $job->increment('views');
        $job->refresh();

        // ❤️ reactions (user state)
        $userReaction = null;

        if ($user) {
            $userReaction = $job->reactions()
                ->where('user_id', $user->id)
                ->first();
        }

        $job->is_reacted = $userReaction ? true : false;
        $job->reaction_type = $userReaction?->type;

        // 🔖 saved
        $job->is_saved = $user
            ? $job->saves()->where('user_id', $user->id)->exists()
            : false;

        // 📩 applied
        $job->has_applied = $user
            ? $job->applications()->where('user_id', $user->id)->exists()
            : false;

        // ✅ can apply
        $job->can_apply =
            !$job->has_applied &&
            $job->status === 'published' &&
            (!$job->expires_at || !$job->expires_at->isPast());

        // 👤 owner
        $job->is_owner = $user
            ? $job->created_by === $user->id
            : false;

        $job->comments_count = $job->comments_count;

        $job->reactions_count = $job->reactions_count;


        $job->reaction_icons = $job->reactions()
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->values();

        // 📊 reaction breakdown
        $job->reaction_counts = $job->reactions()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        // 🕒 time helpers
        $job->published_since = $job->created_at->diffForHumans();
        $job->expires_in = $job->expires_at
            ? $job->expires_at->diffForHumans()
            : null;

        // 🏢 company stats
        $job->company_jobs_count = JobPost::where('company_id', $job->company_id)->count();

        $job->company_active_jobs_count = JobPost::where('company_id', $job->company_id)
            ->where('status', 'published')
            ->count();

        // 👥 followers (if exists)
        $job->company_followers_count = method_exists($job->company, 'followers')
            ? $job->company->followers()->count()
            : 0;

        return $job;
    }
    public function list(array $filters)
    {
        $user = auth()->user();

        $query = JobPost::query()
            ->with([
                'company',
                'categories'
            ])
            ->withCount([
                'comments',
                'reactions'
            ])->excludingBlockedBy($user);;

        // 🔎 Search
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        // Skills
        if (!empty($filters['skills'])) {

            $skills = is_array($filters['skills'])
                ? $this->formatSkills($filters['skills'])
                : $this->formatSkills(explode(',', $filters['skills']));

            $query->where(function ($q) use ($skills) {
                foreach ($skills as $skill) {
                    $q->orWhereJsonContains('skills', $skill);
                }
            });
        }

        // Tags
        if (!empty($filters['tags'])) {

            $tags = is_array($filters['tags'])
                ? $this->formatTags($filters['tags'])
                : $this->formatTags(explode(',', $filters['tags']));

            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        if (!empty($filters['location'])) {
            $query->where('location', 'like', "%{$filters['location']}%");
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_remote'])) {
            $query->where('is_remote', filter_var($filters['is_remote'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = min((int)($filters['per_page'] ?? 10), 50);

        $jobs = $query->latest()->paginate($perPage);


        $jobs->load([
            'reactions:id,job_post_id,user_id,type',
            'saves:id,job_post_id,user_id',
            'company.followers:id'
        ]);

        $jobs->getCollection()->transform(function ($job) use ($user) {

            $userReaction = $job->reactions
                ->firstWhere('user_id', $user?->id);

            $job->is_reacted = $userReaction !== null;

            $job->reaction_type = $userReaction?->type;

            $job->is_saved = $job->saves
                ->contains('user_id', $user?->id);

            $job->is_following_company = $job->company
                ? $job->company->followers->contains('id', $user?->id)
                : false;

            // أسماء أنواع التفاعلات الموجودة فقط
            $job->reaction_icons = $job->reactions
                ->pluck('type')
                ->unique()
                ->values();

            unset($job->reactions);
            unset($job->saves);

            return $job;
        });

        return $jobs;
    }
}
