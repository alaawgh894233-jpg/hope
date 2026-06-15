<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\CreateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $service
    ) {}

    // CREATE
    public function store(CreateProfileRequest $request)
    {
        return response()->json(
            $this->service->create(
                $request->user(),
                $request->validated()
            )
        );
    }

    // UPDATE
    public function update(UpdateProfileRequest $request)
    {
        $profile = $this->service->update(
            $request->user(),
            $request->validated()
        );

        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json($profile);
    }

    // SHOW
    public function show(Request $request)
    {
        return response()->json(
            $this->service->get($request->user())
        );
    }

    // DELETE
    public function destroy(Request $request)
    {
        $deleted = $this->service->delete($request->user());

        if (!$deleted) {
            return response()->json([
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}
