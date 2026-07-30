<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\Permission\PermissionAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class UserPermissionController extends Controller
{
    protected PermissionAssignmentService $permissionService;
    protected SubscriptionService $subscriptionService;

    public function __construct(
        PermissionAssignmentService $permissionService,
        SubscriptionService $subscriptionService
    ) {
        $this->permissionService = $permissionService;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Permission Management Screen
     */
  public function edit(User $user)
{
    $this->authorizeUser($user);

    $permissions = $this->availablePermissions();

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

        $allowedPermissionIds = $this->availablePermissions()->modelKeys();
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
        $this->authorizePermissionForSubscription($permission);

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
        $this->authorizePermissionForSubscription($permission);

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
        $this->authorizePermissionForSubscription($permission);

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

        if (
            $user->company_id !== auth()->user()->company_id
        ) {
            abort(403);
        }
    }

    protected function availablePermissions()
    {
        $company = auth()->user()->company;

        return Permission::orderBy('name')
            ->get()
            ->filter(fn (Permission $permission) => $this->subscriptionService
                ->canAccessPermission($company, $permission->name))
            ->values();
    }

    protected function authorizePermissionForSubscription(Permission $permission): void
    {
        if (! $this->subscriptionService->canAccessPermission(auth()->user()->company, $permission->name)) {
            abort(403, 'This permission is not available on the current subscription plan.');
        }
    }
}
