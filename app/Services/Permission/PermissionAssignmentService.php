<?php

namespace App\Services\Permission;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PermissionAssignmentService
{
    /**
     * Allow a permission for a user.
     */
    public function assignPermissionToUser(User $user, int $permissionId): UserPermission
    {
        return DB::transaction(function () use ($user, $permissionId) {

            return UserPermission::updateOrCreate(
                [
                    'user_id'       => $user->id,
                    'permission_id' => $permissionId,
                ],
                [
                    'is_allowed' => true,
                ]
            );
        });
    }

    /**
     * Explicitly deny a permission.
     */
    public function denyPermissionToUser(User $user, int $permissionId): UserPermission
    {
        return DB::transaction(function () use ($user, $permissionId) {

            return UserPermission::updateOrCreate(
                [
                    'user_id'       => $user->id,
                    'permission_id' => $permissionId,
                ],
                [
                    'is_allowed' => false,
                ]
            );
        });
    }

    /**
     * Remove individual permission.
     * User will fall back to Role Permission.
     */
    public function revokePermissionFromUser(User $user, int $permissionId): bool
    {
        return DB::transaction(function () use ($user, $permissionId) {

            return UserPermission::where('user_id', $user->id)
                ->where('permission_id', $permissionId)
                ->delete();
        });
    }

    /**
     * Replace all user permissions.
     *
     * Example:
     * [
     *     5 => true,
     *     7 => false,
     *     9 => true,
     * ]
     */
    public function syncUserPermissions(
        User $user,
        array $permissions,
        ?array $replaceablePermissionIds = null
    ): void
    {
        DB::transaction(function () use ($user, $permissions, $replaceablePermissionIds) {

            $existingPermissions = UserPermission::where('user_id', $user->id);

            if ($replaceablePermissionIds !== null) {
                $existingPermissions->whereIn('permission_id', $replaceablePermissionIds);
            }

            $existingPermissions->delete();

            foreach ($permissions as $permissionId => $allowed) {

                if (!is_bool($allowed)) {
                    throw new InvalidArgumentException(
                        "Permission value must be boolean."
                    );
                }

                UserPermission::create([
                    'user_id'       => $user->id,
                    'permission_id' => $permissionId,
                    'is_allowed'    => $allowed,
                ]);
            }
        });
    }
}
