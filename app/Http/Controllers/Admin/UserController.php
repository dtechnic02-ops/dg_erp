<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\UserPasswordResetLinkMail;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\PlatformMailService;
use App\Services\UserPasswordResetService;

class UserController extends Controller
{
    public function __construct(
        private UserPasswordResetService $passwordResets,
        private PlatformMailService $platformMail,
    ) {}
    // 🔥 USER LIST
    public function index(Request $request)
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_users_manage'), 403);

        $query = User::with('company');

        // 🔍 SEARCH
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // 🚦 STATUS FILTER
        if ($request->filled('status')) {
            $query->where('account_status', $request->status);
        }

        // 👤 ROLE FILTER
        if ($request->filled('role')) {
            $query->where('role_id', $request->role);
        }

        $users = $query->latest()->paginate(10);

        return view('admin.users', compact('users'));
    }

    public function show(User $user)
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_users_manage'), 403);

        $user->load(['company', 'role']);

        return view('admin.users_show', compact('user'));
    }

    // 🔥 DELETE USER
    public function delete($id)
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_users_manage'), 403);

        $user = User::findOrFail($id);

        // ❗ Prevent self delete
        if ($user->id == auth()->id()) {
            return back()->with('error', 'You cannot delete yourself');
        }

        $user->delete();

        return back()->with('success', 'User Deleted');
    }

    // 🔥 BLOCK USER
    public function block($id)
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_users_manage'), 403);

        $user = User::findOrFail($id);

        // ❗ Prevent self block
        if ($user->id == auth()->id()) {
            return back()->with('error', 'You cannot block yourself');
        }

        $user->account_status = 'blocked';
        $user->save();

        return back()->with('success', 'User Blocked');
    }

    // 🔥 UNBLOCK USER
    public function unblock($id)
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_users_manage'), 403);

        $user = User::findOrFail($id);

        $user->account_status = 'active';
        $user->save();

        return back()->with('success', 'User Activated');
    }

    public function requestPasswordReset(User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertResetTarget($user);

        abort_unless($user->account_status === 'active', 422, 'Only active users are eligible for password reset.');

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

    private function authorizeSuperAdmin(): void
    {
        abort_unless(app(\App\Services\PlatformAuthorizationService::class)->can(auth()->user(), 'platform_users_manage'), 403);
    }

    private function assertResetTarget(User $user): void
    {
        abort_if($user->id === auth()->id(), 422, 'You cannot reset your own password.');
    }

}
