<?php

namespace App\Services;

use App\Models\Company;
use App\Models\StartupProject;
use App\Models\StartupProjectInterest;
use Illuminate\Support\Facades\Mail;

class StartupProjectService
{
    // ─── صاحب المشروع ──────────────────────────────────────

    public function create($user, array $data): StartupProject
    {
        $project = StartupProject::create([
            'user_id'       => $user->id,
            'company_id'    => $user->company?->id,
            'title'         => $data['title'],
            'description'   => $data['description'],
            'summary'       => $data['summary'],
            'category'      => $data['category'] ?? null,
            'stage'         => $data['stage'] ?? 'idea',
            'support_types' => $data['support_types'],
            'funding_goal'  => $data['funding_goal'] ?? null,
            'location'      => $data['location'] ?? null,
            'website_url'   => $data['website_url'] ?? null,
            'status'        => 'active',
        ]);

        // ✅ فوراً بعد النشر — نلاقي الشركات المناسبة ونبعتلهن إيميل
        $this->notifyMatchingCompanies($project);

        return $project;
    }

    // ✅ يلاقي الشركات المناسبة ويبعتلهن إيميل
    private function notifyMatchingCompanies(StartupProject $project): void
    {
        $companies = $this->findMatchingCompanies($project);

        foreach ($companies as $company) {
            // ✅ ما نبعت لشركة صاحب المشروع نفسه
            if ($company->id === $project->company_id) continue;

            $contactEmail = $company->user?->email;
            if (!$contactEmail) continue;

            Mail::send([], [], function ($message) use ($company, $project) {
                $message
                    ->to($contactEmail ?? $company->user?->email, $company->company_name)
                    ->subject("مشروع جديد يناسب شركتك: {$project->title}")
                    ->html($this->buildCompanyNotificationEmail($company, $project));
            });
        }
    }

    // ✅ منطق الاقتراح: support_offers + موقع + قطاع
    private function findMatchingCompanies(StartupProject $project)
    {
        $query = Company::with('user:id,email')
            ->where('status', 'approved')
            ->where('id', '!=', $project->company_id);

        $query->where(function ($q) use ($project) {

            // ✅ الشركة تقدم نفس نوع الدعم اللي المشروع محتاجه
            foreach ($project->support_types as $type) {
                $q->orWhereJsonContains('support_offers', $type);
            }

            // موقع قريب — إضافي
            if ($project->location) {
                $q->orWhere('local_address', 'like', "%{$project->location}%");
            }

            // نفس القطاع — إضافي
            if ($project->category) {
                $q->orWhere('category', $project->category);
            }
        });

        return $query->take(20)->get();
    }

    // ✅ قالب الإيميل للشركة
    private function buildCompanyNotificationEmail(Company $company, StartupProject $project): string
    {
        $supportTypes = collect($project->support_types)->map(fn($t) => match($t) {
            'funding'     => 'تمويل مالي 💰',
            'mentoring'   => 'إرشاد وتوجيه 🎯',
            'partnership' => 'شراكة استراتيجية 🤝',
            default       => $t
        })->implode(' — ');

        $stage = match($project->stage) {
            'idea'        => 'فكرة 💡',
            'in_progress' => 'قيد التنفيذ 🚀',
            'expanding'   => 'توسع 📈',
            default       => $project->stage
        };

        $fundingLine = $project->funding_goal
            ? "<p>🎯 هدف التمويل: <strong>" . number_format($project->funding_goal, 0) . " $</strong></p>"
            : '';

        $locationLine = $project->location
            ? "<p>📍 الموقع: <strong>{$project->location}</strong></p>"
            : '';

        return "
        <div dir='rtl' style='font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px'>
            <h2 style='color:#1d4ed8'>مشروع جديد يناسب شركتك 🚀</h2>
            <p>مرحباً <strong>{$company->company_name}</strong>،</p>
            <p>وجدنا مشروعاً جديداً قد يكون فرصة مناسبة لكم:</p>

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
                    🔒 التفاصيل الكاملة للمشروع محمية — بإمكانك التعبير عن اهتمامك وسيقرر صاحب المشروع مشاركة التفاصيل معك.
                </p>
            </div>

            <p style='color:#6b7280;font-size:13px;margin-top:24px'>مع تحيات فريق المنصة</p>
        </div>";
    }

    // ─── باقي الدوال بدون تغيير ────────────────────────────

    public function list(array $filters)
    {
        $query = StartupProject::with(['user:id,name', 'company:id,company_name'])
            ->where('status', 'active')
            ->select([
                'id', 'user_id', 'company_id', 'title', 'summary',
                'category', 'stage', 'support_types', 'funding_goal',
                'location', 'views', 'offers_count', 'created_at'
            ]);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('summary', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['support_type'])) {
            $query->whereJsonContains('support_types', $filters['support_type']);
        }

        if (!empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['location'])) {
            $query->where('location', 'like', "%{$filters['location']}%");
        }

        $perPage = min((int)($filters['per_page'] ?? 10), 50);
        return $query->latest()->paginate($perPage);
    }

    public function getById(int $id, $user): array
    {
        $project = StartupProject::with(['user:id,name', 'company:id,company_name'])
            ->findOrFail($id);

        $project->increment('views');
        $project->refresh();

        $isOwner          = $project->user_id === $user->id;
        $isAdmin          = $user->role === 'admin';
        $isApprovedCompany = false;

        if ($user->company) {
            $isApprovedCompany = StartupProjectInterest::where([
                'startup_project_id' => $project->id,
                'company_id'         => $user->company->id,
                'details_shared'     => true,
            ])->exists();
        }

        $canSeeDetails = $isOwner || $isAdmin || $isApprovedCompany;
        $data = $project->toArray();

        if (!$canSeeDetails) {
            unset($data['description']);
            $data['details_locked'] = true;
        }

        return ['status' => 200, 'data' => $data];
    }

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

        if ($project->status !== 'active') {
            return ['status' => 403, 'message' => 'Project not accepting interests'];
        }

        if (!in_array($data['support_type'], $project->support_types)) {
            return ['status' => 422, 'message' => 'This support type is not needed for this project'];
        }

        $exists = StartupProjectInterest::where([
            'startup_project_id' => $projectId,
            'company_id'         => $company->id,
            'support_type'       => $data['support_type'],
        ])->exists();

        if ($exists) {
            return ['status' => 409, 'message' => 'Already expressed interest'];
        }

        $interest = StartupProjectInterest::create([
            'startup_project_id' => $projectId,
            'company_id'         => $company->id,
            'support_type'       => $data['support_type'],
            'message'            => $data['message'] ?? null,
            'funding_amount'     => $data['funding_amount'] ?? null,
            'status'             => 'pending',
            'details_shared'     => false,
        ]);

        $project->increment('offers_count');

        // ✅ إيميل لصاحب المشروع إنو في شركة مهتمة
        $this->notifyOwnerOfInterest($project, $company, $data['support_type']);

        return ['status' => 200, 'message' => 'Interest submitted — waiting for owner approval', 'data' => $interest];
    }

    // ✅ إيميل لصاحب المشروع لما شركة تعبر عن اهتمام
    private function notifyOwnerOfInterest(StartupProject $project, Company $company, string $supportType): void
    {
        $owner = $project->user;
        if (!$owner?->email) return;

        $supportLabel = match($supportType) {
            'funding'     => 'تمويل مالي 💰',
            'mentoring'   => 'إرشاد وتوجيه 🎯',
            'partnership' => 'شراكة استراتيجية 🤝',
            default       => $supportType
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
                        <p style='margin:0'>نوع الدعم المطلوب: <strong>{$supportLabel}</strong></p>
                    </div>
                    <p>يمكنك الموافقة أو الرفض من خلال لوحة التحكم.</p>
                    <p style='color:#6b7280;font-size:13px'>مع تحيات فريق المنصة</p>
                </div>");
        });
    }

    public function respondToInterest($user, int $interestId, string $action): array
    {
        $interest = StartupProjectInterest::with('project')->findOrFail($interestId);

        if ($interest->project->user_id !== $user->id) {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        if ($action === 'approve') {
            $interest->update(['status' => 'approved', 'details_shared' => true]);
            return ['status' => 200, 'message' => 'Interest approved — details shared with company', 'data' => $interest];
        }

        if ($action === 'reject') {
            $interest->update(['status' => 'rejected']);
            return ['status' => 200, 'message' => 'Interest rejected', 'data' => $interest];
        }

        return ['status' => 422, 'message' => 'Invalid action'];
    }

    public function suggestCompanies(int $projectId, $user): array
    {
        $project = StartupProject::findOrFail($projectId);

        if ($project->user_id !== $user->id && $user->role !== 'admin') {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        return ['status' => 200, 'data' => $this->findMatchingCompanies($project)];
    }

    public function listInterests($user, int $projectId): array
    {
        $project = StartupProject::findOrFail($projectId);

        if ($project->user_id !== $user->id && $user->role !== 'admin') {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        return [
            'status' => 200,
            'data'   => $project->interests()->with('company:id,company_name,logo,local_address')->latest()->get()
        ];
    }

    public function update($user, int $id, array $data): array
    {
        $project = StartupProject::findOrFail($id);

        if ($project->user_id !== $user->id && $user->role !== 'admin') {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        $project->update($data);
        return ['status' => 200, 'data' => $project->fresh()];
    }

    public function delete($user, int $id): array
    {
        $project = StartupProject::findOrFail($id);

        if ($project->user_id !== $user->id && $user->role !== 'admin') {
            return ['status' => 403, 'message' => 'Unauthorized'];
        }

        $project->delete();
        return ['status' => 200, 'message' => 'Deleted successfully'];
    }
}
