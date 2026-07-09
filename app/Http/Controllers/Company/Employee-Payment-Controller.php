<?php

namespace App\Http\Controllers\Company;
use App\Services\ValidationService;
use App\Services\FileUploadService;
use App\Services\AccountBalanceService;
use App\Models\AccountTransaction;
use App\Http\Controllers\Controller;
use App\Models\EmployeePayment;
use App\Models\EmployeeAccount;
use App\Models\FinancialYear;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeePaymentController extends Controller
{

public function index(Request $request)
{
    $companyId =
        auth()->user()->company_id;

    $query =
        EmployeePayment::where(
            'company_id',
            $companyId
        );

    $startDate = null;

    $endDate = null;

    /*
    Active Financial Year
    */
    if (
        !$request->has(
            'financial_year_id'
        )
    ) {

        $activeFy =
            FinancialYear::where(
                'company_id',
                $companyId
            )
            ->where(
                'is_active',
                1
            )
            ->first();

        if ($activeFy) {

            $startDate =
                $activeFy->start_date;

            $endDate =
                $activeFy->end_date;

            $query
                ->whereDate(
                    'payment_date',
                    '>=',
                    $startDate
                )
                ->whereDate(
                    'payment_date',
                    '<=',
                    $endDate
                );

        }

    }
    /*
    Selected Financial Year
    */
    else {

        $financialYear =
            FinancialYear::where(
                'company_id',
                $companyId
            )
            ->find(
                $request->financial_year_id
            );

        if ($financialYear) {

            $startDate =
                $financialYear->start_date;

            $endDate =
                $financialYear->end_date;

            $query
                ->whereDate(
                    'payment_date',
                    '>=',
                    $startDate
                )
                ->whereDate(
                    'payment_date',
                    '<=',
                    $endDate
                );

        }

    }

    /*
    Search
    */
    if (
        $request->search
    ) {

        $query->whereHas(
            'employee',
            function ($q)
            use (
                $request
            ) {

                $q->where(
                    'employee_code',
                    'like',
                    '%'
                    .$request->search
                    .'%'
                )
                ->orWhere(
                    'first_name',
                    'like',
                    '%'
                    .$request->search
                    .'%'
                );

            }
        );

    }

    $employeePayments =
        $query
        ->latest()
        ->paginate(20)
        ->withQueryString();

    $financialYears =
        FinancialYear::where(
            'company_id',
            $companyId
        )
        ->latest()
        ->get();

    return view(
        'company.employee-payment.index',
        compact(
            'employeePayments',
            'financialYears',
            'startDate',
            'endDate'
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

    /*
    Active Financial Year
    */
    $activeFy =
        FinancialYear::where(
            'company_id',
            $companyId
        )
        ->where(
            'is_active',
            1
        )
        ->first();

    if (
        !$activeFy
    ) {

        return back()
            ->with(
                'error',
                'Active financial year not found.'
            );

    }

    /*
    Voucher Number
    */
    $lastPayment =
        EmployeePayment::where(
            'company_id',
            $companyId
        )
        ->where(
            'financial_year_id',
            $activeFy->id
        )
        ->latest(
            'id'
        )
        ->first();

    $next = 1;

    if (
        $lastPayment
    ) {

        preg_match(
            '/(\d+)$/',
            $lastPayment->voucher_no,
            $match
        );

        $next =
            isset(
                $match[1]
            )
            ?
            (
                (int)
                $match[1]
            ) + 1
            :
            1;

    }

    $voucherNo =
        'EP-'
        .$companyId
        .'-'
        .$activeFy->id
        .'-'
        .str_pad(
            $next,
            4,
            '0',
            STR_PAD_LEFT
        );

    /*
    Employees
    */
    $employees =
        EmployeeAccount::where(
            'company_id',
            $companyId
        )
        ->where(
            'status',
            1
        )
        ->orderBy(
            'first_name'
        )
        ->get();

    /*
    Accounts
    */
    $accounts =
        Account::where(
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
    'company.employee-payment.create',
    compact(
        'voucherNo',
        'employees',
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

        'employee_account_id' =>
        'required|exists:employee_accounts,id',
        'voucher_no' =>
'required|unique:employee_payments,voucher_no',

        'payment_date' =>
        'required|date',

        'salary_year' =>
        'required|integer',

        'salary_month' =>
        'required|integer|min:1|max:12',

        'account_id' =>
        'required|exists:accounts,id',

        'amount' =>
        ValidationService::requiredAmount(),

        'attachment' =>
        ValidationService::document(),

    ]);

    $companyId =
        auth()->user()->company_id;

    /*
    Active Financial Year
    */
    $activeFy =
        FinancialYear::where(
            'company_id',
            $companyId
        )
        ->where(
            'is_active',
            1
        )
        ->first();

    if (
        !$activeFy
    ) {

        return back()
            ->withInput()
            ->with(
                'error',
                'Active financial year not found.'
            );

    }

    /*
    Date Validation
    */
    $transactionDate =
        Carbon::parse(
            $request->payment_date
        );

    $startDate =
        Carbon::parse(
            $activeFy->start_date
        );

    $endDate =
        Carbon::parse(
            $activeFy->end_date
        );

    if (
        $transactionDate->lt(
            $startDate
        )
        ||
        $transactionDate->gt(
            $endDate
        )
    ) {

        return back()
            ->withInput()
            ->with(
                'error',
                'Payment date is outside active financial year.'
            );

    }

    /*
    Upload
    */
    $attachment = null;

    if (
        $request->hasFile(
            'attachment'
        )
    ) {

        $attachment =
            FileUploadService::uploadFile(
                $request->file(
                    'attachment'
                ),
                'companies/'
                .$companyId
                .'/employee-payments'
            );

    }


    try {

    DB::transaction(function ()
use (
    $request,
    $companyId,
    $activeFy,
    $attachment
)
{

    /*
    Create Employee Payment
    */
    $payment =
    EmployeePayment::create([

        'company_id' =>
        $companyId,

        'financial_year_id' =>
        $activeFy->id,

        'voucher_no' =>
        $request->voucher_no,

        'employee_account_id' =>
        $request->employee_account_id,

        'payment_date' =>
        $request->payment_date,

        'salary_year' =>
        $request->salary_year,

        'salary_month' =>
        $request->salary_month,

        'account_id' =>
        $request->account_id,

        'amount' =>
        (float)$request->amount,

        'attachment' =>
        $attachment,

        'note' =>
        $request->note,

        'created_by' =>
        auth()->id(),

        'status' =>
        1

    ]);

    /*
    Account Transaction
    */
    AccountBalanceService::createTransaction([

        'company_id' =>
        $companyId,

        'financial_year_id' =>
        $activeFy->id,

        'account_id' =>
        $request->account_id,

        'transaction_date' =>
        $request->payment_date,

        'voucher_no' =>
        $request->voucher_no,

        'reference_type' =>
        'EmployeePayment',

        'reference_id' =>
        $payment->id,

        'description' =>
        'Employee Salary Payment',

        'debit' =>
        0,

        'credit' =>
        $request->amount

    ]);

});



        

        return redirect()

        ->route(
            'company.employee-payment.index'
        )

        ->with(
            'success',
            'Employee payment created successfully.'
        );

    }
    catch (\Exception $e) {

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
| EDIT
|--------------------------------------------------------------------------
*/
public function edit($id)
{
    $companyId =
        auth()->user()->company_id;

    $employeePayment =
        EmployeePayment::where(
            'company_id',
            $companyId
        )
        ->findOrFail(
            $id
        );

    /*
    Employees
    */
    $employees =
        EmployeeAccount::where(
            'company_id',
            $companyId
        )
        ->where(
            'status',
            1
        )
        ->orderBy(
            'first_name'
        )
        ->get();

    /*
    Accounts
    */
    $accounts =
        Account::where(
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
        'company.employee-payment.edit',
        compact(
            'employeePayment',
            'employees',
            'accounts'
        )
    );
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
    $request->validate([

        'employee_account_id' =>
        'required|exists:employee_accounts,id',

        'payment_date' =>
        'required|date',

        'salary_year' =>
        'required|integer',

        'salary_month' =>
        'required|integer|min:1|max:12',

        'account_id' =>
        'required|exists:accounts,id',

        'amount' =>
        ValidationService::requiredAmount(),

        'attachment' =>
        ValidationService::document(51200),

    ]);
    /*
Active Financial Year
*/
$activeFy =
    FinancialYear::where(
        'company_id',
        auth()->user()->company_id
    )
    ->where(
        'is_active',
        1
    )
    ->first();

if (
    !$activeFy
)
{

    return back()
        ->withInput()
        ->with(
            'error',
            'Active financial year not found.'
        );

}

/*
Date Validation
*/
$transactionDate =
    Carbon::parse(
        $request->payment_date
    );

$startDate =
    Carbon::parse(
        $activeFy->start_date
    );

$endDate =
    Carbon::parse(
        $activeFy->end_date
    );

if (
    $transactionDate->lt(
        $startDate
    )
    ||
    $transactionDate->gt(
        $endDate
    )
)
{

    return back()
        ->withInput()
        ->with(
            'error',
            'Payment date is outside active financial year.'
        );

}

    $employeePayment =
        EmployeePayment::where(
            'company_id',
            auth()->user()->company_id
        )
        ->findOrFail(
            $id
        );

    $companyId =
        auth()->user()->company_id;

    $folder =
        'companies/'
        .$companyId
        .'/employee-payments';

    try {

        DB::transaction(function ()
        use (
            $request,
            $employeePayment,
            $folder
        ) {
$transaction =
AccountTransaction::where(
    'reference_type',
    'EmployeePayment'
)
->where(
    'reference_id',
    $employeePayment->id
)
->first();

if (!$transaction)
{
    throw new \Exception(
        'Account transaction not found.'
    );
}

AccountBalanceService::updateTransaction(
    $transaction,
    [

        'company_id' =>
        auth()->user()->company_id,

        'financial_year_id' =>
        $activeFy->id,

        'account_id' =>
        $request->account_id,

        'transaction_date' =>
        $request->payment_date,

        'voucher_no' =>
        $employeePayment->voucher_no,

        'reference_type' =>
        'EmployeePayment',

        'reference_id' =>
        $employeePayment->id,

        'description' =>
        'Employee Salary Payment',

        'debit' =>
        0,

        'credit' =>
        $request->amount

    ]
);



            $attachment =
                $employeePayment->attachment;

            /*
            Replace Attachment
            */
            $attachment =
                FileUploadService::replaceFile(
                    $request,
                    'attachment',
                    $attachment,
                    $folder
                );

            $employeePayment->update([

                'employee_account_id' =>
                $request->employee_account_id,

                'payment_date' =>
                $request->payment_date,

                'salary_year' =>
                $request->salary_year,

                'salary_month' =>
                $request->salary_month,

                'account_id' =>
                $request->account_id,

                'amount' =>
                (float)
                $request->amount,

                'attachment' =>
                $attachment,

                'note' =>
                $request->note,

            ]);

        });


        return redirect()

        ->route(
            'company.employee-payment.index'
        )

        ->with(
            'success',
            'Employee payment updated successfully.'
        );

    }
    catch (\Exception $e) {

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
| DELETE
|--------------------------------------------------------------------------
*/

public function destroy($id)
{
    try {

        $employeePayment =

        EmployeePayment::where(

            'company_id',

            auth()->user()->company_id

        )

        ->findOrFail($id);

        DB::transaction(function ()
        use (
            $employeePayment
        ) {

            /*
            Delete Attachment
            */
            FileUploadService::deleteFile(

                $employeePayment->attachment

            );

            /*
            Delete Account Transaction
            */
            $transaction =

            AccountTransaction::where(

                'reference_type',

                'EmployeePayment'

            )

            ->where(

                'reference_id',

                $employeePayment->id

            )

            ->first();

            if ($transaction)
            {

                AccountBalanceService::deleteTransaction(

                    $transaction

                );

            }

            /*
            Delete Payment
            */
            $employeePayment->delete();

        });

        return back()

        ->with(

            'success',

            'Deleted.'

        );

    }
    catch (\Exception $e) {

        return back()

        ->with(

            'error',

            $e->getMessage()

        );

    }
}
/*
|--------------------------------------------------------------------------
| SHOW
|--------------------------------------------------------------------------
*/
public function show($id)
{
    $employeePayment =

    EmployeePayment::where(

        'company_id',

        auth()->user()->company_id

    )

    ->findOrFail($id);

    return view(

        'company.employee-payment.show',

        compact(

            'employeePayment'

        )

    );

}










}



