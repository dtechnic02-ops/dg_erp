<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Services\Accounting\PurchaseAccountingIntegrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PurchaseProductServiceAccountingTest extends TestCase
{
    private const COMPANY_ID = 1;
    private const FOREIGN_COMPANY_ID = 2;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['accounting_entry_lines', 'accounting_entries', 'purchase_payments', 'purchase_items', 'purchase_invoices', 'chart_accounts', 'accounts'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('account_type');
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('chart_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('code');
            $table->string('name');
            $table->string('account_class');
            $table->string('system_code')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('purchase_invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('invoice_no');
            $table->date('purchase_date');
            $table->decimal('discount', 20, 4)->default(0);
            $table->decimal('total_vat', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4);
            $table->decimal('paid_amount', 20, 4)->default(0);
            $table->decimal('due_amount', 20, 4)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });
        Schema::create('purchase_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->string('item_type');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('total_price', 20, 4);
            $table->decimal('vat_amount', 20, 4)->default(0);
            $table->integer('status')->default(1);
            $table->timestamps();
        });
        Schema::create('purchase_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('account_id');
            $table->string('payment_no');
            $table->date('payment_date');
            $table->decimal('amount', 20, 4);
            $table->integer('status')->default(1);
            $table->timestamps();
        });
        Schema::create('accounting_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('entry_number');
            $table->date('entry_date');
            $table->string('reference_number')->nullable();
            $table->string('source_module');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_event');
            $table->string('source_key');
            $table->text('description')->nullable();
            $table->string('status');
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

        $this->seedChartAccounts(self::COMPANY_ID);
        $this->seedChartAccounts(self::FOREIGN_COMPANY_ID);
        DB::table('accounts')->insert([
            ['id' => 10, 'company_id' => self::COMPANY_ID, 'account_type' => 'Cash', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 20, 'company_id' => self::FOREIGN_COMPANY_ID, 'account_type' => 'Cash', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_product_only_credit_purchase_posts_inventory_and_supplier_payable(): void
    {
        $purchase = $this->purchase('100.0000', '0.0000', '0.0000', '100.0000');
        $this->item($purchase, 'product', '100.0000');

        $entry = $this->postPurchase($purchase);

        $this->assertLine($entry, 'INVENTORY', '100.0000', '0.0000');
        $this->assertLine($entry, 'ACCOUNTS_PAYABLE', '0.0000', '100.0000', 'supplier', 50);
        $this->assertNoLine($entry, 'SERVICE_PURCHASE_EXPENSE');
        $this->assertBalanced($entry);
    }

    public function test_service_only_cash_purchase_posts_service_expense_tax_and_cash_without_inventory(): void
    {
        $purchase = $this->purchase('110.0000', '10.0000', '110.0000', '0.0000');
        $this->item($purchase, 'service', '110.0000', '10.0000');
        $this->payment($purchase, 10, '110.0000');

        $entry = $this->postPurchase($purchase);

        $this->assertLine($entry, 'SERVICE_PURCHASE_EXPENSE', '100.0000', '0.0000');
        $this->assertLine($entry, 'INPUT_TAX_RECEIVABLE', '10.0000', '0.0000');
        $this->assertLine($entry, 'CASH_IN_HAND', '0.0000', '110.0000');
        $this->assertNoLine($entry, 'INVENTORY');
        $this->assertBalanced($entry);
    }

    public function test_mixed_purchase_allocates_invoice_discount_proportionally_and_remains_balanced(): void
    {
        $purchase = $this->purchase('135.0000', '0.0000', '0.0000', '135.0000', '15.0000');
        $this->item($purchase, 'product', '100.0000');
        $this->item($purchase, 'service', '50.0000');

        $entry = $this->postPurchase($purchase);

        $this->assertLine($entry, 'INVENTORY', '90.0000', '0.0000');
        $this->assertLine($entry, 'SERVICE_PURCHASE_EXPENSE', '45.0000', '0.0000');
        $this->assertLine($entry, 'ACCOUNTS_PAYABLE', '0.0000', '135.0000', 'supplier', 50);
        $this->assertBalanced($entry);
    }

    public function test_duplicate_missing_or_inactive_service_expense_account_rejects_posting_without_entry(): void
    {
        $purchase = $this->purchase('25.0000', '0.0000', '0.0000', '25.0000');
        $this->item($purchase, 'service', '25.0000');
        DB::table('chart_accounts')->where('company_id', self::COMPANY_ID)->where('system_code', 'SERVICE_PURCHASE_EXPENSE')->delete();

        try {
            $this->postPurchase($purchase);
            $this->fail('Missing service purchase expense account must reject posting.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Chart account system code [SERVICE_PURCHASE_EXPENSE] could not be resolved for this company.', $exception->getMessage());
        }
        $this->assertSame(0, AccountingEntry::count());

        $this->seedChartAccounts(self::COMPANY_ID);
        DB::table('chart_accounts')->where('company_id', self::COMPANY_ID)->where('system_code', 'SERVICE_PURCHASE_EXPENSE')->update(['status' => 'inactive']);
        try {
            $this->postPurchase($purchase);
            $this->fail('Inactive service purchase expense account must reject posting.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Chart account system code [SERVICE_PURCHASE_EXPENSE] could not be resolved for this company.', $exception->getMessage());
        }
        $this->assertSame(0, AccountingEntry::count());
    }

    public function test_duplicate_and_foreign_company_chart_accounts_are_rejected(): void
    {
        $purchase = $this->purchase('40.0000', '0.0000', '0.0000', '40.0000');
        $this->item($purchase, 'service', '40.0000');
        $this->postPurchase($purchase);

        try {
            $this->postPurchase($purchase);
            $this->fail('Duplicate purchase posting must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('An accounting entry has already been posted for this source key.', $exception->getMessage());
        }

        $foreignPurchase = $this->purchase('30.0000', '0.0000', '0.0000', '30.0000');
        $this->item($foreignPurchase, 'service', '30.0000');
        DB::table('chart_accounts')->where('company_id', self::COMPANY_ID)->where('system_code', 'SERVICE_PURCHASE_EXPENSE')->delete();
        try {
            $this->postPurchase($foreignPurchase);
            $this->fail('A foreign-company chart account must not satisfy posting.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Chart account system code [SERVICE_PURCHASE_EXPENSE] could not be resolved for this company.', $exception->getMessage());
        }
    }

    private function purchase(string $grandTotal, string $vat, string $paid, string $due, string $discount = '0.0000'): PurchaseInvoice
    {
        return PurchaseInvoice::create([
            'company_id' => self::COMPANY_ID, 'financial_year_id' => 1, 'supplier_id' => 50,
            'invoice_no' => 'PU-' . (PurchaseInvoice::count() + 1), 'purchase_date' => '2026-07-29',
            'discount' => $discount, 'total_vat' => $vat, 'grand_total' => $grandTotal,
            'paid_amount' => $paid, 'due_amount' => $due, 'status' => 1,
        ]);
    }

    private function item(PurchaseInvoice $purchase, string $type, string $total, string $vat = '0.0000'): void
    {
        PurchaseItem::create(['company_id' => self::COMPANY_ID, 'financial_year_id' => 1, 'purchase_invoice_id' => $purchase->id, 'item_type' => $type, 'quantity' => '1.0000', 'unit_price' => $total, 'total_price' => $total, 'vat_amount' => $vat, 'status' => 1]);
    }

    private function payment(PurchaseInvoice $purchase, int $accountId, string $amount): void
    {
        PurchasePayment::create(['company_id' => self::COMPANY_ID, 'financial_year_id' => 1, 'purchase_invoice_id' => $purchase->id, 'supplier_id' => 50, 'account_id' => $accountId, 'payment_no' => 'PP-' . $purchase->id, 'payment_date' => '2026-07-29', 'amount' => $amount, 'status' => 1]);
    }

    private function postPurchase(PurchaseInvoice $purchase): AccountingEntry
    {
        $purchase->refresh();
        app(PurchaseAccountingIntegrationService::class)->postPurchase($purchase);
        return AccountingEntry::where('company_id', self::COMPANY_ID)->where('source_id', $purchase->id)->firstOrFail();
    }

    private function seedChartAccounts(int $companyId): void
    {
        foreach (['INVENTORY', 'SERVICE_PURCHASE_EXPENSE', 'INPUT_TAX_RECEIVABLE', 'ACCOUNTS_PAYABLE', 'CASH_IN_HAND', 'BANK_ACCOUNTS'] as $code) {
            if (! DB::table('chart_accounts')->where('company_id', $companyId)->where('system_code', $code)->exists()) {
                DB::table('chart_accounts')->insert(['company_id' => $companyId, 'code' => $code, 'name' => $code, 'account_class' => $code === 'SERVICE_PURCHASE_EXPENSE' ? 'expense' : 'asset', 'system_code' => $code, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    private function assertLine(AccountingEntry $entry, string $systemCode, string $debit, string $credit, ?string $subledgerType = null, ?int $subledgerId = null): void
    {
        $line = $entry->lines()->whereHas('chartAccount', fn ($query) => $query->where('system_code', $systemCode))->firstOrFail();
        $this->assertSame($debit, number_format((float) $line->debit, 4, '.', ''));
        $this->assertSame($credit, number_format((float) $line->credit, 4, '.', ''));
        $this->assertSame($subledgerType, $line->subledger_type);
        $this->assertSame($subledgerId, $line->subledger_id);
    }

    private function assertNoLine(AccountingEntry $entry, string $systemCode): void
    {
        $this->assertSame(0, $entry->lines()->whereHas('chartAccount', fn ($query) => $query->where('system_code', $systemCode))->count());
    }

    private function assertBalanced(AccountingEntry $entry): void
    {
        $this->assertSame(
            number_format((float) $entry->lines()->sum('debit'), 4, '.', ''),
            number_format((float) $entry->lines()->sum('credit'), 4, '.', '')
        );
    }
}
