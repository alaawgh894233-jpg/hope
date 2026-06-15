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
        $job = JobPost::with(['company', 'categories'])->findOrFail($id);
        $job->increment('views');

        return $job->fresh();
    }

    public function list(array $filters)
    {
        $query = JobPost::with('company');

        // 🔎 search
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        // 🔧 فلترة بالـ skills (بدون #)
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

        // 🏷️ فلترة بالـ tags (مع #)
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

        return $query->latest()->paginate($perPage);
    }
}
