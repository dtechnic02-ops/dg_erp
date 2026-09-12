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
        'auditor' => 'Auditor',
    ];

    private const OPERATIONAL_DOMAINS = [
        'sub_admin' => ['*'],
        'manager' => ['sales', 'purchase', 'inventory', 'delivery', 'hr', 'reports'],
        'hr' => ['staff_management', 'hr', 'payroll'],
        'accountant' => ['accounts', 'cash_accounts', 'account_transactions', 'income', 'expense', 'journal', 'contra', 'vat', 'reports'],
        'sales' => ['sales', 'customers'],
        'cashier' => ['sales', 'sales_payments', 'cash_accounts'],
        'receiver' => ['purchase', 'suppliers', 'inventory'],
        'delivery' => ['delivery'],
        'company_staff' => [],
        'auditor' => ['sales', 'sales_payments', 'customers', 'purchase', 'suppliers', 'inventory', 'accounts', 'cash_accounts', 'account_transactions', 'income', 'expense', 'journal', 'vat', 'reports', 'settings'],
    ];

    private const DOMAIN_PERMISSION_MODULES = [
        'staff_management' => ['module_users'],
        'sales' => ['module_sales'],
        'sales_payments' => ['module_sales_payment'],
        'customers' => ['module_customer'],
        'purchase' => ['module_purchase'],
        'suppliers' => ['module_supplier'],
        'inventory' => ['module_stock'],
        'delivery' => ['module_delivery'],
        'hr' => ['module_hr'],
        'payroll' => ['module_payroll'],
        'accounts' => ['module_accounts'],
        'cash_accounts' => ['module_accounts'],
        'account_transactions' => ['module_account_transaction'],
        'income' => ['module_income'],
        'expense' => ['module_expense'],
        'journal' => ['module_journal'],
        'contra' => ['module_contra'],
        'vat' => ['module_vat'],
        'reports' => ['module_reports'],
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
            Role::AUDITOR_ID => 'auditor',
            default => array_key_exists($user->job_role, self::JOB_ROLES)
                ? $user->job_role
                : 'company_staff',
        };
    }

    public function canSeeMenu(User $user, string $menu): bool
    {
        $domains = $this->operationalDomains($user);
        if (in_array('*', $domains, true)) {
            return true;
        }

        return in_array($menu, $domains, true);
    }

    public function operationalDomains(User $user): array
    {
        $role = $this->visibilityRole($user);

        if ($role === 'company_admin') {
            return ['*'];
        }

        return self::OPERATIONAL_DOMAINS[$role] ?? [];
    }

    public function assignablePermissionModules(User $user): array
    {
        $domains = $this->operationalDomains($user);
        if (in_array('*', $domains, true)) {
            return ['*'];
        }

        $modules = [];
        foreach ($domains as $domain) {
            $modules = array_merge($modules, self::DOMAIN_PERMISSION_MODULES[$domain] ?? []);
        }

        return array_values(array_unique($modules));
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
