<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\AccountBalanceService;
use App\Models\FinancialYear;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use App\Services\FileUploadService;
use App\Services\ValidationService;
use App\Services\InvoiceNumberService;
use App\Models\AccountTransaction;

class AccountController extends Controller
{


public function index(Request $request)
{

   $query = Account::where(

'company_id',

auth()->user()->company_id

)

->where(

'status',

'!=',

'inactive'

);


    if($request->filled('search'))
    {

        $search = trim(
            $request->search
        );

        $query->where(

            function($q)
            use($search){

                $q->where(
                    'bank_name',
                    'like',
                    "%{$search}%"
                )

                ->orWhere(
                    'account_name',
                    'like',
                    "%{$search}%"
                )

                ->orWhere(
                    'account_no',
                    'like',
                    "%{$search}%"
                )

                ->orWhere(
                    'iban',
                    'like',
                    "%{$search}%"
                );

            }

        );

    }


    $accounts = $query

        ->latest()

        ->paginate(20)

        ->withQueryString();


    return view(

        'company.accounts.index',

        compact(
            'accounts'
        )

    );

}




    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
$request->validate([

    'account_type' =>
        'required',

    'account_name' => [

        'required',

        Rule::unique(
            'accounts',
            'account_name'
        )
     
        ->where(function($query){

            return $query->where(

                'company_id',

                auth()->user()->company_id

            );

        })

    ],

    'image_path' =>
        ValidationService::document(),

],[
    'account_name.unique' =>
        'Account already exists in your company.'
]);

        /*
        IMAGE
        */

$imagePath = null;

$folder =
'companies/' .
auth()->user()->company_id .
'/accounts';

if (
    $request->hasFile(
        'image_path'
    )
)
{
    $imagePath =
        FileUploadService::uploadFile(
            $request->file(
                'image_path'
            ),
            $folder
        );
}

DB::beginTransaction();
try{
       $account = Account::create([

    'company_id' =>
        auth()->user()->company_id,

    'account_type' =>
        $request->account_type,

    'bank_name' =>
        $request->bank_name,

    'account_name' =>
        $request->account_name,

    'branch' =>
        $request->branch,

    'account_no' =>
        $request->account_no,

    'iban' =>
        $request->iban,

    'swift_code' =>
        $request->swift_code,

    'currency' =>
        $request->currency ?? 'AED',

    'opening_balance' =>
        $request->opening_balance ?? 0,

    'current_balance' => 0,

    'note' =>
        $request->note,

'image_path' =>
$imagePath,

    'status' =>
        $request->status ?? 'active',

]);
        if (
    $account->opening_balance > 0
)
{
    $activeFy = FinancialYear::where(
        'company_id',
        auth()->user()->company_id
    )
    ->where(
        'is_active',
        1
    )
    ->first();

    if ($activeFy)
    {
       AccountBalanceService::createTransaction([

    'company_id' =>
        auth()->user()->company_id,

    'financial_year_id' =>
        $activeFy->id,

    'account_id' =>
        $account->id,

    'transaction_date' =>
        $activeFy->start_date,
'voucher_no' =>
    InvoiceNumberService::generate(
        'OB',
        auth()->user()->company_id,
        $activeFy->id,
        AccountTransaction::class,
        'voucher_no'
    ),

    'reference_type' =>
        'opening_balance',

    'reference_id' =>
        $account->id,

    'description' =>
        'Opening Balance - ' . $account->account_name,

    'debit' =>
        $account->opening_balance,

    'credit' =>
        0,

]);
    }
}
   

DB::commit();

return back()->with(
    'success',
    'Account Added Successfully'
);

}
catch(\Exception $e){

    DB::rollBack();

    FileUploadService::deleteFile(
        $imagePath
    );

    throw $e;
} 
    }
    





    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(

        Request $request,
        $id
    )
    {

        $account = Account::where(

            'company_id',

            auth()->user()->company_id

        )

        ->findOrFail($id);


       $request->validate([


'account_type' =>
    'required',

'account_name' => [

    'required',

    Rule::unique(
        'accounts',
        'account_name'
    )
    ->ignore(
        $account->id
    )
    ->where(function($query){

        return $query->where(

            'company_id',

            auth()->user()->company_id

        );

    })

],

'image_path' =>
    ValidationService::document(),


],[
'account_name.unique' =>
'Account already exists in your company.'
]);


        


        $data = [

            'account_type' =>

                $request->account_type,

            'bank_name' =>

                $request->bank_name,

            'account_name' =>

                $request->account_name,

            'branch' =>

                $request->branch,

            'account_no' =>

                $request->account_no,

            'iban' =>

                $request->iban,

            'swift_code' =>

                $request->swift_code,

            'currency' =>

$request->currency
?? 'AED',

'opening_balance' =>

$request->opening_balance,

'current_balance'=>

$account->current_balance,

'note' =>

$request->note,

'status' =>

$request->status
?? 'active',
        ];


        /*
        IMAGE UPDATE
        */


$folder =
'companies/' .
auth()->user()->company_id .
'/accounts';

$data['image_path'] =
    FileUploadService::replaceFile(
        $request,
        'image_path',
        $account->image_path,
        $folder
    );



        $account->update(
            $data
        );


        return back()->with(

            'success',

            'Account Updated Successfully'

        );

    }



    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */



public function destroy($id)
{
    $account = Account::where(
        'company_id',
        auth()->user()->company_id
    )->findOrFail($id);

    DB::beginTransaction();

    try{

        if ($account->current_balance != 0)
        {
            $image = $account->image_path;

            $account->update([

                'status' => 'inactive',

                'image_path' => null

            ]);

            FileUploadService::deleteFile(
                $image
            );

            $message =
                'Account Archived Successfully';
        }
        else
        {
            FileUploadService::deleteFile(
                $account->image_path
            );

            $account->delete();

            $message =
                'Account Deleted Successfully';
        }

        DB::commit();

        return back()->with(
            'success',
            $message
        );

    }
    catch(\Exception $e){

        DB::rollBack();

        throw $e;
    }
}

public function show($id)
{
    $account = Account::where(
        'company_id',
        auth()->user()->company_id
    )
    ->findOrFail($id);

    return view(
        'company.accounts.show',
        compact(
            'account'
        )
    );
}






                  public function print()
               {

                $accounts = Account::where(

'company_id',

auth()->user()->company_id

)

->where(

'status',

'!=',

'inactive'

)

->latest()

->get();


                            return view(

                          'company.accounts.print',

                            compact('accounts')
                          );

             }

}