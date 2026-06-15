<?php

namespace App\Http\Controllers;

use App\Http\Requests\Experience\StoreExperienceRequest;
use App\Http\Requests\Experience\UpdateExperienceRequest;
use App\Services\ExperienceService;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    public function __construct(
        protected ExperienceService $service
    ) {}

    // CREATE
    public function store(StoreExperienceRequest $request)
    {
        return response()->json(
            $this->service->create(
                $request->user(),
                $request->validated()
            ),
            201
        );
    }

    // UPDATE
    public function update(
        UpdateExperienceRequest $request,
        int $id
    ) {
        $experience = $this->service->update(
            $request->user(),
            $id,
            $request->validated()
        );

        if (!$experience) {
            return response()->json([
                'message' => 'Experience not found'
            ], 404);
        }

        return response()->json($experience);
    }

    // GET ALL
    public function index(Request $request)
    {
        return response()->json(
            $this->service->getAll(
                $request->user()
            )
        );
    }

    // GET ONE
    public function show(
        Request $request,
        int $id
    ) {
        $experience = $this->service->getOne(
            $request->user(),
            $id
        );

        if (!$experience) {
            return response()->json([
                'message' => 'Experience not found'
            ], 404);
        }

        return response()->json($experience);
    }

    // DELETE
    public function destroy(
        Request $request,
        int $id
    ) {
        $deleted = $this->service->delete(
            $request->user(),
            $id
        );

        if (!$deleted) {
            return response()->json([
                'message' => 'Experience not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}
