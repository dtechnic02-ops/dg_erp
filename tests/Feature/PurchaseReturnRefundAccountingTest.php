<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\PurchaseReturnRefund;
use App\Services\Accounting\Integrations\PurchaseReturnRefundAccountingIntegrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PurchaseReturnRefundAccountingTest extends TestCase
{
    private const COMPANY_ID = 1;
    private const FINANCIAL_YEAR_ID = 1;
    private const SUPPLIER_ID = 50;
    private const CASH_ACCOUNT_ID = 10;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'accounting_entry_lines',
            'accounting_entries',
            'supplier_transactions',
            'account_transactions',
            'purchase_return_refund_adjustments',
            'purchase_return_refunds',
            'purchase_returns',
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
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('supplier_id');
            $table->decimal('total_vat', 20, 4);
            $table->decimal('grand_total', 20, 4);
            $table->integer('status');
        });

        Schema::create('purchase_return_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('financial_year_id');
            $table->unsignedBigInteger('purchase_return_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('refund_no');
            $table->date('refund_date');
            $table->decimal('refund_amount', 20, 4);
            $table->decimal('adjust_amount', 20, 4);
            $table->decimal('cash_amount', 20, 4);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('status');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_return_refund_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('purchase_return_refund_id');
            $table->unsignedBigInteger('purchase_invoice_id')->nullable();
            $table->decimal('adjust_amount', 20, 4);
            $table->integer('status');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->decimal('debit', 20, 4);
            $table->integer('status');
        });

        Schema::create('supplier_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->decimal('debit', 20, 4);
            $table->integer('status');
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
            'id' => self::CASH_ACCOUNT_ID,
            'company_id' => self::COMPANY_ID,
            'account_type' => 'Cash',
            'status' => 'active',
        ]);

        DB::table('chart_accounts')->insert(array_map(
            fn (string $code): array => [
                'company_id' => self::COMPANY_ID,
                'system_code' => $code,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            ['PURCHASE_RETURNS', 'INPUT_TAX_RECEIVABLE', 'ACCOUNTS_PAYABLE', 'CASH_IN_HAND']
        ));

        DB::table('financial_years')->insert([
            'id' => self::FINANCIAL_YEAR_ID,
            'company_id' => self::COMPANY_ID,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
    }

    public function test_cash_settlement_persists_a_balanced_canonical_entry(): void
    {
        $returnId = $this->createPurchaseReturn('300.0000', '30.0000');
        $refund = $this->createRefund(1, $returnId, '300.0000', '0.0000', '300.0000');

        $this->postRefund($refund);

        $entry = $this->entryFor($refund->id);
        $this->assertSame('purchase_return_refund', $entry->source_type);
        $this->assertSame($refund->id, $entry->source_id);
        $this->assertSame('created', $entry->source_event);
        $this->assertLine($entry, 'PURCHASE_RETURNS', '0.0000', '270.0000');
        $this->assertLine($entry, 'INPUT_TAX_RECEIVABLE', '0.0000', '30.0000');
        $this->assertLine($entry, 'CASH_IN_HAND', '300.0000', '0.0000', self::CASH_ACCOUNT_ID);
        $this->assertBalanced($entry);
        $this->assertUnrelatedCodesAreAbsent($entry);
    }

    public function test_invoice_adjustment_posts_payable_without_cash_account_line(): void
    {
        $returnId = $this->createPurchaseReturn('300.0000', '30.0000');
        $refund = $this->createRefund(2, $returnId, '300.0000', '300.0000', '0.0000');

        $this->postRefund($refund);

        $entry = $this->entryFor($refund->id);
        $this->assertLine($entry, 'PURCHASE_RETURNS', '0.0000', '270.0000');
        $this->assertLine($entry, 'INPUT_TAX_RECEIVABLE', '0.0000', '30.0000');
        $this->assertLine($entry, 'ACCOUNTS_PAYABLE', '300.0000', '0.0000', null, 'supplier', self::SUPPLIER_ID);
        $this->assertSame(3, $entry->lines()->count());
        $this->assertBalanced($entry);
    }

    public function test_mixed_settlement_posts_cash_and_payable_components(): void
    {
        $returnId = $this->createPurchaseReturn('300.0000', '30.0000');
        $refund = $this->createRefund(3, $returnId, '300.0000', '100.0000', '200.0000');

        $this->postRefund($refund);

        $entry = $this->entryFor($refund->id);
        $this->assertLine($entry, 'ACCOUNTS_PAYABLE', '100.0000', '0.0000', null, 'supplier', self::SUPPLIER_ID);
        $this->assertLine($entry, 'CASH_IN_HAND', '200.0000', '0.0000', self::CASH_ACCOUNT_ID);
        $this->assertSame('300.0000', $this->sumDebits($entry));
        $this->assertSame('300.0000', $this->sumCredits($entry));
        $this->assertBalanced($entry);
    }

    public function test_partial_settlements_use_incremental_components_and_exact_final_tax_residue(): void
    {
        $returnId = $this->createPurchaseReturn('1000.0000', '100.0000');
        $first = $this->createRefund(4, $returnId, '300.0000', '0.0000', '300.0000');
        $this->postRefund($first);

        $second = $this->createRefund(5, $returnId, '400.0000', '0.0000', '400.0000');
        $this->postRefund($second);

        $third = $this->createRefund(6, $returnId, '300.0000', '0.0000', '300.0000');
        $this->postRefund($third);

        $firstEntry = $this->entryFor($first->id);
        $secondEntry = $this->entryFor($second->id);
        $thirdEntry = $this->entryFor($third->id);

        $this->assertLine($firstEntry, 'PURCHASE_RETURNS', '0.0000', '270.0000');
        $this->assertLine($firstEntry, 'INPUT_TAX_RECEIVABLE', '0.0000', '30.0000');
        $this->assertLine($secondEntry, 'PURCHASE_RETURNS', '0.0000', '360.0000');
        $this->assertLine($secondEntry, 'INPUT_TAX_RECEIVABLE', '0.0000', '40.0000');
        $this->assertLine($thirdEntry, 'PURCHASE_RETURNS', '0.0000', '270.0000');
        $this->assertLine($thirdEntry, 'INPUT_TAX_RECEIVABLE', '0.0000', '30.0000');
        $this->assertSame('900.0000', $this->componentCreditFor([$firstEntry, $secondEntry, $thirdEntry], 'PURCHASE_RETURNS'));
        $this->assertSame('100.0000', $this->componentCreditFor([$firstEntry, $secondEntry, $thirdEntry], 'INPUT_TAX_RECEIVABLE'));
        $this->assertSame('1000.0000', $this->sumCredits($firstEntry, $secondEntry, $thirdEntry));

        foreach ([$firstEntry, $secondEntry, $thirdEntry] as $entry) {
            $this->assertBalanced($entry);
        }
    }

    public function test_duplicate_posting_is_blocked_without_duplicate_lines(): void
    {
        $returnId = $this->createPurchaseReturn('300.0000', '30.0000');
        $refund = $this->createRefund(7, $returnId, '300.0000', '0.0000', '300.0000');

        $this->postRefund($refund);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An accounting entry has already been posted for this source key.');

        try {
            $this->postRefund($refund);
        } finally {
            $this->assertSame(1, AccountingEntry::where('company_id', self::COMPANY_ID)
                ->where('source_type', 'purchase_return_refund')
                ->where('source_id', $refund->id)
                ->where('source_event', 'created')
                ->count());
            $this->assertSame(3, DB::table('accounting_entry_lines')->count());
        }
    }

    public function test_cancellation_creates_one_exact_persisted_reversal(): void
    {
        $returnId = $this->createPurchaseReturn('300.0000', '30.0000');
        $refund = $this->createRefund(8, $returnId, '300.0000', '100.0000', '200.0000');

        $this->postRefund($refund);
        $original = $this->entryFor($refund->id);
        $this->integration()->reverseRefund($refund, '2026-06-15', 1);
        $reversal = AccountingEntry::where('company_id', self::COMPANY_ID)
            ->where('reversal_of_id', $original->id)
            ->firstOrFail();

        $this->assertSame('reversed', $original->fresh()->status);
        $this->assertSame('cancelled', $reversal->source_event);
        $this->assertSame('purchase_return_refund', $reversal->source_type);
        $this->assertSame($original->id, $reversal->reversal_of_id);
        $this->assertBalanced($reversal);

        foreach ($original->lines()->orderBy('line_number')->get() as $line) {
            $reversedLine = $reversal->lines()->where('line_number', $line->line_number)->firstOrFail();
            $this->assertSame($line->chart_account_id, $reversedLine->chart_account_id);
            $this->assertSame($line->operational_account_id, $reversedLine->operational_account_id);
            $this->assertSame($line->credit, $reversedLine->debit);
            $this->assertSame($line->debit, $reversedLine->credit);
        }

        $this->expectException(RuntimeException::class);
        $this->integration()->reverseRefund($refund, '2026-06-15', 1);
    }

    public function test_legacy_source_type_is_used_for_duplicate_residue_and_reversal_compatibility(): void
    {
        $returnId = $this->createPurchaseReturn('1000.0000', '100.0000');
        $legacy = $this->createRefund(9, $returnId, '300.0000', '0.0000', '300.0000');
        $this->createLegacyEntry($legacy->id, '270.0000', '30.0000', '300.0000');
        $current = $this->createRefund(10, $returnId, '700.0000', '0.0000', '700.0000');

        $this->postRefund($current);
        $currentEntry = $this->entryFor($current->id);
        $this->assertLine($currentEntry, 'PURCHASE_RETURNS', '0.0000', '630.0000');
        $this->assertLine($currentEntry, 'INPUT_TAX_RECEIVABLE', '0.0000', '70.0000');

        try {
            $this->postRefund($legacy);
            $this->fail('Legacy source identity must block a duplicate canonical posting.');
        } catch (RuntimeException $exception) {
            $this->assertSame('An accounting entry has already been posted for this source key.', $exception->getMessage());
        }

        $this->integration()->reverseRefund($legacy, '2026-06-15', 1);
        $this->assertDatabaseHas('accounting_entries', [
            'reversal_of_id' => AccountingEntry::where('source_type', PurchaseReturnRefund::class)
                ->where('source_id', $legacy->id)
                ->value('id'),
            'source_event' => 'cancelled',
        ]);
    }

    public function test_accounting_failure_rolls_back_the_enclosing_refund_transaction(): void
    {
        $returnId = $this->createPurchaseReturn('300.0000', '30.0000');
        DB::table('chart_accounts')->where('system_code', 'INPUT_TAX_RECEIVABLE')->delete();

        try {
            DB::transaction(function () use ($returnId): void {
                $refund = $this->createRefund(11, $returnId, '300.0000', '0.0000', '300.0000');
                $this->postRefund($refund);
            });
            $this->fail('Posting with a missing required chart account must fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('INPUT_TAX_RECEIVABLE', $exception->getMessage());
        }

        $this->assertDatabaseMissing('purchase_return_refunds', ['id' => 11]);
        $this->assertDatabaseMissing('account_transactions', ['reference_id' => 11]);
        $this->assertDatabaseMissing('supplier_transactions', ['reference_id' => 11]);
        $this->assertSame(0, DB::table('accounting_entries')->count());
        $this->assertSame(0, DB::table('accounting_entry_lines')->count());
    }

    private function createPurchaseReturn(string $grandTotal, string $tax): int
    {
        return (int) DB::table('purchase_returns')->insertGetId([
            'company_id' => self::COMPANY_ID,
            'financial_year_id' => self::FINANCIAL_YEAR_ID,
            'supplier_id' => self::SUPPLIER_ID,
            'total_vat' => $tax,
            'grand_total' => $grandTotal,
            'status' => 1,
        ]);
    }

    private function createRefund(int $id, int $returnId, string $settlement, string $adjustment, string $cash): PurchaseReturnRefund
    {
        DB::table('purchase_return_refunds')->insert([
            'id' => $id,
            'company_id' => self::COMPANY_ID,
            'financial_year_id' => self::FINANCIAL_YEAR_ID,
            'purchase_return_id' => $returnId,
            'supplier_id' => self::SUPPLIER_ID,
            'account_id' => $cash === '0.0000' ? null : self::CASH_ACCOUNT_ID,
            'refund_no' => 'PRR-' . $id,
            'refund_date' => '2026-06-15',
            'refund_amount' => $settlement,
            'adjust_amount' => $adjustment,
            'cash_amount' => $cash,
            'created_by' => 1,
            'status' => PurchaseReturnRefund::STATUS_ACTIVE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($adjustment !== '0.0000') {
            DB::table('purchase_return_refund_adjustments')->insert([
                'company_id' => self::COMPANY_ID,
                'purchase_return_refund_id' => $id,
                'purchase_invoice_id' => 1,
                'adjust_amount' => $adjustment,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($cash !== '0.0000') {
            DB::table('account_transactions')->insert([
                'company_id' => self::COMPANY_ID,
                'reference_type' => 'purchase_return_refund',
                'reference_id' => $id,
                'debit' => $cash,
                'status' => 1,
            ]);
        }

        DB::table('supplier_transactions')->insert([
            'company_id' => self::COMPANY_ID,
            'reference_type' => 'purchase_return_refund',
            'reference_id' => $id,
            'debit' => $settlement,
            'status' => 1,
        ]);

        return PurchaseReturnRefund::findOrFail($id);
    }

    private function createLegacyEntry(int $refundId, string $net, string $tax, string $settlement): void
    {
        $entryId = DB::table('accounting_entries')->insertGetId([
            'company_id' => self::COMPANY_ID,
            'entry_number' => 'LEGACY-' . $refundId,
            'entry_date' => '2026-06-15',
            'source_module' => 'purchase_return_refund',
            'source_type' => PurchaseReturnRefund::class,
            'source_id' => $refundId,
            'source_event' => 'created',
            'source_key' => 'purchase_return_refund:' . $refundId . ':created',
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['PURCHASE_RETURNS', '0.0000', $net],
            ['INPUT_TAX_RECEIVABLE', '0.0000', $tax],
            ['CASH_IN_HAND', $settlement, '0.0000'],
        ] as $lineNumber => [$code, $debit, $credit]) {
            DB::table('accounting_entry_lines')->insert([
                'accounting_entry_id' => $entryId,
                'chart_account_id' => DB::table('chart_accounts')->where('system_code', $code)->value('id'),
                'operational_account_id' => $code === 'CASH_IN_HAND' ? self::CASH_ACCOUNT_ID : null,
                'line_number' => $lineNumber + 1,
                'debit' => $debit,
                'credit' => $credit,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function postRefund(PurchaseReturnRefund $refund): void
    {
        $this->integration()->postRefund($refund);
    }

    private function integration(): PurchaseReturnRefundAccountingIntegrationService
    {
        return app(PurchaseReturnRefundAccountingIntegrationService::class);
    }

    private function entryFor(int $refundId): AccountingEntry
    {
        return AccountingEntry::where('company_id', self::COMPANY_ID)
            ->where('source_type', 'purchase_return_refund')
            ->where('source_id', $refundId)
            ->where('source_event', 'created')
            ->firstOrFail();
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

    private function componentCreditFor(array $entries, string $code): string
    {
        $total = 0.0;

        foreach ($entries as $entry) {
            $total += (float) $entry->lines()
                ->whereHas('chartAccount', fn ($query) => $query->where('system_code', $code))
                ->sum('credit');
        }

        return number_format($total, 4, '.', '');
    }

    private function assertUnrelatedCodesAreAbsent(AccountingEntry $entry): void
    {
        $codes = $entry->lines()->with('chartAccount')->get()->pluck('chartAccount.system_code')->all();

        foreach (['SALES_RETURNS', 'SALES_REVENUE', 'SERVICE_REVENUE', 'OUTPUT_TAX_PAYABLE'] as $code) {
            $this->assertNotContains($code, $codes);
        }
    }
}
