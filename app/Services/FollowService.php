<?php

namespace App\Services;

use App\Models\CompanyFollower;

class FollowService
{
    public function toggle($userId, $companyId)
    {
        $follow = CompanyFollower::where([
            'user_id' => $userId,
            'company_id' => $companyId
        ])->first();

        if ($follow) {
            $follow->delete();
            return 'unfollowed';
        }

        CompanyFollower::create([
            'user_id' => $userId,
            'company_id' => $companyId
        ]);

        return 'followed';
    }

    public function isFollowing($userId, $companyId)
    {
        return CompanyFollower::where([
            'user_id' => $userId,
            'company_id' => $companyId
        ])->exists();
    }

    public function followersCount($companyId)
    {
        return CompanyFollower::where('company_id', $companyId)->count();
    }

    public function getUserFollowing($userId)
    {
        return CompanyFollower::with('company')
            ->where('user_id', $userId)
            ->get();
    }

    public function getCompanyFollowers($companyId)
    {
        return CompanyFollower::with('user')
            ->where('company_id', $companyId)
            ->get();
    }
}
