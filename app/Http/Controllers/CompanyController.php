<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\CompanyProfileUpdateRequest;
use App\Models\Company;
use App\Models\JobPost;
use App\Models\StartupProject;
use App\Models\User;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyService $service
    ) {}

    // 📌 عرض بروفايل شركتي (للشركة نفسها)
    public function myCompany(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->myCompany($user)
        );
    }

    // 📌 تحديث بروفايل الشركة
    public function update(CompanyProfileUpdateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('company-logos', 'public');
        }

        return response()->json(
            $this->service->update($user, $data)
        );
    }

    // 📌 رفع وثيقة
    public function uploadDocument(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'file' => 'required|file|max:5120'
        ]);

        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->uploadDocument($user, $request->type, $request->file('file'))
        );
    }

    // 📌 عرض وثائق الشركة
    public function documents(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json(
            $this->service->documents($user)
        );
    }

    // 🌐 بروفايل الشركة العام — للجميع
    public function publicProfile(int $id): JsonResponse
    {
        $company = Company::with('user:id,name,email')
            ->where('status', 'approved')
            ->findOrFail($id);

        $jobs = JobPost::where('company_id', $company->id)
            ->where('status', 'published')
            ->latest()
            ->take(6)
            ->get(['id', 'title', 'location', 'type', 'is_remote', 'created_at']);

        $projects = StartupProject::where('company_id', $company->id)
            ->where('status', 'active')
            ->latest()
            ->take(4)
            ->get(['id', 'title', 'stage', 'support_types', 'created_at']);

        return response()->json([
            'company'  => $company,
            'jobs'     => $jobs,
            'projects' => $projects,
            'stats'    => [
                'total_jobs'     => JobPost::where('company_id', $company->id)
                    ->where('status', 'published')->count(),
                'total_projects' => StartupProject::where('company_id', $company->id)
                    ->where('status', 'active')->count(),
                'followers'      => $company->followers()->count(),
            ]
        ]);
    }
}
