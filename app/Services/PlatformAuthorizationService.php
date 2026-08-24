<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permission\PermissionModuleResolver;

class PlatformAuthorizationService
{
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
            return in_array($permission, self::SUPER_STAFF_ASSIGNABLE_PERMISSIONS, true);
        }

        $record = Permission::query()->where('name', $permission)->where('scope', Permission::SCOPE_PLATFORM)->first();
        if (! $record) return false;

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
