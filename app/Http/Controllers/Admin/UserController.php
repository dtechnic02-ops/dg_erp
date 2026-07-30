<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteAdminUserPasswordResetRequest;
use App\Http\Requests\VerifyAdminUserPasswordResetOtpRequest;
use App\Mail\AdminUserPasswordResetCompletedMail;
use App\Mail\AdminUserPasswordResetOtpMail;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\AdminUserPasswordResetOtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    private const PASSWORD_RESET_SESSION_KEY = 'admin_user_password_reset';

    public function __construct(private AdminUserPasswordResetOtpService $resetOtp)
    {
    }
    // 🔥 USER LIST
    public function index(Request $request)
    {
        abort_unless(auth()->check() && (int) auth()->user()->role_id === Role::SUPER_ADMIN_ID, 403);

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
        abort_unless(auth()->check() && (int) auth()->user()->role_id === Role::SUPER_ADMIN_ID, 403);

        $user->load(['company', 'role']);

        return view('admin.users_show', compact('user'));
    }

    // 🔥 DELETE USER
    public function delete($id)
    {
        abort_unless((int) auth()->user()->role_id === Role::SUPER_ADMIN_ID, 403);

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
        abort_unless((int) auth()->user()->role_id === Role::SUPER_ADMIN_ID, 403);

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
        abort_unless((int) auth()->user()->role_id === Role::SUPER_ADMIN_ID, 403);

        $user = User::findOrFail($id);

        $user->account_status = 'active';
        $user->save();

        return back()->with('success', 'User Activated');
    }

    public function requestPasswordReset(User $user)
    {
        $this->authorizeSuperAdmin();
        $this->assertResetTarget($user);

        [$requestId, $otp] = $this->resetOtp->issue(auth()->id(), $user->id);

        try {
            Mail::to(auth()->user()->email)->send(new AdminUserPasswordResetOtpMail($otp, $user->name));
        } catch (\Throwable) {
            $this->resetOtp->invalidate($requestId, auth()->id(), $user->id);

            return back()->with('error', 'Unable to send the password reset OTP. No password was changed.');
        }

        session()->put(self::PASSWORD_RESET_SESSION_KEY, [
            'request_id' => $requestId,
            'target_user_id' => $user->id,
            'admin_user_id' => auth()->id(),
            'verified' => false,
        ]);

        return redirect()
            ->route('admin.user.reset.verify.form', $user)
            ->with('success', 'A verification code was sent to your registered email address.');
    }

    public function showPasswordResetVerification(User $user)
    {
        $this->resetContext($user);

        return view('admin.user_password_reset_verify', compact('user'));
    }

    public function verifyPasswordResetOtp(VerifyAdminUserPasswordResetOtpRequest $request, User $user)
    {
        $context = $this->resetContext($user);
        $result = $this->resetOtp->verify(
            $context['request_id'],
            (int) $context['admin_user_id'],
            (int) $context['target_user_id'],
            $request->validated('otp')
        );

        if ($result !== 'verified') {
            if (in_array($result, ['expired', 'locked'], true)) {
                $this->forgetResetState();
            }

            $message = match ($result) {
                'expired' => 'The OTP has expired. Request a new password reset OTP.',
                'locked' => 'Maximum OTP attempts exceeded. Request a new password reset OTP.',
                'used' => 'This OTP has already been used. Request a new password reset OTP.',
                default => 'The OTP is invalid. Please try again.',
            };

            return back()->withErrors(['otp' => $message]);
        }

        session()->put(self::PASSWORD_RESET_SESSION_KEY.'.verified', true);

        return redirect()->route('admin.user.reset.password.form', $user);
    }

    public function showPasswordResetForm(User $user)
    {
        $context = $this->resetContext($user);

        if (! ($context['verified'] ?? false) || ! $this->resetOtp->isVerified($context['request_id'], auth()->id(), $user->id)) {
            $this->forgetResetState();

            return redirect()->route('admin.users')->with('error', 'Password reset OTP verification is required.');
        }

        return view('admin.user_password_reset_password', compact('user'));
    }

    public function completePasswordReset(CompleteAdminUserPasswordResetRequest $request, User $user)
    {
        $context = $this->resetContext($user);

        if (! ($context['verified'] ?? false) || ! $this->resetOtp->isVerified($context['request_id'], auth()->id(), $user->id)) {
            $this->forgetResetState();

            return redirect()->route('admin.users')->with('error', 'Password reset OTP verification is required.');
        }

        $user->update(['password' => Hash::make($request->validated('password'))]);
        $this->resetOtp->invalidate($context['request_id'], auth()->id(), $user->id);
        $this->forgetResetState();

        try {
            Mail::to($user->email)->send(new AdminUserPasswordResetCompletedMail($user->name));
        } catch (\Throwable) {
            return redirect()->route('admin.user.show', $user)
                ->with('warning', 'Password reset completed, but the account notification email could not be sent.');
        }

        return redirect()->route('admin.user.show', $user)->with('success', 'Password reset completed successfully.');
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->check() && (int) auth()->user()->role_id === Role::SUPER_ADMIN_ID, 403);
    }

    private function assertResetTarget(User $user): void
    {
        abort_if($user->id === auth()->id(), 422, 'You cannot reset your own password.');
    }

    private function resetContext(User $user): array
    {
        $this->authorizeSuperAdmin();
        $this->assertResetTarget($user);

        $context = session(self::PASSWORD_RESET_SESSION_KEY);

        abort_unless(
            is_array($context)
            && (int) ($context['admin_user_id'] ?? 0) === auth()->id()
            && (int) ($context['target_user_id'] ?? 0) === $user->id
            && ! empty($context['request_id']),
            403
        );

        return $context;
    }

    private function forgetResetState(): void
    {
        session()->forget(self::PASSWORD_RESET_SESSION_KEY);
    }
}
