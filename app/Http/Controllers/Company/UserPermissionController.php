<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Services\JobRoleVisibilityService;
use App\Services\SubscriptionService;
use App\Services\Permission\PermissionAssignmentService;
use App\Services\Permission\PermissionModuleResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class UserPermissionController extends Controller
{
    protected PermissionAssignmentService $permissionService;
    protected SubscriptionService $subscriptionService;
    protected JobRoleVisibilityService $jobRoleVisibilityService;

    public function __construct(
        PermissionAssignmentService $permissionService,
        SubscriptionService $subscriptionService,
        JobRoleVisibilityService $jobRoleVisibilityService
    ) {
        $this->permissionService = $permissionService;
        $this->subscriptionService = $subscriptionService;
        $this->jobRoleVisibilityService = $jobRoleVisibilityService;
    }

    /**
     * Permission Management Screen
     */
  public function edit(User $user)
{
    $this->authorizeUser($user);

    $permissions = $this->availablePermissions($user);

    $overrides = $user->permissions()
        ->pluck('user_permissions.is_allowed', 'permissions.id')
        ->toArray();

    return view(
        'company.user_permissions.edit',
        compact(
            'user',
            'permissions',
            'overrides'
        )
    );
}

    /**
     * Save Permission Overrides
     */
    public function update(
        Request $request,
        User $user
    ) {
        $this->authorizeUser($user);

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => [
                Rule::in([
                    'allow',
                    'deny',
                    'default',
                ]),
            ],
        ]);

        $allowedPermissionIds = $this->availablePermissions($user)->modelKeys();
        $submittedPermissionIds = array_keys($validated['permissions'] ?? []);

        if (array_diff($submittedPermissionIds, array_map('strval', $allowedPermissionIds))) {
            abort(403, 'A permission is not available on the current subscription plan.');
        }

        $sync = [];

        foreach ($validated['permissions'] ?? [] as $permissionId => $state) {

            if ($state === 'default') {
                continue;
            }

            $sync[$permissionId] = (
                $state === 'allow'
            );
        }

        $this->permissionService
            ->syncUserPermissions(
                $user,
                $sync,
                $allowedPermissionIds
            );

        return redirect()
            ->back()
            ->with(
                'success',
                'User permissions updated successfully.'
            );
    }

    /**
     * Allow Single Permission
     */
    public function assign(
        User $user,
        Permission $permission
    ) {
        $this->authorizeUser($user);
        $this->authorizePermissionForSubscription($permission, $user);

        $this->permissionService
            ->assignPermissionToUser(
                $user,
                $permission->id
            );

        return back()->with(
            'success',
            'Permission allowed.'
        );
    }

    /**
     * Deny Single Permission
     */
    public function deny(
        User $user,
        Permission $permission
    ) {
        $this->authorizeUser($user);
        $this->authorizePermissionForSubscription($permission, $user);

        $this->permissionService
            ->denyPermissionToUser(
                $user,
                $permission->id
            );

        return back()->with(
            'success',
            'Permission denied.'
        );
    }

    /**
     * Remove Override
     */
    public function revoke(
        User $user,
        Permission $permission
    ) {
        $this->authorizeUser($user);
        $this->authorizePermissionForSubscription($permission, $user);

        $this->permissionService
            ->revokePermissionFromUser(
                $user,
                $permission->id
            );

        return back()->with(
            'success',
            'Permission restored to role default.'
        );
    }

    /**
     * Company Isolation
     */
    protected function authorizeUser(
        User $user
    ): void {

        abort_unless(auth()->user()?->hasPermission('manage_users', auth()->user()?->company_id), 403);

        if (
            $user->company_id !== auth()->user()->company_id
            || (int) $user->role_id !== \App\Models\Role::COMPANY_STAFF_ID
        ) {
            abort(403);
        }
    }

    protected function availablePermissions(User $user)
    {
        $company = auth()->user()->company;
        $assignableModules = $this->jobRoleVisibilityService
            ->assignablePermissionModules($user);
        $existingPermissionIds = $user->permissions()
            ->where('permissions.scope', Permission::SCOPE_COMPANY)
            ->pluck('permissions.id');

        $assignable = Permission::company()
            ->whereNotIn('name', [
                'view_company_profile', 'edit_company_profile', 'company_reset', 'database_reset',
                'dangerous_maintenance', 'system_maintenance', 'cache_clear', 'queue_restart',
                'log_management', 'maintenance_mode', 'system_utilities', 'company_delete',
                'approve_company', 'block_company', 'delete_company',
            ])
            ->orderBy('name')
            ->get()
            ->filter(fn (Permission $permission) => $this->subscriptionService
                ->canAccessPermission($company, $permission->name))
            ->filter(function (Permission $permission) use ($assignableModules) {
                if (in_array('*', $assignableModules, true)) {
                    return true;
                }

                $module = PermissionModuleResolver::companyModule($permission->name);
                return $module !== null && in_array($module, $assignableModules, true);
            });

        $existing = Permission::company()
            ->whereIn('id', $existingPermissionIds)
            ->get();

        return $assignable
            ->merge($existing)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    protected function authorizePermissionForSubscription(Permission $permission, User $user): void
    {
        abort_unless($permission->scope === Permission::SCOPE_COMPANY, 403);
        abort_unless(
            $this->availablePermissions($user)->contains('id', $permission->id),
            403,
            'This permission is not available for the staff member\'s Job Role.'
        );
    }
}
