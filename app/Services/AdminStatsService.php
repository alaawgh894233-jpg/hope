<?php

namespace App\Services;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\StartupProject;
use App\Models\User;

class AdminStatsService
{
    public function dashboard(): array
    {
        return [
            'status' => 200,
            'data'   => [

                // 👥 المستخدمين
                'users' => [
                    'total'    => User::count(),
                    'users'    => User::where('role', 'user')->count(),
                    'companies'=> User::where('role', 'company')->count(),
                    'banned'   => User::whereNotNull('banned_at')->count(),
                    'new_this_month' => User::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)->count(),
                ],

                // 🏢 الشركات
                'companies' => [
                    'total'    => Company::count(),
                    'pending'  => Company::where('status', 'pending')->count(),
                    'approved' => Company::where('status', 'approved')->count(),
                    'rejected' => Company::where('status', 'rejected')->count(),
                ],

                // 💼 فرص العمل
                'jobs' => [
                    'total'     => JobPost::count(),
                    'published' => JobPost::where('status', 'published')->count(),
                    'draft'     => JobPost::where('status', 'draft')->count(),
                    'closed'    => JobPost::where('status', 'closed')->count(),
                    'new_this_month' => JobPost::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)->count(),
                ],

                // 📋 الطلبات
                'applications' => [
                    'total'     => JobApplication::count(),
                    'pending'   => JobApplication::where('status', 'pending')->count(),
                    'interview' => JobApplication::where('status', 'interview')->count(),
                    'training'  => JobApplication::where('status', 'training')->count(),
                    'accepted'  => JobApplication::where('status', 'accepted')->count(),
                    'rejected'  => JobApplication::where('status', 'rejected')->count(),
                ],

                // 🚀 المشاريع
                'projects' => [
                    'total'  => StartupProject::count(),
                    'active' => StartupProject::where('status', 'active')->count(),
                    'closed' => StartupProject::where('status', 'closed')->count(),
                    'new_this_month' => StartupProject::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)->count(),
                ],

                // 📈 آخر النشاطات
                'recent' => [
                    'users'    => User::latest()->take(5)
                        ->get(['id', 'name', 'email', 'role', 'created_at']),
                    'companies'=> Company::with('user:id,name,email')
                        ->where('status', 'pending')
                        ->latest()->take(5)->get(),
                    'jobs'     => JobPost::with('company:id,company_name')
                        ->latest()->take(5)
                        ->get(['id', 'title', 'status', 'company_id', 'created_at']),
                ]
            ]
        ];
    }
}
