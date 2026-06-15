<?php

namespace App\Services;

use App\Models\JobPost;
use App\Models\StartupProject;
use Illuminate\Support\Facades\Mail;

class AdminContentService
{
    // ─── فرص العمل ─────────────────────────────────────────

    public function listJobs(array $filters): array
    {
        $query = JobPost::with('company:id,company_name');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }

        $perPage = min((int)($filters['per_page'] ?? 15), 50);

        return ['status' => 200, 'data' => $query->latest()->paginate($perPage)];
    }

    public function showJob(int $id): array
    {
        return [
            'status' => 200,
            'data'   => JobPost::with(['company', 'applications'])->findOrFail($id)
        ];
    }

    // ✅ الأدمن يحذف الوظيفة مع إيميل فيه السبب
    public function deleteJob(int $id, ?string $reason = null): array
    {
        $job   = JobPost::with('company.user')->findOrFail($id);
        $email = $job->company?->user?->email;

        if ($email && $reason) {
            Mail::send([], [], function ($m) use ($email, $job, $reason) {
                $m->to($email)
                    ->subject("تم حذف فرصة العمل: {$job->title}")
                    ->html("
                  <div dir='rtl' style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:8px'>
                      <h2 style='color:#dc2626'>تم حذف فرصة العمل ❌</h2>
                      <p>تم حذف فرصة العمل <strong>\"{$job->title}\"</strong> من قبل إدارة المنصة.</p>
                      <div style='background:#fef2f2;border-right:4px solid #dc2626;padding:12px;border-radius:4px;margin:16px 0'>
                          <p style='margin:0'><strong>السبب:</strong> {$reason}</p>
                      </div>
                      <p>للاستفسار يرجى التواصل مع الدعم.</p>
                      <p style='color:#6b7280;font-size:13px'>مع تحيات فريق المنصة</p>
                  </div>");
            });
        }

        $job->delete();

        return ['status' => 200, 'message' => 'Job deleted'];
    }

    // ─── المشاريع (StartupProject) ──────────────────────────
    // ✅ الأدمن ما يوافق/يرفض — العلاقة بين الشركة والمستخدم
    // الأدمن بس يشوف ويحذف لو في مخالفة

    public function listProjects(array $filters): array
    {
        $query = StartupProject::with('user:id,name,email');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }

        if (!empty($filters['support_type'])) {
            $query->whereJsonContains('support_types', $filters['support_type']);
        }

        $perPage = min((int)($filters['per_page'] ?? 15), 50);

        return ['status' => 200, 'data' => $query->latest()->paginate($perPage)];
    }

    public function showProject(int $id): array
    {
        return [
            'status' => 200,
            'data'   => StartupProject::with(['user', 'company', 'interests.company'])->findOrFail($id)
        ];
    }

    // ✅ الأدمن يحذف المشروع مع إيميل فيه السبب
    public function deleteProject(int $id, ?string $reason = null): array
    {
        $project = StartupProject::with('user')->findOrFail($id);
        $email   = $project->user?->email;

        if ($email && $reason) {
            Mail::send([], [], function ($m) use ($email, $project, $reason) {
                $m->to($email)
                    ->subject("تم حذف مشروعك: {$project->title}")
                    ->html("
                  <div dir='rtl' style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:8px'>
                      <h2 style='color:#dc2626'>تم حذف المشروع ❌</h2>
                      <p>تم حذف مشروعك <strong>\"{$project->title}\"</strong> من قبل إدارة المنصة.</p>
                      <div style='background:#fef2f2;border-right:4px solid #dc2626;padding:12px;border-radius:4px;margin:16px 0'>
                          <p style='margin:0'><strong>السبب:</strong> {$reason}</p>
                      </div>
                      <p>للاستفسار يرجى التواصل مع الدعم.</p>
                      <p style='color:#6b7280;font-size:13px'>مع تحيات فريق المنصة</p>
                  </div>");
            });
        }

        $project->delete();

        return ['status' => 200, 'message' => 'Project deleted'];
    }
}
