<?php

namespace App\Http\Controllers;

use App\Services\StartupProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StartupProjectController extends Controller
{
    public function __construct(
        private readonly StartupProjectService $service
    ) {}

    // 1️⃣ POST /startup-projects
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'summary'        => 'required|string|max:500',
            'category'       => 'nullable|string|max:100',
            'stage'          => 'nullable|in:idea,in_progress,expanding',
            'support_types'  => 'required|array|min:1',
            'support_types.*'=> 'in:funding,mentoring,partnership',
            'funding_goal'   => 'nullable|numeric|min:0',
            'location'       => 'nullable|string|max:255',
            'website_url'    => 'nullable|url',
        ]);

        $result = $this->service->create(auth()->user(), $validated);
        return response()->json($result, $result['status']);
    }

    // 2️⃣ GET /startup-projects/{id}/suggest-companies
    public function suggestCompanies(int $id): JsonResponse
    {
        $result = $this->service->suggestCompanies($id, auth()->user());
        return response()->json($result, $result['status']);
    }

    // 3️⃣ POST /startup-projects/{id}/invite
    public function invite(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'company_ids'   => 'required|array|min:1',
            'company_ids.*' => 'integer|exists:companies,id',
        ]);

        $result = $this->service->invite(
            auth()->user(),
            $id,
            $validated['company_ids']
        );
        return response()->json($result, $result['status']);
    }

    // 4️⃣ POST /startup-projects/{id}/interest
    public function expressInterest(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'support_type'   => 'required|in:funding,mentoring,partnership',
            'message'        => 'nullable|string|max:1000',
            'funding_amount' => 'nullable|numeric|min:0',
        ]);

        $result = $this->service->expressInterest(
            auth()->user(),
            $id,
            $validated
        );
        return response()->json($result, $result['status']);
    }

    // 5️⃣ POST /startup-interests/{interestId}/respond
    public function respondToInterest(Request $request, int $interestId): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $result = $this->service->respondToInterest(
            auth()->user(),
            $interestId,
            $validated['action']
        );
        return response()->json($result, $result['status']);
    }

    // 6️⃣ GET /startup-projects/{id}
    public function show(int $id): JsonResponse
    {
        $result = $this->service->getById($id, auth()->user());
        return response()->json($result, $result['status']);
    }

    // 7️⃣ GET /startup-projects/{id}/interests
    public function interests(int $id): JsonResponse
    {
        $result = $this->service->listInterests(auth()->user(), $id);
        return response()->json($result, $result['status']);
    }

    // 8️⃣ POST /startup-projects/{id}/update
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title'          => 'sometimes|string|max:255',
            'description'    => 'sometimes|string',
            'summary'        => 'sometimes|string|max:500',
            'category'       => 'nullable|string|max:100',
            'stage'          => 'nullable|in:idea,in_progress,expanding',
            'support_types'  => 'sometimes|array|min:1',
            'support_types.*'=> 'in:funding,mentoring,partnership',
            'funding_goal'   => 'nullable|numeric|min:0',
            'location'       => 'nullable|string|max:255',
            'website_url'    => 'nullable|url',
        ]);

        $result = $this->service->update(auth()->user(), $id, $validated);
        return response()->json($result, $result['status']);
    }

    // 9️⃣ DELETE /startup-projects/{id}
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete(auth()->user(), $id);
        return response()->json($result, $result['status']);
    }
}
