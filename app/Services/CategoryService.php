<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryService
{
    public function list(array $filters): array
    {
        $query = Category::active();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return ['status' => 200, 'data' => $query->orderBy('name')->get()];
    }

    // ✅ عرض فئة مع فرص العمل المرتبطة
    public function show(int $id): array
    {
        $category = Category::findOrFail($id);

        $jobs = $category->jobPosts()
            ->with('company:id,company_name')
            ->where('status', 'published')
            ->latest()
            ->paginate(10);

        return [
            'status' => 200,
            'data'   => [
                'category'   => $category,
                'jobs'       => $jobs,
                'jobs_count' => $category->jobPosts()->where('status', 'published')->count(),
            ]
        ];
    }

    public function store(array $data): array
    {
        $category = Category::create([
            ...$data,
            'slug'      => Str::slug($data['name']),
            'is_active' => true,
        ]);

        return ['status' => 200, 'data' => $category];
    }

    public function update(int $id, array $data): array
    {
        $category = Category::findOrFail($id);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return ['status' => 200, 'data' => $category->fresh()];
    }

    public function destroy(int $id): array
    {
        Category::findOrFail($id)->delete();
        return ['status' => 200, 'message' => 'Category deleted'];
    }

    public function toggle(int $id): array
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        return [
            'status'  => 200,
            'message' => $category->is_active ? 'Category activated' : 'Category deactivated',
            'data'    => $category
        ];
    }
}
