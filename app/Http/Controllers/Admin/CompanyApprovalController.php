<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Concerns\AuthorizesAdminCompany;

use App\Http\Controllers\Controller;

use App\Models\CompanyRegistration;

use App\Models\Company;
use App\Models\Country;

use App\Models\SubscriptionPlan;

use App\Models\Role;

use App\Models\User;

use App\Services\SubscriptionService;
use App\Services\PlatformAuthorizationService;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\File;

use RuntimeException;



class CompanyApprovalController extends Controller

{

    use AuthorizesAdminCompany;



    public function __construct(
        private SubscriptionService $subscriptionService,
        private PlatformAuthorizationService $platformAuthorization
    )

    {

    }



    public function index()

    {

        $this->authorizePlatform('platform_registrations_view');



        $registrations = CompanyRegistration::latest()->paginate(10);



        return view('admin.registrations', compact('registrations'));

    }

    public function show(CompanyRegistration $registration)
    {
        $this->authorizePlatform('platform_registrations_view');

        return view('admin.registration_show', compact('registration'));
    }



    public function approve($id)

    {

        $this->authorizePlatform('platform_registrations_approve');



        $reg = CompanyRegistration::findOrFail($id);



        if ($reg->status !== 'pending') {

            return back()->with('error', 'Already processed!');

        }



        if (! $reg->mobile_no) {

            return back()->with('error', 'Mobile number missing.');

        }

        if (! Country::query()->whereKey($reg->country_id)->where('is_active', true)->exists()) {
            return back()->with('error', 'The registration country is missing or inactive.');
        }

        if (! is_string($reg->password) || trim($reg->password) === '') {
            return back()->with('error', 'The registration password is missing. Company approval cannot continue.');
        }



        try {

            DB::transaction(function () use ($reg) {

                $company = Company::firstOrCreate(

                    ['email' => $reg->email],

                    [

                        'company_name' => $reg->company_name,

                        'mobile' => $reg->mobile_no,

                        'status' => 'active',
                        'country_id' => $reg->country_id,

                    ]

                );

                $trialPlan = SubscriptionPlan::active()
                    ->where('code', 'trial')
                    ->first();

                if (! $trialPlan) {
                    throw new RuntimeException(
                        'The required Trial subscription plan is missing or inactive. Company approval cannot continue.'
                    );
                }



                $folderPath = public_path('companies/' . $company->id);



                if (File::exists($folderPath)) {

                    throw new RuntimeException('Folder already exists!');

                }



                $user = User::firstOrNew(['email' => $reg->email]);

                $user->fill([

                    'company_id' => $company->id,

                    'name' => $reg->full_name,

                    'role_id' => Role::COMPANY_ADMIN_ID,

                    'password' => $reg->password,

                ]);

                $user->save();



                File::makeDirectory($folderPath, 0755, true);



                $this->subscriptionService->startRegisterTrial($company, $trialPlan, auth()->user());



                $reg->update(['status' => 'approved']);

            });

        } catch (RuntimeException $e) {

            return back()->with('error', $e->getMessage());

        }



        return redirect()->route('admin.registrations')

            ->with('success', 'Company Approved Successfully');

    }



    public function reject($id)

    {

        $this->authorizePlatform('platform_registrations_reject');



        $reg = CompanyRegistration::findOrFail($id);



        if ($reg->status !== 'pending') {

            return back()->with('error', 'Already processed!');

        }



        $reg->update(['status' => 'rejected']);



        return redirect()->route('admin.registrations')

            ->with('success', 'Company Rejected');

    }

    private function authorizePlatform(string $permission): void
    {
        abort_unless($this->platformAuthorization->can(auth()->user(), $permission), 403);
    }

}
