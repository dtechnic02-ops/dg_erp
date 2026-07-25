<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Concerns\AuthorizesAdminCompany;

use App\Http\Controllers\Controller;

use App\Models\CompanyRegistration;

use App\Models\Company;

use App\Models\Role;

use App\Models\User;

use App\Services\SubscriptionService;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\File;

use Illuminate\Support\Facades\Hash;

use RuntimeException;



class CompanyApprovalController extends Controller

{

    use AuthorizesAdminCompany;



    public function __construct(private SubscriptionService $subscriptionService)

    {

    }



    public function index()

    {

        $this->authorizeViewCompany();



        $registrations = CompanyRegistration::latest()->paginate(10);



        return view('admin.registrations', compact('registrations'));

    }



    public function approve($id)

    {

        $this->authorizeApproveCompany();



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



                $this->subscriptionService->startRegisterTrial($company, auth()->user());



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

        $this->authorizeApproveCompany();



        $reg = CompanyRegistration::findOrFail($id);



        if ($reg->status !== 'pending') {

            return back()->with('error', 'Already processed!');

        }



        $reg->update(['status' => 'rejected']);



        return redirect()->route('admin.registrations')

            ->with('success', 'Company Rejected');

    }

}

