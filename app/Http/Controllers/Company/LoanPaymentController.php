<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\LoanAccount;
use App\Models\LoanPayment;
use App\Models\Account;
use App\Models\PartyAccount;
use App\Models\LoanSavingLedger;
class LoanPaymentController extends Controller
{

public function index()
{

$payments=

LoanPayment::with([

'loanAccount.partyAccount',

'account'

])

->where(

'company_id',

auth()->user()->company_id

)

->latest()

->paginate(20);

return view(

'company.loan-payment.index',

compact(

'payments'

)

);

}


public function create($id)
{

$companyId=

auth()->user()->company_id;

$loan=

LoanAccount::with(

'partyAccount'

)

->where(

'company_id',

$companyId

)

->findOrFail($id);

$accounts=

Account::where(

'company_id',

$companyId

)

->where(

'status',

1

)

->get();

return view(

'company.loan-payment.create',

compact(

'loan',

'accounts'

)

);

}


public function store(Request $request)
{

$request->validate([

'loan_account_id'=>

'required',

'account_id'=>

'required',

'payment_date'=>

'required|date',

'principal_amount'=>

'required|numeric|min:0',

'interest_amount'=>

'nullable|numeric',

'fine_amount'=>

'nullable|numeric',

'saving_amount'=>

'nullable|numeric',

'attachment'=>

'nullable|

mimes:jpg,jpeg,png,pdf|

max:5120'

]);

DB::transaction(

function()

use($request){

$companyId=

auth()->user()->company_id;

$loan=

LoanAccount::with(

'partyAccount'

)

->where(

'company_id',

$companyId

)

->findOrFail(

$request->loan_account_id

);

$account=

Account::where(

'company_id',

$companyId

)

->findOrFail(

$request->account_id

);

$party=

$loan->partyAccount;

$total=

(float)

$request->principal_amount

+

(float)

($request->interest_amount ??0)

+

(float)

($request->fine_amount ??0)

+

(float)

($request->saving_amount ??0);

if(

(float)

$request->principal_amount

>

(float)

$loan->remaining_principal

)
{

throw new \Exception(

'Principal exceeds remaining.'

);

}

$file=null;

if(

$request->hasFile(

'attachment'

)

){

$folder=

'companies/'

.$companyId

.'/loan-payments';

if(

!file_exists(

public_path(

$folder

))

){

mkdir(

public_path(

$folder

),

0777,

true

);

}

$name=

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

$file=

$folder

.'/'

.$name;

}

$newRemaining =

(float)

$loan->remaining_principal

-

(float)

$request->principal_amount;


/*
LOAN TAKEN
*/

if(

$loan->loan_type

==

'taken'

){

if(

(float)

$account->current_balance

<

(float)

$total

)
{

throw new \Exception(

'Insufficient balance.'

);

}

$account->decrement(

'current_balance',

(float)

$total

);

}

/*
LOAN GIVEN
*/

else{

$account->increment(

'current_balance',

(float)

$total

);

}

/*
PARTY BALANCE REDUCE
*/
/*
Only principal reduces loan liability.
Saving handled separately.
*/

$party->decrement(

'current_balance',

(float)

$request->principal_amount

);

/*
SAVE PAYMENT
*/

$payment = LoanPayment::create([

'company_id'=>

$companyId,

'loan_account_id'=>

$loan->id,

'account_id'=>

$account->id,

'principal_amount'=>

$request->principal_amount,

'interest_amount'=>

$request->interest_amount ?? 0,

'fine_amount'=>

$request->fine_amount ?? 0,

'saving_amount'=>

$request->saving_amount ?? 0,

'total_amount'=>

(float)

$total,

'remaining_principal'=>

$newRemaining,

'payment_date'=>

$request->payment_date,

'attachment'=>

$file,

'note'=>

$request->note,

'created_by'=>

auth()->id(),

'status'=>1

]);

/*
|--------------------------------------------------------------------------
| SAVING LEDGER
|--------------------------------------------------------------------------
*/
if(

(float)

($request->saving_amount ?? 0)

> 0

){

$previousSaving =

LoanSavingLedger::where(

'company_id',

$companyId

)

->where(

'loan_account_id',

$loan->id

)

->latest('id')

->value(

'balance_after'

)

?? 0;

$newSavingBalance =

(float)

$previousSaving

+

(float)

$request->saving_amount;

LoanSavingLedger::create([

'company_id'=>

$companyId,

'loan_account_id'=>

$loan->id,

'loan_payment_id'=>

$payment->id,

'account_id'=>

$account->id,

'type'=>

'deposit',

'amount'=>

$request->saving_amount,

'balance_after'=>

$newSavingBalance,

'date'=>

$request->payment_date,

'attachment'=>

$file,

'note'=>

$request->note,

'created_by'=>

auth()->id(),

'status'=>1

]);

}

$loan->update([

'remaining_principal'=>

$newRemaining

]);

});

return redirect()

->route(

'company.loan-payment.index'

)

->with(

'success',

'Payment saved.'

);

}



public function show($id)
{

$payment=

LoanPayment::with([

'loanAccount.partyAccount',

'account'

])

->where(

'company_id',

auth()->user()->company_id

)

->findOrFail($id);

return view(

'company.loan-payment.show',

compact(

'payment'

)

);

}

}