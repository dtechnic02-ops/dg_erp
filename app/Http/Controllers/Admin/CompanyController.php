<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Concerns\AuthorizesAdminCompany;

use App\Http\Requests\CompleteAdminUserPasswordResetRequest;
use App\Http\Requests\VerifyAdminUserPasswordResetOtpRequest;

use App\Mail\AdminUserPasswordResetCompletedMail;
use App\Mail\AdminUserPasswordResetOtpMail;

use App\Http\Controllers\Controller;

use App\Models\Company;

use App\Models\Role;

use App\Models\User;

use App\Services\SubscriptionService;
use App\Services\PlatformAuthorizationService;
use App\Services\AdminUserPasswordResetOtpService;

use Carbon\Carbon;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;



class CompanyController extends Controller

{

    private const PASSWORD_RESET_SESSION_KEY = 'company_password_reset';

    use AuthorizesAdminCompany;



    public function __construct(
        private SubscriptionService $subscriptionService,
        private PlatformAuthorizationService $platformAuthorization,
        private AdminUserPasswordResetOtpService $resetOtp
    )

    {

    }



    public function index()

    {

        $this->authorizePlatform('platform_companies_view');



        $search = request('search');

        $status = request('status');



        $companies = Company::when($search, function ($q) use ($search) {

                $q->where('company_name', 'like', "%$search%")

                  ->orWhere('email', 'like', "%$search%");

            })

            ->when($status, function ($q) use ($status) {

                $q->where('status', $status);

            })

            ->paginate(10);



        foreach ($companies as $c) {

            $c->expiry = $c->expiry_date;



            $c->days = $c->expiry

                ? now()->diffInDays(Carbon::parse($c->expiry), false)

                : null;

        }



        return view('admin.companies', compact('companies', 'search', 'status'));

    }

    public function show(Company $company)
    {
        $this->authorizePlatform('platform_companies_view');

        $subscription = $this->subscriptionService
            ->getCurrentSubscription($company)
            ?->load(['plan', 'billingCycle']);

        $companyAdmin = User::query()
            ->where('company_id', $company->id)
            ->where('role_id', Role::COMPANY_ADMIN_ID)
            ->first();

        return view('admin.companies_show', compact('company', 'subscription', 'companyAdmin'));
    }



    public function delete(Request $request, $id)

    {

        $this->authorizeDeleteCompany();



        $admin = auth()->user();



        if (! Hash::check($request->admin_password, $admin->password)) {

            return back()->with('error', 'Wrong Admin Password');

        }



        $company = Company::findOrFail($id);



        User::where('company_id', $company->id)->delete();

        $company->delete();



        return back()->with('success', 'Company Deleted');

    }



    public function updateLimit(Request $request, $id)

    {

        $this->authorizeEditCompany();



        $request->validate([

            'limit' => 'required|integer|min:0',

        ]);



        $company = Company::findOrFail($id);



        $company->selected_user_limit = $request->limit;



        $company->save();



        return back()->with('success', 'User limit updated');

    }



    public function updateCustomerLimit(Request $request, $id)

    {

        $this->authorizeEditCompany();



        $request->validate([

            'customer_limit' => 'required|integer|min:0',

        ]);



        $company = Company::findOrFail($id);



        $company->selected_customer_limit = $request->customer_limit;

        $company->save();



        return back()->with('success', 'Customer limit updated');

    }



    public function block(Request $request, $id)

    {

        $this->authorizePlatform('platform_companies_block');



        $company = Company::findOrFail($id);



        $company->status = 'blocked';

        $company->save();



        return back()->with('success', 'Company Blocked');

    }



    public function unblock(Request $request, $id)

    {

        $this->authorizePlatform('platform_companies_unblock');



        $company = Company::findOrFail($id);

        $subscription = $this->subscriptionService->getActiveSubscription($company);



        $hasValidSubscription = $subscription !== null

            && (

                $subscription->expiry_date === null

                || ! Carbon::parse($subscription->expiry_date)->lt(now()->startOfDay())

            );



        $company->status = $hasValidSubscription ? 'active' : 'expired';

        $company->save();



        return back()->with(

            'success',

            $hasValidSubscription

                ? 'Company Activated'

                : 'Company unblocked. Subscription expired — status set to expired.'

        );

    }



    public function requestPasswordReset(Company $company)
    {
        $this->authorizeCompanyPasswordReset();

        [$user, $error] = $this->resolveCompanyAdmin($company);

        if (! $user) {
            return back()->with('error', $error);
        }

        [$requestId, $otp] = $this->resetOtp->issue(auth()->id(), $user->id);

        try {
            Mail::to(auth()->user()->email)->send(new AdminUserPasswordResetOtpMail($otp, $user->name));
        } catch (\Throwable) {
            $this->resetOtp->invalidate($requestId, auth()->id(), $user->id);

            return back()->with('error', 'Unable to send the password reset OTP. No password was changed.');
        }

        session()->put(self::PASSWORD_RESET_SESSION_KEY, [
            'request_id' => $requestId,
            'company_id' => $company->id,
            'target_user_id' => $user->id,
            'admin_user_id' => auth()->id(),
            'verified' => false,
        ]);

        return redirect()
            ->route('admin.company.reset.verify.form', $company)
            ->with('success', 'A verification code was sent to your registered email address.');
    }

    public function showPasswordResetVerification(Company $company)
    {
        $context = $this->resetContext($company);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        [, $user] = $context;

        return view('admin.company_password_reset_verify', compact('company', 'user'));
    }

    public function verifyPasswordResetOtp(VerifyAdminUserPasswordResetOtpRequest $request, Company $company)
    {
        $context = $this->resetContext($company);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        [$state, $user] = $context;
        $result = $this->resetOtp->verify(
            $state['request_id'],
            (int) $state['admin_user_id'],
            (int) $state['target_user_id'],
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

        return redirect()->route('admin.company.reset.password.form', $company);
    }

    public function showPasswordResetForm(Company $company)
    {
        $context = $this->resetContext($company);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        [$state, $user] = $context;

        if (! ($state['verified'] ?? false) || ! $this->resetOtp->isVerified($state['request_id'], auth()->id(), $user->id)) {
            $this->forgetResetState();

            return redirect()->route('admin.companies')->with('error', 'Password reset OTP verification is required.');
        }

        return view('admin.company_password_reset_password', compact('company', 'user'));
    }

    public function completePasswordReset(CompleteAdminUserPasswordResetRequest $request, Company $company)
    {
        $context = $this->resetContext($company);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        [$state, $user] = $context;

        if (! ($state['verified'] ?? false) || ! $this->resetOtp->isVerified($state['request_id'], auth()->id(), $user->id)) {
            $this->forgetResetState();

            return redirect()->route('admin.companies')->with('error', 'Password reset OTP verification is required.');
        }

        $user->update(['password' => Hash::make($request->validated('password'))]);
        $this->resetOtp->invalidate($state['request_id'], auth()->id(), $user->id);
        $this->forgetResetState();

        try {
            Mail::to($user->email)->send(new AdminUserPasswordResetCompletedMail($user->name));
        } catch (\Throwable) {
            Log::warning('Company admin password reset notification could not be sent.', [
                'company_id' => $company->id,
                'user_id' => $user->id,
            ]);

            return redirect()->route('admin.companies')
                ->with('success', 'Password reset completed. The account notification email could not be sent.');
        }

        return redirect()->route('admin.companies')->with('success', 'Password reset completed successfully.');
    }

    private function authorizeCompanyPasswordReset(): void
    {
        abort_unless(auth()->check() && (int) auth()->user()->role_id === Role::SUPER_ADMIN_ID, 403);
        $this->authorizeResetCompanyPassword();
    }

    private function resolveCompanyAdmin(Company $company): array
    {
        $users = User::query()
            ->where('company_id', $company->id)
            ->where('role_id', Role::COMPANY_ADMIN_ID)
            ->limit(2)
            ->get();

        if ($users->isEmpty()) {
            return [null, 'No Company Admin account was found. Correct the account structure before resetting the password.'];
        }

        if ($users->count() > 1) {
            return [null, 'Multiple Company Admin accounts were found. Correct the account structure before resetting the password.'];
        }

        return [$users->first(), null];
    }

    private function resetContext(Company $company): array|RedirectResponse
    {
        $this->authorizeCompanyPasswordReset();

        [$user, $error] = $this->resolveCompanyAdmin($company);

        if (! $user) {
            $this->forgetResetState();

            return redirect()->route('admin.companies')->with('error', $error);
        }

        $state = session(self::PASSWORD_RESET_SESSION_KEY);

        if (! is_array($state)
            || (int) ($state['admin_user_id'] ?? 0) !== auth()->id()
            || (int) ($state['company_id'] ?? 0) !== $company->id
            || (int) ($state['target_user_id'] ?? 0) !== $user->id
            || empty($state['request_id'])) {
            $this->forgetResetState();

            return redirect()->route('admin.companies')->with('error', 'Password reset request is no longer valid.');
        }

        return [$state, $user];
    }

    private function forgetResetState(): void
    {
        session()->forget(self::PASSWORD_RESET_SESSION_KEY);
    }

    private function authorizePlatform(string $permission): void
    {
        abort_unless($this->platformAuthorization->can(auth()->user(), $permission), 403);
    }

}

