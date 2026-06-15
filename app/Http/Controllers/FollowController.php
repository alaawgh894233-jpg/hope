<?php

namespace App\Http\Controllers;

use App\Services\FollowService;

class FollowController extends Controller
{
    public function __construct(
        private FollowService $service
    ) {}

    public function toggle($companyId)
    {
        $result = $this->service->toggle(auth()->id(), $companyId);

        return response()->json([
            'message' => $result
        ]);
    }

    public function isFollowing($companyId)
    {
        return response()->json([
            'following' => $this->service->isFollowing(auth()->id(), $companyId)
        ]);
    }

    public function followersCount($companyId)
    {
        return response()->json([
            'count' => $this->service->followersCount($companyId)
        ]);
    }

    public function myFollowing()
    {
        return response()->json(
            $this->service->getUserFollowing(auth()->id())
        );
    }

    public function companyFollowers($companyId)
    {
        $user = auth()->user();

        if (
            $user->role !== 'admin' &&
            $user->company?->id != $companyId
        ) {
            abort(403);
        }
        return response()->json(
            $this->service->getCompanyFollowers($companyId)
        );
    }
}
