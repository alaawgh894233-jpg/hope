<?php

namespace App\Http\Controllers;

use App\Models\JobPost;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2'
        ]);

        $query = $request->q;

        $jobs = JobPost::where('title', 'like', "%{$query}%")
            ->latest()
            ->limit(10)
            ->get();

        $companies = Company::where('company_name', 'like', "%{$query}%")
            ->latest()
            ->limit(10)
            ->get();

        $users = User::where('name', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        return response()->json([
            'jobs' => $jobs,
            'companies' => $companies,
            'users' => $users
        ]);
    }
}
