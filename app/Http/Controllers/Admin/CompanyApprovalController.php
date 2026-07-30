<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Concerns\AuthorizesAdminCompany;

use App\Http\Controllers\Controller;

use App\Models\CompanyRegistration;

use App\Models\Company;

use App\Models\SubscriptionPlan;

use App\Models\Role;

use App\Models\User;

use App\Services\SubscriptionService;
use App\Services\PlatformAuthorizationService;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\File;

use Illuminate\Support\Facades\Hash;

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



        try {

            DB::transaction(function () use ($reg) {

                $company = Company::firstOrCreate(

                    ['email' => $reg->email],

                    [

                        'company_name' => $reg->company_name,

                        'mobile' => $reg->mobile_no,

                        'status' => 'active',

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



                $passwordHash = $reg->password ?: Hash::make('123456');



                $user = User::firstOrNew(['email' => $reg->email]);

                $user->fill([

                    'company_id' => $company->id,

                    'name' => $reg->full_name,

                    'role_id' => Role::COMPANY_ADMIN_ID,

                    'password' => $passwordHash,

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

