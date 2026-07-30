<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PlatformAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuperStaffController extends Controller
{
    public function index()
    {
        $this->authorizeSuperAdmin();

        $superStaff = User::query()
            ->where('role_id', Role::SUPER_STAFF_ID)
            ->whereNull('company_id')
            ->latest()
            ->paginate(15);

        return view('admin.super_staff.index', compact('superStaff'));
    }

    public function create()
    {
        $this->authorizeSuperAdmin();

        return view('admin.super_staff.create');
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => Role::SUPER_STAFF_ID,
            'company_id' => null,
            'job_role' => null,
            'account_status' => 'active',
        ]);

        return redirect()
            ->route('admin.super-staff.index')
            ->with('success', 'Super Staff created successfully.');
    }

    public function show(User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertSuperStaff($user);

        return view('admin.super_staff.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertSuperStaff($user);

        return view('admin.super_staff.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertSuperStaff($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);

        return redirect()
            ->route('admin.super-staff.show', $user)
            ->with('success', 'Super Staff updated successfully.');
    }

    public function block(User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertSuperStaff($user);

        $user->update(['account_status' => 'blocked']);

        return back()->with('success', 'Super Staff blocked successfully.');
    }

    public function unblock(User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertSuperStaff($user);

        $user->update(['account_status' => 'active']);

        return back()->with('success', 'Super Staff unblocked successfully.');
    }

    public function editPermissions(User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertSuperStaff($user);

        $permissionGroups = Permission::platform()
            ->whereIn('name', PlatformAuthorizationService::SUPER_STAFF_ASSIGNABLE_PERMISSIONS)
            ->orderBy('name')
            ->get()
            ->groupBy(function (Permission $permission): string {
                $parts = explode('_', $permission->name);
                $module = count($parts) > 1
                    ? implode(' ', array_slice($parts, 1))
                    : $permission->name;

                return ucwords(str_replace('_', ' ', $module));
            });

        $assignedPermissionIds = $user->permissions()
            ->where('permissions.scope', Permission::SCOPE_PLATFORM)
            ->wherePivot('is_allowed', true)
            ->pluck('permissions.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view(
            'admin.super_staff.permissions',
            compact('user', 'permissionGroups', 'assignedPermissionIds')
        );
    }

    public function updatePermissions(Request $request, User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertSuperStaff($user);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ]);

        $selectedPermissionIds = array_map('intval', $data['permissions'] ?? []);
        $platformPermissionIds = Permission::platform()
            ->whereIn('name', PlatformAuthorizationService::SUPER_STAFF_ASSIGNABLE_PERMISSIONS)
            ->whereIn('id', $selectedPermissionIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($platformPermissionIds) !== count($selectedPermissionIds)) {
            throw ValidationException::withMessages([
                'permissions' => 'Only platform-scoped permissions may be assigned to Super Staff.',
            ]);
        }

        DB::transaction(function () use ($user, $platformPermissionIds): void {
            $existingAllowedPlatformIds = $user->permissions()
                ->where('permissions.scope', Permission::SCOPE_PLATFORM)
                ->whereIn('permissions.name', PlatformAuthorizationService::SUPER_STAFF_ASSIGNABLE_PERMISSIONS)
                ->wherePivot('is_allowed', true)
                ->pluck('permissions.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $removedPermissionIds = array_diff($existingAllowedPlatformIds, $platformPermissionIds);

            if ($removedPermissionIds !== []) {
                $user->permissions()->detach($removedPermissionIds);
            }

            if ($platformPermissionIds !== []) {
                $user->permissions()->syncWithoutDetaching(
                    collect($platformPermissionIds)
                        ->mapWithKeys(fn (int $id) => [$id => ['is_allowed' => true]])
                        ->all()
                );
            }
        });

        return redirect()
            ->route('admin.super-staff.permissions.edit', $user)
            ->with('success', 'Platform permissions updated successfully.');
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(
            (int) auth()->user()?->role_id === Role::SUPER_ADMIN_ID,
            403
        );
    }

    private function assertSuperStaff(User $user): void
    {
        abort_unless(
            (int) $user->role_id === Role::SUPER_STAFF_ID && $user->company_id === null,
            404
        );
    }
}
