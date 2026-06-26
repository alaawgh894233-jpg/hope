<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 👤 مستخدمين عاديين
        User::firstOrCreate(['email' => 'user1@test.com'], [
            'name'              => 'أحمد محمد',
            'password'          => Hash::make('password123'),
            'role'              => 'user',
            'email_verified_at' => now(),
        ]);

        User::firstOrCreate(['email' => 'user2@test.com'], [
            'name'              => 'سارة علي',
            'password'          => Hash::make('password123'),
            'role'              => 'user',
            'email_verified_at' => now(),
        ]);

        User::firstOrCreate(['email' => 'user3@test.com'], [
            'name'              => 'خالد إبراهيم',
            'password'          => Hash::make('password123'),
            'role'              => 'user',
            'email_verified_at' => now(),
        ]);

        // 🏢 مستخدمين شركات
        User::firstOrCreate(['email' => 'company1@test.com'], [
            'name'              => 'مدير شركة التقنية',
            'password'          => Hash::make('password123'),
            'role'              => 'company',
            'email_verified_at' => now(),
        ]);

        User::firstOrCreate(['email' => 'company2@test.com'], [
            'name'              => 'مدير شركة الاستثمار',
            'password'          => Hash::make('password123'),
            'role'              => 'company',
            'email_verified_at' => now(),
        ]);
    }
}
