<?php

namespace App\Services\Permission;

final class PermissionModuleResolver
{
    private const COMPANY_ALIASES = [
        'users' => 'module_users', 'user' => 'module_users',
        'income_categories' => 'module_income', 'income' => 'module_income',
        'expense_categories' => 'module_expense', 'expense' => 'module_expense',
        'loan_account' => 'module_loan', 'loan_payment' => 'module_loan',
        'loan_saving_ledger' => 'module_loan', 'loan_saving_withdraw' => 'module_loan',
        'employee' => 'module_hr', 'salary' => 'module_payroll',
        'delivery' => 'module_delivery', 'crm' => 'module_crm',
        'company_profile' => 'module_company_profile',
        'system_maintenance' => 'module_maintenance',
        'opening-balance' => 'module_opening_balance', 'journal' => 'module_journal',
    ];

    private const PLATFORM_ALIASES = [
        'dashboard' => 'platform_module_dashboard',
        'companies' => 'platform_module_companies',
        'registrations' => 'platform_module_registrations',
        'subscriptions' => 'platform_module_subscriptions',
        'subscription_payments' => 'platform_module_subscription_payments',
        'subscription_reports' => 'platform_module_subscription_reports',
        'settings' => 'platform_module_settings',
        'plans' => 'platform_module_plans',
        'super_staff' => 'platform_module_super_staff',
        'users' => 'platform_module_users',
    ];

    public static function companyModule(string $permission): ?string
    {
        if (str_starts_with($permission, 'module_')) return $permission;
        $subject = self::subject($permission);
        foreach (self::COMPANY_ALIASES as $needle => $module) {
            if ($subject === $needle || str_starts_with($subject, $needle . '_')) return $module;
        }
        return $subject === '' ? null : 'module_' . str_replace('-', '_', $subject);
    }

    public static function platformModule(string $permission): ?string
    {
        if (str_starts_with($permission, 'platform_module_')) return $permission;
        $subject = preg_replace('/^platform_/', '', self::subject($permission)) ?? '';
        foreach (self::PLATFORM_ALIASES as $needle => $module) {
            if ($subject === $needle || str_starts_with($subject, $needle . '_')) return $module;
        }
        return $subject === '' ? null : 'platform_module_' . $subject;
    }

    private static function subject(string $permission): string
    {
        if (str_contains($permission, '.')) return explode('.', $permission, 2)[0];
        $value = preg_replace('/^(view|create|edit|delete|manage|block|unblock|reset|cancel|print|process|approve|reject|post|reverse|lock|unlock|export|archive|close|report)_/', '', $permission) ?? '';
        $value = preg_replace('/_(view|create|edit|delete|manage|block|unblock|reset|cancel|print|process|approve|reject|post|reverse|lock|unlock|export|archive|close|report)$/', '', $value) ?? '';
        return $value;
    }
}
