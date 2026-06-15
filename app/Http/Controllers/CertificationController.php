<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CertificationService;
use App\Http\Requests\Certification\StoreCertificationRequest;
use App\Http\Requests\Certification\UpdateCertificationRequest;

class CertificationController extends Controller
{
    public function __construct(
        protected CertificationService $service
    ) {}

    // CREATE
    public function store(
        StoreCertificationRequest $request
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
        UpdateCertificationRequest $request,
        int $id
    ) {
        $certification = $this->service->update(
            $request->user(),
            $id,
            $request->validated()
        );

        if (!$certification) {
            return response()->json([
                'message' => 'Certification not found'
            ], 404);
        }

        return response()->json($certification);
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
        $certification = $this->service->getOne(
            $request->user(),
            $id
        );

        if (!$certification) {
            return response()->json([
                'message' => 'Certification not found'
            ], 404);
        }

        return response()->json($certification);
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
                'message' => 'Certification not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}
