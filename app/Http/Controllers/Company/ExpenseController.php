<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesTransactionDocumentationEdit;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialYear;
use App\Services\AccountBalanceService;
use App\Services\FileUploadService;
use App\Services\ValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    use AuthorizesCompanyPermission;
    use HandlesTransactionDocumentationEdit;

    protected function buildExpenseQuery(Request $request, int $companyId)
    {
        $query = Expense::with([
                'category',
                'account',
                'financialYear',
            ])
            ->where('company_id', $companyId);

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        if ($request->filled('financial_year_id')) {
            $query->where('financial_year_id', $request->financial_year_id);
        } elseif (
            !$request->filled('start_date')
            && !$request->filled('end_date')
            && $activeFy
        ) {
            $query->where('financial_year_id', $activeFy->id);
        }

        if (!$request->has('status')) {
            $query->where('status', Expense::STATUS_ACTIVE);
        } elseif ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('expense_no', 'like', '%' . $search . '%')
                    ->orWhere('reference_no', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('expense_category_id')) {
            $query->where('expense_category_id', $request->expense_category_id);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('expense_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('expense_date', '<=', $request->end_date);
        }

        return $query;
    }

    protected function generateExpenseNo(int $companyId, FinancialYear $activeFy): string
    {
        $last = Expense::where('company_id', $companyId)
            ->where('financial_year_id', $activeFy->id)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $next = 1;

        if ($last && $last->expense_no) {
            $parts = explode('-', $last->expense_no);
            $next = ((int) end($parts)) + 1;
        }

        return 'EXP-'
            . $companyId
            . '-'
            . $activeFy->name
            . '-'
            . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    protected function validateCategory(int $companyId, int $categoryId): ExpenseCategory
    {
        return ExpenseCategory::where('company_id', $companyId)
            ->where('status', ExpenseCategory::STATUS_ACTIVE)
            ->findOrFail($categoryId);
    }

    protected function validateAccount(int $companyId, int $accountId): Account
    {
        return Account::where('company_id', $companyId)
            ->where('status', 1)
            ->findOrFail($accountId);
    }

    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('view_expense');

        $companyId = auth()->user()->company_id;

        $query = $this->buildExpenseQuery($request, $companyId);

        $summaryQuery = clone $query;
        $totalAmount = (clone $summaryQuery)
            ->where('status', Expense::STATUS_ACTIVE)
            ->sum('amount');
        $activeCount = (clone $summaryQuery)
            ->where('status', Expense::STATUS_ACTIVE)
            ->count();
        $totalCount = (clone $summaryQuery)->count();

        $allowedPerPage = [10, 20, 50, 100, 200];

        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $expenses = $query->latest()->paginate($perPage)->withQueryString();

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->latest('id')
            ->get();

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        $categories = ExpenseCategory::where('company_id', $companyId)
            ->where('status', ExpenseCategory::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('account_name')
            ->get();

        return view('company.expense.index', compact(
            'expenses',
            'financialYears',
            'activeFy',
            'categories',
            'accounts',
            'totalAmount',
            'activeCount',
            'totalCount',
            'perPage'
        ));
    }

    public function create()
    {
        $this->authorizeCompanyPermission('create_expense');

        $companyId = auth()->user()->company_id;

        try {
            $activeFy = $this->assertActiveFinancialYear($companyId);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('account_name')
            ->get();

        $categories = ExpenseCategory::where('company_id', $companyId)
            ->where('status', ExpenseCategory::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return view('company.expense.create', compact(
            'accounts',
            'categories',
            'activeFy'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_expense');

        $request->validate([
            'expense_category_id' => 'required|integer',
            'account_id'          => 'required|integer',
            'amount'              => ValidationService::requiredAmount(),
            'expense_date'        => ValidationService::requiredDate(),
            'reference_no'        => ValidationService::text(100),
            'attachment'          => ValidationService::document(),
            'note'                => ValidationService::text(),
        ]);

        $file = null;

        try {
            $expense = DB::transaction(function () use ($request, &$file) {
                $companyId = auth()->user()->company_id;
                $activeFy = $this->assertActiveFinancialYear($companyId);

                $this->assertDateWithinFinancialYear(
                    $request->expense_date,
                    $activeFy,
                    'Expense date must be inside financial year.'
                );

                $this->validateCategory($companyId, (int) $request->expense_category_id);
                $this->validateAccount($companyId, (int) $request->account_id);

                $expenseNo = $this->generateExpenseNo($companyId, $activeFy);

                $folder = 'companies/' . $companyId . '/expenses';

                if ($request->hasFile('attachment')) {
                    $file = FileUploadService::uploadFile(
                        $request->file('attachment'),
                        $folder
                    );
                }

                $expense = Expense::create([
                    'company_id'          => $companyId,
                    'financial_year_id'   => $activeFy->id,
                    'expense_no'          => $expenseNo,
                    'expense_category_id' => $request->expense_category_id,
                    'account_id'          => $request->account_id,
                    'amount'              => $request->amount,
                    'expense_date'        => $request->expense_date,
                    'reference_no'        => $request->reference_no,
                    'attachment'          => $file,
                    'note'                => $request->note,
                    'created_by'          => auth()->id(),
                    'status'              => Expense::STATUS_ACTIVE,
                ]);

                AccountBalanceService::createTransaction([
                    'company_id'        => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'account_id'        => $request->account_id,
                    'transaction_date'  => $request->expense_date,
                    'voucher_no'        => $expense->expense_no,
                    'reference_type'    => 'Expense',
                    'reference_id'      => $expense->id,
                    'description'       => 'Expense Payment',
                    'debit'             => 0,
                    'credit'            => $request->amount,
                ]);

                return $expense;
            });

            return redirect()
                ->route('company.expense.show', $expense->id)
                ->with('success', 'Expense created successfully.');
        } catch (\Exception $e) {
            FileUploadService::deleteFile($file);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $this->authorizeCompanyPermission('view_expense');

        $expense = Expense::with([
                'category',
                'account',
                'financialYear',
                'createdBy',
                'updatedByUser',
                'cancelledByUser',
            ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view('company.expense.show', compact('expense'));
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('edit_expense');

        $companyId = auth()->user()->company_id;

        $expense = Expense::where('company_id', $companyId)->findOrFail($id);

        if (!$expense->isActive()) {
            return back()->with('error', 'Cancelled expense cannot be edited.');
        }

        if ($expense->financial_year_id) {
            $expenseFy = FinancialYear::where([
                'id'         => $expense->financial_year_id,
                'company_id' => $companyId,
            ])->first();

            if ($expenseFy && !$expenseFy->is_active) {
                return back()->with('error', 'Closed financial year cannot be edited.');
            }
        }

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('account_name')
            ->get();

        $categories = ExpenseCategory::where('company_id', $companyId)
            ->where('status', ExpenseCategory::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return view('company.expense.edit', compact(
            'expense',
            'accounts',
            'categories'
        ));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('edit_expense');

        $request->validate([
            'expense_category_id' => 'required|integer',
            'account_id'          => 'required|integer',
            'amount'              => ValidationService::requiredAmount(),
            'expense_date'        => ValidationService::requiredDate(),
            'reference_no'        => ValidationService::text(100),
            'attachment'          => ValidationService::document(),
            'note'                => ValidationService::text(),
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $companyId = auth()->user()->company_id;

                $expense = Expense::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                $this->guardEditableTransaction(
                    $expense,
                    'Cancelled expense cannot be edited.'
                );

                $currentFy = FinancialYear::where('company_id', $companyId)
                    ->findOrFail($expense->financial_year_id);

                if (!$currentFy->is_active) {
                    throw new \Exception('Closed financial year cannot be edited.');
                }

                $this->assertDateWithinFinancialYear(
                    $request->expense_date,
                    $currentFy,
                    'Expense date must be inside financial year.'
                );

                $this->validateCategory($companyId, (int) $request->expense_category_id);
                $this->validateAccount($companyId, (int) $request->account_id);

                $transaction = AccountTransaction::where('company_id', $companyId)
                    ->where('reference_type', 'Expense')
                    ->where('reference_id', $expense->id)
                    ->where('status', 1)
                    ->first();

                if (!$transaction) {
                    throw new \Exception('Account transaction not found.');
                }

                AccountBalanceService::updateTransaction($transaction, [
                    'company_id'        => $companyId,
                    'financial_year_id' => $expense->financial_year_id,
                    'account_id'        => $request->account_id,
                    'transaction_date'  => $request->expense_date,
                    'voucher_no'        => $expense->expense_no,
                    'reference_type'    => 'Expense',
                    'reference_id'      => $expense->id,
                    'description'       => 'Expense Payment',
                    'debit'             => 0,
                    'credit'            => $request->amount,
                ]);

                $folder = 'companies/' . $companyId . '/expenses';
                $file = FileUploadService::replaceFile(
                    $request,
                    'attachment',
                    $expense->attachment,
                    $folder
                );

                $expense->update($this->appendUpdatedBy([
                    'expense_category_id' => $request->expense_category_id,
                    'account_id'          => $request->account_id,
                    'amount'              => $request->amount,
                    'expense_date'        => $request->expense_date,
                    'reference_no'        => $request->reference_no,
                    'attachment'          => $file,
                    'note'                => $request->note,
                ], $expense));

                $this->logDocumentationEdit('Expense updated.', $expense);
            });

            return redirect()
                ->route('company.expense.show', $id)
                ->with('success', 'Expense updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, $id)
    {
        $this->authorizeCompanyPermission('cancel_expense');

        $request->validate([
            'cancel_date'   => ValidationService::requiredDate(),
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
                $cancelDescription = 'Expense Cancel: ' . $cancelReason;

                $expense = Expense::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                if (!$expense->isActive()) {
                    throw new \Exception('Expense already cancelled.');
                }

                $accountTransaction = AccountTransaction::where('company_id', $companyId)
                    ->where('reference_type', 'Expense')
                    ->where('reference_id', $expense->id)
                    ->where('status', 1)
                    ->firstOrFail();

                AccountBalanceService::reverseTransaction(
                    $accountTransaction,
                    'expense_cancel',
                    $cancelDescription,
                    $cancelBusinessDate,
                    $activeFy->id
                );

                $expense->update([
                    'status'         => Expense::STATUS_CANCELLED,
                    'cancelled_by'   => auth()->id(),
                    'cancelled_date' => $cancelBusinessDate,
                    'cancel_reason'  => $cancelReason,
                    'note'           => trim(($expense->note ?? '') . ' [Cancelled: ' . $cancelReason . ']'),
                ]);
            });

            return back()->with('success', 'Expense cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        $this->authorizeCompanyPermission('print_expense');

        $companyId = auth()->user()->company_id;
        $query = $this->buildExpenseQuery($request, $companyId);

        $expenses = $query->latest()->get();

        $totalAmount = $expenses
            ->where('status', Expense::STATUS_ACTIVE)
            ->sum('amount');

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->latest('id')
            ->get();

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        return view('company.expense.print', compact(
            'expenses',
            'totalAmount',
            'financialYears',
            'activeFy'
        ));
    }

    public function printVoucher($id)
    {
        $this->authorizeCompanyPermission('print_expense');

        $expense = Expense::with([
                'category',
                'account',
                'financialYear',
                'createdBy',
            ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view('company.expense.voucher-print', compact('expense'));
    }
}
