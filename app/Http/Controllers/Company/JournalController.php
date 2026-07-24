<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesTransactionDocumentationEdit;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\EmployeeAccount;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\PartyAccount;
use App\Models\Supplier;
use App\Services\AccountBalanceService;
use App\Services\FileUploadService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalController extends Controller
{
    use AuthorizesCompanyPermission;
    use HandlesTransactionDocumentationEdit;

    protected function buildJournalQuery(Request $request, int $companyId)
    {
        $query = Journal::with(['financialYear', 'createdBy'])
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
            $query->where('status', Journal::STATUS_ACTIVE);
        } elseif ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('journal_no', 'like', '%' . $search . '%')
                    ->orWhere('reference_no', 'like', '%' . $search . '%')
                    ->orWhere('note', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('journal_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('journal_date', '<=', $request->end_date);
        }

        return $query;
    }

    protected function generateJournalNo(int $companyId, FinancialYear $activeFy): string
    {
        $last = Journal::where('company_id', $companyId)
            ->where('financial_year_id', $activeFy->id)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $next = 1;

        if ($last && $last->journal_no) {
            $parts = explode('-', $last->journal_no);
            $next = ((int) end($parts)) + 1;
        }

        return 'JRN-'
            . $companyId
            . '-'
            . $activeFy->name
            . '-'
            . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    protected function validateAccount(int $companyId, int $accountId): Account
    {
        return Account::where('company_id', $companyId)
            ->where('status', 1)
            ->findOrFail($accountId);
    }

    protected function chartAccountsForJournal(int $companyId)
    {
        return Account::where('company_id', $companyId)
            ->where('status', '!=', 'inactive')
            ->orderBy('account_name')
            ->get();
    }

    protected function subLedgerCollections(int $companyId): array
    {
        return [
            'customers' => Customer::where('company_id', $companyId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'suppliers' => Supplier::where('company_id', $companyId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'employees' => EmployeeAccount::where('company_id', $companyId)
                ->where('status', 1)
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'middle_name', 'last_name']),
            'parties' => PartyAccount::where('company_id', $companyId)
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    protected function validateSubLedgerEntity(int $companyId, string $subLedgerType, int $subLedgerId): void
    {
        $exists = match ($subLedgerType) {
            Account::SUB_LEDGER_CUSTOMER => Customer::where('company_id', $companyId)
                ->where('status', 'active')
                ->whereKey($subLedgerId)
                ->exists(),
            Account::SUB_LEDGER_SUPPLIER => Supplier::where('company_id', $companyId)
                ->where('status', 'active')
                ->whereKey($subLedgerId)
                ->exists(),
            Account::SUB_LEDGER_EMPLOYEE => EmployeeAccount::where('company_id', $companyId)
                ->where('status', 1)
                ->whereKey($subLedgerId)
                ->exists(),
            Account::SUB_LEDGER_PARTY => PartyAccount::where('company_id', $companyId)
                ->where('status', 1)
                ->whereKey($subLedgerId)
                ->exists(),
            default => false,
        };

        if (!$exists) {
            throw new \Exception('Selected sub ledger is invalid or inactive.');
        }
    }

    /**
     * @return array<int, array{account_id:int,type:string,amount:float,note:?string,sub_ledger_type:?string,sub_ledger_id:?int}>
     */
    protected function parseDetailRows(Request $request): array
    {
        $accountIds = $request->input('account_id', []);
        $debits = $request->input('debit', []);
        $credits = $request->input('credit', []);
        $rowNotes = $request->input('row_note', []);
        $subLedgerIds = $request->input('sub_ledger_id', []);
        $companyId = auth()->user()->company_id;

        if (!is_array($accountIds) || count($accountIds) < 2) {
            throw new \Exception('At least two journal detail rows are required.');
        }

        $rows = [];
        $debitTotal = 0.0;
        $creditTotal = 0.0;

        foreach ($accountIds as $index => $accountId) {
            if (empty($accountId)) {
                throw new \Exception('Account is required on every journal detail row.');
            }

            $account = $this->validateAccount($companyId, (int) $accountId);
            $subLedgerId = !empty($subLedgerIds[$index]) ? (int) $subLedgerIds[$index] : null;

            if ($account->requiresSubLedger()) {
                if (!$subLedgerId) {
                    throw new \Exception(
                        'Sub Ledger is required for account: ' . $account->account_name . '.'
                    );
                }

                $this->validateSubLedgerEntity($companyId, $account->sub_ledger_type, $subLedgerId);
            } else {
                $subLedgerId = null;
            }

            $debit = round((float) ($debits[$index] ?? 0), 2);
            $credit = round((float) ($credits[$index] ?? 0), 2);

            if ($debit > 0 && $credit > 0) {
                throw new \Exception('Debit and Credit cannot both contain values on the same row.');
            }

            if ($debit <= 0 && $credit <= 0) {
                throw new \Exception('Each row must have either a debit or a credit amount greater than zero.');
            }

            if ($debit > 0) {
                $type = JournalItem::TYPE_DEBIT;
                $amount = $debit;
                $debitTotal += $amount;
            } else {
                $type = JournalItem::TYPE_CREDIT;
                $amount = $credit;
                $creditTotal += $amount;
            }

            $rows[] = [
                'account_id'       => (int) $accountId,
                'type'             => $type,
                'amount'           => $amount,
                'note'             => isset($rowNotes[$index]) ? trim((string) $rowNotes[$index]) : null,
                'sub_ledger_type'  => $account->sub_ledger_type,
                'sub_ledger_id'    => $subLedgerId,
            ];
        }

        if (round($debitTotal, 2) !== round($creditTotal, 2)) {
            throw new \Exception('Total Debit must equal Total Credit.');
        }

        return [
            'rows'         => $rows,
            'debit_total'  => $debitTotal,
            'credit_total' => $creditTotal,
        ];
    }

    protected function createJournalTransactions(
        Journal $journal,
        array $rows,
        FinancialYear $financialYear,
        int $companyId,
        string $narration
    ): void {
        foreach ($rows as $row) {
            $this->validateAccount($companyId, $row['account_id']);

            JournalItem::create([
                'company_id'      => $companyId,
                'journal_id'      => $journal->id,
                'account_id'      => $row['account_id'],
                'sub_ledger_type' => $row['sub_ledger_type'],
                'sub_ledger_id'   => $row['sub_ledger_id'],
                'type'            => $row['type'],
                'amount'          => $row['amount'],
                'note'            => $row['note'] ?: null,
                'status'          => 1,
            ]);

            $description = $narration;

            if (!empty($row['note'])) {
                $description .= ' — ' . $row['note'];
            }

            AccountBalanceService::createTransaction([
                'company_id'        => $companyId,
                'financial_year_id' => $financialYear->id,
                'account_id'        => $row['account_id'],
                'transaction_date'  => $journal->journal_date->format('Y-m-d'),
                'voucher_no'        => $journal->journal_no,
                'reference_type'    => 'Journal',
                'reference_id'      => $journal->id,
                'description'       => $description,
                'debit'             => $row['type'] === JournalItem::TYPE_DEBIT ? $row['amount'] : 0,
                'credit'            => $row['type'] === JournalItem::TYPE_CREDIT ? $row['amount'] : 0,
            ]);
        }
    }

    protected function removeJournalTransactions(Journal $journal): void
    {
        $transactions = AccountTransaction::where('company_id', $journal->company_id)
            ->where('reference_type', 'Journal')
            ->where('reference_id', $journal->id)
            ->where('status', 1)
            ->get();

        foreach ($transactions as $transaction) {
            AccountBalanceService::deleteTransaction($transaction);
        }
    }

    public function index(Request $request)
    {
        $this->authorizeCompanyPermission('view_journal');

        $companyId = auth()->user()->company_id;
        $query = $this->buildJournalQuery($request, $companyId);

        $summaryQuery = clone $query;
        $totalAmount = (clone $summaryQuery)
            ->where('status', Journal::STATUS_ACTIVE)
            ->sum('total_amount');
        $activeCount = (clone $summaryQuery)
            ->where('status', Journal::STATUS_ACTIVE)
            ->count();
        $totalCount = (clone $summaryQuery)->count();

        $allowedPerPage = [10, 20, 50, 100, 200];
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $journals = $query->latest()->paginate($perPage)->withQueryString();

        $financialYears = FinancialYear::where('company_id', $companyId)
            ->latest('id')
            ->get();

        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        return view('company.journal.index', compact(
            'journals',
            'financialYears',
            'activeFy',
            'totalAmount',
            'activeCount',
            'totalCount',
            'perPage'
        ));
    }

    public function create()
    {
        $this->authorizeCompanyPermission('create_journal');

        $companyId = auth()->user()->company_id;

        try {
            $activeFy = $this->assertActiveFinancialYear($companyId);
        } catch (\Exception $e) {
            return redirect()
                ->route('company.financial-years.index')
                ->with('error', $e->getMessage());
        }

        $chartAccounts = $this->chartAccountsForJournal($companyId);
        $subLedgerData = $this->subLedgerCollections($companyId);

        return view('company.journal.create', array_merge(
            $subLedgerData,
            compact('chartAccounts', 'activeFy')
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeCompanyPermission('create_journal');

        $request->validate([
            'journal_date' => ValidationService::requiredDate(),
            'reference_no' => 'nullable|string|max:100',
            'note'         => ValidationService::requiredText(1000),
            'attachment'   => ValidationService::document(),
            'account_id'   => 'required|array|min:2',
            'account_id.*' => 'required|integer',
            'debit'          => 'required|array',
            'credit'         => 'required|array',
            'row_note'       => 'nullable|array',
            'sub_ledger_id'  => 'nullable|array',
            'sub_ledger_id.*'=> 'nullable|integer',
        ]);

        $file = null;

        try {
            $journal = DB::transaction(function () use ($request, &$file) {
                $companyId = auth()->user()->company_id;
                $activeFy = $this->assertActiveFinancialYear($companyId);

                $this->assertDateWithinFinancialYear(
                    $request->journal_date,
                    $activeFy,
                    'Journal date must be inside the active financial year.'
                );

                $parsed = $this->parseDetailRows($request);
                $rows = $parsed['rows'];
                $debitTotal = $parsed['debit_total'];

                foreach ($rows as $row) {
                    $this->validateAccount($companyId, $row['account_id']);

                    if ($row['sub_ledger_type'] && $row['sub_ledger_id']) {
                        $this->validateSubLedgerEntity(
                            $companyId,
                            $row['sub_ledger_type'],
                            $row['sub_ledger_id']
                        );
                    }
                }

                $journalNo = $this->generateJournalNo($companyId, $activeFy);
                $folder = 'companies/' . $companyId . '/journals';

                if ($request->hasFile('attachment')) {
                    $file = FileUploadService::uploadFile(
                        $request->file('attachment'),
                        $folder
                    );
                }

                $journal = Journal::create([
                    'company_id'        => $companyId,
                    'financial_year_id' => $activeFy->id,
                    'journal_no'        => $journalNo,
                    'journal_date'      => $request->journal_date,
                    'reference_no'    => $request->reference_no,
                    'total_amount'      => $debitTotal,
                    'attachment'        => $file,
                    'note'              => $request->note,
                    'created_by'        => auth()->id(),
                    'status'            => Journal::STATUS_ACTIVE,
                ]);

                $this->createJournalTransactions(
                    $journal,
                    $rows,
                    $activeFy,
                    $companyId,
                    trim($request->note)
                );

                return $journal;
            });

            return redirect()
                ->route('company.journal.show', $journal->id)
                ->with('success', 'Journal entry saved successfully.');
        } catch (\Exception $e) {
            FileUploadService::deleteFile($file);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $this->authorizeCompanyPermission('view_journal');

        $journal = Journal::with([
                'items.account',
                'financialYear',
                'createdBy',
                'updatedByUser',
            ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view('company.journal.show', compact('journal'));
    }

    public function edit($id)
    {
        $this->authorizeCompanyPermission('edit_journal');

        $companyId = auth()->user()->company_id;

        $journal = Journal::with(['items', 'financialYear'])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        if (!$journal->isActive()) {
            return back()->with('error', 'Cancelled journal cannot be edited.');
        }

        if ($journal->financial_year_id) {
            $journalFy = FinancialYear::where([
                'id'         => $journal->financial_year_id,
                'company_id' => $companyId,
            ])->first();

            if ($journalFy && !$journalFy->is_active) {
                return back()->with('error', 'Closed financial year cannot be edited.');
            }
        }

        $chartAccounts = $this->chartAccountsForJournal($companyId);
        $subLedgerData = $this->subLedgerCollections($companyId);

        return view('company.journal.edit', array_merge(
            $subLedgerData,
            compact('journal', 'chartAccounts')
        ));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCompanyPermission('edit_journal');

        $request->validate([
            'journal_date' => ValidationService::requiredDate(),
            'reference_no' => 'nullable|string|max:100',
            'note'         => ValidationService::requiredText(1000),
            'attachment'   => ValidationService::document(),
            'account_id'   => 'required|array|min:2',
            'account_id.*' => 'required|integer',
            'debit'          => 'required|array',
            'credit'         => 'required|array',
            'row_note'       => 'nullable|array',
            'sub_ledger_id'  => 'nullable|array',
            'sub_ledger_id.*'=> 'nullable|integer',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $companyId = auth()->user()->company_id;

                $journal = Journal::with('items')
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                $this->guardEditableTransaction(
                    $journal,
                    'Cancelled journal cannot be edited.'
                );

                $currentFy = FinancialYear::where('company_id', $companyId)
                    ->findOrFail($journal->financial_year_id);

                if (!$currentFy->is_active) {
                    throw new \Exception('Closed financial year cannot be edited.');
                }

                $this->assertDateWithinFinancialYear(
                    $request->journal_date,
                    $currentFy,
                    'Journal date must be inside the financial year.'
                );

                $parsed = $this->parseDetailRows($request);
                $rows = $parsed['rows'];
                $debitTotal = $parsed['debit_total'];

                foreach ($rows as $row) {
                    $this->validateAccount($companyId, $row['account_id']);

                    if ($row['sub_ledger_type'] && $row['sub_ledger_id']) {
                        $this->validateSubLedgerEntity(
                            $companyId,
                            $row['sub_ledger_type'],
                            $row['sub_ledger_id']
                        );
                    }
                }

                $this->removeJournalTransactions($journal);
                $journal->items()->delete();

                $folder = 'companies/' . $companyId . '/journals';
                $file = FileUploadService::replaceFile(
                    $request,
                    'attachment',
                    $journal->attachment,
                    $folder
                );

                $journal->update($this->appendUpdatedBy([
                    'journal_date'   => $request->journal_date,
                    'reference_no'   => $request->reference_no,
                    'total_amount'   => $debitTotal,
                    'attachment'     => $file,
                    'note'           => $request->note,
                ], $journal));

                $this->createJournalTransactions(
                    $journal,
                    $rows,
                    $currentFy,
                    $companyId,
                    trim($request->note)
                );

                $this->logDocumentationEdit('Journal updated.', $journal);
            });

            return redirect()
                ->route('company.journal.show', $id)
                ->with('success', 'Journal entry updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function print()
    {
        $this->authorizeCompanyPermission('view_journal');

        $companyId = auth()->user()->company_id;

        $query = Journal::with('financialYear')
            ->where('company_id', $companyId);

        if (request('search')) {
            $query->where('journal_no', 'like', '%' . request('search') . '%');
        }

        if (request('from_date') && request('to_date')) {
            $query->whereBetween('journal_date', [request('from_date'), request('to_date')]);
        } elseif (request('from_date')) {
            $query->whereDate('journal_date', '>=', request('from_date'));
        } elseif (request('to_date')) {
            $query->whereDate('journal_date', '<=', request('to_date'));
        }

        if (request('financial_year')) {
            $query->where('financial_year_id', request('financial_year'));
        }

        $journals = $query->latest()->get();

        return view('company.journal.print', compact('journals'));
    }

    public function printVoucher($id)
    {
        $this->authorizeCompanyPermission('print_journal');

        $journal = Journal::with([
                'items.account',
                'financialYear',
                'createdBy',
                'updatedByUser',
            ])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return view('company.journal.voucher-print', compact('journal'));
    }
}
