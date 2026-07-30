<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class PlatformAuthorizationService
{
    public const SUPER_STAFF_ASSIGNABLE_PERMISSIONS = [
        'platform_companies_view',
        'platform_companies_block',
        'platform_companies_unblock',
        'platform_registrations_view',
        'platform_registrations_approve',
        'platform_registrations_reject',
        'platform_subscriptions_view',
        'platform_subscription_payments_view',
        'platform_subscription_payments_invoice_view',
        'platform_subscription_reports_view',
    ];

    public function can(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        if ((int) $user->role_id === Role::SUPER_ADMIN_ID) {
            return true;
        }

        if (
            (int) $user->role_id !== Role::SUPER_STAFF_ID
            || $user->company_id !== null
            || ! in_array($permission, self::SUPER_STAFF_ASSIGNABLE_PERMISSIONS, true)
        ) {
            return false;
        }

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
