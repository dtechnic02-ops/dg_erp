<?php



namespace App\Http\Controllers\Company;



use App\Http\Controllers\Controller;

use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;

use App\Http\Controllers\Concerns\AuthorizesSubscriptionModule;

use App\Http\Controllers\Concerns\HandlesTransactionDocumentationEdit;

use Illuminate\Routing\Controllers\HasMiddleware;

use App\Models\Account;

use App\Models\AccountTransaction;

use App\Models\EmployeeAccount;

use App\Models\EmployeePayment;

use App\Models\FinancialYear;

use App\Models\SalarySheet;

use App\Services\AccountBalanceService;

use App\Services\FileUploadService;

use App\Services\SalarySheetPaymentService;

use App\Services\ValidationService;

use Carbon\Carbon;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;



class EmployeePaymentController extends Controller implements HasMiddleware

{

    use AuthorizesCompanyPermission;

    use AuthorizesSubscriptionModule;

    use HandlesTransactionDocumentationEdit;



    public static function middleware(): array

    {

        return self::subscriptionModuleMiddleware();

    }



    protected static function subscriptionModuleCode(): string

    {

        return 'hr';

    }



    public function __construct(

        private SalarySheetPaymentService $salarySheetPaymentService

    ) {

    }



    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('salary.payment.view');

        $companyId = auth()->user()->company_id;

        [$query, $financialYears, $activeFy, $startDate, $endDate] = $this->buildPaymentQuery($request, $companyId);

        $perPage = in_array((int) $request->per_page, [10, 20, 100, 200], true)
            ? (int) $request->per_page
            : 20;

        $totalsQuery = (clone $query)->where('status', EmployeePayment::STATUS_ACTIVE);

        $totalPayment = (float) (clone $totalsQuery)->sum('amount');
        $totalCount = (clone $query)->count();

        $payments = $query
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $employees = EmployeeAccount::where('company_id', $companyId)
            ->orderBy('first_name')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->where(function ($accountQuery) {
                $accountQuery->where('status', 1)
                    ->orWhere('status', 'active');
            })
            ->orderBy('account_name')
            ->get();

        return view('company.employee-payment.index', compact(
            'payments',
            'employees',
            'accounts',
            'financialYears',
            'activeFy',
            'totalPayment',
            'totalCount',
            'startDate',
            'endDate',
            'perPage'
        ));
    }

    public function printList(Request $request)
    {
        $this->authorizeCompanyPermission('salary.payment.view');

        $companyId = auth()->user()->company_id;

        [$query, $financialYears, $activeFy, $startDate, $endDate] = $this->buildPaymentQuery($request, $companyId);

        $totalsQuery = (clone $query)->where('status', EmployeePayment::STATUS_ACTIVE);

        $totalPayment = (float) (clone $totalsQuery)->sum('amount');
        $totalCount = (clone $query)->count();
        $activeCount = (clone $query)->where('status', EmployeePayment::STATUS_ACTIVE)->count();
        $cancelledCount = (clone $query)->where('status', EmployeePayment::STATUS_CANCELLED)->count();

        $payments = $query
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $employees = EmployeeAccount::where('company_id', $companyId)
            ->orderBy('first_name')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->where(function ($accountQuery) {
                $accountQuery->where('status', 1)
                    ->orWhere('status', 'active');
            })
            ->orderBy('account_name')
            ->get();

        return view('company.employee-payment.print-list', compact(
            'payments',
            'employees',
            'accounts',
            'financialYears',
            'activeFy',
            'totalPayment',
            'totalCount',
            'activeCount',
            'cancelledCount',
            'startDate',
            'endDate'
        ));
    }

    public function create(Request $request)

    {

        $this->authorizeCompanyPermission('salary.payment.create');



        $companyId = auth()->user()->company_id;



        try {

            $activeFy = $this->assertActiveFinancialYear($companyId);

            $this->assertFinancialYearIsActive($activeFy);

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }



        $salarySheet = null;



        if ($request->filled('salary_sheet_id')) {

            $salarySheet = SalarySheet::with(['employee', 'financialYear'])

                ->where('company_id', $companyId)

                ->findOrFail($request->salary_sheet_id);



            if (!$salarySheet->canAcceptPayment()) {

                return redirect()

                    ->route('company.salary-sheets.show', $salarySheet->id)

                    ->with('error', 'This salary sheet cannot accept new payments.');

            }



            try {

                $this->assertFinancialYearIsActive(

                    $salarySheet->financialYear
                        ?? FinancialYear::where('company_id', $companyId)
                            ->findOrFail($salarySheet->financial_year_id)

                );

            } catch (\Exception $e) {

                return redirect()

                    ->route('company.salary-sheets.show', $salarySheet->id)

                    ->with('error', $e->getMessage());

            }

        }



        $voucherNo = SalarySheetPaymentService::generateVoucherNo($companyId, $activeFy);



        $accounts = Account::where('company_id', $companyId)

            ->where('status', 1)

            ->orderBy('account_name')

            ->get();



        return view('company.employee-payment.create', compact(

            'voucherNo',

            'accounts',

            'salarySheet',

            'activeFy'

        ));

    }



    public function store(Request $request)

    {

        $this->authorizeCompanyPermission('salary.payment.create');



        $companyId = auth()->user()->company_id;



        $request->validate([

            'salary_sheet_id' => [

                'required',

                ValidationService::existsForCompany('salary_sheets', $companyId),

            ],

            'payment_date' => ValidationService::requiredDate(),

            'account_id' => [

                'required',

                ValidationService::existsForCompany('accounts', $companyId),

            ],

            'amount' => ValidationService::requiredAmount(),

            'attachment' => ValidationService::document(),

        ]);



        try {

            $activeFy = $this->assertActiveFinancialYear($companyId);

            $this->assertFinancialYearIsActive($activeFy);

            $this->assertDateWithinFinancialYear($request->payment_date, $activeFy);



            DB::transaction(function () use ($request, $companyId, $activeFy) {

                $salarySheet = SalarySheet::with('financialYear')

                    ->where('company_id', $companyId)

                    ->lockForUpdate()

                    ->findOrFail($request->salary_sheet_id);



                if (!$salarySheet->canAcceptPayment()) {

                    throw new \Exception('This salary sheet cannot accept new payments.');

                }



                $sheetFy = $salarySheet->financialYear

                    ?? FinancialYear::where('company_id', $companyId)

                        ->findOrFail($salarySheet->financial_year_id);



                $this->assertFinancialYearIsActive(
                    $sheetFy,
                    'Inactive financial year cannot accept salary payments.'
                );

                $this->assertTransactionFinancialYear(
                    $salarySheet,
                    $activeFy,
                    'Salary payment must belong to the active financial year.'
                );



                $amount = round((float) $request->amount, 2);

                $this->salarySheetPaymentService->assertPaymentAmount($salarySheet, $amount);



                [$salaryYear, $salaryMonth] = $this->resolveSalaryPeriod($salarySheet);

                $voucherNo = SalarySheetPaymentService::generateVoucherNo($companyId, $activeFy);



                $attachment = null;



                if ($request->hasFile('attachment')) {

                    $attachment = FileUploadService::uploadFile(

                        $request->file('attachment'),

                        'companies/' . $companyId . '/employee-payments'

                    );

                }



                $payment = EmployeePayment::create([

                    'company_id' => $companyId,

                    'financial_year_id' => $activeFy->id,

                    'salary_sheet_id' => $salarySheet->id,

                    'employee_account_id' => $salarySheet->employee_id,

                    'voucher_no' => $voucherNo,

                    'payment_date' => $request->payment_date,

                    'salary_year' => $salaryYear,

                    'salary_month' => $salaryMonth,

                    'account_id' => $request->account_id,

                    'amount' => $amount,

                    'attachment' => $attachment,

                    'note' => $request->note,

                    'created_by' => auth()->id(),

                    'status' => EmployeePayment::STATUS_ACTIVE,

                ]);



                AccountBalanceService::createTransaction([

                    'company_id' => $companyId,

                    'financial_year_id' => $activeFy->id,

                    'account_id' => $request->account_id,

                    'transaction_date' => $request->payment_date,

                    'voucher_no' => $voucherNo,

                    'reference_type' => 'EmployeePayment',

                    'reference_id' => $payment->id,

                    'description' => 'Employee Salary Payment',

                    'debit' => 0,

                    'credit' => $amount,

                    'created_by' => auth()->id(),

                ]);



                $this->salarySheetPaymentService->sync($salarySheet);

            });



            return redirect()

                ->route('company.salary-sheets.show', $request->salary_sheet_id)

                ->with('success', 'Employee payment created successfully.');

        } catch (\Exception $e) {

            return back()->withInput()->with('error', $e->getMessage());

        }

    }



    public function show($id)

    {

        $this->authorizeCompanyPermission('salary.payment.view');



        $employeePayment = EmployeePayment::with([

            'employee',

            'salarySheet.employee',

            'account',

            'financialYear',

            'creator',

            'updater',

            'canceller',

        ])

            ->where('company_id', auth()->user()->company_id)

            ->findOrFail($id);



        return view('company.employee-payment.show', compact('employeePayment'));

    }



    public function edit($id)

    {

        $this->authorizeCompanyPermission('salary.payment.edit');



        $companyId = auth()->user()->company_id;



        $employeePayment = EmployeePayment::with(['salarySheet.financialYear', 'financialYear'])

            ->where('company_id', $companyId)

            ->findOrFail($id);



        if (!$employeePayment->isActive()) {

            return redirect()

                ->route('company.employee-payment.show', $employeePayment->id)

                ->with('error', 'Cancelled employee payment cannot be edited.');

        }



        try {

            $this->assertFinancialYearIsActive(
                $employeePayment->financialYear
                    ?? FinancialYear::where('company_id', $companyId)
                        ->findOrFail($employeePayment->financial_year_id),
                'Inactive financial year cannot be edited.'
            );

        } catch (\Exception $e) {

            return redirect()

                ->route('company.employee-payment.show', $employeePayment->id)

                ->with('error', $e->getMessage());

        }



        $accounts = Account::where('company_id', $companyId)

            ->where('status', 1)

            ->orderBy('account_name')

            ->get();



        return view('company.employee-payment.edit', compact('employeePayment', 'accounts'));

    }



    public function update(Request $request, $id)

    {

        $this->authorizeCompanyPermission('salary.payment.edit');



        $companyId = auth()->user()->company_id;



        $request->validate([

            'payment_date' => ValidationService::requiredDate(),

            'account_id' => [

                'required',

                ValidationService::existsForCompany('accounts', $companyId),

            ],

            'amount' => ValidationService::requiredAmount(),

            'attachment' => ValidationService::document(51200),

        ]);



        try {

            $activeFy = $this->assertActiveFinancialYear($companyId);

            $this->assertFinancialYearIsActive($activeFy);

            $this->assertDateWithinFinancialYear($request->payment_date, $activeFy);



            DB::transaction(function () use ($request, $companyId, $activeFy, $id) {

                $employeePayment = EmployeePayment::with(['salarySheet.financialYear', 'financialYear'])

                    ->where('company_id', $companyId)

                    ->lockForUpdate()

                    ->findOrFail($id);



                if (!$employeePayment->isActive()) {

                    throw new \Exception('Cancelled employee payment cannot be edited.');

                }



                $paymentFy = $employeePayment->financialYear

                    ?? FinancialYear::where('company_id', $companyId)

                        ->findOrFail($employeePayment->financial_year_id);



                $this->assertFinancialYearIsActive(
                    $paymentFy,
                    'Inactive financial year cannot be edited.'
                );

                $this->assertTransactionFinancialYear(
                    $employeePayment,
                    $activeFy,
                    'Salary payment must belong to the active financial year.'
                );



                $salarySheet = SalarySheet::where('company_id', $companyId)

                    ->lockForUpdate()

                    ->findOrFail($employeePayment->salary_sheet_id);



                $amount = round((float) $request->amount, 2);

                $this->salarySheetPaymentService->assertPaymentAmount(

                    $salarySheet,

                    $amount,

                    $employeePayment->id

                );



                $transaction = AccountTransaction::where('company_id', $companyId)

                    ->where('reference_type', 'EmployeePayment')

                    ->where('reference_id', $employeePayment->id)

                    ->where('status', 1)

                    ->first();



                if (!$transaction) {

                    throw new \Exception('Account transaction not found.');

                }



                AccountBalanceService::updateTransaction($transaction, [

                    'company_id' => $companyId,

                    'financial_year_id' => $activeFy->id,

                    'account_id' => $request->account_id,

                    'transaction_date' => $request->payment_date,

                    'voucher_no' => $employeePayment->voucher_no,

                    'reference_type' => 'EmployeePayment',

                    'reference_id' => $employeePayment->id,

                    'description' => 'Employee Salary Payment',

                    'debit' => 0,

                    'credit' => $amount,

                ]);



                $folder = 'companies/' . $companyId . '/employee-payments';

                $attachment = FileUploadService::replaceFile(

                    $request,

                    'attachment',

                    $employeePayment->attachment,

                    $folder

                );



                $employeePayment->update([

                    'payment_date' => $request->payment_date,

                    'account_id' => $request->account_id,

                    'amount' => $amount,

                    'attachment' => $attachment,

                    'note' => $request->note,

                    'updated_by' => auth()->id(),

                ]);



                $this->salarySheetPaymentService->sync($salarySheet);

            });



            return redirect()

                ->route('company.employee-payment.show', $id)

                ->with('success', 'Employee payment updated successfully.');

        } catch (\Exception $e) {

            return back()->withInput()->with('error', $e->getMessage());

        }

    }



    public function cancel(Request $request, $id)

    {

        $this->authorizeCompanyPermission('salary.payment.cancel');



        $request->validate([

            'cancel_date' => ValidationService::requiredDate(),

            'cancel_reason' => ValidationService::requiredString(500),

        ]);



        try {

            DB::transaction(function () use ($request, $id) {

                $companyId = auth()->user()->company_id;

                $activeFy = $this->assertActiveFinancialYear($companyId);

                $this->assertDateWithinFinancialYear(
                    $request->cancel_date,
                    $activeFy,
                    'Cancel date must belong to the active financial year.'
                );

                $cancelBusinessDate = Carbon::parse($request->cancel_date)->toDateString();

                $cancelReason = trim($request->cancel_reason);



                $employeePayment = EmployeePayment::with(['salarySheet', 'financialYear'])

                    ->where('company_id', $companyId)

                    ->lockForUpdate()

                    ->findOrFail($id);



                if (!$employeePayment->isActive()) {

                    throw new \Exception('Employee payment already cancelled.');

                }



                $paymentFy = $employeePayment->financialYear

                    ?? FinancialYear::where('company_id', $companyId)

                        ->findOrFail($employeePayment->financial_year_id);



                $this->assertFinancialYearIsActive(
                    $paymentFy,
                    'Inactive financial year cannot be cancelled.'
                );

                $this->assertTransactionFinancialYear(
                    $employeePayment,
                    $activeFy,
                    'Salary payment must belong to the active financial year.'
                );



                $transaction = AccountTransaction::where('company_id', $companyId)

                    ->where('reference_type', 'EmployeePayment')

                    ->where('reference_id', $employeePayment->id)

                    ->where('status', 1)

                    ->firstOrFail();



                AccountBalanceService::reverseTransaction(

                    $transaction,

                    'employee_payment_cancel',

                    'Employee Payment Cancel: ' . $cancelReason,

                    $cancelBusinessDate,

                    $activeFy->id

                );



                $employeePayment->update([

                    'status' => EmployeePayment::STATUS_CANCELLED,

                    'cancelled_by' => auth()->id(),

                    'cancelled_at' => now(),

                    'cancel_reason' => $cancelReason,

                    'updated_by' => auth()->id(),

                    'note' => trim(($employeePayment->note ?? '') . ' [Cancelled: ' . $cancelReason . ']'),

                ]);



                $salarySheet = SalarySheet::where('company_id', $companyId)

                    ->lockForUpdate()

                    ->findOrFail($employeePayment->salary_sheet_id);



                // Sync after cancel status so cancelled payment is excluded from paid/due (§10B).
                $this->salarySheetPaymentService->sync($salarySheet);

            });



            return back()->with('success', 'Employee payment cancelled successfully.');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }

    }



    public function print($id)

    {

        $this->authorizeCompanyPermission('salary.payment.view');



        $employeePayment = EmployeePayment::with([

            'employee',

            'salarySheet',

            'account',

            'financialYear',

            'creator',

        ])

            ->where('company_id', auth()->user()->company_id)

            ->findOrFail($id);



        return view('company.employee-payment.print', compact('employeePayment'));

    }



    private function buildPaymentQuery(Request $request, int $companyId): array
    {
        $query = EmployeePayment::with(['employee', 'salarySheet', 'account'])
            ->where('company_id', $companyId);

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->latest('id')
            ->get();

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        $startDate = null;
        $endDate = null;

        if (!$request->has('financial_year_id')) {
            if ($activeFy) {
                $query->where('financial_year_id', $activeFy->id);
                $startDate = $activeFy->start_date;
                $endDate = $activeFy->end_date;
            }
        } else {
            if ($request->financial_year_id) {
                $query->where('financial_year_id', $request->financial_year_id);
            }

            $startDate = $request->start_date;
            $endDate = $request->end_date;
        }

        if (!$request->has('status')) {
            $query->where('status', EmployeePayment::STATUS_ACTIVE);
        } elseif ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_account_id', $request->employee_id);
        }

        if ($request->filled('voucher_no')) {
            $query->where('voucher_no', 'like', '%' . $request->voucher_no . '%');
        }

        if ($request->filled('salary_month')) {
            $query->whereHas('salarySheet', function ($salarySheet) use ($request) {
                $salarySheet->where('salary_month', $request->salary_month);
            });
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', '%' . $search . '%')
                    ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                        $employeeQuery->where('employee_code', 'like', '%' . $search . '%')
                            ->orWhere('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%');
                    });
            });
        }

        if (!empty($startDate)) {
            $query->whereDate('payment_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('payment_date', '<=', $endDate);
        }

        return [$query, $financialYears, $activeFy, $startDate, $endDate];
    }

    private function resolveSalaryPeriod(SalarySheet $salarySheet): array

    {

        if (!preg_match('/^(\d{4})-(\d{2})$/', (string) $salarySheet->salary_month, $matches)) {

            throw new \Exception('Invalid salary month on salary sheet.');

        }



        return [(int) $matches[1], (int) $matches[2]];

    }

}


