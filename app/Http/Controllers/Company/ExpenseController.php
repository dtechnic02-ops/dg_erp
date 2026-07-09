<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Services\AccountBalanceService;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Expense;
use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Models\FinancialYear;
use App\Services\FileUploadService;
use App\Services\ValidationService;

class ExpenseController extends Controller
{

/*
|--------------------------------------------------------------------------
| INDEX
|--------------------------------------------------------------------------
*/

public function index(Request $request)
{

$companyId =
auth()->user()->company_id;

$query = Expense::with([

'category',

'account'

])


->where(
'company_id',
$companyId
);
$financialYears = FinancialYear::where(
    'company_id',
    $companyId
)
->latest('id')
->get();

$activeFy = FinancialYear::where(
    'company_id',
    $companyId
)
->where(
    'is_active',
    1
)
->first();

if ($request->financial_year_id)
{
    $query->where(
        'financial_year_id',
        $request->financial_year_id
    );
}
elseif(
    !$request->start_date
    &&
    !$request->end_date
    &&
    $activeFy
)
{
    $query->where(
        'financial_year_id',
        $activeFy->id
    );
}
if($request->search){

    $query->where(function($q) use ($request){

        $q->where(
            'expense_no',
            'like',
            '%'.$request->search.'%'
        )
        ->orWhere(
            'reference_no',
            'like',
            '%'.$request->search.'%'
        );

    });

}

if($request->expense_category_id){

$query->where(

'expense_category_id',

$request->expense_category_id

);

}

if($request->account_id){

$query->where(

'account_id',

$request->account_id

);

}

if($request->start_date){

$query->whereDate(

'expense_date',

'>=',

$request->start_date

);

}

if($request->end_date){

$query->whereDate(

'expense_date',

'<=',

$request->end_date

);

}

$expenses =

$query

->latest()

->paginate(20)

->withQueryString();

$categories =
ExpenseCategory::where(
'company_id',
$companyId
)->get();

$accounts =
Account::where(
'company_id',
$companyId
)->get();

return view(

'company.expense.index',

compact(
    'expenses',
    'categories',
    'accounts',
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

$companyId =
auth()->user()->company_id;

$categories =
ExpenseCategory::where(

'company_id',

$companyId

)->get();

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


$activeFy = FinancialYear::where(
    'company_id',
    $companyId
)
->where(
    'is_active',
    1
)
->first();

if (!$activeFy)
{
    return back()->with(
        'error',
        'Please activate financial year first.'
    );
}

$fyYear = $activeFy->name;

$lastExpense =
Expense::where(

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

if($lastExpense){

$parts =
explode(
'-',
$lastExpense->expense_no
);

$next =
((int) end($parts))
+1;

}
$expenseNo =

'EXP-'.

$companyId.

'-'.

$fyYear.

'-'.

str_pad(
    $next,
    4,
    '0',
    STR_PAD_LEFT
);
return view(

'company.expense.create',

compact(
'categories',
'accounts',
'expenseNo',
'activeFy'
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


    'expense_category_id' => 'required',

    'account_id' => 'required',

    'expense_date' => 'required|date',
'amount' =>
ValidationService::requiredAmount(),

'attachment' =>
ValidationService::document(),

]);

$file = null;

try {

    $expense = DB::transaction(

        function () use ($request, &$file) {

            $companyId =
                auth()->user()->company_id;

            $activeFy = FinancialYear::where(
                'company_id',
                $companyId
            )
            ->where(
                'is_active',
                1
            )
            ->firstOrFail();

          $transactionDate = Carbon::parse(
    $request->expense_date
);

$startDate = Carbon::parse(
    $activeFy->start_date
);

$endDate = Carbon::parse(
    $activeFy->end_date
);

if(
    $transactionDate->lt($startDate)
    ||
    $transactionDate->gt($endDate)
){
    throw new \Exception(
        'Expense date must be inside financial year.'
    );
}
           
            
$fyYear = $activeFy->name;
            $last = Expense::where(
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

            if ($last)
            {
                $parts = explode(
                    '-',
                    $last->expense_no
                );

                $next =
                    ((int) end($parts))
                    + 1;
            }

            $expenseNo =
                'EXP-' .
                $companyId .
                '-' .
                $fyYear .
                '-' .
                str_pad(
                    $next,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

           
            $folder =
    'companies/'
    .$companyId
    .'/expenses';

if (
    $request->hasFile(
        'attachment'
    )
)
{
    $file =
        FileUploadService::uploadFile(
            $request->file(
                'attachment'
            ),
            $folder
        );
}

            $expense = Expense::create([

                'company_id' =>
                    $companyId,

                'financial_year_id' =>
                    $activeFy->id,

                'expense_no' =>
                    $expenseNo,

                'expense_category_id' =>
                    $request->expense_category_id,

                'account_id' =>
                    $request->account_id,

                'expense_date' =>
                    $request->expense_date,

                'amount' =>
                    $request->amount,

                'reference_no' =>
                    $request->reference_no,

                'note' =>
                    $request->note,

                'attachment' =>
                    $file,

                'created_by' =>
                    auth()->id(),

                'status' => 1

            ]);

            AccountBalanceService::createTransaction([

    'company_id' =>
    $companyId,

    'financial_year_id' =>
    $activeFy->id,

    'account_id' =>
    $request->account_id,

    'transaction_date' =>
    $request->expense_date,

    'voucher_no' =>
    $expense->expense_no,

    'reference_type' =>
    'Expense',

    'reference_id' =>
    $expense->id,

    'description' =>
    'Expense Payment',

    'debit' =>
    0,

    'credit' =>
    $request->amount

]);

            return $expense;
        }

    );

    return redirect()
        ->route(
            'company.expense.show',
            $expense->id
        )
        ->with(
            'success',
            'Expense created successfully.'
        );

}
catch (\Exception $e)
{
  FileUploadService::deleteFile(
    $file
);

    return back()
        ->withInput()
        ->with(
            'error',
            $e->getMessage()
        );


}
}
public function update(Request $request, $id)
{

$request->validate([

    'expense_category_id' => 'required',

    'account_id' => 'required',

    'expense_date' => 'required|date',

    'amount' => 'required|numeric|min:0.01',

    'attachment' =>
        'nullable|mimes:jpg,jpeg,png,pdf|max:2048'

]);

try{

$companyId = auth()->user()->company_id;

$expense = Expense::where(

    'company_id',

    $companyId

)

->findOrFail($id);

/* closed financial year check */

if(

    $expense->financial_year_id

){

    $expenseFy = FinancialYear::where([

        'id' => $expense->financial_year_id,

        'company_id' => $companyId

    ])

    ->first();

    if(

        $expenseFy

        &&

        !$expenseFy->is_active

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
    $expense,
    $companyId

){

$currentFY = FinancialYear::where(
    'company_id',
    $companyId
)
->where(
    'id',
    $expense->financial_year_id
)
->firstOrFail();

   $transactionDate = Carbon::parse(
    $request->expense_date
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
        'Expense date must be inside financial year.'
    );
}
$transaction =
AccountTransaction::where(
    'reference_type',
    'Expense'
)
->where(
    'reference_id',
    $expense->id
)
->first();

if (
    !$transaction
)
{
    throw new \Exception(
        'Account transaction not found.'
    );
}

AccountBalanceService::updateTransaction(
    $transaction,
    [

        'company_id' =>
        $companyId,

        'financial_year_id' =>
        $expense->financial_year_id,

        'account_id' =>
        $request->account_id,

        'transaction_date' =>
        $request->expense_date,

        'voucher_no' =>
        $expense->expense_no,

        'reference_type' =>
        'Expense',

        'reference_id' =>
        $expense->id,

        'description' =>
        'Expense Payment',

        'debit' =>
        0,

        'credit' =>
        $request->amount

    ]
);

    $folder =
    'companies/'
    .$companyId
    .'/expenses';
$file =
    $expense->attachment;

$file =
    FileUploadService::replaceFile(
        $request,
        'attachment',
        $file,
        $folder
    );

$expense->update([

    'expense_category_id' =>
        $request->expense_category_id,

    'account_id' =>
        $request->account_id,

    'expense_date' =>
        $request->expense_date,

    'amount' =>
        $request->amount,

    'reference_no' =>
        $request->reference_no,

    'note' =>
        $request->note,

    'attachment' =>
        $file

]);

});

return redirect()

    ->route(

        'company.expense.show',

        $expense->id

    )

    ->with(

        'success',

        'Expense updated successfully.'

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

    $expense = Expense::where(

        'company_id',

        $companyId

    )

    ->findOrFail($id);

    /* closed financial year check */

    if(

        $expense->financial_year_id

    ){

        $expenseFy = FinancialYear::where([

            'id' => $expense->financial_year_id,

            'company_id' => $companyId

        ])

        ->first();

        if(

            $expenseFy

            &&

            !$expenseFy->is_active

        ){

            return back()->with(

                'error',

                'Closed financial year cannot be deleted.'

            );

        }

    }

    DB::transaction(function()

    use(

        $expense,
        $companyId

    ){

      $transaction =
AccountTransaction::where(
    'reference_type',
    'Expense'
)
->where(
    'reference_id',
    $expense->id
)
->first();

if ($transaction)
{
    AccountBalanceService::deleteTransaction(
        $transaction
    );
}
FileUploadService::deleteFile(
    $expense->attachment
);
        
        $expense->delete();

    });

    return back()

        ->with(

            'success',

            'Expense deleted successfully.'

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

public function edit($id)
{

$companyId = auth()->user()->company_id;

$expense = Expense::where(

    'company_id',

    $companyId

)

->findOrFail($id);

/* closed financial year check */

if(

    $expense->financial_year_id

){

    $expenseFy = FinancialYear::where([

        'id' => $expense->financial_year_id,

        'company_id' => $companyId

    ])

    ->first();

    if(

        $expenseFy

        &&

        !$expenseFy->is_active

    ){

        return back()->with(

            'error',

            'Closed financial year cannot be edited.'

        );

    }

}

$categories = ExpenseCategory::where(

    'company_id',

    $companyId

)

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

    'company.expense.edit',

    compact(

        'expense',

        'categories',

        'accounts'

    )

);

}


public function print(Request $request)
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

$query = Expense::with([
    'category',
    'account'
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

    !$request->start_date

    &&

    !$request->end_date

    &&

    $activeFy

){

    $query->where(
        'financial_year_id',
        $activeFy->id
    );

}
if($request->search){

    $query->where(function($q) use ($request){

        $q->where(
            'expense_no',
            'like',
            '%'.$request->search.'%'
        )
        ->orWhere(
            'reference_no',
            'like',
            '%'.$request->search.'%'
        );

    });

}

if($request->expense_category_id){

    $query->where(
        'expense_category_id',
        $request->expense_category_id
    );

}

if($request->account_id){

    $query->where(
        'account_id',
        $request->account_id
    );

}

if($request->start_date){

    $query->whereDate(
        'expense_date',
        '>=',
        $request->start_date
    );

}

if($request->end_date){

    $query->whereDate(
        'expense_date',
        '<=',
        $request->end_date
    );

}

$expenses = $query
    ->latest()
    ->get();

return view(
    'company.expense.print',
    compact(
        'expenses'
    )
);

}




/*
|--------------------------------------------------------------------------
| SHOW
|--------------------------------------------------------------------------
*/

public function show($id)
{

$expense =
Expense::with([

'category',

'account'

])

->where(

'company_id',

auth()->user()->company_id

)

->findOrFail($id);

return view(

'company.expense.show',

compact(

'expense'

)

);

}

}