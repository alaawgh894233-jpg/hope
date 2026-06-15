<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StartupProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StartupProjectController extends Controller
{
    public function __construct(
        private readonly StartupProjectService $service
    ) {}

    // 🌐 قائمة المشاريع — عام، summary بس
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->list($request->all())
        );
    }

    // 🌐 عرض مشروع — التفاصيل حسب الصلاحية
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        return response()->json(
            $this->service->getById($id, $user)
        );
    }

    // 📌 نشر مشروع
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'summary'         => 'required|string|max:500',   // ✅ ملخص عام
            'description'     => 'required|string',            // ✅ تفاصيل محمية
            'category'        => 'nullable|string|max:100',
            'stage'           => 'required|in:idea,in_progress,expanding',
            'support_types'   => 'required|array|min:1',
            'support_types.*' => 'in:funding,mentoring,partnership',
            'funding_goal'    => 'nullable|numeric|min:0',
            'location'        => 'nullable|string',
            'website_url'     => 'nullable|url',
        ]);

        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->create($user, $validated),
            201
        );
    }

    // 📌 تعديل مشروع
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title'           => 'sometimes|string|max:255',
            'summary'         => 'sometimes|string|max:500',
            'description'     => 'sometimes|string',
            'category'        => 'nullable|string|max:100',
            'stage'           => 'sometimes|in:idea,in_progress,expanding',
            'support_types'   => 'sometimes|array|min:1',
            'support_types.*' => 'in:funding,mentoring,partnership',
            'funding_goal'    => 'nullable|numeric|min:0',
            'location'        => 'nullable|string',
            'website_url'     => 'nullable|url',
            'status'          => 'sometimes|in:active,closed',
        ]);

        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->update($user, $id, $validated)
        );
    }

    // 📌 حذف مشروع
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->delete($user, $id)
        );
    }

    // 🏢 الشركة تعبر عن اهتمام
    public function expressInterest(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'support_type'   => 'required|in:funding,mentoring,partnership',
            'message'        => 'nullable|string|max:1000',
            'funding_amount' => 'nullable|numeric|min:0|required_if:support_type,funding',
        ]);

        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->expressInterest($user, $id, $validated)
        );
    }

    // ✅ صاحب المشروع يرد على اهتمام شركة
    public function respondToInterest(Request $request, int $interestId): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject'
        ]);

        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->respondToInterest($user, $interestId, $request->action)
        );
    }

    // 💡 اقتراح شركات للمشروع
    public function suggestCompanies(int $id): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->suggestCompanies($id, $user)
        );
    }

    // 📋 قائمة الاهتمامات على مشروع
    public function interests(int $id): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->listInterests($user, $id)
        );
    }
}
