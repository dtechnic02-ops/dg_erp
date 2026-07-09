<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\LoanAccount;
use App\Models\LoanSavingLedger;
use App\Models\Account;

class LoanSavingWithdrawController extends Controller
{

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

$savingBalance=

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

return view(

'company.loan-saving-withdraw.create',

compact(

'loan',

'accounts',

'savingBalance'

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

'amount'=>

'required|numeric|min:0.01',

'date'=>

'required'

]);

DB::transaction(

function()

use($request){

$companyId=
auth()->user()->company_id;

$account=
Account::where(

'company_id',

$companyId

)

->findOrFail(

$request->account_id

);

$currentSaving=

LoanSavingLedger::where(

'company_id',

$companyId

)

->where(

'loan_account_id',

$request->loan_account_id

)

->latest('id')

->value(

'balance_after'

)

??0;

if(

(float)

$request->amount

>

(float)

$currentSaving

){

throw new \Exception(

'Insufficient saving balance.'

);

}

$newBalance=

(float)

$currentSaving

-

(float)

$request->amount;

if(

(float)

$account->current_balance

<

(float)

$request->amount

){

throw new \Exception(

'Insufficient account balance.'

);

}

$account->decrement(

'current_balance',

(float)

$request->amount

);

LoanSavingLedger::create([

'company_id'=>

$companyId,

'loan_account_id'=>

$request->loan_account_id,

'account_id'=>

$request->account_id,

'type'=>

'withdraw',

'amount'=>

$request->amount,

'balance_after'=>

$newBalance,

'date'=>

$request->date,

'note'=>

$request->note,

'created_by'=>

auth()->id(),

'status'=>1

]);

}

);

return redirect()

->route(

'company.loan-payment.index'

)

->with(

'success',

'Saving withdrawn.'

);

}

}