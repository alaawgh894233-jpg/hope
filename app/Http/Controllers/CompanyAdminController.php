<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AdminCompanyApprovalService;
use Illuminate\Http\Request;

class CompanyAdminController extends Controller
{
    public function __construct(
        private AdminCompanyApprovalService $service
    ) {}

    // 📌 list companies
    public function index(Request $request)
    {
        return response()->json(
            $this->service->list($request->all())
        );
    }

    // 📌 show company
    public function show($id)
    {
        return response()->json(
            $this->service->show($id)
        );
    }

    // 📌 approve company
    public function approve($id)
    {
        return response()->json([
            'message' => 'Company approved',
            'data' => $this->service->approve($id)
        ]);
    }

    // 📌 reject company
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string'
        ]);

        return response()->json([
            'message' => 'Company rejected',
            'data' => $this->service->reject($id, $request->rejection_reason)
        ]);
    }
}
