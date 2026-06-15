<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\JobPost;
use App\Models\StartupProject;
use Illuminate\Support\Facades\Storage;

class CompanyService
{
    // 📌 عرض بروفايل شركتي
    public function myCompany($user): array
    {
        $company = $user->company;

        if (!$company) {
            return ['status' => 404, 'message' => 'Company not found'];
        }

        return ['status' => 200, 'data' => $company];
    }

    // 📌 تحديث بروفايل الشركة
    public function update($user, array $data): array
    {
        $company = $user->company;

        if (!$company) {
            return ['status' => 404, 'message' => 'Company not found'];
        }

        if (isset($data['logo']) && $company->logo) {
            Storage::delete($company->logo);
        }

        $company->update(array_filter([
            'company_name'   => $data['company_name'] ?? null,
            'description'    => $data['description'] ?? null,
            'website_url'    => $data['website_url'] ?? null,
            'local_address'  => $data['local_address'] ?? null,
            'phone'          => $data['phone'] ?? null,
            'sector'         => $data['sector'] ?? null,
            'category'       => $data['category'] ?? null,
            'logo'           => $data['logo'] ?? null,
            'support_offers' => $data['support_offers'] ?? null,
        ], fn($v) => !is_null($v)));

        return ['status' => 200, 'data' => $company->fresh()];
    }

    // 📌 رفع وثيقة
    public function uploadDocument($user, $type, $file): array
    {
        $company = $user->company;

        if (!$company) {
            return ['status' => 404, 'message' => 'Company not found'];
        }

        $path = $file->store('company-documents');

        $doc = CompanyDocument::create([
            'company_id' => $company->id,
            'type'       => $type,
            'file_path'  => $path
        ]);

        return ['status' => 200, 'data' => $doc];
    }

    // 📌 عرض وثائق الشركة
    public function documents($user): array
    {
        $company = $user->company;

        if (!$company) {
            return ['status' => 404, 'message' => 'Company not found'];
        }

        return [
            'status' => 200,
            'data'   => $company->documents()->latest()->get()
        ];
    }

    // 🌐 بروفايل الشركة العام
    public function publicProfile(int $id): array
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

        return [
            'status' => 200,
            'data'   => [
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
            ]
        ];
    }
}
