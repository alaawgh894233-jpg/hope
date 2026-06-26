<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $company1User = User::where('email', 'company1@test.com')->first();
        $company2User = User::where('email', 'company2@test.com')->first();

        Company::firstOrCreate(['user_id' => $company1User->id], [
            'company_name'   => 'شركة التقنية المتقدمة',
            'description'    => 'شركة متخصصة في تطوير البرمجيات وحلول الذكاء الاصطناعي',
            'website_url'    => 'https://techadvanced.com',
            'local_address'  => 'دمشق، سوريا',
            'phone'          => '+963111222333',
            'category'       => 'tech',
            'support_offers' => ['mentoring', 'partnership'],
            'status'         => 'approved',
        ]);

        Company::firstOrCreate(['user_id' => $company2User->id], [
            'company_name'   => 'شركة الاستثمار والتمويل',
            'description'    => 'شركة متخصصة في تمويل المشاريع الناشئة والاستثمار',
            'website_url'    => 'https://investfund.com',
            'local_address'  => 'دمشق، سوريا',
            'phone'          => '+963444555666',
             'category'       => 'startup',
            'support_offers' => ['funding', 'mentoring'],
            'status'         => 'approved',
        ]);
    }
}
