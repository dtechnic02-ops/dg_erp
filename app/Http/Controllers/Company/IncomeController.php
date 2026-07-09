<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Income;
use App\Models\Account;
use App\Models\IncomeCategory;
use App\Models\FinancialYear;

class IncomeController extends Controller
{

public function index(Request $request)
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

$query = Income::with(

    'account',
    'financialYear'

)

->where(

    'company_id',

    auth()->user()->company_id

);

if($request->search){

    $query->where(

        'title',

        'like',

        '%'.$request->search.'%'

    );

}

if($request->from_date){

    $query->whereDate(

        'income_date',

        '>=',

        $request->from_date

    );

}

if($request->to_date){

    $query->whereDate(

        'income_date',

        '<=',

        $request->to_date

    );

}

if($request->financial_year){

    $query->where(

        'financial_year_id',

        $request->financial_year

    );

}
elseif(

    !$request->from_date

    &&

    !$request->to_date

    &&

    $activeFy

){

    $query->where(

        'financial_year_id',

        $activeFy->id

    );

}

$incomes =

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

    'company.income.index',

    compact(

        'incomes',

        'financialYears',

        'activeFy'

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

$companyId = auth()->user()->company_id;

$currentFY = FinancialYear::where([

'company_id'=>$companyId,

'is_active'=>1

])->first();

if(!$currentFY){

return back()->with(

'error',

'Active Financial Year Missing'

);

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

$categories = IncomeCategory::where(

'company_id',

$companyId

)

->where(

'status',

1

)

->orderBy(

'name'

)

->get();

$year = $currentFY->name;

$last = Income::where(
    'company_id',
    $companyId
)
->where(
    'financial_year_id',
    $currentFY->id
)
->latest('id')
->first();

$next = 1;

if($last){

preg_match(

'/(\d+)$/',

$last->income_no,

$m

);

$next = isset($m[1])

? ((int)$m[1])+1

: 1;

}

$incomeNo =

'INC-'

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

'company.income.create',

compact(

'incomeNo',

'accounts',

'categories',

'currentFY'

)

);

}





public function store(Request $request)
{

$request->validate([

'title'=>'required',

'account_id'=>'required',

'category'=>'nullable',

'amount'=>'required|numeric|min:0.01',

'income_date'=>'required|date',

'attachment'=>'nullable|mimes:jpg,jpeg,png,pdf|max:5120'

]);

try{

$companyId = auth()->user()->company_id;

$file = null;

DB::transaction(function()

use(

$request,
$companyId,
&$file

){

$currentFY = FinancialYear::where([

'company_id'=>$companyId,

'is_active'=>1

])->first();

if(!$currentFY){

throw new \Exception(

'Active Financial Year Missing.'

);

}

/* financial year date validation */

$transactionDate = Carbon::parse(
    $request->income_date
);

$startDate = Carbon::parse(
    $currentFY->start_date
);

$endDate = Carbon::parse(
    $currentFY->end_date
);

if(
    $transactionDate->lt($startDate)
    ||
    $transactionDate->gt($endDate)
){
    throw new \Exception(
        'Income date must be inside financial year.'
    );
}

/* duplicate check */

$exists = Income::where([

'company_id'=>$companyId,

'income_no'=>$request->income_no

])->exists();

if($exists){

throw new \Exception(

'Income number already exists.'

);

}

$account = Account::where(

'company_id',

$companyId

)

->findOrFail(

$request->account_id

);

/* upload */

if($request->hasFile('attachment')){

$folder = 'companies/'.$companyId.'/income';

if(!file_exists(public_path($folder))){

mkdir(

public_path($folder),

0777,

true

);

}

$name =

time()

.'_'

.$request
->file('attachment')
->getClientOriginalName();

$request
->file('attachment')
->move(

public_path($folder),

$name

);

$file =

$folder

.'/'

.$name;

}

/* save */

Income::create([

'company_id'=>$companyId,

'financial_year_id'=>$currentFY->id,

'income_no'=>$request->income_no,

'title'=>$request->title,

'account_id'=>$request->account_id,

'amount'=>$request->amount,

'income_date'=>$request->income_date,

'category'=>$request->category,

'attachment'=>$file,

'note'=>$request->note,

'created_by'=>auth()->id(),

'status'=>1

]);

/* update account balance */

$account->increment(

'current_balance',

(float)

$request->amount

);

});

return redirect()

->route(

'company.income.index'

)

->with(

'success',

'Income saved successfully.'

);

}
catch(\Exception $e){

return back()

->withInput()

->with(

'error',

$e->getMessage()

);

}

}
/*
|--------------------------------------------------------------------------
| EDIT Method
|--------------------------------------------------------------------------
*/

public function edit($id)
{

$companyId = auth()->user()->company_id;

$income = Income::with(

'account',
'financialYear'

)

->where(

'company_id',

$companyId

)

->findOrFail($id);

if(

$income->financial_year_id

){

$currentFY = FinancialYear::where([

'id'=>$income->financial_year_id,

'company_id'=>$companyId

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

$categories = IncomeCategory::where(

'company_id',

$companyId

)

->where(

'status',

1

)

->orderBy(

'name'

)

->get();

return view(

'company.income.edit',

compact(

'income',

'accounts',

'categories'

)

);

}

public function update(Request $request,$id)
{

try{

$request->validate([

'title'=>'required',

'account_id'=>'required',

'amount'=>'required|numeric|min:0.01',

'income_date'=>'required|date',

'attachment'=>'nullable|mimes:jpg,jpeg,png,pdf|max:5120'

]);

$companyId = auth()->user()->company_id;

$income = Income::where(

'company_id',

$companyId

)

->findOrFail($id);

/* closed financial year check */

if(

$income->financial_year_id

){

$currentFYCheck = FinancialYear::where([

'id'=>$income->financial_year_id,

'company_id'=>$companyId

])

->first();

if(

$currentFYCheck

&&

!$currentFYCheck->is_active

){

return back()->with(

'error',

'Closed financial year cannot be edited.'

);

}

}

DB::transaction(function()

use(

$request,
$income,
$companyId

){

/* income financial year */
/* income financial year */
$currentFY = FinancialYear::where(
    'company_id',
    $companyId
)
->findOrFail(
    $income->financial_year_id
);


/* date validation */

$transactionDate = Carbon::parse(
    $request->income_date
);

$startDate = Carbon::parse(
    $currentFY->start_date
);

$endDate = Carbon::parse(
    $currentFY->end_date
);

if(
    $transactionDate->lt($startDate)
    ||
    $transactionDate->gt($endDate)
){
    throw new \Exception(
        'Income date must be inside financial year.'
    );
}

/* old account */

$oldAccount = Account::where(

'company_id',

$companyId

)

->findOrFail(

$income->account_id

);

/* balance adjustment */

if(

$income->account_id == $request->account_id

){

$balanceDifference =

(float)$request->amount

-

(float)$income->amount;

$oldAccount->increment(

'current_balance',

$balanceDifference

);

}
else{

$oldAccount->decrement(

'current_balance',

(float)$income->amount

);

$newAccount = Account::where(

'company_id',

$companyId

)

->findOrFail(

$request->account_id

);

$newAccount->increment(

'current_balance',

(float)$request->amount

);

}

/* attachment */

$file = $income->attachment;

$oldFile = $income->attachment;

if($request->hasFile('attachment')){

$folder =

'companies/'

.$companyId

.'/income';

if(

!file_exists(

public_path($folder)

)

){

mkdir(

public_path($folder),

0777,

true

);

}

$name =

time()

.'_'

.$request
->file('attachment')
->getClientOriginalName();

$request
->file('attachment')
->move(

public_path($folder),

$name

);

$file =

$folder

.'/'

.$name;

/* remove old file */

if(

$oldFile

&&

file_exists(

public_path($oldFile)

)

){

unlink(

public_path($oldFile)

);

}

}

/* update */

$income->update([

'title'=>$request->title,

'account_id'=>$request->account_id,

'amount'=>$request->amount,

'income_date'=>$request->income_date,

'category'=>$request->category,

'attachment'=>$file,

'note'=>$request->note

]);

});

return redirect()

->route(

'company.income.index'

)

->with(

'success',

'Income updated successfully.'

);

}
catch(\Exception $e){

return back()

->withInput()

->with(

'error',

$e->getMessage()

);

}

}


/*
|------------------------------------------------------------------
| SHOW
|------------------------------------------------------------------
*/

public function show($id)
{

$income = Income::with(

'account',
'financialYear'

)

->where(

'company_id',

auth()->user()->company_id

)

->findOrFail($id);


return view(

'company.income.show',

compact(

'income'

)

);

}



/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/

public function destroy($id)
{

try{

    $income = Income::where(

        'company_id',

        auth()->user()->company_id

    )

    ->findOrFail($id);

    /* financial year check */

    if(

        $income->financial_year_id

    ){

        $currentFY = FinancialYear::where([

            'id'=>$income->financial_year_id,

            'company_id'=>auth()->user()->company_id

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

    $companyId = auth()->user()->company_id;

    DB::transaction(function()

    use(

        $income,
        $companyId

    ){

        $account = Account::where(

            'company_id',

            $companyId

        )

        ->findOrFail(

            $income->account_id

        );

        $account->decrement(

            'current_balance',

            $income->amount

        );

        if(

            $income->attachment

            &&

            file_exists(

                public_path(

                    $income->attachment

                )

            )

        ){

            unlink(

                public_path(

                    $income->attachment

                )

            );

        }

        $income->delete();

    });

    return back()

        ->with(

            'success',

            'Deleted.'

        );

}
catch(\Exception $e){

    return back()

        ->with(

            'error',

            $e->getMessage()

        );

}

}


/*
|--------------------------------------------------------------------------
|                PRINT Method
|--------------------------------------------------------------------------
*/
public function print(Request $request)
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
$query = Income::with(

'financialYear'

)

->where(

'company_id',

auth()->user()->company_id

);

if($request->from_date){

    $query->whereDate(

        'income_date',

        '>=',

        $request->from_date

    );

}

if($request->to_date){

    $query->whereDate(

        'income_date',

        '<=',

        $request->to_date

    );

}


if($request->financial_year){

    $query->where(

        'financial_year_id',

        $request->financial_year

    );

}
elseif(

    !$request->from_date

    &&

    !$request->to_date

    &&

    $activeFy

){

    $query->where(

        'financial_year_id',

        $activeFy->id

    );

}
$incomes =

$query

->latest()

->get();

return view(

'company.income.print',

compact(

'incomes'

)

);

}


}