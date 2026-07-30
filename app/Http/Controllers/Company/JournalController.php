<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesTransactionDocumentationEdit;
use App\Http\Controllers\Concerns\AuthorizesCompanyPermission;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\EmployeeAccount;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\PartyAccount;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Services\AccountBalanceService;
use App\Services\CustomerTransactionService;
use App\Services\SupplierTransactionService;
use App\Services\FileUploadService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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

    protected function resolveControlAccount(int $companyId, string $subLedgerType): Account
    {
        $accounts = Account::where('company_id', $companyId)
            ->where('status', 1)
            ->where('sub_ledger_type', $subLedgerType)
            ->limit(2)
            ->get();

        $label = ucfirst($subLedgerType);

        if ($accounts->isEmpty()) {
            throw new \Exception("Configure exactly one active {$label} control account before posting this journal line.");
        }

        if ($accounts->count() > 1) {
            throw new \Exception("Keep exactly one active {$label} control account before posting this journal line.");
        }

        return $accounts->first();
    }

    protected function createSubmissionToken(): string
    {
        $token = (string) Str::uuid();
        session()->put('journal_submission_tokens.' . $token, true);

        return $token;
    }

    protected function consumeSubmissionToken(string $token): void
    {
        if (!session()->pull('journal_submission_tokens.' . $token)) {
            throw new \Exception('This journal submission has already been processed or has expired. Please create a new journal request.');
        }
    }

    protected function restoreSubmissionToken(string $token): void
    {
        session()->put('journal_submission_tokens.' . $token, true);
    }

    /**
     * @return array<int, array{account_id:int,type:string,amount:float,note:?string,sub_ledger_type:?string,sub_ledger_id:?int}>
     */
    protected function parseDetailRows(Request $request): array
    {
        $ledgerSelections = $request->input('ledger_selection', []);
        $debits = $request->input('debit', []);
        $credits = $request->input('credit', []);
        $rowNotes = $request->input('row_note', []);
        $companyId = auth()->user()->company_id;

        if (!is_array($ledgerSelections) || count($ledgerSelections) < 2) {
            throw new \Exception('At least two journal detail rows are required.');
        }

        $rows = [];
        $debitTotal = 0.0;
        $creditTotal = 0.0;

        foreach ($ledgerSelections as $index => $ledgerSelection) {
            [$ledgerType, $ledgerId] = array_pad(explode(':', (string) $ledgerSelection, 2), 2, null);
            $ledgerId = ctype_digit((string) $ledgerId) ? (int) $ledgerId : null;

            if (!in_array($ledgerType, ['account', Account::SUB_LEDGER_CUSTOMER, Account::SUB_LEDGER_SUPPLIER, Account::SUB_LEDGER_EMPLOYEE, Account::SUB_LEDGER_PARTY], true)) {
                throw new \Exception('Ledger Type is invalid on a journal detail row.');
            }

            if (!$ledgerId) {
                throw new \Exception('Ledger / Related Party is required on every journal detail row.');
            }

            if ($ledgerType === 'account') {
                $account = $this->validateAccount($companyId, $ledgerId);

                if ($account->requiresSubLedger()) {
                    throw new \Exception('Select the matching Ledger Type for control account: ' . $account->account_name . '.');
                }

                $subLedgerType = null;
                $subLedgerId = null;
            } else {
                $this->validateSubLedgerEntity($companyId, $ledgerType, $ledgerId);
                $account = $this->resolveControlAccount($companyId, $ledgerType);
                $subLedgerType = $ledgerType;
                $subLedgerId = $ledgerId;
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
                'account_id'       => $account->id,
                'type'             => $type,
                'amount'           => $amount,
                'note'             => isset($rowNotes[$index]) ? trim((string) $rowNotes[$index]) : null,
                'sub_ledger_type'  => $subLedgerType,
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

            $journalItem = JournalItem::create([
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
                'journal_item_id'   => $journalItem->id,
                'description'       => $description,
                'debit'             => $row['type'] === JournalItem::TYPE_DEBIT ? $row['amount'] : 0,
                'credit'            => $row['type'] === JournalItem::TYPE_CREDIT ? $row['amount'] : 0,
            ]);

            $statementData = [
                'company_id' => $companyId,
                'financial_year_id' => $financialYear->id,
                'transaction_date' => $journal->journal_date->format('Y-m-d'),
                'voucher_no' => $journal->journal_no,
                'reference_type' => 'Journal',
                'reference_id' => $journal->id,
                'journal_item_id' => $journalItem->id,
                'reference_no' => $journal->reference_no,
                'description' => $description,
                'debit' => $row['type'] === JournalItem::TYPE_DEBIT ? $row['amount'] : 0,
                'credit' => $row['type'] === JournalItem::TYPE_CREDIT ? $row['amount'] : 0,
                'created_by' => auth()->id(),
                'status' => 1,
            ];

            if ($row['sub_ledger_type'] === Account::SUB_LEDGER_CUSTOMER) {
                CustomerTransactionService::createTransaction($statementData + ['customer_id' => $row['sub_ledger_id']]);
            }

            if ($row['sub_ledger_type'] === Account::SUB_LEDGER_SUPPLIER) {
                SupplierTransactionService::createTransaction($statementData + ['supplier_id' => $row['sub_ledger_id']]);
            }
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
        $submissionToken = $this->createSubmissionToken();

        return view('company.journal.create', array_merge(
            $subLedgerData,
            compact('chartAccounts', 'activeFy', 'submissionToken')
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
            'submission_token' => 'required|uuid',
            'ledger_selection'   => 'required|array|min:2',
            'ledger_selection.*' => 'required|string|max:30',
            'debit'          => 'required|array',
            'credit'         => 'required|array',
            'row_note'       => 'nullable|array',
        ]);

        $file = null;
        $submissionToken = (string) $request->submission_token;
        $submissionConsumed = false;
        $submissionProcessed = false;
        $submissionLock = Cache::lock('journal-submission:' . $submissionToken, 30);

        if (!$submissionLock->get()) {
            return back()->withInput()->with('error', 'This journal submission is already being processed.');
        }

        try {
            $this->consumeSubmissionToken($submissionToken);
            $submissionConsumed = true;

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
                    'posted_by'         => auth()->id(),
                    'posted_at'         => now(),
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

            $submissionProcessed = true;

            return redirect()
                ->route('company.journal.show', $journal->id)
                ->with('success', 'Journal entry saved successfully.');
        } catch (\Exception $e) {
            FileUploadService::deleteFile($file);

            if ($submissionConsumed && !$submissionProcessed) {
                $this->restoreSubmissionToken($submissionToken);
            }

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        } finally {
            $submissionLock->release();
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
            'note' => ValidationService::requiredText(1000),
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $companyId = auth()->user()->company_id;
                $journal = Journal::where('company_id', $companyId)->lockForUpdate()->findOrFail($id);

                abort_unless($journal->isPosted(), 422, 'Only posted journals may be updated.');

                $financialYear = FinancialYear::where('company_id', $companyId)
                    ->whereKey($journal->financial_year_id)
                    ->where('is_active', 1)
                    ->firstOrFail();

                $this->assertDateWithinFinancialYear($request->journal_date, $financialYear);

                $journal->update($this->appendUpdatedBy([
                    'journal_date' => $request->journal_date,
                    'note' => $request->note,
                ], $journal));

                $description = trim($request->note);
                foreach ([AccountTransaction::class, \App\Models\CustomerTransaction::class, \App\Models\SupplierTransaction::class] as $transactionModel) {
                    $transactionModel::where('company_id', $companyId)
                        ->where('reference_type', 'Journal')
                        ->where('reference_id', $journal->id)
                        ->where('status', 1)
                        ->update(['transaction_date' => $request->journal_date, 'description' => $description]);
                }
            });

            return redirect()->route('company.journal.show', $id)->with('success', 'Posted journal date and narration updated successfully.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

    }

    public function reverse(Request $request, $id)
    {
        $this->authorizeCompanyPermission('edit_journal');

        $request->validate([
            'cancel_reason' => ValidationService::requiredText(1000),
        ]);

        try {
            $reversal = DB::transaction(function () use ($request, $id) {
                $companyId = auth()->user()->company_id;
                $original = Journal::with('items')
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->findOrFail($id);

                if (!$original->isPosted() || $original->reversal_of_journal_id) {
                    throw new \Exception('Only an original posted journal can be reversed.');
                }

                if (Journal::where('company_id', $companyId)
                    ->where('reversal_of_journal_id', $original->id)
                    ->lockForUpdate()
                    ->exists()) {
                    throw new \Exception('This journal has already been reversed.');
                }

                $financialYear = FinancialYear::where('company_id', $companyId)
                    ->whereKey($original->financial_year_id)
                    ->firstOrFail();
                $reason = trim($request->cancel_reason);
                $reversal = Journal::create([
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYear->id,
                    'journal_no' => $this->generateJournalNo($companyId, $financialYear),
                    'journal_date' => $original->journal_date,
                    'reference_no' => $original->reference_no,
                    'total_amount' => $original->total_amount,
                    'note' => 'Reversal of ' . $original->journal_no . ': ' . $reason,
                    'created_by' => auth()->id(),
                    'posted_by' => auth()->id(),
                    'posted_at' => now(),
                    'reversal_of_journal_id' => $original->id,
                    'status' => Journal::STATUS_POSTED,
                ]);

                foreach ($original->items as $originalItem) {
                    $originalAccountTransaction = AccountTransaction::where('company_id', $companyId)
                        ->where('reference_type', 'Journal')
                        ->where('reference_id', $original->id)
                        ->where('journal_item_id', $originalItem->id)
                        ->where('status', 1)
                        ->lockForUpdate()
                        ->first();

                    if (!$originalAccountTransaction) {
                        throw new \Exception('Journal reversal requires transaction traceability for every original journal line.');
                    }

                    $reversalItem = JournalItem::create([
                        'company_id' => $companyId,
                        'journal_id' => $reversal->id,
                        'account_id' => $originalItem->account_id,
                        'sub_ledger_type' => $originalItem->sub_ledger_type,
                        'sub_ledger_id' => $originalItem->sub_ledger_id,
                        'type' => $originalItem->type === JournalItem::TYPE_DEBIT ? JournalItem::TYPE_CREDIT : JournalItem::TYPE_DEBIT,
                        'amount' => $originalItem->amount,
                        'note' => 'Reversal of journal item #' . $originalItem->id,
                        'status' => 1,
                    ]);

                    $description = 'Reversal of ' . $original->journal_no . ': ' . $reason;
                    AccountBalanceService::createTransaction([
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYear->id,
                        'account_id' => $originalAccountTransaction->account_id,
                        'transaction_date' => $original->journal_date->format('Y-m-d'),
                        'voucher_no' => $reversal->journal_no,
                        'reference_type' => 'Journal',
                        'reference_id' => $reversal->id,
                        'journal_item_id' => $reversalItem->id,
                        'reversed_transaction_id' => $originalAccountTransaction->id,
                        'description' => $description,
                        'debit' => $originalAccountTransaction->credit,
                        'credit' => $originalAccountTransaction->debit,
                    ], false);

                    $this->createReversalSubLedgerTransaction($original, $reversal, $originalItem, $reversalItem, $financialYear, $description, $companyId);
                }

                $original->update($this->appendUpdatedBy([
                    'status' => Journal::STATUS_REVERSED,
                    'cancelled_by' => auth()->id(),
                    'cancelled_date' => now()->toDateString(),
                    'cancel_reason' => $reason,
                    'reversed_by' => auth()->id(),
                    'reversed_at' => now(),
                ], $original));

                return $reversal;
            });

            return redirect()->route('company.journal.show', $reversal->id)
                ->with('success', 'Journal reversed successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    protected function createReversalSubLedgerTransaction(
        Journal $original,
        Journal $reversal,
        JournalItem $originalItem,
        JournalItem $reversalItem,
        FinancialYear $financialYear,
        string $description,
        int $companyId
    ): void {
        if (!in_array($originalItem->sub_ledger_type, [Account::SUB_LEDGER_CUSTOMER, Account::SUB_LEDGER_SUPPLIER], true)) {
            return;
        }

        $transactionModel = $originalItem->sub_ledger_type === Account::SUB_LEDGER_CUSTOMER
            ? CustomerTransaction::class
            : SupplierTransaction::class;
        $service = $originalItem->sub_ledger_type === Account::SUB_LEDGER_CUSTOMER
            ? CustomerTransactionService::class
            : SupplierTransactionService::class;
        $originalTransaction = $transactionModel::where('company_id', $companyId)
            ->where('reference_type', 'Journal')
            ->where('reference_id', $original->id)
            ->where('journal_item_id', $originalItem->id)
            ->where('status', 1)
            ->lockForUpdate()
            ->firstOrFail();
        $partyKey = $originalItem->sub_ledger_type === Account::SUB_LEDGER_CUSTOMER ? 'customer_id' : 'supplier_id';

        $service::createTransaction([
            'company_id' => $companyId,
            'financial_year_id' => $financialYear->id,
            $partyKey => $originalTransaction->{$partyKey},
            'transaction_date' => $original->journal_date->format('Y-m-d'),
            'voucher_no' => $reversal->journal_no,
            'reference_type' => 'Journal',
            'reference_id' => $reversal->id,
            'journal_item_id' => $reversalItem->id,
            'reversed_transaction_id' => $originalTransaction->id,
            'reference_no' => $originalTransaction->reference_no,
            'description' => $description,
            'debit' => $originalTransaction->credit,
            'credit' => $originalTransaction->debit,
            'created_by' => auth()->id(),
            'status' => 1,
        ]);
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
