<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Services\Accounting\Integrations\CustomerOpeningBalanceAccountingIntegrationService;
use App\Services\Accounting\Integrations\SupplierOpeningBalanceAccountingIntegrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class OpeningBalanceAccountingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['accounting_entry_lines', 'accounting_entries', 'customer_transactions', 'supplier_transactions', 'customers', 'suppliers', 'chart_accounts', 'financial_years'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('financial_years', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('chart_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->string('account_class');
            $table->string('account_category')->nullable();
            $table->string('normal_balance');
            $table->string('system_code')->nullable();
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_control')->default(false);
            $table->boolean('allow_manual_entry')->default(true);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('name');
            $table->decimal('opening_balance', 20, 4)->default(0);
            $table->decimal('current_balance', 20, 4)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('name');
            $table->decimal('opening_balance', 20, 4)->default(0);
            $table->decimal('current_balance', 20, 4)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        foreach (['supplier_transactions' => 'supplier_id', 'customer_transactions' => 'customer_id'] as $table => $partyColumn) {
            Schema::create($table, function (Blueprint $table) use ($partyColumn): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('financial_year_id');
                $table->unsignedBigInteger($partyColumn);
                $table->date('transaction_date');
                $table->string('voucher_no')->nullable();
                $table->string('reference_type');
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('reference_no')->nullable();
                $table->text('description')->nullable();
                $table->decimal('debit', 20, 4)->default(0);
                $table->decimal('credit', 20, 4)->default(0);
                $table->decimal('balance', 20, 4)->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        Schema::create('accounting_entries', function (Blueprint $table): void {
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

        Schema::create('accounting_entry_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('accounting_entry_id');
            $table->unsignedBigInteger('chart_account_id');
            $table->unsignedBigInteger('operational_account_id')->nullable();
            $table->unsignedInteger('line_number');
            $table->text('description')->nullable();
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->string('subledger_type')->nullable();
            $table->unsignedBigInteger('subledger_id')->nullable();
            $table->timestamps();
        });

        foreach ([1, 2] as $companyId) {
            DB::table('financial_years')->insert([
                'company_id' => $companyId,
                'name' => '2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function test_supplier_opening_balance_posts_a_balanced_payable_entry_with_supplier_subledger_metadata(): void
    {
        $this->seedOpeningAccounts(1);
        $supplier = $this->supplier('125.0000');
        $this->supplierTransaction($supplier, '125.0000');

        $this->supplierIntegration()->postOpeningBalance($supplier);

        $entry = $this->entry('supplier_opening_balance', $supplier->id);
        $this->assertSame('supplier', $entry->source_module);
        $this->assertSame('created', $entry->source_event);
        $this->assertSame('supplier-opening-balance:' . $supplier->id, $entry->source_key);
        $this->assertLine($entry, 'OPENING_BALANCE_EQUITY', '125.0000', '0.0000');
        $this->assertLine($entry, 'ACCOUNTS_PAYABLE', '0.0000', '125.0000', 'supplier', $supplier->id);
        $this->assertBalanced($entry);
    }

    public function test_customer_opening_balance_posts_a_balanced_receivable_entry_with_customer_subledger_metadata(): void
    {
        $this->seedOpeningAccounts(1);
        $customer = $this->customer('175.0000');
        $this->customerTransaction($customer, '175.0000');

        $this->customerIntegration()->postOpeningBalance($customer);

        $entry = $this->entry('customer_opening_balance', $customer->id);
        $this->assertSame('customer', $entry->source_module);
        $this->assertSame('created', $entry->source_event);
        $this->assertSame('customer-opening-balance:' . $customer->id, $entry->source_key);
        $this->assertLine($entry, 'ACCOUNTS_RECEIVABLE', '175.0000', '0.0000', 'customer', $customer->id);
        $this->assertLine($entry, 'OPENING_BALANCE_EQUITY', '0.0000', '175.0000');
        $this->assertBalanced($entry);
    }

    public function test_zero_opening_balance_creates_no_subledger_or_accounting_entry(): void
    {
        $supplier = $this->supplier('0.0000');
        $customer = $this->customer('0.0000');

        $this->assertSame(0, SupplierTransaction::where('supplier_id', $supplier->id)->count());
        $this->assertSame(0, CustomerTransaction::where('customer_id', $customer->id)->count());
        $this->assertSame(0, AccountingEntry::count());
    }

    public function test_duplicate_supplier_opening_balance_posting_is_rejected(): void
    {
        $this->seedOpeningAccounts(1);
        $supplier = $this->supplier('100.0000');
        $this->supplierTransaction($supplier, '100.0000');
        $this->supplierIntegration()->postOpeningBalance($supplier);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An accounting entry has already been posted for this source key.');

        $this->supplierIntegration()->postOpeningBalance($supplier);
    }

    public function test_missing_inactive_or_foreign_chart_accounts_reject_posting_without_entries(): void
    {
        $supplier = $this->supplier('100.0000');
        $this->supplierTransaction($supplier, '100.0000');

        $this->seedOpeningAccounts(2);
        try {
            $this->supplierIntegration()->postOpeningBalance($supplier);
            $this->fail('Foreign-company chart accounts must not be used.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('OPENING_BALANCE_EQUITY', $exception->getMessage());
        }

        $this->seedOpeningAccounts(1);
        DB::table('chart_accounts')->where('company_id', 1)->where('system_code', 'ACCOUNTS_PAYABLE')->update(['status' => 'inactive']);
        try {
            $this->supplierIntegration()->postOpeningBalance($supplier);
            $this->fail('Inactive chart accounts must not be used.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ACCOUNTS_PAYABLE', $exception->getMessage());
        }

        $this->assertSame(0, AccountingEntry::count());
    }

    public function test_accounting_failure_rolls_back_supplier_and_subledger_creation_in_the_outer_transaction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENING_BALANCE_EQUITY');

        try {
            DB::transaction(function (): void {
                $supplier = $this->supplier('100.0000');
                $this->supplierTransaction($supplier, '100.0000');
                $this->supplierIntegration()->postOpeningBalance($supplier);
            });
        } finally {
            $this->assertSame(0, Supplier::count());
            $this->assertSame(0, SupplierTransaction::count());
            $this->assertSame(0, AccountingEntry::count());
        }
    }

    private function seedOpeningAccounts(int $companyId): void
    {
        foreach ([
            ['code' => '1130', 'name' => 'Accounts Receivable', 'class' => 'asset', 'system' => 'ACCOUNTS_RECEIVABLE', 'normal' => 'debit'],
            ['code' => '2110', 'name' => 'Accounts Payable', 'class' => 'liability', 'system' => 'ACCOUNTS_PAYABLE', 'normal' => 'credit'],
            ['code' => '3140', 'name' => 'Opening Balance Equity', 'class' => 'equity', 'system' => 'OPENING_BALANCE_EQUITY', 'normal' => 'credit'],
        ] as $account) {
            DB::table('chart_accounts')->updateOrInsert(
                ['company_id' => $companyId, 'system_code' => $account['system']],
                [
                    'code' => $account['code'], 'name' => $account['name'], 'account_class' => $account['class'],
                    'normal_balance' => $account['normal'], 'status' => 'active', 'is_system' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]
            );
        }
    }

    private function supplier(string $amount): Supplier
    {
        return Supplier::create(['company_id' => 1, 'name' => 'Supplier', 'opening_balance' => $amount, 'current_balance' => $amount, 'status' => 'active']);
    }

    private function customer(string $amount): Customer
    {
        return Customer::create(['company_id' => 1, 'name' => 'Customer', 'opening_balance' => $amount, 'current_balance' => $amount, 'status' => 'active']);
    }

    private function supplierTransaction(Supplier $supplier, string $amount): void
    {
        SupplierTransaction::create([
            'company_id' => 1, 'financial_year_id' => 1, 'supplier_id' => $supplier->id, 'transaction_date' => '2026-01-01',
            'voucher_no' => 'OPEN-' . $supplier->id, 'reference_type' => 'opening_balance', 'reference_id' => $supplier->id,
            'reference_no' => 'OPEN-' . $supplier->id, 'debit' => '0.0000', 'credit' => $amount, 'balance' => '0.0000', 'status' => 1,
        ]);
    }

    private function customerTransaction(Customer $customer, string $amount): void
    {
        CustomerTransaction::create([
            'company_id' => 1, 'financial_year_id' => 1, 'customer_id' => $customer->id, 'transaction_date' => '2026-01-01',
            'voucher_no' => 'OPEN-' . $customer->id, 'reference_type' => 'opening_balance', 'reference_id' => $customer->id,
            'reference_no' => 'OPEN-' . $customer->id, 'debit' => $amount, 'credit' => '0.0000', 'balance' => '0.0000', 'status' => 1,
        ]);
    }

    private function supplierIntegration(): SupplierOpeningBalanceAccountingIntegrationService
    {
        return app(SupplierOpeningBalanceAccountingIntegrationService::class);
    }

    private function customerIntegration(): CustomerOpeningBalanceAccountingIntegrationService
    {
        return app(CustomerOpeningBalanceAccountingIntegrationService::class);
    }

    private function entry(string $sourceType, int $sourceId): AccountingEntry
    {
        return AccountingEntry::where('source_type', $sourceType)->where('source_id', $sourceId)->where('source_event', 'created')->firstOrFail();
    }

    private function assertLine(AccountingEntry $entry, string $systemCode, string $debit, string $credit, ?string $subledgerType = null, ?int $subledgerId = null): void
    {
        $line = $entry->lines()->whereHas('chartAccount', fn ($query) => $query->where('system_code', $systemCode))->firstOrFail();
        $this->assertSame($debit, $line->debit);
        $this->assertSame($credit, $line->credit);
        $this->assertSame($subledgerType, $line->subledger_type);
        $this->assertSame($subledgerId, $line->subledger_id);
    }

    private function assertBalanced(AccountingEntry $entry): void
    {
        $this->assertSame($this->decimal($entry->lines()->sum('debit')), $this->decimal($entry->lines()->sum('credit')));
    }

    private function decimal(mixed $amount): string
    {
        return number_format((float) $amount, 4, '.', '');
    }
}
