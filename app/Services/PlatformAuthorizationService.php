<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permission\PermissionModuleResolver;

class PlatformAuthorizationService
{
    public const COMPLIANCE_MANAGE = 'platform_compliance_manage';
    public const REGISTRATIONS_CREATE = 'platform_registrations_create';

    private const SUPER_ADMIN_ONLY_PERMISSIONS = [
        self::COMPLIANCE_MANAGE,
    ];

    public const SUPER_STAFF_ASSIGNABLE_PERMISSIONS = [
        'platform_module_dashboard',
        'platform_module_companies',
        'platform_module_registrations',
        'platform_module_subscriptions',
        'platform_module_subscription_payments',
        'platform_module_subscription_reports',
        'platform_module_settings',
        'platform_module_plans',
        'platform_module_super_staff',
        'platform_module_users',
        'platform_dashboard_view',
        'platform_companies_view',
        'platform_companies_block',
        'platform_companies_unblock',
        'platform_companies_edit',
        'platform_companies_delete',
        'platform_registrations_view',
        self::REGISTRATIONS_CREATE,
        'platform_registrations_approve',
        'platform_registrations_reject',
        'platform_subscriptions_view',
        'platform_subscriptions_manage',
        'platform_subscription_payments_view',
        'platform_subscription_payments_invoice_view',
        'platform_subscription_reports_view',
        'platform_settings_manage',
        'platform_plans_manage',
        'platform_companies_reset_password',
        'platform_super_staff_manage',
        'platform_users_manage',
    ];

    public function can(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->company_id !== null) return false;

        if ((int) $user->role_id === Role::SUPER_ADMIN_ID) {
            return in_array($permission, self::SUPER_STAFF_ASSIGNABLE_PERMISSIONS, true)
                || in_array($permission, self::SUPER_ADMIN_ONLY_PERMISSIONS, true);
        }

        if ((int) $user->role_id === Role::COUNTRY_ADMIN_ID) {
            return $user->country_id !== null && in_array($permission, [
                'platform_module_dashboard', 'platform_module_companies', 'platform_module_registrations',
                'platform_dashboard_view', 'platform_companies_view', 'platform_companies_block',
                'platform_companies_unblock', 'platform_companies_edit', 'platform_companies_reset_password',
                'platform_registrations_view', self::REGISTRATIONS_CREATE,
                'platform_registrations_approve', 'platform_registrations_reject', self::COMPLIANCE_MANAGE,
            ], true);
        }

        $record = Permission::query()->where('name', $permission)->where('scope', Permission::SCOPE_PLATFORM)->first();
        if (! $record) return false;

        if ((int) $user->role_id === Role::SUPER_STAFF_ID && in_array($permission, [
            'platform_registrations_approve', 'platform_registrations_reject', self::COMPLIANCE_MANAGE,
        ], true)) return false;

        if (
            (int) $user->role_id !== Role::SUPER_STAFF_ID
            || ! in_array($permission, self::SUPER_STAFF_ASSIGNABLE_PERMISSIONS, true)
        ) {
            return false;
        }

        $module = PermissionModuleResolver::platformModule($permission);
        if (! $module) return false;
        if ($module !== $permission && ! $this->assigned($user, $module)) return false;
        return $this->assigned($user, $permission);
    }

    public function canManageCompanyCompliance(?User $user, Company $company): bool
    {
        if (! $this->can($user, self::COMPLIANCE_MANAGE)) {
            return false;
        }

        // Current holders are global Super Admins. Future country-scoped administrators
        // can be constrained against the route-resolved company at this single seam.
        return app(PlatformCountryScopeService::class)->permitsCompany($user, $company);
    }

    private function assigned(User $user, string $permission): bool
    {
        return $user->permissions()
            ->where('permissions.name', $permission)
            ->where('permissions.scope', Permission::SCOPE_PLATFORM)
            ->wherePivot('is_allowed', true)
            ->exists();
    }

    public function canAny(?User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function approvedPermissions(): array
    {
        return self::SUPER_STAFF_ASSIGNABLE_PERMISSIONS;
    }
}
