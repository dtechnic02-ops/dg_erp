<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Account;
use App\Models\FinancialYear;
class JournalController extends Controller
{

/*
|--------------------------------------------------------------------------
| INDEX
|--------------------------------------------------------------------------
*/

public function index()
{

$query = Journal::with(

'financialYear'

)->where(

'company_id',

auth()->user()->company_id

);


if(request('search')){

$query->where(

'journal_no',

'like',

'%'.request('search').'%'

);

}
if(
request('from_date')
&&
request('to_date')
){

$query->whereBetween(

'journal_date',

[

request('from_date'),

request('to_date')

]

);

}elseif(

request('from_date')

){
    

$query->whereDate(

'journal_date',

'>=',

request('from_date')

);

}elseif(

request('to_date')

){

$query->whereDate(

'journal_date',

'<=',

request('to_date')

);

}




if(request('financial_year')){

$query->where(

'financial_year_id',

request('financial_year')

);

}


$journals =

$query

->latest()

->paginate(20)

->withQueryString();


$financialYears = FinancialYear::where(

'company_id',

auth()->user()->company_id

)

->get();


return view(

'company.journal.index',

compact(

'journals',

'financialYears'

)

);

}

/*
|--------------------------------------------------------------------------
| CREATE
|--------------------------------------------------------------------------
*/

public function create()
{

$companyId=

auth()->user()->company_id;


$currentFY=

FinancialYear::where([

'company_id'=>$companyId,

'is_active'=>1

])->first();
    
if(!$currentFY){

return redirect()

->route(

'company.financial-years.index'

)

->with(

'error',

'Please create active financial year.'

);

}


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

$currentFY->name;

$last = Journal::where(

'company_id',

$companyId

)

->where(

'financial_year_id',

$currentFY->id

)

->latest('id')

->first();

$next=1;

if($last){

preg_match(

'/(\d+)$/',

$last->journal_no,

$m

);

$next=

isset($m[1])

?

((int)$m[1])+1

:

1;

}

$journalNo=

'JV-'

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

'company.journal.create',

compact(

'journalNo',

'accounts',

'currentFY'

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
    $file = null;

$request->validate([

'journal_no'=>'required',

'journal_date'=>'required|date',

'account_id'=>'required|array',
'account_id.*'=>'required|integer',

'type'=>'required|array',

'type.*'=>'required|in:debit,credit',

'amount'=>'required|array',

'amount.*'=>'required|numeric|min:0.01',

'attachment'=>'nullable|mimes:jpg,jpeg,png,pdf|max:5120'

]);



$debitTotal=0;

$creditTotal=0;

foreach(

$request->amount as $k=>$amt

){

$value=

(float)

$amt;

if(

$request->type[$k]

=='debit'

){

$debitTotal += $value;

}else{

$creditTotal += $value;

}

}

if(

round($debitTotal,2)

!=

round($creditTotal,2)

){

return back()

->withErrors([

'error'=>

'Debit and Credit must match.'

])

->withInput();

}



try{

$companyId = auth()->user()->company_id;



if($request->hasFile('attachment')){

$folder='companies/'.$companyId.'/journals';

if(!file_exists(public_path($folder))){

mkdir(
public_path($folder),
0755,
true
);

}

$name=time().'_'.$request

->file('attachment')

->getClientOriginalName();

$request

->file('attachment')

->move(

public_path($folder),

$name

);

$file=$folder.'/'.$name;

}

DB::transaction(function()

use(

$request,
$debitTotal,
$companyId,
$file

){

$currentFY =

FinancialYear::where([

'company_id'=>$companyId,

'is_active'=>1

])->first();


if(!$currentFY){

throw new \Exception(

'Active financial year missing.'

);

}
$exists = Journal::where([

'company_id'=>$companyId,

'journal_no'=>$request->journal_no

])->exists();


if($exists){

throw new \Exception(

'Journal number already exists.'

);

}

$journal=

Journal::create([

'company_id'=>$companyId,

'financial_year_id'=>$currentFY->id,

'journal_no'=>$request->journal_no,


'journal_date'=>$request->journal_date,

'total_amount'=>$debitTotal,

'attachment'=>$file,

'note'=>$request->note,

'created_by'=>auth()->id(),

'status'=>1

]);


foreach(

$request->account_id as $k=>$accountId

){

$amount=(float)

$request->amount[$k];

$type=

$request->type[$k];

$account=

Account::where(

'company_id',

$companyId

)

->findOrFail(

$accountId

);


/*
Balance Check
*/

if(

$type=='credit'

&&

(float)

$account->current_balance

<

$amount

){

throw new \Exception(

'Insufficient balance in account: '

.$account->account_name

);

}


JournalItem::create([

'company_id'=>$companyId,

'journal_id'=>$journal->id,

'account_id'=>$accountId,

'type'=>$type,

'amount'=>$amount,

'note'=>$request->row_note[$k] ?? null,

'status'=>1

]);


if(

$type=='debit'

){

$account->increment(

'current_balance',

$amount

);

}else{

$account->decrement(

'current_balance',

$amount

);

}

}

});

return redirect()

->route(

'company.journal.index'

)

->with(

'success',

'Journal saved.'

);

}

catch(\Exception $e){

if(

$request->hasFile('attachment')

&&

$file

&&

file_exists(

public_path($file)

)

){

unlink(

public_path($file)

);

}

return back()

->withErrors([

'error'=>$e->getMessage()

])

->withInput();

}
}
/*
|--------------------------------------------------------------------------
| SHOW
|--------------------------------------------------------------------------
*/

public function show($id)
{

$journal=

Journal::with(

'items.account'

)

->where(

'company_id',

auth()->user()->company_id

)

->findOrFail($id);

return view(

'company.journal.show',

compact(

'journal'

)

);

}

/*
EDIT
--------------------------------------------------------------------------
*/
public function edit($id)
{

$companyId = auth()->user()->company_id;

$journal = Journal::with(

'items',
'financialYear'

)

->where(

'company_id',

$companyId

)

->findOrFail($id);

if(

$journal->financial_year_id

){

$currentFY = FinancialYear::where([

'id' => $journal->financial_year_id,

'company_id' => $companyId

])->first();

if(

$currentFY

&&

!$currentFY->is_active

){

return back()->with(

'error',

'Closed financial year cannot be edited.'

);

}

}

$accounts = Account::where(

'company_id',

$companyId

)

->where(

'status',

1

)

->get();

return view(

'company.journal.edit',

compact(

'journal',

'accounts'

)

);

}

/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

public function update(Request $request,$id)
{

$request->validate([

'attachment'=>'nullable|mimes:jpg,jpeg,png,pdf|max:5120',

'journal_no'=>'required',

'journal_date'=>'required|date',

'account_id'=>'required|array',

'account_id.*'=>'required|integer',

'type'=>'required|array',

'type.*'=>'required|in:debit,credit',

'amount'=>'required|array',

'amount.*'=>'required|numeric|min:0.01'

]);



/* debit credit check */

$debitTotal=0;

$creditTotal=0;

foreach($request->amount as $k=>$amt){

$value=(float)$amt;

if($request->type[$k]=='debit'){

$debitTotal += $value;

}else{

$creditTotal += $value;

}

}

if(round($debitTotal,2)!=round($creditTotal,2)){

return back()

->withErrors([

'error'=>'Debit and Credit must match.'

])

->withInput();

}



/* load journal */

$journal = Journal::with(

'items',

'financialYear'

)

->where(

'company_id',

auth()->user()->company_id

)

->findOrFail($id);



/* duplicate journal no check */

$exists = Journal::where(

'company_id',

auth()->user()->company_id

)

->where(

'journal_no',

$request->journal_no

)

->where(

'id',

'!=',

$id

)

->exists();

if($exists){

return back()

->withInput()

->withErrors([

'error'=>'Journal number already exists.'

]);

}

$oldFile = $journal->attachment;

$file = $journal->attachment;

try{

if($journal->financial_year_id){

$currentFY = FinancialYear::where([

'id'=>$journal->financial_year_id,

'company_id'=>auth()->user()->company_id

])->first();

if(

$currentFY

&&

!$currentFY->is_active

){

return back()->with(

'error',

'Closed financial year cannot be edited.'

);

}

}

$companyId = auth()->user()->company_id;

if($request->hasFile('attachment')){

$folder='companies/'.$companyId.'/journals';

if(!file_exists(public_path($folder))){

mkdir(

public_path($folder),

0755,

true

);

}

$name=time().'_'.$request

->file('attachment')

->getClientOriginalName();

$request

->file('attachment')

->move(

public_path($folder),

$name

);

$file=$folder.'/'.$name;

}



DB::transaction(function()

use(

$request,

$journal,

$debitTotal,

$companyId,

$file

){


/* reverse old balances */

foreach($journal->items as $item){

$account = Account::where(

'company_id',

$companyId

)

->findOrFail(

$item->account_id

);

if($item->type=='debit'){

$account->decrement(

'current_balance',

$item->amount

);

}else{

$account->increment(

'current_balance',

$item->amount

);

}

}



/* delete old items */

$journal->items()->delete();



/* create new items */

foreach(

$request->account_id as $k=>$accountId

){

$amount=(float)

$request->amount[$k];

$type=

$request->type[$k];


$account = Account::where(

'company_id',

$companyId

)

->findOrFail(

$accountId

);


/* balance check */

if(

$type=='credit'

&&

(float)

$account->current_balance

<

$amount

){

throw new \Exception(

'Insufficient balance in '

.$account->account_name

);

}



/* create item */

JournalItem::create([

'company_id'=>$companyId,

'journal_id'=>$journal->id,

'account_id'=>$accountId,

'type'=>$type,

'amount'=>$amount,

'note'=>$request->row_note[$k] ?? null,

'status'=>1

]);



/* apply balances */

if($type=='debit'){

$account->increment(

'current_balance',

$amount

);

}else{

$account->decrement(

'current_balance',

$amount

);

}

}



/* update journal */

$journal->update([

'journal_no'=>$request->journal_no,

'journal_date'=>$request->journal_date,

'total_amount'=>$debitTotal,

'attachment'=>$file,

'note'=>$request->note

]);
});

if(

$request->hasFile('attachment')

&&

$oldFile

&&

$file != $oldFile

&&

file_exists(

public_path($oldFile)

)

){

unlink(

public_path($oldFile)

);

}

return redirect()

->route(

'company.journal.index'

)

->with(

'success',

'Updated.'

);



}

catch(\Exception $e){

if(

$request->hasFile('attachment')

&&

$file

&&

$file != $oldFile

&&

file_exists(

public_path($file)

)

){

unlink(

public_path($file)

);

}

return back()

->withInput()

->withErrors([

'error'=>$e->getMessage()

]);

}
}
/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/

public function destroy($id)
{

$companyId = auth()->user()->company_id;

$journal = Journal::with(

'items'

)

->where(

'company_id',

$companyId

)

->findOrFail($id);



/* financial year check */

if(

$journal->financial_year_id

){

$currentFY = FinancialYear::where([

'id'=>$journal->financial_year_id,

'company_id'=>$companyId

])->first();


if(

$currentFY

&&

!$currentFY->is_active

){

return back()->with(

'error',

'Closed financial year cannot be deleted.'

);

}

}



try{

$attachment = $journal->attachment;

DB::transaction(function()

use(

$journal,
$companyId

){


/* reverse balances */

foreach(

$journal->items as $item

){

$account = Account::where(

'company_id',

$companyId

)

->findOrFail(

$item->account_id

);


if(

$item->type=='debit'

){

$account->decrement(

'current_balance',

$item->amount

);

}else{

$account->increment(

'current_balance',

$item->amount

);

}

}



/* delete items */

$journal->items()->delete();


/* delete journal */

$journal->delete();

});


/* delete attachment AFTER successful transaction */

if(

$attachment

&&

file_exists(

public_path(

$attachment

)

)

){

unlink(

public_path(

$attachment

)

);

}



return back()

->with(

'success',

'Deleted.'

);

}catch(\Exception $e){

return back()

->withErrors([

'error'=>$e->getMessage()

]);

}

}

// print 
public function print()
{

$query = Journal::with(

'financialYear'

)

->where(

'company_id',

auth()->user()->company_id

);

if(request('search')){

$query->where(

'journal_no',

'like',

'%'.request('search').'%'

);

}

if(

request('from_date')

&&

request('to_date')

){

$query->whereBetween(

'journal_date',

[

request('from_date'),

request('to_date')

]

);

}elseif(

request('from_date')

){

$query->whereDate(

'journal_date',

'>=',

request('from_date')

);

}elseif(

request('to_date')

){

$query->whereDate(

'journal_date',

'<=',

request('to_date')

);

}

if(request('financial_year')){

$query->where(

'financial_year_id',

request('financial_year')

);

}

$journals =

$query

->latest()

->get();

return view(

'company.journal.print',

compact(

'journals'

)

);

}


}