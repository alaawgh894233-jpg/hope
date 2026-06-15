<?php

namespace App\Http\Controllers;

use App\Http\Requests\Complaint\StoreComplaintRequest;
use App\Http\Requests\Complaint\UpdateComplaintStatusRequest;
use App\Services\ComplaintService;


class ComplaintController extends Controller
{
    public function __construct(
        private ComplaintService $service
    ) {}

    public function store(StoreComplaintRequest $request)
    {
        return $this->service->create(
            auth()->id(),
            $request->validated()
        );
    }

    public function index()
    {
        $this->authorizeAdmin();

        return $this->service->getAll();
    }

    public function updateStatus(UpdateComplaintStatusRequest $request, $id)
    {
        $this->authorizeAdmin();

        return $this->service->updateStatus($id, $request->status);
    }

    public function destroy($id)
    {
        $this->authorizeAdmin();

        $this->service->delete($id);

        return response()->json([
            'message' => 'deleted'
        ]);
    }

    private function authorizeAdmin()
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized');
        }
    }
}
