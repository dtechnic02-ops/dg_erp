<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesTransactionDocumentationEdit;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Models\FinancialYear;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Services\AccountBalanceService;
use App\Services\FileUploadService;
use App\Services\ValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeController extends Controller
{
    use AuthorizesCompanyPermission;
    use HandlesTransactionDocumentationEdit;

    protected function buildIncomeQuery(Request $request, int $companyId)
    {
        $query = Income::with([
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
            $query->where('status', Income::STATUS_ACTIVE);
        } elseif ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('income_no', 'like', '%' . $search . '%')
                    ->orWhere('title', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('income_category_id')) {
            $query->where('income_category_id', $request->income_category_id);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('income_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('income_date', '<=', $request->end_date);
        }

        return $query;
    }

    protected function generateIncomeNo(int $companyId, FinancialYear $activeFy): string
    {
        $last = Income::where('company_id', $companyId)
            ->where('financial_year_id', $activeFy->id)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $next = 1;

        if ($last && $last->income_no) {
            $parts = explode('-', $last->income_no);
            $next = ((int) end($parts)) + 1;
        }

        return 'INC-'
            . $companyId
            . '-'
            . $activeFy->name
            . '-'
            . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    protected function validateCategory(int $companyId, int $categoryId): IncomeCategory
    {
        return IncomeCategory::where('company_id', $companyId)
            ->where('status', IncomeCategory::STATUS_ACTIVE)
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
        $this->authorizeCompanyPermission('view_income');

        $companyId = auth()->user()->company_id;

        $query = $this->buildIncomeQuery($request, $companyId);

        $summaryQuery = clone $query;
        $totalAmount = (clone $summaryQuery)
            ->where('status', Income::STATUS_ACTIVE)
            ->sum('amount');
        $activeCount = (clone $summaryQuery)
            ->where('status', Income::STATUS_ACTIVE)
            ->count();
        $totalCount = (clone $summaryQuery)->count();

        $allowedPerPage = [10, 20, 50, 100, 200];

        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $incomes = $query->latest()->paginate($perPage)->withQueryString();

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->latest('id')
            ->get();

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        $categories = IncomeCategory::where('company_id', $companyId)
            ->where('status', IncomeCategory::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('account_name')
            ->get();

        return view('company.income.index', compact(
            'incomes',
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
        $this->authorizeCompanyPermission('create_income');

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

        $categories = IncomeCategory::where('company_id', $companyId)
            ->where('status', IncomeCategory::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return view('company.income.create', compact(
            'accounts',
            'categories',
            'activeFy'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_income');

        $request->validate([
            'title'               => ValidationService::requiredString(200),
            'income_category_id'  => 'required|integer',
            'account_id'          => 'required|integer',
            'amount'              => ValidationService::requiredAmount(),
            'income_date'         => ValidationService::requiredDate(),
            'attachment'          => ValidationService::document(),
            'note'                => ValidationService::text(),
        ]);

        $file = null;

        try {
            $income = DB::transaction(function () use ($request, &$file) {
                $companyId = auth()->user()->company_id;
                $activeFy = $this->assertActiveFinancialYear($companyId);

                $this->assertDateWithinFinancialYear(
                    $request->income_date,
                    $activeFy,
                    'Income date must be inside financial year.'
                );

                $this->validateCategory($companyId, (int) $request->income_category_id);
                $this->validateAccount($companyId, (int) $request->account_id);

                $incomeNo = $this->generateIncomeNo($companyId, $activeFy);

                $folder = 'companies/' . $companyId . '/income';

                if ($request->hasFile('attachment')) {
                    $file = FileUploadService::uploadFile(
                        $request->file('attachment'),
                        $folder
                    );
                }

                $income = Income::create([
                    'company_id'          => $companyId,
                    'financial_year_id'   => $activeFy->id,
                    'income_category_id'  => $request->income_category_id,
                    'income_no'           => $incomeNo,
                    'title'               => $request->title,
                    'account_id'          => $request->account_id,
                    'amount'              => $request->amount,
                    'income_date'         => $request->income_date,
                    'attachment'          => $file,
                    'note'                => $request->note,
                    'created_by'          => auth()->id(),
                    'status'              => Income::STATUS_ACTIVE,
                ]);

                AccountBalanceService::createTransaction([
                    'company_id'          => $companyId,
                    'financial_year_id'   => $activeFy->id,
                    'account_id'          => $request->account_id,
                    'transaction_date'    => $request->income_date,
                    'voucher_no'          => $income->income_no,
                    'reference_type'      => 'Income',
                    'reference_id'        => $income->id,
                    'description'         => 'Income Receipt',
                    'debit'               => $request->amount,
                    'credit'              => 0,
                ], false);

                return $income;
            });

            return redirect()
                ->route('company.income.show', $income->id)
                ->with('success', 'Income created successfully.');
        } catch (\Exception $e) {
            FileUploadService::deleteFile($file);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $this->authorizeCompanyPermission('view_income');

        $income = Income::with([
                'category',
                'account',
                'financialYear',
                'createdBy',
                'updatedByUser',
                'cancelledByUser',
            ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view('company.income.show', compact('income'));
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('edit_income');

        $companyId = auth()->user()->company_id;

        $income = Income::where('company_id', $companyId)->findOrFail($id);

        if (!$income->isActive()) {
            return back()->with('error', 'Cancelled income cannot be edited.');
        }

        if ($income->financial_year_id) {
            $incomeFy = FinancialYear::where([
                'id'         => $income->financial_year_id,
                'company_id' => $companyId,
            ])->first();

            if ($incomeFy && !$incomeFy->is_active) {
                return back()->with('error', 'Closed financial year cannot be edited.');
            }
        }

        $accounts = Account::where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('account_name')
            ->get();

        $categories = IncomeCategory::where('company_id', $companyId)
            ->where('status', IncomeCategory::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return view('company.income.edit', compact(
            'income',
            'accounts',
            'categories'
        ));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('edit_income');

        $request->validate([
            'title'               => ValidationService::requiredString(200),
            'income_category_id'  => 'required|integer',
            'account_id'          => 'required|integer',
            'amount'              => ValidationService::requiredAmount(),
            'income_date'         => ValidationService::requiredDate(),
            'attachment'          => ValidationService::document(),
            'note'                => ValidationService::text(),
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $companyId = auth()->user()->company_id;

                $income = Income::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                $this->guardEditableTransaction(
                    $income,
                    'Cancelled income cannot be edited.'
                );

                $currentFy = FinancialYear::where('company_id', $companyId)
                    ->findOrFail($income->financial_year_id);

                if (!$currentFy->is_active) {
                    throw new \Exception('Closed financial year cannot be edited.');
                }

                $this->assertDateWithinFinancialYear(
                    $request->income_date,
                    $currentFy,
                    'Income date must be inside financial year.'
                );

                $this->validateCategory($companyId, (int) $request->income_category_id);
                $this->validateAccount($companyId, (int) $request->account_id);

                $transaction = AccountTransaction::where('reference_type', 'Income')
                    ->where('reference_id', $income->id)
                    ->where('status', 1)
                    ->first();

                if (!$transaction) {
                    throw new \Exception('Account transaction not found.');
                }

                AccountBalanceService::updateTransaction($transaction, [
                    'company_id'        => $companyId,
                    'financial_year_id' => $income->financial_year_id,
                    'account_id'        => $request->account_id,
                    'transaction_date'  => $request->income_date,
                    'voucher_no'        => $income->income_no,
                    'reference_type'    => 'Income',
                    'reference_id'      => $income->id,
                    'description'       => 'Income Receipt',
                    'debit'             => $request->amount,
                    'credit'            => 0,
                ]);

                $folder = 'companies/' . $companyId . '/income';
                $file = FileUploadService::replaceFile(
                    $request,
                    'attachment',
                    $income->attachment,
                    $folder
                );

                $income->update($this->appendUpdatedBy([
                    'title'              => $request->title,
                    'income_category_id' => $request->income_category_id,
                    'account_id'         => $request->account_id,
                    'amount'             => $request->amount,
                    'income_date'        => $request->income_date,
                    'attachment'         => $file,
                    'note'               => $request->note,
                ], $income));

                $this->logDocumentationEdit('Income updated.', $income);
            });

            return redirect()
                ->route('company.income.show', $id)
                ->with('success', 'Income updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, $id)
    {
        $this->authorizeCompanyPermission('cancel_income');

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
                $cancelDescription = 'Income Cancel: ' . $cancelReason;

                $income = Income::where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                if (!$income->isActive()) {
                    throw new \Exception('Income already cancelled.');
                }

                $accountTransaction = AccountTransaction::where('company_id', $companyId)
                    ->where('reference_type', 'Income')
                    ->where('reference_id', $income->id)
                    ->where('status', 1)
                    ->firstOrFail();

                AccountBalanceService::reverseTransaction(
                    $accountTransaction,
                    'income_cancel',
                    $cancelDescription,
                    $cancelBusinessDate,
                    $activeFy->id
                );

                $income->update([
                    'status'         => Income::STATUS_CANCELLED,
                    'cancelled_by'   => auth()->id(),
                    'cancelled_date' => $cancelBusinessDate,
                    'cancel_reason'  => $cancelReason,
                    'note'           => trim(($income->note ?? '') . ' [Cancelled: ' . $cancelReason . ']'),
                ]);
            });

            return back()->with('success', 'Income cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        $this->authorizeCompanyPermission('print_income');

        $companyId = auth()->user()->company_id;
        $query = $this->buildIncomeQuery($request, $companyId);

        $incomes = $query->latest()->get();

        $totalAmount = $incomes
            ->where('status', Income::STATUS_ACTIVE)
            ->sum('amount');

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->latest('id')
            ->get();

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        return view('company.income.print', compact(
            'incomes',
            'totalAmount',
            'financialYears',
            'activeFy'
        ));
    }

    public function printVoucher($id)
    {
        $this->authorizeCompanyPermission('print_income');

        $income = Income::with([
                'category',
                'account',
                'financialYear',
                'createdBy',
            ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view('company.income.voucher-print', compact('income'));
    }
}
