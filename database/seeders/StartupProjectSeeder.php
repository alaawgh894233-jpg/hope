<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\StartupProject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StartupProjectSeeder extends Seeder
{
    public function run(): void
    {
        // ══════════════════════════════════════════════════
        //  1️⃣ صاحب المشروع
        // ══════════════════════════════════════════════════
        $owner = User::create([
            'name'     => 'أحمد — صاحب المشروع',
            'email'    => 'owner@test.com',
            'password' => Hash::make('password'),
            'role'     => 'user',
        ]);

        // ══════════════════════════════════════════════════
        //  2️⃣ الشركات + مستخدميهن
        // ══════════════════════════════════════════════════

        // ✅ شركة A — تمويل + نفس القطاع + نفس الموقع → أعلى أولوية
        $userA = User::create([
            'name'     => 'شركة رأس المال',
            'email'    => 'company_a@test.com',
            'password' => Hash::make('password'),
            'role'     => 'company',
        ]);
        $companyA = Company::create([
            'user_id'       => $userA->id,
            'company_name'  => 'شركة رأس المال للاستثمار',
            'category'      => 'tech',
            'local_address' => 'الرياض — حي العليا',
            'support_offers'=> json_encode(['funding', 'partnership']),
            'status'        => 'approved',
        ]);

        // ✅ شركة B — إرشاد + نفس القطاع → أولوية ثانية
        $userB = User::create([
            'name'     => 'شركة المسرّعات',
            'email'    => 'company_b@test.com',
            'password' => Hash::make('password'),
            'role'     => 'company',
        ]);
        $companyB = Company::create([
            'user_id'       => $userB->id,
            'company_name'  => 'مسرّعة التقنية',
            'category'      => 'tech',
            'local_address' => 'جدة — حي الروضة',
            'support_offers'=> json_encode(['mentoring']),
            'status'        => 'approved',
        ]);

        // ✅ شركة C — تمويل + موقع مختلف + قطاع مختلف → أولوية ثالثة
        $userC = User::create([
            'name'     => 'صندوق التمويل',
            'email'    => 'company_c@test.com',
            'password' => Hash::make('password'),
            'role'     => 'company',
        ]);
        $companyC = Company::create([
            'user_id'       => $userC->id,
            'company_name'  => 'صندوق ريادة للتمويل',
            'category'      => 'finance',
            'local_address' => 'الدمام — حي الشاطئ',
            'support_offers'=> json_encode(['funding']),
            'status'        => 'approved',
        ]);

        // ❌ شركة D — partnership فقط → ما راح تظهر (المشروع ما طلبها)
        $userD = User::create([
            'name'     => 'شركة الشراكات',
            'email'    => 'company_d@test.com',
            'password' => Hash::make('password'),
            'role'     => 'company',
        ]);
        $companyD = Company::create([
            'user_id'       => $userD->id,
            'company_name'  => 'شركة الشراكات الاستراتيجية',
            'category'      => 'tech',
            'local_address' => 'الرياض — حي النخيل',
            'support_offers'=> json_encode(['partnership']),
            'status'        => 'approved',
        ]);

        // ❌ شركة E — غير معتمدة → ما راح تظهر
        $userE = User::create([
            'name'     => 'شركة غير معتمدة',
            'email'    => 'company_e@test.com',
            'password' => Hash::make('password'),
            'role'     => 'company',
        ]);
        $companyE = Company::create([
            'user_id'       => $userE->id,
            'company_name'  => 'شركة غير معتمدة',
            'category'      => 'tech',
            'local_address' => 'الرياض',
            'support_offers'=> json_encode(['funding', 'mentoring']),
            'status'        => 'pending', // ❌ مش approved
        ]);

        // ══════════════════════════════════════════════════
        //  3️⃣ المشروع
        // ══════════════════════════════════════════════════
        $project = StartupProject::create([
            'user_id'       => $owner->id,
            'company_id'    => null,
            'title'         => 'منصة توظيف ذكية',
            'description'   => 'منصة تستخدم الذكاء الاصطناعي لمطابقة الخريجين مع فرص العمل المناسبة بناءً على مهاراتهم',
            'summary'       => 'AI لربط الخريجين بأصحاب العمل',
            'category'      => 'tech',
            'stage'         => 'idea',
            'support_types' => ['funding', 'mentoring'],
            'funding_goal'  => 50000,
            'location'      => 'الرياض',
            'website_url'   => 'https://example.com',
            'status'        => 'draft',
        ]);

        // ══════════════════════════════════════════════════
        //  OUTPUT
        // ══════════════════════════════════════════════════
        $this->command->info('');
        $this->command->info('✅ Seeder تم بنجاح!');
        $this->command->info('══════════════════════════════════════');
        $this->command->info('👤 صاحب المشروع:');
        $this->command->info("   Email    : owner@test.com");
        $this->command->info("   Password : password");
        $this->command->info("   Project  : ID = {$project->id}");
        $this->command->info('');
        $this->command->info('🏢 الشركات:');
        $this->command->table(
            ['ID', 'الشركة', 'support_offers', 'القطاع', 'الموقع', 'status', 'متوقع يظهر؟'],
            [
                [$companyA->id, 'شركة رأس المال', 'funding, partnership', 'tech', 'الرياض', 'approved', '✅ نعم — أولوية 1'],
                [$companyB->id, 'مسرّعة التقنية', 'mentoring',           'tech', 'جدة',    'approved', '✅ نعم — أولوية 2'],
                [$companyC->id, 'صندوق ريادة',    'funding',             'finance','الدمام','approved', '✅ نعم — أولوية 3'],
                [$companyD->id, 'شركة الشراكات',  'partnership',         'tech', 'الرياض', 'approved', '❌ لا — ما عندها funding/mentoring'],
                [$companyE->id, 'غير معتمدة',     'funding, mentoring',  'tech', 'الرياض', 'pending',  '❌ لا — غير معتمدة'],
            ]
        );
        $this->command->info('');
        $this->command->info('📋 بيانات الاختبار:');
        $this->command->info("   POST /api/startup-projects/{$project->id}/invite");
        $this->command->info("   body: { \"company_ids\": [{$companyA->id}, {$companyB->id}, {$companyC->id}] }");
        $this->command->info('');
        $this->command->info('   POST /api/startup-projects/{$project->id}/interest');
        $this->command->info("   Token: company_a@test.com | body: { \"support_type\": \"funding\" }");
        $this->command->info('══════════════════════════════════════');
    }
}
