<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;

class JobRoleVisibilityService
{
    public const JOB_ROLES = [
        'sub_admin' => 'Sub Admin',
        'manager' => 'Manager',
        'hr' => 'HR',
        'accountant' => 'Accountant',
        'sales' => 'Sales',
        'cashier' => 'Cashier',
        'receiver' => 'Receiver',
        'delivery' => 'Delivery',
        'company_staff' => 'Company Staff',
    ];

    public static function jobRoles(): array
    {
        return self::JOB_ROLES;
    }

    public function visibilityRole(User $user): string
    {
        return match ((int) $user->role_id) {
            Role::SUPER_ADMIN_ID => 'super_admin',
            Role::SUPER_STAFF_ID => 'super_staff',
            Role::COMPANY_ADMIN_ID => 'company_admin',
            default => array_key_exists($user->job_role, self::JOB_ROLES)
                ? $user->job_role
                : 'company_staff',
        };
    }

    public function canSeeMenu(User $user, string $menu): bool
    {
        $role = $this->visibilityRole($user);

        if (in_array($role, ['company_admin', 'sub_admin'], true)) {
            return true;
        }

        return in_array($menu, match ($role) {
            'manager' => ['sales', 'purchase', 'inventory', 'delivery', 'hr', 'reports'],
            'hr' => ['staff_management', 'hr'],
            'accountant' => ['accounts', 'cash_accounts', 'account_transactions', 'income', 'expense', 'journal', 'contra', 'vat', 'reports'],
            'sales' => ['sales', 'customers'],
            'cashier' => ['sales', 'sales_payments', 'cash_accounts'],
            'receiver' => ['purchase', 'suppliers', 'inventory'],
            'delivery' => ['delivery'],
            default => [],
        }, true);
    }

    public function canSeeDashboard(User $user, string $section): bool
    {
        return $this->canSeeMenu($user, $section);
    }

    public function canSeeMaintenance(User $user): bool
    {
        return (int) $user->role_id === Role::COMPANY_ADMIN_ID;
    }
}
