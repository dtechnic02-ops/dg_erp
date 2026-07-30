<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\SalesPaymentController;
use App\Models\AccountingEntry;
use App\Models\SalesPayment;
use App\Services\Accounting\Integrations\SalesPaymentAccountingIntegrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class SalesPaymentAccountingTest extends TestCase
{
    private const COMPANY_ID = 1;
    private const FINANCIAL_YEAR_ID = 1;
    private const CUSTOMER_ID = 10;
    private const CASH_ACCOUNT_ID = 100;
    private const BANK_ACCOUNT_ID = 101;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'accounting_entry_lines',
            'accounting_entries',
            'sales_return_refund_adjustments',
            'customer_transactions',
            'account_transactions',
            'sales_payments',
            'sales_invoices',
            'financial_years',
            'chart_accounts',
            'accounts',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('account_type');
            $table->string('status');
        });

        Schema::create('chart_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('system_code');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('invoice_no');
            $table->date('sale_date');
            $table->decimal('grand_total', 20, 4);
            $table->decimal('paid_amount', 20, 4);
            $table->decimal('due_amount', 20, 4);
            $table->string('payment_status');
            $table->integer('status');
            $table->timestamps();
        });

        Schema::create('sales_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('account_id');
            $table->string('payment_no');
            $table->date('payment_date');
            $table->decimal('paid_amount', 20, 4);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('status');
            $table->timestamps();
        });

        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('account_id');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->decimal('debit', 20, 4);
            $table->decimal('credit', 20, 4);
            $table->integer('status');
        });

        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->decimal('debit', 20, 4);
            $table->decimal('credit', 20, 4);
            $table->integer('status');
        });

        Schema::create('sales_return_refund_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->decimal('adjust_amount', 20, 4);
            $table->integer('status');
            $table->softDeletes();
        });

        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('entry_number');
            $table->date('entry_date');
            $table->string('reference_number')->nullable();
            $table->string('source_module');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_event')->nullable();
            $table->string('source_key')->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('reversal_of_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'source_key']);
        });

        Schema::create('accounting_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accounting_entry_id');
            $table->unsignedBigInteger('chart_account_id');
            $table->unsignedBigInteger('operational_account_id')->nullable();
            $table->unsignedInteger('line_number');
            $table->text('description')->nullable();
            $table->decimal('debit', 20, 4);
            $table->decimal('credit', 20, 4);
            $table->string('subledger_type')->nullable();
            $table->unsignedBigInteger('subledger_id')->nullable();
            $table->timestamps();
        });

        DB::table('accounts')->insert([
            ['id' => self::CASH_ACCOUNT_ID, 'company_id' => self::COMPANY_ID, 'account_type' => 'Cash', 'status' => 'active'],
            ['id' => self::BANK_ACCOUNT_ID, 'company_id' => self::COMPANY_ID, 'account_type' => 'Bank', 'status' => 'active'],
            ['id' => 200, 'company_id' => 2, 'account_type' => 'Cash', 'status' => 'active'],
        ]);

        $codes = [
            'CASH_IN_HAND',
            'BANK_ACCOUNTS',
            'ACCOUNTS_RECEIVABLE',
            'SALES_REVENUE',
            'SERVICE_REVENUE',
            'OUTPUT_TAX_PAYABLE',
            'SALES_RETURNS',
            'CUSTOMER_ADVANCE',
        ];

        foreach ([self::COMPANY_ID, 2] as $companyId) {
            DB::table('chart_accounts')->insert(array_map(
                fn (string $code): array => [
                    'company_id' => $companyId,
                    'system_code' => $code,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                $codes
            ));
        }

        DB::table('financial_years')->insert([
            ['id' => self::FINANCIAL_YEAR_ID, 'company_id' => self::COMPANY_ID, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true],
            ['id' => 2, 'company_id' => 2, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true],
        ]);
    }

    public function test_cash_payment_persists_a_balanced_settlement_entry(): void
    {
        $invoiceId = $this->createInvoice(1, '500.0000');
        $payment = $this->createPayment(1, $invoiceId, '500.0000', self::CASH_ACCOUNT_ID);

        $this->postPayment($payment);

        $entry = $this->entryFor($payment->id);
        $this->assertSame('sales_payment', $entry->source_type);
        $this->assertSame($payment->id, $entry->source_id);
        $this->assertSame('created', $entry->source_event);
        $this->assertLine($entry, 'CASH_IN_HAND', '500.0000', '0.0000', self::CASH_ACCOUNT_ID);
        $this->assertLine($entry, 'ACCOUNTS_RECEIVABLE', '0.0000', '500.0000', null, 'customer', self::CUSTOMER_ID);
        $this->assertBalanced($entry);
        $this->assertForbiddenCodesAbsent($entry);
    }

    public function test_bank_payment_uses_bank_accounts_and_not_cash_in_hand(): void
    {
        $invoiceId = $this->createInvoice(2, '500.0000');
        $payment = $this->createPayment(2, $invoiceId, '500.0000', self::BANK_ACCOUNT_ID);

        $this->postPayment($payment);

        $entry = $this->entryFor($payment->id);
        $this->assertLine($entry, 'BANK_ACCOUNTS', '500.0000', '0.0000', self::BANK_ACCOUNT_ID);
        $this->assertLine($entry, 'ACCOUNTS_RECEIVABLE', '0.0000', '500.0000', null, 'customer', self::CUSTOMER_ID);
        $this->assertSame(0, $entry->lines()->whereHas('chartAccount', fn ($query) => $query->where('system_code', 'CASH_IN_HAND'))->count());
        $this->assertBalanced($entry);
    }

    public function test_partial_payment_posts_only_the_payment_and_synchronizes_invoice_state(): void
    {
        $invoiceId = $this->createInvoice(3, '1000.0000');
        $payment = $this->createPayment(3, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID);

        $this->synchronizeInvoice($invoiceId);
        $this->postPayment($payment);

        $invoice = DB::table('sales_invoices')->where('id', $invoiceId)->first();
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
        $first = $this->createPayment(4, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID);
        $this->synchronizeInvoice($invoiceId);
        $this->postPayment($first);

        $second = $this->createPayment(5, $invoiceId, '400.0000', self::BANK_ACCOUNT_ID);
        $this->synchronizeInvoice($invoiceId);
        $this->postPayment($second);

        $third = $this->createPayment(6, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID);
        $this->synchronizeInvoice($invoiceId);
        $this->postPayment($third);

        $invoice = DB::table('sales_invoices')->where('id', $invoiceId)->first();
        $entries = [$this->entryFor($first->id), $this->entryFor($second->id), $this->entryFor($third->id)];

        $this->assertSame('1000.0000', $this->sumDebits(...$entries));
        $this->assertSame('1000.0000', $this->sumCredits(...$entries));
        $this->assertSame('1000.0000', $this->componentCredit($entries, 'ACCOUNTS_RECEIVABLE'));
        $this->assertSame('0.0000', $this->decimal($invoice->due_amount));
        $this->assertSame('paid', $invoice->payment_status);

        foreach ($entries as $entry) {
            $this->assertBalanced($entry);
        }
    }

    public function test_duplicate_payment_posting_is_blocked_without_duplicate_lines(): void
    {
        $invoiceId = $this->createInvoice(5, '300.0000');
        $payment = $this->createPayment(7, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID);

        $this->postPayment($payment);

        try {
            $this->postPayment($payment);
            $this->fail('Duplicate sales payment posting must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('An accounting entry has already been posted for this source key.', $exception->getMessage());
        }

        $this->assertSame(1, AccountingEntry::where('company_id', self::COMPANY_ID)
            ->where('source_type', 'sales_payment')
            ->where('source_id', $payment->id)
            ->where('source_event', 'created')
            ->count());
        $this->assertSame(2, DB::table('accounting_entry_lines')->count());
    }

    public function test_cancellation_creates_an_exact_reversal_and_resynchronizes_the_invoice(): void
    {
        $invoiceId = $this->createInvoice(6, '500.0000');
        $payment = $this->createPayment(8, $invoiceId, '500.0000', self::BANK_ACCOUNT_ID);
        $this->synchronizeInvoice($invoiceId);
        $this->postPayment($payment);
        $original = $this->entryFor($payment->id);

        DB::transaction(function () use ($payment, $invoiceId): void {
            $payment->update(['status' => SalesPayment::STATUS_CANCELLED]);
            $this->synchronizeInvoice($invoiceId);
            $this->integration()->reversePayment($payment, '2026-06-20', 1);
        });

        $reversal = AccountingEntry::where('company_id', self::COMPANY_ID)
            ->where('reversal_of_id', $original->id)
            ->firstOrFail();
        $invoice = DB::table('sales_invoices')->where('id', $invoiceId)->first();

        $this->assertSame('reversed', $original->fresh()->status);
        $this->assertSame('sales_payment', $reversal->source_type);
        $this->assertSame('cancelled', $reversal->source_event);
        $this->assertSame($original->id, $reversal->reversal_of_id);
        $this->assertBalanced($reversal);
        $this->assertSame('0.0000', $this->decimal($invoice->paid_amount));
        $this->assertSame('500.0000', $this->decimal($invoice->due_amount));
        $this->assertSame('unpaid', $invoice->payment_status);

        foreach ($original->lines()->orderBy('line_number')->get() as $line) {
            $reversalLine = $reversal->lines()->where('line_number', $line->line_number)->firstOrFail();
            $this->assertSame($line->chart_account_id, $reversalLine->chart_account_id);
            $this->assertSame($line->operational_account_id, $reversalLine->operational_account_id);
            $this->assertSame($line->credit, $reversalLine->debit);
            $this->assertSame($line->debit, $reversalLine->credit);
        }

        try {
            $this->integration()->reversePayment($payment, '2026-06-20', 1);
            $this->fail('Repeated accounting reversal must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The original posted accounting entry could not be resolved for reversal.', $exception->getMessage());
        }
    }

    public function test_model_class_legacy_source_alias_is_compatible_with_duplicate_and_reversal_lookup(): void
    {
        $invoiceId = $this->createInvoice(7, '300.0000');
        $payment = $this->createPayment(9, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID);
        $legacyId = $this->createLegacyEntry($payment->id, '300.0000');

        try {
            $this->postPayment($payment);
            $this->fail('The supported model-class source alias must block a canonical duplicate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('An accounting entry has already been posted for this source key.', $exception->getMessage());
        }

        $this->integration()->reversePayment($payment, '2026-06-20', 1);
        $this->assertDatabaseHas('accounting_entries', [
            'reversal_of_id' => $legacyId,
            'source_type' => 'sales_payment',
            'source_event' => 'cancelled',
        ]);
    }

    public function test_accounting_failure_rolls_back_the_enclosing_sales_payment_transaction(): void
    {
        $invoiceId = $this->createInvoice(8, '300.0000');
        DB::table('chart_accounts')
            ->where('company_id', self::COMPANY_ID)
            ->where('system_code', 'ACCOUNTS_RECEIVABLE')
            ->delete();

        try {
            DB::transaction(function () use ($invoiceId): void {
                $payment = $this->createPayment(10, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID);
                $this->synchronizeInvoice($invoiceId);
                $this->postPayment($payment);
            });
            $this->fail('A missing required chart account must fail the accounting post.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ACCOUNTS_RECEIVABLE', $exception->getMessage());
        }

        $invoice = DB::table('sales_invoices')->where('id', $invoiceId)->first();
        $this->assertDatabaseMissing('sales_payments', ['id' => 10]);
        $this->assertDatabaseMissing('account_transactions', ['reference_id' => 10]);
        $this->assertDatabaseMissing('customer_transactions', ['reference_id' => 10]);
        $this->assertSame(0, DB::table('accounting_entries')->count());
        $this->assertSame(0, DB::table('accounting_entry_lines')->count());
        $this->assertSame('0.0000', $this->decimal($invoice->paid_amount));
        $this->assertSame('300.0000', $this->decimal($invoice->due_amount));
        $this->assertSame('unpaid', $invoice->payment_status);
    }

    public function test_company_isolation_blocks_foreign_invoice_account_and_chart_account_usage(): void
    {
        $foreignInvoiceId = $this->createInvoice(9, '300.0000', 2, 2, 2);
        $foreignInvoicePayment = $this->createPayment(11, $foreignInvoiceId, '300.0000', self::CASH_ACCOUNT_ID);

        try {
            $this->postPayment($foreignInvoicePayment);
            $this->fail('A payment must not post against another company invoice.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The sales payment, invoice, customer, company, and financial year must belong together.', $exception->getMessage());
        }

        $invoiceId = $this->createInvoice(10, '300.0000');
        $foreignAccountPayment = $this->createPayment(12, $invoiceId, '300.0000', 200);

        try {
            $this->postPayment($foreignAccountPayment);
            $this->fail('A payment must not post against another company operational account.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The sales payment operational account is invalid.', $exception->getMessage());
        }

        $chartInvoiceId = $this->createInvoice(11, '300.0000');
        $chartPayment = $this->createPayment(13, $chartInvoiceId, '300.0000', self::CASH_ACCOUNT_ID);
        DB::table('chart_accounts')
            ->where('company_id', self::COMPANY_ID)
            ->where('system_code', 'ACCOUNTS_RECEIVABLE')
            ->delete();

        try {
            $this->postPayment($chartPayment);
            $this->fail('A payment must not use another company chart account.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ACCOUNTS_RECEIVABLE', $exception->getMessage());
        }

        $this->assertSame(0, DB::table('accounting_entries')->count());
    }

    public function test_financial_year_validation_rejects_a_payment_date_outside_its_financial_year(): void
    {
        $invoiceId = $this->createInvoice(12, '300.0000');
        $payment = $this->createPayment(14, $invoiceId, '300.0000', self::CASH_ACCOUNT_ID, '2027-01-01');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The sales payment date does not belong to its company financial year.');
        $this->postPayment($payment);
    }

    private function createInvoice(int $id, string $total, int $companyId = self::COMPANY_ID, int $financialYearId = self::FINANCIAL_YEAR_ID, int $customerId = self::CUSTOMER_ID): int
    {
        DB::table('sales_invoices')->insert([
            'id' => $id,
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'customer_id' => $customerId,
            'invoice_no' => 'SI-' . $id,
            'sale_date' => '2026-06-15',
            'grand_total' => $total,
            'paid_amount' => '0.0000',
            'due_amount' => $total,
            'payment_status' => 'unpaid',
            'status' => 1,
        ]);

        return $id;
    }

    private function createPayment(int $id, int $invoiceId, string $amount, int $accountId, string $date = '2026-06-15'): SalesPayment
    {
        DB::table('sales_payments')->insert([
            'id' => $id,
            'company_id' => self::COMPANY_ID,
            'financial_year_id' => self::FINANCIAL_YEAR_ID,
            'sales_invoice_id' => $invoiceId,
            'customer_id' => self::CUSTOMER_ID,
            'account_id' => $accountId,
            'payment_no' => 'SP-' . $id,
            'payment_date' => $date,
            'paid_amount' => $amount,
            'created_by' => 1,
            'status' => SalesPayment::STATUS_ACTIVE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_transactions')->insert([
            'company_id' => self::COMPANY_ID,
            'financial_year_id' => self::FINANCIAL_YEAR_ID,
            'account_id' => $accountId,
            'reference_type' => 'sales_payment',
            'reference_id' => $id,
            'debit' => $amount,
            'credit' => '0.0000',
            'status' => 1,
        ]);

        DB::table('customer_transactions')->insert([
            'company_id' => self::COMPANY_ID,
            'financial_year_id' => self::FINANCIAL_YEAR_ID,
            'customer_id' => self::CUSTOMER_ID,
            'reference_type' => 'sales_payment',
            'reference_id' => $id,
            'debit' => '0.0000',
            'credit' => $amount,
            'status' => 1,
        ]);

        return SalesPayment::findOrFail($id);
    }

    private function synchronizeInvoice(int $invoiceId): void
    {
        $invoice = \App\Models\SalesInvoice::findOrFail($invoiceId);
        $controller = new SalesPaymentController($this->integration());
        $method = new ReflectionMethod($controller, 'syncInvoicePaymentState');
        $method->setAccessible(true);
        $method->invoke($controller, $invoice);
    }

    private function postPayment(SalesPayment $payment): void
    {
        $this->integration()->postPayment($payment);
    }

    private function integration(): SalesPaymentAccountingIntegrationService
    {
        return app(SalesPaymentAccountingIntegrationService::class);
    }

    private function entryFor(int $paymentId): AccountingEntry
    {
        return AccountingEntry::query()
            ->where('company_id', self::COMPANY_ID)
            ->where('source_type', 'sales_payment')
            ->where('source_id', $paymentId)
            ->where('source_event', 'created')
            ->firstOrFail();
    }

    private function createLegacyEntry(int $paymentId, string $amount): int
    {
        $entryId = DB::table('accounting_entries')->insertGetId([
            'company_id' => self::COMPANY_ID,
            'entry_number' => 'LEGACY-SP-' . $paymentId,
            'entry_date' => '2026-06-15',
            'source_module' => 'sales_payment',
            'source_type' => SalesPayment::class,
            'source_id' => $paymentId,
            'source_event' => 'created',
            'source_key' => 'sales_payment:' . $paymentId . ':created',
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['CASH_IN_HAND', self::CASH_ACCOUNT_ID, $amount, '0.0000', null, null],
            ['ACCOUNTS_RECEIVABLE', null, '0.0000', $amount, 'customer', self::CUSTOMER_ID],
        ] as $number => [$code, $accountId, $debit, $credit, $subledgerType, $subledgerId]) {
            DB::table('accounting_entry_lines')->insert([
                'accounting_entry_id' => $entryId,
                'chart_account_id' => DB::table('chart_accounts')->where('company_id', self::COMPANY_ID)->where('system_code', $code)->value('id'),
                'operational_account_id' => $accountId,
                'line_number' => $number + 1,
                'debit' => $debit,
                'credit' => $credit,
                'subledger_type' => $subledgerType,
                'subledger_id' => $subledgerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $entryId;
    }

    private function assertLine(AccountingEntry $entry, string $code, string $debit, string $credit, ?int $operationalAccountId = null, ?string $subledgerType = null, ?int $subledgerId = null): void
    {
        $line = $entry->lines()->whereHas('chartAccount', fn ($query) => $query->where('system_code', $code))->firstOrFail();
        $this->assertSame($debit, $line->debit);
        $this->assertSame($credit, $line->credit);
        $this->assertSame($operationalAccountId, $line->operational_account_id);
        $this->assertSame($subledgerType, $line->subledger_type);
        $this->assertSame($subledgerId, $line->subledger_id);
    }

    private function assertBalanced(AccountingEntry $entry): void
    {
        $this->assertSame($this->sumDebits($entry), $this->sumCredits($entry));
    }

    private function sumDebits(AccountingEntry ...$entries): string
    {
        return $this->sumLines($entries, 'debit');
    }

    private function sumCredits(AccountingEntry ...$entries): string
    {
        return $this->sumLines($entries, 'credit');
    }

    private function sumLines(array $entries, string $column): string
    {
        $total = 0.0;

        foreach ($entries as $entry) {
            $total += (float) $entry->lines()->sum($column);
        }

        return number_format($total, 4, '.', '');
    }

    private function componentCredit(array $entries, string $code): string
    {
        $total = 0.0;

        foreach ($entries as $entry) {
            $total += (float) $entry->lines()
                ->whereHas('chartAccount', fn ($query) => $query->where('system_code', $code))
                ->sum('credit');
        }

        return number_format($total, 4, '.', '');
    }

    private function assertForbiddenCodesAbsent(AccountingEntry $entry): void
    {
        $codes = $entry->lines()->with('chartAccount')->get()->pluck('chartAccount.system_code')->all();

        foreach (['SALES_REVENUE', 'SERVICE_REVENUE', 'OUTPUT_TAX_PAYABLE', 'SALES_RETURNS', 'CUSTOMER_ADVANCE'] as $code) {
            $this->assertNotContains($code, $codes);
        }
    }

    private function decimal(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
