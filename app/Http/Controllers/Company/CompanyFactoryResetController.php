<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExecuteCompanyFactoryResetRequest;
use App\Mail\CompanyFactoryResetOtpMail;
use App\Models\Company;
use App\Models\CompanyDestructiveChallenge;
use App\Models\CompanyFactoryResetAudit;
use App\Models\Role;
use App\Services\CompanyDestructiveChallengeService;
use App\Services\CompanyFactoryResetService;
use App\Services\PlatformMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class CompanyFactoryResetController extends Controller
{
    private const SESSION_KEY = 'company_factory_reset_challenge';

    public function __construct(
        private PlatformMailService $mail,
        private CompanyDestructiveChallengeService $challenges,
        private CompanyFactoryResetService $reset,
    ) {
    }

    public function show(): View
    {
        [$admin, $company] = $this->authorizeCompanyAdmin();
        $challengeId = session(self::SESSION_KEY);
        $cleanupAudit = CompanyFactoryResetAudit::query()->where('company_id', $company->id)
            ->where('file_cleanup_state', 'failed')->latest('id')->first();
        return view('company.settings.factory-reset', compact('admin', 'company', 'challengeId', 'cleanupAudit'));
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        [$admin, $company] = $this->authorizeCompanyAdmin();
        $request->validate(['confirmation_phrase' => ['required', 'in:RESET MY COMPANY']]);

        try {
            [$challenge, $otp] = $this->challenges->issue(
                $company->id,
                $admin->id,
                CompanyDestructiveChallenge::PURPOSE_FACTORY_RESET
            );
            $this->mail->send($admin->email, new CompanyFactoryResetOtpMail(
                companyName: $company->company_name,
                requestedByName: $admin->name,
                otp: $otp,
                expiresInMinutes: CompanyDestructiveChallengeService::TTL_MINUTES,
            ));
        } catch (Throwable $e) {
            if (isset($challenge)) {
                $this->challenges->invalidate($challenge->id, $company->id, $admin->id, CompanyDestructiveChallenge::PURPOSE_FACTORY_RESET);
            }
            $message = $e instanceof RuntimeException && str_starts_with($e->getMessage(), 'Please wait')
                ? $e->getMessage()
                : 'Unable to send the verification code. No Company data was reset.';
            return back()->with('error', $message);
        }

        session()->put(self::SESSION_KEY, $challenge->id);
        return back()->with('success', 'A verification code was sent to your authenticated Company Admin email.');
    }

    public function execute(ExecuteCompanyFactoryResetRequest $request): RedirectResponse
    {
        [$admin, $company] = $this->authorizeCompanyAdmin();
        $validated = $request->validated();
        $sessionChallengeId = (int) session(self::SESSION_KEY, 0);
        if ($sessionChallengeId === 0 || $sessionChallengeId !== (int) $validated['challenge_id']) {
            return back()->with('error', 'The Factory Reset verification request is invalid.');
        }

        try {
            $this->challenges->consume(
                $sessionChallengeId,
                $company->id,
                $admin->id,
                CompanyDestructiveChallenge::PURPOSE_FACTORY_RESET,
                $validated['otp']
            );
            $audit = $this->reset->reset($company, $admin->id, $request->session()->getId());
        } catch (Throwable $e) {
            $message = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Factory Reset failed safely. Database changes were rolled back.';
            return back()->with('error', $message);
        }

        session()->forget(self::SESSION_KEY);
        $message = $audit->file_cleanup_state === 'completed'
            ? 'Company Factory Reset completed successfully.'
            : 'Company Factory Reset completed. Verified operational file cleanup requires administrator retry.';
        return redirect()->route('company.dashboard')->with('success', $message);
    }

    public function retryFileCleanup(CompanyFactoryResetAudit $audit): RedirectResponse
    {
        [, $company] = $this->authorizeCompanyAdmin();
        abort_unless((int) $audit->company_id === (int) $company->id, 403);
        try {
            $audit = $this->reset->retryFileCleanup($audit);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', $audit->file_cleanup_state === 'completed'
            ? 'Verified operational file cleanup completed.'
            : 'Verified operational file cleanup still requires retry.');
    }

    private function authorizeCompanyAdmin(): array
    {
        $user = auth()->user();
        abort_unless(
            $user
            && (int) $user->role_id === Role::COMPANY_ADMIN_ID
            && $user->company_id !== null
            && $user->account_status === 'active'
            && filter_var($user->email, FILTER_VALIDATE_EMAIL),
            403
        );
        $company = Company::query()->whereKey($user->company_id)->where('status', 'active')->first();
        abort_unless($company, 403);

        return [$user, $company];
    }
}
