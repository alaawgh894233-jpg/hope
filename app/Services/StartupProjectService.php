<?php

namespace App\Services;

use App\Models\Company;
use App\Models\StartupProject;
use App\Models\StartupProjectInterest;
use App\Models\StartupProjectInvitation;
use Illuminate\Support\Facades\Mail;

class StartupProjectService
{
    // ══════════════════════════════════════════════════
    //  1️⃣ إنشاء الفكرة
    // ══════════════════════════════════════════════════
    public function create($user, array $data): array
    {
        $project = StartupProject::create([
            'user_id'       => $user->id,
            'company_id'    => $user->company?->id,
            'title'         => $data['title'],
            'description'   => $data['description'],
            'summary'       => $data['summary'],
            'category'      => $data['category']    ?? null,
            'stage'         => $data['stage']        ?? 'idea',
            'support_types' => $data['support_types'],
            'funding_goal'  => $data['funding_goal'] ?? null,
            'location'      => $data['location']     ?? null,
            'website_url'   => $data['website_url']  ?? null,
            'status'        => 'draft',
        ]);

        // ✅ نرجع المشروع + الشركات المقترحة فوراً
        $suggestedCompanies = $this->findMatchingCompanies($project);

        return [
            'status'              => 201,
            'message'             => 'Project created — choose companies to invite',
            'data'                => $project,
            'suggested_companies' => $suggestedCompanies,
        ];
    }

    // ══════════════════════════════════════════════════
    //  2️⃣ اقتراح الشركات
    // ══════════════════════════════════════════════════
    public function suggestCompanies(int $projectId, $user): array
    {
        $project = StartupProject::findOrFail($projectId);

        if ($project->user_id !== $user->id && $user->role !== 'admin') {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        $companies = $this->findMatchingCompanies($project);

        return [
            'status' => 200,
            'total'  => count($companies),
            'data'   => $companies,
        ];
    }

    // ══════════════════════════════════════════════════
    //  3️⃣ المستخدم يختار ويدعو الشركات
    // ══════════════════════════════════════════════════
    public function invite($user, int $projectId, array $companyIds): array
    {
        $project = StartupProject::findOrFail($projectId);

        if ($project->user_id !== $user->id) {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        if (!in_array($project->status, ['draft', 'inviting'])) {
            return ['status' => 409, 'message' => 'Project is not open for invitations'];
        }

        $sent    = [];
        $skipped = [];

        foreach ($companyIds as $companyId) {
            $company = Company::with('user:id,email,name')
                ->where('status', 'approved')
                ->find($companyId);

            if (!$company) {
                $skipped[] = [
                    'company_id' => $companyId,
                    'reason'     => 'Company not found or not approved',
                ];
                continue;
            }

            if ($company->id === $project->company_id) {
                $skipped[] = [
                    'company_id' => $companyId,
                    'reason'     => 'Cannot invite your own company',
                ];
                continue;
            }

            $alreadyInvited = StartupProjectInvitation::where([
                'startup_project_id' => $projectId,
                'company_id'         => $companyId,
            ])->exists();

            if ($alreadyInvited) {
                $skipped[] = [
                    'company_id' => $companyId,
                    'reason'     => 'Already invited',
                ];
                continue;
            }

            // ✅ سجّل الدعوة
            StartupProjectInvitation::create([
                'startup_project_id' => $projectId,
                'company_id'         => $companyId,
                'status'             => 'pending',
                'sent_at'            => now(),
            ]);

            // ✅ ابعت الإيميل
            $this->sendInvitationEmail($company, $project);

            $sent[] = [
                'company_id'   => $companyId,
                'company_name' => $company->company_name,
            ];
        }

        if (!empty($sent)) {
            $project->update(['status' => 'inviting']);
        }

        return [
            'status'  => 200,
            'message' => count($sent) . ' invitations sent',
            'sent'    => $sent,
            'skipped' => $skipped,
        ];
    }

    // ══════════════════════════════════════════════════
    //  4️⃣ الشركة تبدي اهتمام
    // ══════════════════════════════════════════════════
    public function expressInterest($user, int $projectId, array $data): array
    {
        $project = StartupProject::findOrFail($projectId);
        $company = $user->company;

        if (!$company) {
            return ['status' => 403, 'message' => 'Only companies can express interest'];
        }

        if ($project->user_id === $user->id) {
            return ['status' => 403, 'message' => 'Cannot express interest in your own project'];
        }

        // ✅ الشركة لازم تكون مدعوة
        $invitation = StartupProjectInvitation::where([
            'startup_project_id' => $projectId,
            'company_id'         => $company->id,
        ])->first();

        if (!$invitation) {
            return ['status' => 403, 'message' => 'Your company was not invited to this project'];
        }

        if ($invitation->status === 'interested') {
            return ['status' => 409, 'message' => 'Already expressed interest'];
        }

        if (!in_array($data['support_type'], $project->support_types)) {
            return ['status' => 422, 'message' => 'This support type is not needed for this project'];
        }

        $interest = StartupProjectInterest::create([
            'startup_project_id' => $projectId,
            'company_id'         => $company->id,
            'support_type'       => $data['support_type'],
            'message'            => $data['message']        ?? null,
            'funding_amount'     => $data['funding_amount'] ?? null,
            'status'             => 'pending',
        ]);

        // ✅ حدّث الدعوة
        $invitation->update(['status' => 'interested']);
        $project->increment('offers_count');

        // ✅ إيميل لصاحب المشروع
        $this->notifyOwnerOfInterest($project, $company, $data['support_type']);

        return [
            'status'  => 200,
            'message' => 'Interest submitted — waiting for owner approval',
            'data'    => $interest,
        ];
    }

    // ══════════════════════════════════════════════════
    //  5️⃣ المستخدم يرد على الاهتمام
    // ══════════════════════════════════════════════════
    public function respondToInterest($user, int $interestId, string $action): array
    {
        $interest = StartupProjectInterest::with('project')->findOrFail($interestId);

        if ($interest->project->user_id !== $user->id) {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        if (!in_array($action, ['approve', 'reject'])) {
            return ['status' => 422, 'message' => 'Invalid action — use approve or reject'];
        }

        if ($action === 'approve') {
            $interest->update(['status' => 'approved']);
            $interest->project->update(['status' => 'in_progress']);

            return [
                'status'  => 200,
                'message' => 'Interest approved — project is now in progress',
                'data'    => $interest->fresh(),
            ];
        }

        $interest->update(['status' => 'rejected']);

        return [
            'status'  => 200,
            'message' => 'Interest rejected',
            'data'    => $interest->fresh(),
        ];
    }

    // ══════════════════════════════════════════════════
    //  6️⃣ عرض مشروع
    // ══════════════════════════════════════════════════
    public function getById(int $id, $user): array
    {
        $project = StartupProject::with([
            'user:id,name',
            'company:id,company_name',
            'invitations.company:id,company_name,logo',
            'interests.company:id,company_name,logo',
        ])->findOrFail($id);

        $isOwner = $project->user_id === $user->id;
        $isAdmin = $user->role === 'admin';

        if (!$isOwner && !$isAdmin) {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        $project->increment('views');

        return ['status' => 200, 'data' => $project->fresh([
            'user:id,name',
            'company:id,company_name',
            'invitations.company:id,company_name,logo',
            'interests.company:id,company_name,logo',
        ])];
    }

    // ══════════════════════════════════════════════════
    //  7️⃣ عرض الاهتمامات
    // ══════════════════════════════════════════════════
    public function listInterests($user, int $projectId): array
    {
        $project = StartupProject::findOrFail($projectId);

        if ($project->user_id !== $user->id && $user->role !== 'admin') {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        return [
            'status' => 200,
            'data'   => $project->interests()
                ->with('company:id,company_name,logo,local_address')
                ->latest()
                ->get(),
        ];
    }

    // ══════════════════════════════════════════════════
    //  8️⃣ تعديل
    // ══════════════════════════════════════════════════
    public function update($user, int $id, array $data): array
    {
        $project = StartupProject::findOrFail($id);

        if ($project->user_id !== $user->id && $user->role !== 'admin') {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        if ($project->status === 'in_progress') {
            return ['status' => 403, 'message' => 'Cannot edit project after it is in progress'];
        }

        $project->update($data);

        return ['status' => 200, 'data' => $project->fresh()];
    }

    // ══════════════════════════════════════════════════
    //  9️⃣ حذف
    // ══════════════════════════════════════════════════
    public function delete($user, int $id): array
    {
        $project = StartupProject::findOrFail($id);

        if ($project->user_id !== $user->id && $user->role !== 'admin') {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        $project->delete();

        return ['status' => 200, 'message' => 'Deleted successfully'];
    }

    // ══════════════════════════════════════════════════
    //  PRIVATE — منطق الاقتراح
    // ══════════════════════════════════════════════════
    private function findMatchingCompanies(StartupProject $project)
    {
        $query = Company::with('user:id,email,name')
            ->where('status', 'approved')
            ->where('id', '!=', $project->company_id);

        // ✅ شرط إلزامي — نوع الدعم
        $query->where(function ($q) use ($project) {
            foreach ($project->support_types as $type) {
                $q->orWhereJsonContains('support_offers', $type);
            }
        });

        // ✅ ترتيب حسب الأقرب
        $query->orderByRaw("
            (
                CASE WHEN category = ? THEN 2 ELSE 0 END +
                CASE WHEN local_address LIKE ? THEN 1 ELSE 0 END
            ) DESC
        ", [
            $project->category ?? '',
            '%' . ($project->location ?? '') . '%',
        ]);

        return $query->take(20)->get();
    }

    // ══════════════════════════════════════════════════
    //  PRIVATE — الإيميلات
    // ══════════════════════════════════════════════════
    private function sendInvitationEmail(Company $company, StartupProject $project): void
    {
        $contactEmail = $company->user?->email;
        if (!$contactEmail) return;

        $supportTypes = collect($project->support_types)
            ->map(fn($t) => match ($t) {
                'funding'     => 'تمويل مالي 💰',
                'mentoring'   => 'إرشاد وتوجيه 🎯',
                'partnership' => 'شراكة استراتيجية 🤝',
                default       => $t,
            })->implode(' — ');

        $stage = match ($project->stage) {
            'idea'        => 'فكرة 💡',
            'in_progress' => 'قيد التنفيذ 🚀',
            'expanding'   => 'توسع 📈',
            default       => $project->stage,
        };

        $fundingLine = $project->funding_goal
            ? "<p>🎯 هدف التمويل: <strong>" . number_format($project->funding_goal, 0) . " $</strong></p>"
            : '';

        $locationLine = $project->location
            ? "<p>📍 الموقع: <strong>{$project->location}</strong></p>"
            : '';

        Mail::send([], [], function ($message) use (
            $contactEmail, $company, $project,
            $supportTypes, $stage, $fundingLine, $locationLine
        ) {
            $message
                ->to($contactEmail, $company->company_name)
                ->subject("دعوة للتعاون في مشروع: {$project->title}")
                ->html("
                <div dir='rtl' style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px'>
                    <h2 style='color:#1d4ed8'>دُعيت للتعاون في مشروع جديد 🚀</h2>
                    <p>مرحباً <strong>{$company->company_name}</strong>،</p>
                    <p>اختارك صاحب المشروع كشريك محتمل:</p>

                    <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:16px 0'>
                        <h3 style='margin:0 0 12px;color:#0f172a'>{$project->title}</h3>
                        <p style='color:#475569;margin:0 0 12px'>{$project->summary}</p>
                        <p>📊 المرحلة: <strong>{$stage}</strong></p>
                        <p>🤝 نوع الدعم المطلوب: <strong>{$supportTypes}</strong></p>
                        {$fundingLine}
                        {$locationLine}
                    </div>

                    <div style='background:#fef3c7;border-right:4px solid #f59e0b;padding:12px 16px;border-radius:4px;margin:16px 0'>
                        <p style='margin:0;font-size:13px;color:#92400e'>
                            🔒 التفاصيل الكاملة محمية — أبدِ اهتمامك وسيقرر صاحب المشروع مشاركة التفاصيل معك.
                        </p>
                    </div>

                    <p style='color:#6b7280;font-size:13px;margin-top:24px'>مع تحيات فريق المنصة</p>
                </div>");
        });
    }

    private function notifyOwnerOfInterest(
        StartupProject $project,
        Company        $company,
        string         $supportType
    ): void {
        $owner = $project->user;
        if (!$owner?->email) return;

        $supportLabel = match ($supportType) {
            'funding'     => 'تمويل مالي 💰',
            'mentoring'   => 'إرشاد وتوجيه 🎯',
            'partnership' => 'شراكة استراتيجية 🤝',
            default       => $supportType,
        };

        Mail::send([], [], function ($message) use ($owner, $company, $project, $supportLabel) {
            $message
                ->to($owner->email, $owner->name)
                ->subject("شركة مهتمة بمشروعك: {$project->title}")
                ->html("
                <div dir='rtl' style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px'>
                    <h2 style='color:#16a34a'>شركة أبدت اهتماماً بمشروعك! 🎉</h2>
                    <p>مرحباً <strong>{$owner->name}</strong>،</p>
                    <p>شركة <strong>{$company->company_name}</strong> أبدت اهتمامها بمشروعك <strong>\"{$project->title}\"</strong>.</p>
                    <div style='background:#f0fdf4;border-right:4px solid #16a34a;padding:12px 16px;border-radius:4px;margin:16px 0'>
                        <p style='margin:0'>نوع الدعم: <strong>{$supportLabel}</strong></p>
                    </div>
                    <p>يمكنك الموافقة أو الرفض من لوحة التحكم.</p>
                    <p style='color:#6b7280;font-size:13px'>مع تحيات فريق المنصة</p>
                </div>");
        });
    }
}
