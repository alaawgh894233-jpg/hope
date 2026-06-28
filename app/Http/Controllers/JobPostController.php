<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobPost\StoreJobPostRequest;
use App\Http\Requests\JobPost\UpdateJobPostRequest;
use App\Models\JobPost;
use App\Models\User;
use App\Services\JobPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobPostController extends Controller
{
    public function __construct(
        private readonly JobPostService $service  // ✅ readonly
    ) {}

    // 📌 Create
    public function store(StoreJobPostRequest $request): JsonResponse  // ✅ return type
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $company = $user->company;

        if (!$company) {
            return response()->json(['message' => 'No company'], 403);
        }

        $job = $this->service->create(
            $company->id,
            $user->id,
            $request->validated()
        );

        return response()->json($job, 201);
    }

    // 📌 Update
    public function update(UpdateJobPostRequest $request, $id): JsonResponse  // ✅ return type
    {
        $job = JobPost::findOrFail($id);

        $this->authorizeJob($job);

        $updated = $this->service->update($job, $request->validated());

        return response()->json($updated);
    }

    // 📌 Delete
    public function destroy($id): JsonResponse  // ✅ return type
    {
        $job = JobPost::findOrFail($id);

        $this->authorizeJob($job);

        $this->service->delete($job);

        return response()->json(['message' => 'deleted']);
    }

    // 📌 Show
    public function show($id): JsonResponse  // ✅ return type
    {
        return response()->json(
            $this->service->getById($id)
        );
    }

    // app/Http/Controllers/JobPostController.php
    public function index(Request $request): JsonResponse
    {
        $jobs = $this->service->list($request->all(), auth()->user());

        return response()->json($jobs);
    }

    // 🔐 Ownership check — Admin يقدر يعدل/يحذف أي post
    private function authorizeJob(JobPost $job): void  // ✅ return type
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        // ✅ Admin يتجاوز الـ ownership check
        if ($user->role === 'admin') {
            return;
        }

        $company = $user->company;

        if (!$company) {
            abort(403);
        }

        if ($job->company_id !== $company->id) {
            abort(403, 'Unauthorized');
        }
    }
}
