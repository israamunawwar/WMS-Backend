<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * حسابات تجريبية للتطوير فقط، غيّروا كلمات المرور (أو احذفوها) قبل أي نشر حقيقي.
     */
    private const DEMO_PASSWORD = 'password123';

    public function run(): void
    {
        foreach (['super_admin', 'admin', 'trainer'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $accounts = [
            ['head@it.edu', 'رئيس قسم الـ IT', 'super_admin'],
            ['warehouse@it.edu', 'أمين المستودع', 'admin'],
            ['trainer@it.edu', 'مدرب تجريبي', 'trainer'],
        ];

        foreach ($accounts as [$email, $name, $role]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make(self::DEMO_PASSWORD), 'is_active' => true],
            );

            $user->syncRoles([$role]);
        }
    }
}
