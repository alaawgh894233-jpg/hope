<?php

namespace App\Http\Controllers;

use App\Models\SalaryInsight;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SalaryInsightController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SalaryInsight::query()->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        return response()->json($query->get());
    }
}
