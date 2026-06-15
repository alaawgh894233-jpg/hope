<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@yourapp.com')],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make(env('ADMIN_PASSWORD', 'Admin@123!')),
                'role'              => 'admin',
                'email_verified_at' => now(),
            ]
        );

        AuditService::log($admin, 'admin_seeded', 'User', $admin->id);

        $this->command->info('✅ Admin created: ' . $admin->email);
    }
}
