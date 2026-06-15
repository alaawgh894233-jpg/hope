<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EducationService;
use App\Http\Requests\Education\StoreEducationRequest;
use App\Http\Requests\Education\UpdateEducationRequest;

class EducationController extends Controller
{
    public function __construct(
        protected EducationService $service
    ) {}

    // CREATE
    public function store(
        StoreEducationRequest $request
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
        UpdateEducationRequest $request,
        int $id
    ) {
        $education = $this->service->update(
            $request->user(),
            $id,
            $request->validated()
        );

        if (!$education) {
            return response()->json([
                'message' => 'Education not found'
            ], 404);
        }

        return response()->json($education);
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
        $education = $this->service->getOne(
            $request->user(),
            $id
        );

        if (!$education) {
            return response()->json([
                'message' => 'Education not found'
            ], 404);
        }

        return response()->json($education);
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
                'message' => 'Education not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}
