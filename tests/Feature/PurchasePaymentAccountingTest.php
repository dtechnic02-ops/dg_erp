<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\PurchasePaymentController;
use App\Models\AccountingEntry;
use App\Models\AccountTransaction;
use App\Models\PurchasePayment;
use App\Models\SupplierTransaction;
use App\Models\User;
use App\Services\AccountBalanceService;
use App\Services\Accounting\Integrations\PurchasePaymentAccountingIntegrationService;
use App\Services\PurchaseInvoicePaymentStateService;
use App\Services\SupplierTransactionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PurchasePaymentAccountingTest extends TestCase
{
    private const COMPANY_ID = 1;
    private const FINANCIAL_YEAR_ID = 1;
    private const SUPPLIER_ID = 10;
    private const CASH_ACCOUNT_ID = 100;
    private const BANK_ACCOUNT_ID = 101;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'accounting_entry_lines', 'accounting_entries', 'purchase_return_refund_adjustments',
            'supplier_transactions', 'account_transactions', 'purchase_payments', 'purchase_invoices',
            'financial_years', 'chart_accounts', 'suppliers', 'accounts', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id')->nullable(); $table->string('name'); $table->string('email'); $table->string('password'); $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('account_type'); $table->string('status'); $table->decimal('current_balance', 20, 4)->default(0); $table->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('name'); $table->decimal('current_balance', 20, 4)->default(0); $table->timestamps();
        });
        Schema::create('chart_accounts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('system_code'); $table->string('status')->default('active'); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('financial_years', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->date('start_date'); $table->date('end_date'); $table->boolean('is_active')->default(true);
        });
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('financial_year_id'); $table->unsignedBigInteger('supplier_id'); $table->string('invoice_no'); $table->date('purchase_date'); $table->decimal('grand_total', 20, 4); $table->decimal('paid_amount', 20, 4); $table->decimal('due_amount', 20, 4); $table->string('payment_status'); $table->integer('status'); $table->timestamps();
        });
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('financial_year_id'); $table->unsignedBigInteger('purchase_invoice_id'); $table->unsignedBigInteger('supplier_id'); $table->unsignedBigInteger('account_id'); $table->string('payment_no'); $table->date('payment_date'); $table->decimal('amount', 20, 4); $table->unsignedBigInteger('created_by')->nullable(); $table->integer('status'); $table->timestamps();
        });
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('financial_year_id'); $table->unsignedBigInteger('account_id'); $table->date('transaction_date')->nullable(); $table->string('voucher_no')->nullable(); $table->string('reference_type'); $table->unsignedBigInteger('reference_id'); $table->unsignedBigInteger('journal_item_id')->nullable(); $table->unsignedBigInteger('reversed_transaction_id')->nullable(); $table->string('description')->nullable(); $table->decimal('debit', 20, 4); $table->decimal('credit', 20, 4); $table->decimal('balance', 20, 4)->default(0); $table->unsignedBigInteger('created_by')->nullable(); $table->integer('status'); $table->timestamps();
        });
        Schema::create('supplier_transactions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('financial_year_id'); $table->unsignedBigInteger('supplier_id'); $table->date('transaction_date')->nullable(); $table->string('voucher_no')->nullable(); $table->string('reference_type'); $table->unsignedBigInteger('reference_id'); $table->unsignedBigInteger('journal_item_id')->nullable(); $table->unsignedBigInteger('reversed_transaction_id')->nullable(); $table->string('reference_no')->nullable(); $table->string('description')->nullable(); $table->decimal('debit', 20, 4); $table->decimal('credit', 20, 4); $table->decimal('balance', 20, 4)->default(0); $table->unsignedBigInteger('created_by')->nullable(); $table->integer('status'); $table->timestamps();
        });
        Schema::create('purchase_return_refund_adjustments', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('purchase_invoice_id'); $table->decimal('adjust_amount', 20, 4); $table->integer('status'); $table->softDeletes();
        });
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('entry_number'); $table->date('entry_date'); $table->string('reference_number')->nullable(); $table->string('source_module'); $table->string('source_type')->nullable(); $table->unsignedBigInteger('source_id')->nullable(); $table->string('source_event')->nullable(); $table->string('source_key')->nullable(); $table->text('description')->nullable(); $table->string('status'); $table->unsignedBigInteger('reversal_of_id')->nullable(); $table->timestamp('posted_at')->nullable(); $table->unsignedBigInteger('posted_by')->nullable(); $table->timestamps(); $table->unique(['company_id', 'source_key']);
        });
        Schema::create('accounting_entry_lines', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('accounting_entry_id'); $table->unsignedBigInteger('chart_account_id'); $table->unsignedBigInteger('operational_account_id')->nullable(); $table->unsignedInteger('line_number'); $table->text('description')->nullable(); $table->decimal('debit', 20, 4); $table->decimal('credit', 20, 4); $table->string('subledger_type')->nullable(); $table->unsignedBigInteger('subledger_id')->nullable(); $table->timestamps();
        });

        DB::table('users')->insert(['id' => 1, 'company_id' => self::COMPANY_ID, 'name' => 'Tester', 'email' => 'tester@example.test', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('accounts')->insert([
            ['id' => self::CASH_ACCOUNT_ID, 'company_id' => self::COMPANY_ID, 'account_type' => 'Cash', 'status' => 'active', 'current_balance' => '1000.0000', 'created_at' => now(), 'updated_at' => now()],
            ['id' => self::BANK_ACCOUNT_ID, 'company_id' => self::COMPANY_ID, 'account_type' => 'Bank', 'status' => 'active', 'current_balance' => '1000.0000', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 200, 'company_id' => 2, 'account_type' => 'Cash', 'status' => 'active', 'current_balance' => '1000.0000', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('suppliers')->insert([
            ['id' => self::SUPPLIER_ID, 'company_id' => self::COMPANY_ID, 'name' => 'Supplier One', 'current_balance' => '0.0000', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 20, 'company_id' => 2, 'name' => 'Supplier Two', 'current_balance' => '0.0000', 'created_at' => now(), 'updated_at' => now()],
        ]);
        foreach ([self::COMPANY_ID, 2] as $companyId) {
            DB::table('chart_accounts')->insert(array_map(fn (string $code): array => ['company_id' => $companyId, 'system_code' => $code, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()], [
                'ACCOUNTS_PAYABLE', 'CASH_IN_HAND', 'BANK_ACCOUNTS', 'INVENTORY', 'INPUT_TAX_RECEIVABLE', 'PURCHASE_RETURNS', 'SALES_REVENUE', 'SERVICE_REVENUE', 'OUTPUT_TAX_PAYABLE', 'ACCOUNTS_RECEIVABLE',
            ]));
        }
        DB::table('financial_years')->insert([
            ['id' => self::FINANCIAL_YEAR_ID, 'company_id' => self::COMPANY_ID, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true],
            ['id' => 2, 'company_id' => 2, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true],
        ]);
    }

    public function test_cash_payment_persists_a_balanced_settlement_entry_and_business_transactions(): void
    {
        $invoiceId = $this->createInvoice(1, '500.0000');
        $payment = $this->createPayment(1, $invoiceId, '500.0000', self::CASH_ACCOUNT_ID);
        $this->synchronizeInvoice($invoiceId);
        $this->postPayment($payment);

        $entry = $this->entryFor($payment->id);
        $this->assertSame('purchase_payment', $entry->source_type);
        $this->assertSame($payment->id, $entry->source_id);
        $this->assertSame('created', $entry->source_event);
        $this->assertLine($entry, 'ACCOUNTS_PAYABLE', '500.0000', '0.0000', null, 'supplier', self::SUPPLIER_ID);
        $this->assertLine($entry, 'CASH_IN_HAND', '0.0000', '500.0000', self::CASH_ACCOUNT_ID);
        $this->assertDatabaseHas('supplier_transactions', ['reference_type' => 'purchase_payment', 'reference_id' => 1, 'debit' => '500']);
        $this->assertDatabaseHas('account_transactions', ['reference_type' => 'purchase_payment', 'reference_id' => 1, 'credit' => '500']);
        $this->assertBalanced($entry);
        $this->assertForbiddenCodesAbsent($entry);
    }

    public function test_bank_payment_uses_bank_accounts_and_not_cash_in_hand(): void
    {
        $invoiceId = $this->createInvoice(2, '500.0000');
        $payment = $this->createPayment(2, $invoiceId, '500.0000', self::BANK_ACCOUNT_ID);
        $this->postPayment($payment);

        $entry = $this->entryFor($payment->id);
        $this->assertLine($entry, 'ACCOUNTS_PAYABLE', '500.0000', '0.0000', null, 'supplier', self::SUPPLIER_ID);
        $this->assertLine($entry, 'BANK_ACCOUNTS', '0.0000', '500.0000', self::BANK_ACCOUNT_ID);
        $this->assertSame(0, $entry->lines()->whereHas('chartAccount', fn ($query) => $query->where('system_code', 'CASH_IN_HAND'))->count());
        $this->assertBalanced($entry);
    }

    public function test_partial_payment_posts_only_the_settlement_and_synchronizes_invoice_state(): void
    {
        $invoiceId = $this->createInvoice(3, '1000.0000');
        $payment = $this->createPayment(3, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID);
        $this->synchronizeInvoice($invoiceId);
        $this->postPayment($payment);

        $invoice = DB::table('purchase_invoices')->where('id', $invoiceId)->first();
        $entry = $this->entryFor($payment->id);
        $this->assertSame('300.0000', $this->decimal($invoice->paid_amount));
        $this->assertSame('700.0000', $this->decimal($invoice->due_amount));
        $this->assertSame('partial', $invoice->payment_status);
        $this->assertSame('300.0000', $this->sumDebits($entry));
        $this->assertSame('300.0000', $this->sumCredits($entry));
        $this->assertForbiddenCodesAbsent($entry);
    }

    public function test_multiple_payments_create_individual_settlement_entries_and_fully_pay_the_invoice(): void
    {
        $invoiceId = $this->createInvoice(4, '1000.0000');
        $first = $this->createPayment(4, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID); $this->synchronizeInvoice($invoiceId); $this->postPayment($first);
        $second = $this->createPayment(5, $invoiceId, '400.0000', self::BANK_ACCOUNT_ID); $this->synchronizeInvoice($invoiceId); $this->postPayment($second);
        $third = $this->createPayment(6, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID); $this->synchronizeInvoice($invoiceId); $this->postPayment($third);

        $entries = [$this->entryFor($first->id), $this->entryFor($second->id), $this->entryFor($third->id)];
        $invoice = DB::table('purchase_invoices')->where('id', $invoiceId)->first();
        $this->assertSame('1000.0000', $this->componentDebit($entries, 'ACCOUNTS_PAYABLE'));
        $this->assertSame('1000.0000', $this->sumCredits(...$entries));
        $this->assertSame('0.0000', $this->decimal($invoice->due_amount));
        $this->assertSame('paid', $invoice->payment_status);
        foreach ($entries as $entry) { $this->assertBalanced($entry); }
    }

    public function test_insufficient_account_balance_rejects_the_business_transaction_without_persisting_records(): void
    {
        $invoiceId = $this->createInvoice(5, '300.0000');
        DB::table('accounts')->where('id', self::CASH_ACCOUNT_ID)->update(['current_balance' => '100.0000']);

        try {
            DB::transaction(function () use ($invoiceId): void {
                $account = DB::table('accounts')->where('id', self::CASH_ACCOUNT_ID)->first();
                if ((float) $account->current_balance < 300.0) { throw new RuntimeException('Insufficient account balance.'); }
                $this->createPayment(7, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID);
            });
            $this->fail('An insufficient operational account balance must reject the payment.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Insufficient account balance.', $exception->getMessage());
        }

        $invoice = DB::table('purchase_invoices')->where('id', $invoiceId)->first();
        $this->assertDatabaseMissing('purchase_payments', ['id' => 7]);
        $this->assertSame(0, DB::table('supplier_transactions')->count());
        $this->assertSame(0, DB::table('account_transactions')->count());
        $this->assertSame(0, DB::table('accounting_entries')->count());
        $this->assertSame(0, DB::table('accounting_entry_lines')->count());
        $this->assertSame('0.0000', $this->decimal($invoice->paid_amount));
    }

    public function test_duplicate_payment_posting_is_blocked_without_duplicate_lines(): void
    {
        $invoiceId = $this->createInvoice(6, '300.0000');
        $payment = $this->createPayment(8, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID);
        $this->postPayment($payment);
        try { $this->postPayment($payment); $this->fail('Duplicate purchase payment posting must be rejected.'); } catch (RuntimeException $exception) { $this->assertSame('An accounting entry has already been posted for this source key.', $exception->getMessage()); }
        $this->assertSame(1, AccountingEntry::where('company_id', self::COMPANY_ID)->where('source_type', 'purchase_payment')->where('source_id', $payment->id)->where('source_event', 'created')->count());
        $this->assertSame(2, DB::table('accounting_entry_lines')->count());
    }

    public function test_cancellation_creates_exact_accounting_and_business_reversals_and_resynchronizes_invoice(): void
    {
        $invoiceId = $this->createInvoice(7, '500.0000');
        $payment = $this->createPayment(9, $invoiceId, '500.0000', self::BANK_ACCOUNT_ID);
        $this->synchronizeInvoice($invoiceId); $this->postPayment($payment);
        $original = $this->entryFor($payment->id);

        DB::transaction(function () use ($payment, $invoiceId): void {
            $accountTransaction = AccountTransaction::where('reference_type', 'purchase_payment')->where('reference_id', $payment->id)->firstOrFail();
            $supplierTransaction = SupplierTransaction::where('reference_type', 'purchase_payment')->where('reference_id', $payment->id)->firstOrFail();
            AccountBalanceService::reverseTransaction($accountTransaction, 'purchase_payment_cancel', 'Purchase Payment Cancel: test', '2026-06-20', self::FINANCIAL_YEAR_ID);
            SupplierTransactionService::reverseTransaction($supplierTransaction, 'purchase_payment_cancel', 'Purchase Payment Cancel: test', '2026-06-20', self::FINANCIAL_YEAR_ID, 'test');
            $payment->update(['status' => PurchasePayment::STATUS_CANCELLED]);
            $this->synchronizeInvoice($invoiceId);
            $this->integration()->reversePayment($payment, '2026-06-20', 1);
        });

        $reversal = AccountingEntry::where('reversal_of_id', $original->id)->firstOrFail();
        $invoice = DB::table('purchase_invoices')->where('id', $invoiceId)->first();
        $this->assertSame('reversed', $original->fresh()->status);
        $this->assertSame('cancelled', $reversal->source_event);
        $this->assertSame($original->id, $reversal->reversal_of_id);
        $this->assertBalanced($reversal);
        $this->assertDatabaseHas('account_transactions', ['reference_type' => 'purchase_payment_cancel', 'reference_id' => $payment->id]);
        $this->assertDatabaseHas('supplier_transactions', ['reference_type' => 'purchase_payment_cancel', 'reference_id' => $payment->id]);
        $this->assertSame('0.0000', $this->decimal($invoice->paid_amount));
        $this->assertSame('500.0000', $this->decimal($invoice->due_amount));
        $this->assertSame('unpaid', $invoice->payment_status);
        foreach ($original->lines()->orderBy('line_number')->get() as $line) { $reversalLine = $reversal->lines()->where('line_number', $line->line_number)->firstOrFail(); $this->assertSame($line->chart_account_id, $reversalLine->chart_account_id); $this->assertSame($line->operational_account_id, $reversalLine->operational_account_id); $this->assertSame($line->credit, $reversalLine->debit); $this->assertSame($line->debit, $reversalLine->credit); }
        try { $this->integration()->reversePayment($payment, '2026-06-20', 1); $this->fail('Repeated accounting reversal must be rejected.'); } catch (RuntimeException $exception) { $this->assertSame('The original posted accounting entry could not be resolved for reversal.', $exception->getMessage()); }
    }

    public function test_model_class_source_alias_is_compatible_with_duplicate_and_reversal_lookup(): void
    {
        $invoiceId = $this->createInvoice(8, '300.0000');
        $payment = $this->createPayment(10, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID);
        $legacyId = $this->createLegacyEntry($payment->id, '300.0000');
        try { $this->postPayment($payment); $this->fail('The supported model-class source alias must block a canonical duplicate.'); } catch (RuntimeException $exception) { $this->assertSame('An accounting entry has already been posted for this source key.', $exception->getMessage()); }
        $this->integration()->reversePayment($payment, '2026-06-20', 1);
        $this->assertDatabaseHas('accounting_entries', ['reversal_of_id' => $legacyId, 'source_type' => 'purchase_payment', 'source_event' => 'cancelled']);
    }

    public function test_accounting_failure_rolls_back_the_enclosing_purchase_payment_transaction(): void
    {
        $invoiceId = $this->createInvoice(9, '300.0000');
        DB::table('chart_accounts')->where('company_id', self::COMPANY_ID)->where('system_code', 'ACCOUNTS_PAYABLE')->delete();
        try {
            DB::transaction(function () use ($invoiceId): void { $payment = $this->createPayment(11, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID); $this->synchronizeInvoice($invoiceId); $this->postPayment($payment); });
            $this->fail('A missing required chart account must fail accounting posting.');
        } catch (RuntimeException $exception) { $this->assertStringContainsString('ACCOUNTS_PAYABLE', $exception->getMessage()); }
        $invoice = DB::table('purchase_invoices')->where('id', $invoiceId)->first();
        $this->assertDatabaseMissing('purchase_payments', ['id' => 11]); $this->assertDatabaseMissing('supplier_transactions', ['reference_id' => 11]); $this->assertDatabaseMissing('account_transactions', ['reference_id' => 11]);
        $this->assertSame(0, DB::table('accounting_entries')->count()); $this->assertSame(0, DB::table('accounting_entry_lines')->count()); $this->assertSame('0.0000', $this->decimal($invoice->paid_amount)); $this->assertSame('300.0000', $this->decimal($invoice->due_amount)); $this->assertSame('1000.0000', $this->decimal(DB::table('accounts')->where('id', self::CASH_ACCOUNT_ID)->value('current_balance')));
    }

    public function test_company_isolation_blocks_foreign_invoice_account_chart_and_supplier_relationships(): void
    {
        $foreignInvoiceId = $this->createInvoice(10, '300.0000', 2, 2, 20);
        $foreignInvoicePayment = $this->createPayment(12, $foreignInvoiceId, '300.0000', self::CASH_ACCOUNT_ID);
        try { $this->postPayment($foreignInvoicePayment); $this->fail('A payment must not post against another company invoice.'); } catch (RuntimeException $exception) { $this->assertSame('The purchase payment, invoice, supplier, company, and financial year must belong together.', $exception->getMessage()); }

        $invoiceId = $this->createInvoice(11, '300.0000');
        $foreignAccountPayment = $this->createPayment(13, $invoiceId, '300.0000', 200);
        try { $this->postPayment($foreignAccountPayment); $this->fail('A payment must not post against another company operational account.'); } catch (RuntimeException $exception) { $this->assertSame('The purchase payment operational account is invalid.', $exception->getMessage()); }

        $foreignSupplierPayment = $this->createPayment(14, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID, '2026-06-15', 20);
        try { $this->postPayment($foreignSupplierPayment); $this->fail('A payment must not post against another company supplier relationship.'); } catch (RuntimeException $exception) { $this->assertSame('The purchase payment, invoice, supplier, company, and financial year must belong together.', $exception->getMessage()); }

        $chartInvoiceId = $this->createInvoice(12, '300.0000'); $chartPayment = $this->createPayment(15, $chartInvoiceId, '300.0000', self::CASH_ACCOUNT_ID);
        DB::table('chart_accounts')->where('company_id', self::COMPANY_ID)->where('system_code', 'ACCOUNTS_PAYABLE')->delete();
        try { $this->postPayment($chartPayment); $this->fail('A payment must not use another company chart account.'); } catch (RuntimeException $exception) { $this->assertStringContainsString('ACCOUNTS_PAYABLE', $exception->getMessage()); }
        $this->assertSame(0, DB::table('accounting_entries')->count());
    }

    public function test_financial_year_and_invalid_cancellation_date_leave_records_unchanged(): void
    {
        $invoiceId = $this->createInvoice(13, '300.0000');
        $outsideFy = $this->createPayment(16, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID, '2027-01-01');
        try { $this->postPayment($outsideFy); $this->fail('A payment outside its financial year must be rejected.'); } catch (RuntimeException $exception) { $this->assertSame('The purchase payment date does not belong to its company financial year.', $exception->getMessage()); }

        $validInvoiceId = $this->createInvoice(14, '300.0000');
        $validPayment = $this->createPayment(17, $validInvoiceId, '100.0000', self::CASH_ACCOUNT_ID); $this->synchronizeInvoice($validInvoiceId); $this->postPayment($validPayment);
        $this->actingAs(User::findOrFail(1));
        app(PurchasePaymentController::class)->cancel(new Request(['cancel_date' => '2027-01-01', 'cancel_reason' => 'Invalid date']), $validPayment->id);
        $invoice = DB::table('purchase_invoices')->where('id', $validInvoiceId)->first();
        $this->assertSame(PurchasePayment::STATUS_ACTIVE, (int) PurchasePayment::findOrFail($validPayment->id)->status);
        $this->assertSame('100.0000', $this->decimal($invoice->paid_amount)); $this->assertSame('200.0000', $this->decimal($invoice->due_amount));
        $this->assertSame(1, AccountingEntry::where('source_id', $validPayment->id)->where('source_event', 'created')->count()); $this->assertSame(0, AccountingEntry::where('source_id', $validPayment->id)->where('source_event', 'cancelled')->count());
    }

    private function createInvoice(int $id, string $total, int $companyId = self::COMPANY_ID, int $financialYearId = self::FINANCIAL_YEAR_ID, int $supplierId = self::SUPPLIER_ID): int
    {
        DB::table('purchase_invoices')->insert(['id' => $id, 'company_id' => $companyId, 'financial_year_id' => $financialYearId, 'supplier_id' => $supplierId, 'invoice_no' => 'PI-' . $id, 'purchase_date' => '2026-06-15', 'grand_total' => $total, 'paid_amount' => '0.0000', 'due_amount' => $total, 'payment_status' => 'unpaid', 'status' => 1, 'created_at' => now(), 'updated_at' => now()]); return $id;
    }

    private function createPayment(int $id, int $invoiceId, string $amount, int $accountId, string $date = '2026-06-15', int $supplierId = self::SUPPLIER_ID): PurchasePayment
    {
        DB::table('purchase_payments')->insert(['id' => $id, 'company_id' => self::COMPANY_ID, 'financial_year_id' => self::FINANCIAL_YEAR_ID, 'purchase_invoice_id' => $invoiceId, 'supplier_id' => $supplierId, 'account_id' => $accountId, 'payment_no' => 'PP-' . $id, 'payment_date' => $date, 'amount' => $amount, 'created_by' => 1, 'status' => PurchasePayment::STATUS_ACTIVE, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('account_transactions')->insert(['company_id' => self::COMPANY_ID, 'financial_year_id' => self::FINANCIAL_YEAR_ID, 'account_id' => $accountId, 'transaction_date' => $date, 'voucher_no' => 'PP-' . $id, 'reference_type' => 'purchase_payment', 'reference_id' => $id, 'description' => 'Purchase Payment', 'debit' => '0.0000', 'credit' => $amount, 'balance' => '0.0000', 'created_by' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('supplier_transactions')->insert(['company_id' => self::COMPANY_ID, 'financial_year_id' => self::FINANCIAL_YEAR_ID, 'supplier_id' => $supplierId, 'transaction_date' => $date, 'voucher_no' => 'PP-' . $id, 'reference_type' => 'purchase_payment', 'reference_id' => $id, 'reference_no' => 'PP-' . $id, 'description' => 'Purchase Payment', 'debit' => $amount, 'credit' => '0.0000', 'balance' => '0.0000', 'created_by' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        return PurchasePayment::findOrFail($id);
    }

    private function synchronizeInvoice(int $invoiceId): void { PurchaseInvoicePaymentStateService::syncInvoicePaymentState(\App\Models\PurchaseInvoice::findOrFail($invoiceId)); }
    private function postPayment(PurchasePayment $payment): void { $this->integration()->postPayment($payment); }
    private function integration(): PurchasePaymentAccountingIntegrationService { return app(PurchasePaymentAccountingIntegrationService::class); }
    private function entryFor(int $paymentId): AccountingEntry { return AccountingEntry::where('company_id', self::COMPANY_ID)->where('source_type', 'purchase_payment')->where('source_id', $paymentId)->where('source_event', 'created')->firstOrFail(); }

    private function createLegacyEntry(int $paymentId, string $amount): int
    {
        $entryId = DB::table('accounting_entries')->insertGetId(['company_id' => self::COMPANY_ID, 'entry_number' => 'LEGACY-PP-' . $paymentId, 'entry_date' => '2026-06-15', 'source_module' => 'purchase_payment', 'source_type' => PurchasePayment::class, 'source_id' => $paymentId, 'source_event' => 'created', 'source_key' => 'purchase_payment:' . $paymentId . ':created', 'status' => 'posted', 'posted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        foreach ([['ACCOUNTS_PAYABLE', null, $amount, '0.0000', 'supplier', self::SUPPLIER_ID], ['CASH_IN_HAND', self::CASH_ACCOUNT_ID, '0.0000', $amount, null, null]] as $number => [$code, $accountId, $debit, $credit, $subledgerType, $subledgerId]) { DB::table('accounting_entry_lines')->insert(['accounting_entry_id' => $entryId, 'chart_account_id' => DB::table('chart_accounts')->where('company_id', self::COMPANY_ID)->where('system_code', $code)->value('id'), 'operational_account_id' => $accountId, 'line_number' => $number + 1, 'debit' => $debit, 'credit' => $credit, 'subledger_type' => $subledgerType, 'subledger_id' => $subledgerId, 'created_at' => now(), 'updated_at' => now()]); }
        return $entryId;
    }

    private function assertLine(AccountingEntry $entry, string $code, string $debit, string $credit, ?int $operationalAccountId = null, ?string $subledgerType = null, ?int $subledgerId = null): void
    {
        $line = $entry->lines()->whereHas('chartAccount', fn ($query) => $query->where('system_code', $code))->firstOrFail();
        $this->assertSame($debit, $line->debit); $this->assertSame($credit, $line->credit); $this->assertSame($operationalAccountId, $line->operational_account_id); $this->assertSame($subledgerType, $line->subledger_type); $this->assertSame($subledgerId, $line->subledger_id);
    }
    private function assertBalanced(AccountingEntry $entry): void { $this->assertSame($this->sumDebits($entry), $this->sumCredits($entry)); }
    private function sumDebits(AccountingEntry ...$entries): string { return $this->sumLines($entries, 'debit'); }
    private function sumCredits(AccountingEntry ...$entries): string { return $this->sumLines($entries, 'credit'); }
    private function sumLines(array $entries, string $column): string { $total = 0.0; foreach ($entries as $entry) { $total += (float) $entry->lines()->sum($column); } return number_format($total, 4, '.', ''); }
    private function componentDebit(array $entries, string $code): string { $total = 0.0; foreach ($entries as $entry) { $total += (float) $entry->lines()->whereHas('chartAccount', fn ($query) => $query->where('system_code', $code))->sum('debit'); } return number_format($total, 4, '.', ''); }
    private function assertForbiddenCodesAbsent(AccountingEntry $entry): void { $codes = $entry->lines()->with('chartAccount')->get()->pluck('chartAccount.system_code')->all(); foreach (['INVENTORY', 'INPUT_TAX_RECEIVABLE', 'PURCHASE_RETURNS', 'SALES_REVENUE', 'SERVICE_REVENUE', 'OUTPUT_TAX_PAYABLE', 'ACCOUNTS_RECEIVABLE'] as $code) { $this->assertNotContains($code, $codes); } }
    private function decimal(mixed $value): string { return number_format((float) $value, 4, '.', ''); }
}
