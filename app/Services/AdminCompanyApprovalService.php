<?php

namespace App\Services;

use App\Models\Company;

class AdminCompanyApprovalService
{
    // 📌 approve company
    public function approve(int $companyId): Company
    {
        $company = Company::findOrFail($companyId);

        $company->update([
            'status' => 'approved',
            'rejection_reason' => null
        ]);

        return $company;
    }

    // 📌 reject company
    public function reject(int $companyId, string $reason): Company
    {
        $company = Company::findOrFail($companyId);

        $company->update([
            'status' => 'rejected',
            'rejection_reason' => $reason
        ]);

        return $company;
    }

    // 📌 list companies with filters
    public function list(array $filters)
    {
        $query = Company::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('company_name', 'like', "%{$filters['search']}%");
        }

        return $query->with('user')->latest()->paginate(10);
    }

    // 📌 single company
    public function show(int $id): Company
    {
        return Company::with(['user', 'documents'])->findOrFail($id);
    }
}
