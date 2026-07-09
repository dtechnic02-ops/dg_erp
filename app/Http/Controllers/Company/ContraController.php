<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contra;
use App\Models\Account;
use App\Models\FinancialYear;
use Illuminate\Support\Facades\DB;

class ContraController extends Controller
{
    public function index(Request $request)
{

$companyId = auth()->user()->company_id;

$activeFy = FinancialYear::where(

    'company_id',

    $companyId

)

->where(

    'is_active',

    1

)

->first();

$query = Contra::with([

    'fromAccount',

    'toAccount',

    'financialYear'

])

->where(

    'company_id',

    $companyId

);

/*
|--------------------------------------------------------------------------
| Financial Year
|--------------------------------------------------------------------------
*/

if(

    $request->financial_year_id

){

    $query->where(

        'financial_year_id',

        $request->financial_year_id

    );

}
elseif($activeFy){

    $query->where(

        'financial_year_id',

        $activeFy->id

    );

}

/*
|--------------------------------------------------------------------------
| Date Filter
|--------------------------------------------------------------------------
*/

if($request->from_date){

    $query->whereDate(

        'contra_date',

        '>=',

        $request->from_date

    );

}

if($request->to_date){

    $query->whereDate(

        'contra_date',

        '<=',

        $request->to_date

    );

}

/*
|--------------------------------------------------------------------------
| From Account
|--------------------------------------------------------------------------
*/

if($request->from_account_id){

    $query->where(

        'from_account_id',

        $request->from_account_id

    );

}

/*
|--------------------------------------------------------------------------
| To Account
|--------------------------------------------------------------------------
*/

if($request->to_account_id){

    $query->where(

        'to_account_id',

        $request->to_account_id

    );

}

/*
|--------------------------------------------------------------------------
| Contra List
|--------------------------------------------------------------------------
*/

$contras =

$query

->latest()

->paginate(20)

->withQueryString();

/*
|--------------------------------------------------------------------------
| Filter Data
|--------------------------------------------------------------------------
*/

$financialYears = FinancialYear::where(

    'company_id',

    $companyId

)

->latest('id')

->get();

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

    'company.contra.index',

    compact(

        'contras',

        'financialYears',

        'accounts',

        'activeFy'

    )

);

}

/*
|--------------------------------------------------------------------------
| Crate
|--------------------------------------------------------------------------
*/

   
public function create()
{

$companyId = auth()->user()->company_id;

/*
|--------------------------------------------------------------------------
| Active Financial Year
|--------------------------------------------------------------------------
*/

$activeFy = FinancialYear::where(

    'company_id',

    $companyId

)

->where(

    'is_active',

    1

)

->first();

if(!$activeFy){

    return back()->with(

        'error',

        'Please activate financial year first.'

    );

}

/*
|--------------------------------------------------------------------------
| Accounts
|--------------------------------------------------------------------------
*/

$accounts = Account::where(

    'company_id',

    $companyId

)

->where(

    'status',

    1

)

->orderBy(

    'account_name'

)

->get();

/*
|--------------------------------------------------------------------------
| Voucher Number
|--------------------------------------------------------------------------
*/

$fyYear = date(

    'Y',

    strtotime(

        $activeFy->start_date

    )

);

$lastContra = Contra::where(

    'company_id',

    $companyId

)

->where(

    'financial_year_id',

    $activeFy->id

)

->latest('id')

->first();

$next = 1;

if($lastContra){

    $parts = explode(

        '-',

        $lastContra->contra_no

    );

    $next =

        ((int) end($parts))

        + 1;

}

$contraNo =

'CON-'

.$companyId

.'-'

.$fyYear

.'-'

.str_pad(

    $next,

    4,

    '0',

    STR_PAD_LEFT

);

/*
|--------------------------------------------------------------------------
| View
|--------------------------------------------------------------------------
*/

return view(

    'company.contra.create',

    compact(

        'accounts',

        'activeFy',

        'contraNo'

    )

);



}

public function store(Request $request)
{

$request->validate([

    'from_account_id' => 'required',

    'to_account_id' => 'required',

    'contra_date' => 'required|date',

    'amount' => 'required|numeric|min:0.01',

    'attachment' => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120'

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

/*
|--------------------------------------------------------------------------
| Active Financial Year
|--------------------------------------------------------------------------
*/

$activeFy = FinancialYear::where(

    'company_id',

    $companyId

)

->where(

    'is_active',

    1

)

->first();

if(!$activeFy){

    throw new \Exception(

        'Please activate financial year first.'

    );

}

/*
|--------------------------------------------------------------------------
| Date Validation
|--------------------------------------------------------------------------
*/

if(

    $request->contra_date < $activeFy->start_date

    ||

    $request->contra_date > $activeFy->end_date

){

    throw new \Exception(

        'Contra date must be inside active financial year.'

    );

}

/*
|--------------------------------------------------------------------------
| Same Account Validation
|--------------------------------------------------------------------------
*/

if(

    $request->from_account_id

    ==

    $request->to_account_id

){

    throw new \Exception(

        'From account and To account cannot be same.'

    );

}

/*
|--------------------------------------------------------------------------
| Accounts
|--------------------------------------------------------------------------
*/

$fromAccount = Account::where(

    'company_id',

    $companyId

)

->findOrFail(

    $request->from_account_id

);

$toAccount = Account::where(

    'company_id',

    $companyId

)

->findOrFail(

    $request->to_account_id

);

/*
|--------------------------------------------------------------------------
| Balance Check
|--------------------------------------------------------------------------
*/

if(

    $fromAccount->current_balance

    <

    $request->amount

){

    throw new \Exception(

        'Insufficient account balance.'

    );

}

/*
|--------------------------------------------------------------------------
| Voucher Number
|--------------------------------------------------------------------------
*/

$fyYear = date(

    'Y',

    strtotime(

        $activeFy->start_date

    )

);

$lastContra = Contra::where(

    'company_id',

    $companyId

)

->where(

    'financial_year_id',

    $activeFy->id

)

->latest('id')

->first();

$next = 1;

if($lastContra){

    $parts = explode(

        '-',

        $lastContra->contra_no

    );

    $next =

        ((int) end($parts))

        + 1;

}

$contraNo =

'CON-'

.$companyId

.'-'

.$fyYear

.'-'

.str_pad(

    $next,

    4,

    '0',

    STR_PAD_LEFT

);

/*
|--------------------------------------------------------------------------
| Attachment Upload
|--------------------------------------------------------------------------
*/

if($request->hasFile('attachment')){

    $folder =

    'companies/'

    .$companyId

    .'/contra';

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

}



$transferType = 'bank_to_bank';



Contra::create([

    'company_id' => $companyId,

    'financial_year_id' => $activeFy->id,

    'contra_no' => $contraNo,

    'contra_date' => $request->contra_date,

    'from_account_id' => $request->from_account_id,

    'to_account_id' => $request->to_account_id,

    'amount' => $request->amount,

    'transfer_type' => $transferType,

    'reference_no' => $request->reference_no,

    'note' => $request->note,

    'attachment' => $file,

    'created_by' => auth()->id(),

    'status' => 1

]);


$fromAccount->decrement(

    'current_balance',

    (float)

    $request->amount

);

$toAccount->increment(

    'current_balance',

    (float)

    $request->amount

);

});

return redirect()
->route(
    'company.contra.index'
)
->with(
    'success',
    'Contra created successfully.'
);

}
catch(\Exception $e){

if(

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

->withInput()

->with(

    'error',

    $e->getMessage()

);

}
}

public function edit($id)
{

$companyId = auth()->user()->company_id;

$contra = Contra::where(


'company_id',

$companyId


)

->findOrFail($id);

$accounts = Account::where(

'company_id',

$companyId


)

->where(

'status',

1


)

->orderBy(
'account_name'


)

->get();

return view(

'company.contra.edit',

compact(

    'contra',

    'accounts'

)

);

}

public function update(Request $request,$id)
{

$request->validate([

'from_account_id' => 'required',

'to_account_id' => 'required',

'contra_date' => 'required|date',

'amount' => 'required|numeric|min:0.01',

'attachment' => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120'


]);

try{

$companyId = auth()->user()->company_id;

$contra = Contra::where(


'company_id',

$companyId


)

->findOrFail($id);

DB::transaction(function()

use(


$request,
$contra,
$companyId


){

$currentFY = FinancialYear::where(


'company_id',

$companyId


)

->where(

'is_active',

1


)

->first();

if(!$currentFY){

throw new \Exception(

    'Please activate financial year first.'

);


}

if(

$request->contra_date < $currentFY->start_date

||

$request->contra_date > $currentFY->end_date


){


throw new \Exception(

    'Contra date must be inside active financial year.'

);


}

if(


$request->from_account_id

==

$request->to_account_id


){


throw new \Exception(

    'From account and To account cannot be same.'

);


}

$oldFrom = Account::where(


'company_id',

$companyId


)

->findOrFail(


$contra->from_account_id


);

$oldTo = Account::where(


'company_id',

$companyId


)

->findOrFail(


$contra->to_account_id


);

$oldFrom->increment(


'current_balance',

(float)$contra->amount


);

$oldTo->decrement(


'current_balance',

(float)$contra->amount


);


$newFrom = Account::where(


'company_id',

$companyId


)

->findOrFail(


$request->from_account_id


);

$newTo = Account::where(

'company_id',

$companyId


)

->findOrFail(

$request->to_account_id


);

if(


$newFrom->current_balance

<

$request->amount


){


throw new \Exception(

    'Insufficient account balance.'

);


}

$newFrom->decrement(


'current_balance',

(float)$request->amount


);

$newTo->increment(

'current_balance',

(float)$request->amount


);


$file = $contra->attachment;

if($request->hasFile('attachment')){


$folder =

'companies/'

.$companyId

.'/contra';

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

if(

    $contra->attachment

    &&

    file_exists(

        public_path(

            $contra->attachment

        )

    )

){

    unlink(

        public_path(

            $contra->attachment

        )

    );

}


}



$contra->update([


'contra_date' => $request->contra_date,

'from_account_id' => $request->from_account_id,

'to_account_id' => $request->to_account_id,

'amount' => $request->amount,

'reference_no' => $request->reference_no,

'note' => $request->note,

'attachment' => $file


]);

});

return redirect()

->route(

'company.contra.index'

)

->with(
    'success',
    'Contra updated successfully.'
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
public function destroy($id)
{

try{

$companyId = auth()->user()->company_id;

$contra = Contra::where(


'company_id',

$companyId


)

->findOrFail($id);

DB::transaction(function()

use(


$contra,
$companyId


){


$fromAccount = Account::where(

'company_id',

$companyId


)

->findOrFail(


$contra->from_account_id


);

$toAccount = Account::where(

'company_id',

$companyId


)

->findOrFail(


$contra->to_account_id


);

$fromAccount->increment(


'current_balance',

(float)$contra->amount


);

$toAccount->decrement(


'current_balance',

(float)$contra->amount


);


if(


$contra->attachment

&&

file_exists(

    public_path(

        $contra->attachment

    )

)


){

unlink(

    public_path(

        $contra->attachment

    )

);


}



$contra->delete();

});

return redirect()

->route(

'company.contra.index'


)

->with(


'success',

'Contra deleted successfully.'


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



public function show($id)
{

$contra = Contra::with(

    'fromAccount',
    'toAccount',
    'financialYear'

)

->where(

    'company_id',

    auth()->user()->company_id

)

->findOrFail($id);

return view(

    'company.contra.show',

    compact(

        'contra'

    )

);

}

public function print(Request $request)
{
    if($request->from_account_id){
    $query->where('from_account_id',$request->from_account_id);
}

if($request->to_account_id){
    $query->where('to_account_id',$request->to_account_id);
}

$companyId = auth()->user()->company_id;

$activeFy = FinancialYear::where(


'company_id',

$companyId


)

->where(


'is_active',

1


)

->first();

$query = Contra::with([


'fromAccount',

'toAccount',

'financialYear'


])

->where(


'company_id',

$companyId


);



if($request->financial_year_id){


$query->where(

    'financial_year_id',

    $request->financial_year_id

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


if($request->from_date){


$query->whereDate(

    'contra_date',

    '>=',

    $request->from_date

);

}

if($request->to_date){


$query->whereDate(

    'contra_date',

    '<=',

    $request->to_date

);


}


if($request->from_account_id){

$query->where(

    'from_account_id',

    $request->from_account_id

);


}


if($request->to_account_id){


$query->where(

    'to_account_id',

    $request->to_account_id

);


}

$contras =

$query

->latest()

->get();

return view(

'company.contra.print',

compact(

    'contras'

)


);

}

}

