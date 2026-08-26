<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExecuteCompanyPermanentDeleteRequest;
use App\Mail\CompanyPermanentDeletionOtpMail;
use App\Models\Company;
use App\Models\CompanyDestructiveChallenge;
use App\Models\CompanyDeletionAudit;
use App\Models\Role;
use App\Services\CompanyDestructiveChallengeService;
use App\Services\CompanyPermanentDeletionService;
use App\Services\PlatformAuthorizationService;
use App\Services\PlatformMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class CompanyPermanentDeletionController extends Controller
{
    private const SESSION_KEY = 'company_permanent_delete_challenge';

    public function __construct(
        private PlatformAuthorizationService $authorization,
        private PlatformMailService $mail,
        private CompanyDestructiveChallengeService $challenges,
        private CompanyPermanentDeletionService $deletion,
    ) {
    }

    public function show(Company $company): View
    {
        $admin = $this->authorizeCanonicalSuperAdmin();
        $challengeId = session(self::SESSION_KEY.'.'.$company->id);

        return view('admin.company_permanent_delete', compact('company', 'admin', 'challengeId'));
    }

    public function sendOtp(Request $request, Company $company): RedirectResponse
    {
        $admin = $this->authorizeCanonicalSuperAdmin();
        $request->validate([
            'company_name_confirmation' => ['required', 'string', 'max:255'],
        ]);
        if (! hash_equals($company->company_name, (string) $request->input('company_name_confirmation'))) {
            return back()->withErrors(['company_name_confirmation' => 'Enter the exact Company name to request a verification code.']);
        }

        try {
            [$challenge, $otp] = $this->challenges->issue(
                $company->id,
                $admin->id,
                CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE
            );
            $this->mail->send($admin->email, new CompanyPermanentDeletionOtpMail(
                companyName: $company->company_name,
                companyId: $company->id,
                requestedByName: $admin->name,
                otp: $otp,
                expiresInMinutes: CompanyDestructiveChallengeService::TTL_MINUTES,
            ));
        } catch (Throwable $e) {
            if (isset($challenge)) {
                $this->challenges->invalidate(
                    $challenge->id,
                    $company->id,
                    $admin->id,
                    CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE
                );
            }

            $message = $e instanceof RuntimeException && str_starts_with($e->getMessage(), 'Please wait')
                ? $e->getMessage()
                : 'Unable to send the verification code. No Company data was deleted.';
            return back()->with('error', $message);
        }

        session()->put(self::SESSION_KEY.'.'.$company->id, $challenge->id);
        return back()->with('success', 'A verification code was sent to your authenticated Super Admin email.');
    }

    public function destroy(ExecuteCompanyPermanentDeleteRequest $request, Company $company): RedirectResponse
    {
        $admin = $this->authorizeCanonicalSuperAdmin();
        $validated = $request->validated();
        $sessionChallengeId = (int) session(self::SESSION_KEY.'.'.$company->id, 0);
        if ($sessionChallengeId === 0 || $sessionChallengeId !== (int) $validated['challenge_id']) {
            return back()->with('error', 'The deletion verification request is invalid.');
        }
        if (! hash_equals($company->company_name, $validated['company_name_confirmation'])) {
            return back()->withErrors(['company_name_confirmation' => 'Enter the exact Company name to confirm permanent deletion.']);
        }

        try {
            $challenge = $this->challenges->consume(
                $sessionChallengeId,
                $company->id,
                $admin->id,
                CompanyDestructiveChallenge::PURPOSE_PERMANENT_DELETE,
                $validated['otp']
            );
            $audit = $this->deletion->delete($company, $admin->id, $challenge->id);
        } catch (Throwable $e) {
            $message = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Permanent deletion failed safely. Database changes were rolled back.';
            return back()->with('error', $message);
        }

        session()->forget(self::SESSION_KEY.'.'.$company->id);
        $message = $audit->file_cleanup_state === 'completed'
            ? 'Company permanently deleted.'
            : 'Company permanently deleted. Verified file cleanup requires administrator retry.';

        return redirect()->route('admin.companies')->with('success', $message);
    }

    public function retryFileCleanup(CompanyDeletionAudit $audit): RedirectResponse
    {
        $this->authorizeCanonicalSuperAdmin();
        try {
            $audit = $this->deletion->retryFileCleanup($audit);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $audit->file_cleanup_state === 'completed'
            ? 'Verified Company file cleanup completed.'
            : 'Verified Company file cleanup still requires retry.');
    }

    private function authorizeCanonicalSuperAdmin()
    {
        $user = auth()->user();
        abort_unless(
            $user
            && (int) $user->role_id === Role::SUPER_ADMIN_ID
            && $user->company_id === null
            && $user->account_status === 'active'
            && filter_var($user->email, FILTER_VALIDATE_EMAIL)
            && $this->authorization->can($user, 'platform_companies_delete'),
            403
        );

        return $user;
    }
}
