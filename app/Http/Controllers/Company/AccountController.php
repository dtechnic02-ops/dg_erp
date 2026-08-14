<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use App\Services\FileUploadService;
use App\Services\ValidationService;

class AccountController extends Controller
{
    use AuthorizesCompanyPermission;

    // 🔥 SHARED FILTERED QUERY
    // Used by index(), print(), and any other action
    // (e.g. summary totals) so the filter logic is
    // defined in exactly one place.
    private function filteredAccountQuery(Request $request)
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

        if ($request->filled('search'))
        {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_group', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('account_no', 'like', "%{$search}%")
                  ->orWhere('iban', 'like', "%{$search}%");
            });
        }

        if ($request->filled('account_group'))
        {
            $query->where('account_group', $request->account_group);
        }

        return $query;
    }

    private function normalizedBankFields(Request $request, string $accountType): array
    {
        $bankFields = ['bank_name', 'branch', 'account_no', 'iban', 'swift_code'];

        if ($accountType === 'Cash') {
            return collect($bankFields)
                ->mapWithKeys(fn (string $field) => [$field => trim((string) $request->input($field, ''))])
                ->all();
        }

        return [
            'bank_name' => trim((string) $request->input('bank_name', '')),
            'branch' => $request->filled('branch') ? trim((string) $request->branch) : null,
            'account_no' => $request->filled('account_no') ? trim((string) $request->account_no) : null,
            'iban' => $request->filled('iban') ? trim((string) $request->iban) : null,
            'swift_code' => $request->filled('swift_code') ? trim((string) $request->swift_code) : null,
        ];
    }


public function index(Request $request)
{
    $this->authorizeCompanyPermission('view_accounts');

    $accountGroups = Account::accountGroupLabels();

    $accountTypes = Account::accountTypeLabels();

    $totalCurrentBalance = $this->filteredAccountQuery($request)
        ->sum('current_balance');

    $accounts = $this->filteredAccountQuery($request)

        ->latest()

        ->paginate(20)

        ->withQueryString();


    return view(

        'company.accounts.index',

        compact(
            'accounts',
            'totalCurrentBalance',
            'accountGroups',
            'accountTypes'
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
        $this->authorizeCompanyPermission('create_accounts');

$request->validate([

    'account_group' => [
        'required',
        Rule::in(array_keys(Account::accountGroupLabels())),
    ],

    'account_type' =>
        ['required', Rule::in(array_keys(Account::accountTypeLabels()))],

    'sub_ledger_type' =>
        'nullable|in:customer,supplier,employee,party',

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
       $bankFields = $this->normalizedBankFields($request, $request->account_type);

       $account = Account::create([

    'company_id' =>
        auth()->user()->company_id,

    'account_group' =>
        $request->account_group,

    'account_type' =>
        $request->account_type,

    'sub_ledger_type' =>
        $request->sub_ledger_type ?: null,

    'bank_name' =>
        $bankFields['bank_name'],

    'account_name' =>
        $request->account_name,

    'branch' =>
        $bankFields['branch'],

    'account_no' =>
        $bankFields['account_no'],

    'iban' =>
        $bankFields['iban'],

    'swift_code' =>
        $bankFields['swift_code'],

    'currency' =>
        $request->currency ?? 'AED',

    'opening_balance' =>
        0,

    'current_balance' => 0,

    'note' =>
        $request->note,

'image_path' =>
$imagePath,

    'status' =>
        $request->status ?? 'active',

]);
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
        $this->authorizeCompanyPermission('edit_accounts');

        $account = Account::where(

            'company_id',

            auth()->user()->company_id

        )

        ->findOrFail($id);


       $request->validate([

'account_group' => [
    'required',
    Rule::in(array_keys(Account::accountGroupLabels())),
],


'account_type' =>
    ['required', Rule::in(array_keys(Account::accountTypeLabels()))],

'sub_ledger_type' =>
    'nullable|in:customer,supplier,employee,party',

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


        


        $bankFields = $this->normalizedBankFields($request, $request->account_type);

        $data = [

            'account_group' =>

                $request->account_group,

            'account_type' =>

                $request->account_type,

            'sub_ledger_type' =>

                $request->sub_ledger_type ?: null,

            'bank_name' =>

                $bankFields['bank_name'],

            'account_name' =>

                $request->account_name,

            'branch' =>

                $bankFields['branch'],

            'account_no' =>

                $bankFields['account_no'],

            'iban' =>

                $bankFields['iban'],

            'swift_code' =>

                $bankFields['swift_code'],

            'currency' =>

$request->currency
?? 'AED',

'opening_balance' =>
$account->opening_balance,

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
    $this->authorizeCompanyPermission('delete_accounts');

    $account = Account::where(
        'company_id',
        auth()->user()->company_id
    )->findOrFail($id);

    DB::beginTransaction();

    try{

        if (\App\Models\OpeningBalanceLine::where('operational_account_id', $account->id)->exists()
            || $account->transactions()->exists()
            || $account->journalItems()->exists()
            || \App\Models\AccountingEntryLine::where('operational_account_id', $account->id)->exists()) {
            $account->update(['status' => 'inactive']);
            DB::commit();
            return back()->with('success', 'Account has financial history and was safely archived.');
        }

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
    $this->authorizeCompanyPermission('view_accounts');

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






/* =====================

PRINT (LIST)

Reuses the SAME filteredAccountQuery() used by
index(), so Print always reflects only the
currently filtered accounts.

===================== */

public function print(Request $request)
{
    $this->authorizeCompanyPermission('print_accounts');

    $accounts = $this->filteredAccountQuery($request)
        ->latest()
        ->get();

    $totalAccounts = $accounts->count();

    $totalOpeningBalance = $accounts->sum('opening_balance');

    $totalCurrentBalance = $accounts->sum('current_balance');

    return view(
        'company.accounts.print',
        compact(
            'accounts',
            'totalAccounts',
            'totalOpeningBalance',
            'totalCurrentBalance'
        )
    );
}


/* =====================

PRINT PROFILE

Reuses the existing show.blade.php view with
$print = true, exactly like the Customer and
Supplier modules.

===================== */

public function printProfile($id)
{
    $this->authorizeCompanyPermission('print_accounts');

    $companyId = auth()->user()->company_id;

    $account = Account::where('company_id', $companyId)
        ->findOrFail($id);

    $print = true;

    return view(
        'company.accounts.show',
        compact('account', 'print')
    );
}

}
