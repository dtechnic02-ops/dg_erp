<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\LoanAccount;
use App\Models\PartyAccount;
use App\Models\Account;

class LoanAccountController extends Controller
{

/**
* INDEX
*/

public function index(Request $request)
{

$loans = LoanAccount::with([

'partyAccount',

'account'

])

->where(

'company_id',

auth()->user()->company_id

)

->latest()

->paginate(20)

->withQueryString();

return view(

'company.loan-account.index',

compact(

'loans'

)

);

}

/**
* CREATE
*/

public function create()
{

$companyId =
auth()->user()->company_id;

$partyAccounts =
PartyAccount::where(

'company_id',

$companyId

)

->where(

'status',

1

)

->get();

$accounts =
Account::where(

'company_id',

$companyId

)

->where(

'status',

1

)

->get();
$year =
now()->year;

$last =
LoanAccount::where(

'company_id',

$companyId

)

->latest('id')

->first();

$next = 1;

if($last){

$parts =
explode(
'-',
$last->loan_no
);

$next =
((int) end($parts))
+1;

}

$loanNo =

'LOAN-'

.$companyId

.'-'

.$year

.'-'

.str_pad(

$next,

4,

'0',

STR_PAD_LEFT

);

return view(

'company.loan-account.create',

compact(

'loanNo',

'partyAccounts',

'accounts'

)

);

}

/**
* STORE
*/

public function store(Request $request)
{

$request->validate([

'loan_name'=>

'required',

'loan_type'=>

'required',

'party_account_id'=>

'required',

'account_id'=>

'required',

'principal_amount'=>

'required|numeric|min:0.01',

'interest_rate'=>

'nullable|numeric',

'start_date'=>

'required',

'attachment'=>

'nullable|

mimes:jpg,jpeg,png,pdf|

max:5120'

]);

DB::transaction(

function()

use($request){

$companyId =
auth()->user()->company_id;

$account =
Account::where(

'company_id',

$companyId

)

->findOrFail(

$request->account_id

);

$party =
PartyAccount::where(

'company_id',

$companyId

)

->findOrFail(

$request->party_account_id

);

$file = null;

if(

$request->hasFile(

'attachment'

)

){

$folder =

'companies/'

.$companyId

.'/loans';

if(

!file_exists(

public_path(

$folder

)

)

){

mkdir(

public_path(

$folder

),

0777,

true

);

}

$name =

time()

.'_'

.$request

->file(

'attachment'

)

->getClientOriginalName();

$request

->file(

'attachment'

)

->move(

public_path(

$folder

),

$name

);

$file =

$folder

.'/'

.$name;

}

$year =
now()->year;

$last =
LoanAccount::where(

'company_id',

$companyId

)

->latest('id')

->first();

$next=1;

if($last){

$parts=
explode(
'-',
$last->loan_no
);

$next=
((int) end($parts))
+1;

}

$loanNo =

'LOAN-'

.$companyId

.'-'

.now()->year

.'-'

.str_pad(

$next,

4,

'0',

STR_PAD_LEFT

);

$loan = LoanAccount::create([

'company_id'=>

$companyId,

'loan_no'=>

$loanNo,

'loan_name'=>

$request->loan_name,

'loan_type'=>

$request->loan_type,

'party_account_id'=>

$request->party_account_id,

'account_id'=>

$request->account_id,

'principal_amount'=>

$request->principal_amount,

'interest_rate'=>

$request->interest_rate,

'remaining_principal'=>

$request->principal_amount,

'start_date'=>

$request->start_date,

'end_date'=>

$request->end_date,

'next_payment_date'=>

$request->next_payment_date,

'attachment'=>

$file,

'note'=>

$request->note,

'created_by'=>

auth()->id(),

'status'=>1

]);
/*
LOAN TAKEN
*/

if(

$request->loan_type

==

'taken'

){

$account->increment(

'current_balance',

(float)

$request->principal_amount

);

$party->increment(

'current_balance',

(float)

$request->principal_amount

);

}

/*
LOAN GIVEN
*/

else{

if(

(float)

$account->current_balance

<

(float)

$request->principal_amount

){

throw new \Exception(

'Insufficient balance.'

);

}

$account->decrement(

'current_balance',

(float)

$request->principal_amount

);

$party->increment(

'current_balance',

(float)

$request->principal_amount

);

}

}

);

return redirect()

->route(

'company.loan-account.index'

)

->with(

'success',

'Loan created.'

);

}

/**
* SHOW
*/

public function show($id)
{

$loan =

LoanAccount::with([

'partyAccount',

'account',

'payments',

'savingLedgers'

])

->where(

'company_id',

auth()->user()->company_id

)

->findOrFail($id);


/*
|--------------------------------------------------------------------------
| SAVING SUMMARY
|--------------------------------------------------------------------------
*/

$totalSavingDeposit =

$loan->savingLedgers()

->where(

'type',

'deposit'

)

->sum(

'amount'

);

$totalSavingWithdraw =

$loan->savingLedgers()

->where(

'type',

'withdraw'

)

->sum(

'amount'

);

$currentSavingBalance =

$totalSavingDeposit

-

$totalSavingWithdraw;

return view(

'company.loan-account.show',

compact(

'loan',

'totalSavingDeposit',

'totalSavingWithdraw',

'currentSavingBalance'

)

);

}
}