<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Permission\PermissionModuleResolver;

class CompanyAuthorizationService
{
    private const OWNER_RESERVED = [
        'view_company_profile', 'edit_company_profile', 'company_reset', 'database_reset',
        'dangerous_maintenance', 'system_maintenance', 'cache_clear', 'queue_restart',
        'log_management', 'maintenance_mode', 'system_utilities', 'company_delete',
        'approve_company', 'block_company', 'delete_company',
        'reset_password',
    ];

    public function can(?User $user, string $permission, ?int $companyId = null): bool
    {
        if (! $user || ! $user->company_id || ($companyId !== null && (int) $user->company_id !== $companyId)) return false;
        $record = Permission::query()->where('name', $permission)->where('scope', Permission::SCOPE_COMPANY)->first();
        if (! $record) return false;

        if ((int) $user->role_id === Role::COMPANY_ADMIN_ID) {
            if (str_starts_with($permission, 'module_')) return true;

            $module = PermissionModuleResolver::companyModule($permission);
            if (! $module || $module === $permission) return false;

            return Permission::query()
                ->where('name', $module)
                ->where('scope', Permission::SCOPE_COMPANY)
                ->exists();
        }

        if ((int) $user->role_id === Role::AUDITOR_ID) {
            $module = PermissionModuleResolver::companyModule($permission);
            if (! $module) return false;

            return $user->role()->whereHas('permissions', fn ($query) => $query
                    ->where('permissions.name', $permission)
                    ->where('permissions.scope', Permission::SCOPE_COMPANY))
                ->exists()
                && ($module === $permission || $user->role()->whereHas('permissions', fn ($query) => $query
                    ->where('permissions.name', $module)
                    ->where('permissions.scope', Permission::SCOPE_COMPANY))
                    ->exists());
        }

        if ((int) $user->role_id !== Role::COMPANY_STAFF_ID || in_array($permission, self::OWNER_RESERVED, true)) return false;

        $module = PermissionModuleResolver::companyModule($permission);
        if (! $module) return false;
        if ($module === $permission) return $this->assigned($user, $permission);

        return $this->assigned($user, $module) && $this->assigned($user, $permission);
    }

    private function assigned(User $user, string $permission): bool
    {
        return $user->permissions()->where('permissions.name', $permission)
            ->where('permissions.scope', Permission::SCOPE_COMPANY)
            ->wherePivot('is_allowed', true)->exists();
    }
}
