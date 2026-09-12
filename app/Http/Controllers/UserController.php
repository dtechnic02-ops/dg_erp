<?php

namespace App\Http\Controllers;

use App\Mail\UserPasswordResetLinkMail;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\StaffUserService;
use App\Services\SubscriptionService;
use App\Services\PlatformMailService;
use App\Services\UserPasswordResetService;
use App\Services\JobRoleVisibilityService;
use App\Services\Permission\PermissionAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\UserSessionRevocationService;

class UserController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private StaffUserService $staffUserService,
        private PermissionAssignmentService $permissionAssignmentService,
        private UserPasswordResetService $passwordResets,
        private PlatformMailService $platformMail,
    ) {
    }

    public function index(Request $request)
    {
        $company = auth()->user()->company;

        abort_unless($company, 403, 'Company not found.');

        $users = $this->staffUserService->paginateStaff($request, (int) $company->id);
        $staffCount = $this->staffUserService->staffQuery((int) $company->id)->count();
        $staffLimit = $this->subscriptionService->getEffectiveStaffLimit($company);

        return view('company.users.index', compact('users', 'staffCount', 'staffLimit', 'company'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'job_role' => ['required', Rule::in(array_keys(JobRoleVisibilityService::jobRoles()))],
        ]);

        $authUser = auth()->user();
        $company = $authUser->company;

        if (! $company) {
            return back()->with('error', 'Company not found');
        }

        if (! $this->subscriptionService->canCreateStaff($company)) {
            return back()->with('error', 'Staff limit reached. Upgrade your plan.');
        }

        $roleId = $request->job_role === 'auditor' ? Role::AUDITOR_ID : Role::COMPANY_STAFF_ID;

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'company_id' => $company->id,
            'role_id' => $roleId,
            'job_role' => $request->job_role,
            'account_status' => 'active',
        ]);

        return back()->with('success', 'Staff member created successfully.');
    }

    public function edit(int $id)
    {
        $user = $this->staffUserService->findStaffForCompany($id, (int) auth()->user()->company_id);

        return view('company.users.edit', compact('user'));
    }

    public function update(Request $request, int $id)
    {
        $user = $this->staffUserService->findStaffForCompany($id, (int) auth()->user()->company_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'job_role' => ['required', Rule::in(array_keys(JobRoleVisibilityService::jobRoles()))],
            'confirm_job_role_change' => ['nullable', 'boolean'],
        ]);

        $requestedJobRole = (string) $request->job_role;
        $jobRoleChanged = $user->job_role !== $requestedJobRole;

        if ($jobRoleChanged && ! $request->boolean('confirm_job_role_change')) {
            throw ValidationException::withMessages([
                'job_role' => 'Confirm the Job Role change. Existing company permissions will be reset.',
            ]);
        }

        $jobRoleChanged = DB::transaction(function () use ($request, $id, $requestedJobRole): bool {
            $lockedUser = $this->staffUserService
                ->staffQuery((int) auth()->user()->company_id)
                ->lockForUpdate()
                ->findOrFail($id);

            $changed = $lockedUser->job_role !== $requestedJobRole;

            if ($changed && ! $request->boolean('confirm_job_role_change')) {
                throw ValidationException::withMessages([
                    'job_role' => 'Confirm the Job Role change. Existing company permissions will be reset.',
                ]);
            }

            $lockedUser->update([
                'name' => $request->name,
                'job_role' => $requestedJobRole,
                'role_id' => $requestedJobRole === 'auditor' ? Role::AUDITOR_ID : Role::COMPANY_STAFF_ID,
            ]);

            if ($changed) {
                $this->permissionAssignmentService->resetCompanyPermissions(
                    $lockedUser,
                    (int) auth()->user()->company_id
                );
            }

            return $changed;
        });

        if ($jobRoleChanged) {
            return redirect()
                ->route('company.staff-permissions.edit', $user->id)
                ->with('success', 'Job Role changed and existing company permissions reset. Assign permissions for the new Job Role.');
        }

        return redirect()
            ->route('company.users.index')
            ->with('success', 'Staff member updated successfully.');
    }

    public function destroy(int $id)
    {
        $auth = auth()->user();
        $user = $this->staffUserService->findStaffForCompany($id, (int) $auth->company_id);

        if ($user->id === $auth->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'Staff member deleted successfully.');
    }

    public function block(int $id)
    {
        $auth = auth()->user();
        $user = $this->staffUserService->findStaffForCompany($id, (int) $auth->company_id);

        if ($user->id === $auth->id) {
            return back()->with('error', 'You cannot block your own account.');
        }

        $user->update(['account_status' => 'blocked']);
        app(UserSessionRevocationService::class)->revoke($user);

        return back()->with('success', 'Staff member blocked successfully.');
    }

    public function unblock(int $id)
    {
        $user = $this->staffUserService->findStaffForCompany($id, (int) auth()->user()->company_id);
        $user->update(['account_status' => 'active']);

        return back()->with('success', 'Staff member activated successfully.');
    }

    public function resetPassword(int $id)
    {
        $user = $this->staffUserService->findStaffForCompany($id, (int) auth()->user()->company_id);

        abort_unless($user->account_status === 'active', 422, 'Only active staff members are eligible for password reset.');

        [$resetRequest, $token] = $this->passwordResets->initiate($user, auth()->user());

        try {
            $this->platformMail->send($user->email, new UserPasswordResetLinkMail(
                $user->name,
                auth()->user()->name,
                route('password-reset.password.show', ['token' => $token]),
                UserPasswordResetService::LINK_TTL_MINUTES,
            ));
        } catch (\Throwable) {
            $this->passwordResets->invalidate($resetRequest);

            return back()->with('error', 'Unable to send password reset instructions. No password was changed.');
        }

        return back()->with('success', 'Password reset instructions have been sent to the user’s registered email.');
    }

    public function permissionPage()
    {
        $company = auth()->user()->company;

        abort_unless($company, 403, 'Company not found.');

        $permissions = Permission::company()->orderBy('name')->get();
        $assignedPermissionIds = $company->staffPermissions()->pluck('permissions.id')->all();

        return view('company.permissions.index', [
            'permissions' => $permissions,
            'assignedPermissionIds' => $assignedPermissionIds,
        ]);
    }

    public function updateRolePermission(Request $request)
    {
        $company = auth()->user()->company;

        abort_unless($company, 403, 'Company not found.');

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $permissionIds = Permission::company()
            ->whereIn('id', $validated['permissions'] ?? [])
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($company, $permissionIds) {
            $company->staffPermissions()->sync($permissionIds);
        });

        return back()->with('success', 'Company staff permissions updated successfully.');
    }
}
