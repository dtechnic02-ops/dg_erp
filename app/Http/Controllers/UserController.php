<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    // 🔐 HELPER (company admin bypass + staff permission)
    private function checkAccess($permission)
    {
        $user = auth()->user();

        // ❌ Super admin cannot access company panel
        if ($user->role_id == 1) {
            abort(403);
        }

        // ✅ Company admin → full access
        if ($user->role_id == 2) {
            return true;
        }

        // 🔒 Staff → permission required
        abort_unless($user->hasPermission($permission), 403);
    }

    // 👥 USERS LIST
    public function index()
    {
        $this->checkAccess('manage_users');

        $user = auth()->user();

        $users = User::where('company_id', $user->company_id)
                    ->where('role_id', 3)
                    ->latest()
                    ->get();

        return view('company.users.index', compact('users'));
    }

    // ➕ CREATE STAFF
    public function store(Request $request)
    {
        $this->checkAccess('manage_users');

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'job_role' => 'required|in:cashier,receiver,accountant,manager'
        ]);

        $user = auth()->user();
        $company = $user->company;

        if (!$company) {
            return back()->with('error', 'Company not found');
        }

        // 🔥 LIMIT CHECK
        $currentStaff = User::where('company_id', $user->company_id)
            ->where('role_id', 3)
            ->count();

        $limit = $company->selected_user_limit ?? 0;

        if ($limit > 0 && $currentStaff >= $limit) {
            return back()->with('error', 'Staff limit reached! Upgrade your plan.');
        }

        // ✅ CREATE
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $user->company_id,
            'role_id' => 3,
            'job_role' => $request->job_role,
            'account_status' => 'active',
        ]);

        return back()->with('success', 'Staff created successfully');
    }

    // ✏️ EDIT USER
    public function edit($id)
    {
        $this->checkAccess('manage_users');

        $user = User::where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        return view('company.users.edit', compact('user'));
    }

    // 🔄 UPDATE USER
    public function update(Request $request, $id)
    {
        $this->checkAccess('manage_users');

        $user = User::where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        $request->validate([
            'name' => 'required',
            'job_role' => 'required|in:cashier,receiver,accountant,manager'
        ]);

        $user->update([
            'name' => $request->name,
            'job_role' => $request->job_role,
        ]);

        return redirect()->route('company.users.index')->with('success', 'User updated');
    }

    // 🗑 DELETE USER
    public function destroy($id)
    {
        $this->checkAccess('delete_user');

        $auth = auth()->user();

        $user = User::where('id', $id)
            ->where('company_id', $auth->company_id)
            ->firstOrFail();

        if ($user->id == $auth->id) {
            return back()->with('error', 'Cannot delete yourself');
        }

        $user->delete();

        return back()->with('success', 'User deleted');
    }

    // 🚫 BLOCK USER
    public function block($id)
    {
        $this->checkAccess('block_user');

        $user = User::where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        $user->account_status = 'blocked';
        $user->save();

        return back()->with('success', 'User blocked');
    }

    // ✅ UNBLOCK USER
    public function unblock($id)
    {
        $this->checkAccess('block_user');

        $user = User::where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        $user->account_status = 'active';
        $user->save();

        return back()->with('success', 'User activated');
    }

    // 🔑 RESET PASSWORD
    public function resetPassword($id)
    {
        $this->checkAccess('reset_password');

        $user = User::where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        $newPassword = '123456';

        $user->password = Hash::make($newPassword);
        $user->save();

        return back()->with('success', 'Password reset to: ' . $newPassword);
    }

    // 🔐 PERMISSION PAGE
    public function permissionPage()
    {
        $this->checkAccess('manage_users');

        $permissions = \App\Models\Permission::all();

        $rolePermissions = \DB::table('permission_role')
            ->where('role_id', 3)
            ->pluck('permission_id')
            ->toArray();

        return view('company.permissions.index', compact('permissions', 'rolePermissions'));
    }

    // 🔐 UPDATE PERMISSION
    public function updateRolePermission(Request $request)
    {
        $this->checkAccess('manage_users');

        $roleId = 3;

        \DB::table('permission_role')->where('role_id', $roleId)->delete();

        if ($request->permissions) {
            foreach ($request->permissions as $permId) {
                \DB::table('permission_role')->insert([
                    'role_id' => $roleId,
                    'permission_id' => (int)$permId
                ]);
            }
        }

        return back()->with('success', 'Permissions updated');
    }
}