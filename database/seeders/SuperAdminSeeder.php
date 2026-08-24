<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $name = trim((string) config('dg-erp.bootstrap_admin.name'));
        $email = trim((string) config('dg-erp.bootstrap_admin.email'));
        $password = (string) config('dg-erp.bootstrap_admin.password');

        if ($name === '' && $email === '' && $password === '') {
            if (app()->environment('production')) {
                throw new RuntimeException(
                    'Production bootstrap admin credentials are missing. Set DG_ERP_ADMIN_NAME, DG_ERP_ADMIN_EMAIL, and DG_ERP_ADMIN_PASSWORD before seeding.'
                );
            }

            return;
        }

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
            throw new RuntimeException(
                'Bootstrap admin credentials are invalid. A name, valid email, and password of at least 12 characters are required.'
            );
        }

        $role = Role::query()->whereKey(Role::SUPER_ADMIN_ID)->where('name', 'super_admin')->first();

        if (! $role) {
            throw new RuntimeException('The canonical super_admin role is missing. Run RoleSeeder before SuperAdminSeeder.');
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing) {
            if ((int) $existing->role_id !== Role::SUPER_ADMIN_ID || $existing->company_id !== null) {
                throw new RuntimeException('The bootstrap admin email already belongs to a non-platform-admin user.');
            }

            return;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $role->id,
            'company_id' => null,
            'account_status' => 'active',
        ]);
    }
}
