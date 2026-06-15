<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $service
    ) {}

    // 🌐 قائمة الفئات
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|in:job_type,sector,project_type'
        ]);

        return response()->json(
            $this->service->list($request->all())
        );
    }

    // 🌐 عرض فئة مع فرص العمل المرتبطة
    public function show(int $id): JsonResponse
    {
        return response()->json(
            $this->service->show($id)
        );
    }

    // 👑 Admin
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->store($request->validated()),
            201
        );
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        return response()->json(
            $this->service->update($id, $request->validated())
        );
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(
            $this->service->destroy($id)
        );
    }

    public function toggle(int $id): JsonResponse
    {
        return response()->json(
            $this->service->toggle($id)
        );
    }
}
