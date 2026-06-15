<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProjectService;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $service
    ) {}

    // CREATE
    public function store(
        StoreProjectRequest $request
    ) {
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
        UpdateProjectRequest $request,
        int $id
    ) {
        $project = $this->service->update(
            $request->user(),
            $id,
            $request->validated()
        );

        if (!$project) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }

        return response()->json($project);
    }

    // GET ALL
    public function index(
        Request $request
    ) {
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
        $project = $this->service->getOne(
            $request->user(),
            $id
        );

        if (!$project) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }

        return response()->json($project);
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
                'message' => 'Project not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}
