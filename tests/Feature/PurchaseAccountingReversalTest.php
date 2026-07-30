<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\PurchaseInvoice;
use App\Services\Accounting\PurchaseAccountingIntegrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PurchaseAccountingReversalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['accounting_entry_lines', 'accounting_entries', 'purchase_invoices'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('purchase_invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('invoice_no');
            $table->date('purchase_date');
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
    }

    public function test_product_purchase_reversal_copies_exact_accounts_amounts_and_supplier_metadata(): void
    {
        $purchase = $this->purchase(1, 'PU-1');
        $original = $this->original($purchase, [
            [1140, null, '100.0000', '0.0000', null, null],
            [2110, null, '0.0000', '100.0000', 'supplier', 50],
        ]);

        $this->reverse($purchase);

        $reversal = AccountingEntry::where('reversal_of_id', $original->id)->firstOrFail();
        $this->assertSame('reversed', $original->fresh()->status);
        $this->assertSame('purchase_cancel:' . $purchase->id . ':cancelled', $reversal->source_key);
        $this->assertExactOpposite($original, $reversal);
        $this->assertSame('supplier', $reversal->lines()->where('chart_account_id', 2110)->value('subledger_type'));
        $this->assertSame(50, $reversal->lines()->where('chart_account_id', 2110)->value('subledger_id'));
        $this->assertBalanced($reversal);
    }

    public function test_service_and_mixed_vat_purchase_reversal_uses_original_lines_without_current_mapping_resolution(): void
    {
        $purchase = $this->purchase(1, 'PU-2');
        $original = $this->original($purchase, [
            [1140, null, '90.0000', '0.0000', null, null],
            [5270, null, '45.0000', '0.0000', null, null],
            [1150, null, '10.0000', '0.0000', null, null],
            [1120, 77, '0.0000', '20.0000', null, null],
            [2110, null, '0.0000', '125.0000', 'supplier', 50],
        ]);

        $this->reverse($purchase);

        $reversal = AccountingEntry::where('reversal_of_id', $original->id)->firstOrFail();
        $this->assertExactOpposite($original, $reversal);
        $this->assertSame(77, $reversal->lines()->where('chart_account_id', 1120)->value('operational_account_id'));
        $this->assertBalanced($reversal);
    }

    public function test_missing_original_entry_rolls_back_outer_cancellation_work_and_foreign_company_entry_is_not_used(): void
    {
        $purchase = $this->purchase(1, 'PU-3');
        $foreignPurchase = $this->purchase(2, 'PU-F');
        $this->original($foreignPurchase, [[2110, null, '0.0000', '10.0000', 'supplier', 50]]);

        try {
            DB::transaction(function () use ($purchase): void {
                $purchase->update(['status' => 0]);
                $this->reverse($purchase);
            });
            $this->fail('A missing same-company original purchase entry must block cancellation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The original posted accounting entry could not be resolved for reversal.', $exception->getMessage());
        }

        $this->assertSame(1, $purchase->fresh()->status);
        $this->assertSame(0, AccountingEntry::where('company_id', 1)->where('source_event', 'cancelled')->count());
    }

    public function test_second_or_already_reversed_purchase_entry_cannot_create_another_reversal(): void
    {
        $purchase = $this->purchase(1, 'PU-4');
        $original = $this->original($purchase, [
            [5270, null, '50.0000', '0.0000', null, null],
            [2110, null, '0.0000', '50.0000', 'supplier', 50],
        ]);

        $this->reverse($purchase);

        try {
            $this->reverse($purchase);
            $this->fail('A second reversal must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The original posted accounting entry could not be resolved for reversal.', $exception->getMessage());
        }

        $this->assertSame(1, AccountingEntry::where('reversal_of_id', $original->id)->count());
    }

    private function purchase(int $companyId, string $invoiceNo): PurchaseInvoice
    {
        return PurchaseInvoice::create(['company_id' => $companyId, 'invoice_no' => $invoiceNo, 'purchase_date' => '2026-07-29', 'status' => 1]);
    }

    private function original(PurchaseInvoice $purchase, array $lines): AccountingEntry
    {
        $entry = AccountingEntry::create([
            'company_id' => $purchase->company_id,
            'entry_number' => 'TMP-' . $purchase->id,
            'entry_date' => '2026-07-29',
            'reference_number' => $purchase->invoice_no,
            'source_module' => 'purchase',
            'source_type' => PurchaseInvoice::class,
            'source_id' => $purchase->id,
            'source_event' => 'created',
            'source_key' => 'purchase:' . $purchase->id . ':created',
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        foreach ($lines as $number => [$chartAccountId, $operationalAccountId, $debit, $credit, $subledgerType, $subledgerId]) {
            $entry->lines()->create(['chart_account_id' => $chartAccountId, 'operational_account_id' => $operationalAccountId, 'line_number' => $number + 1, 'debit' => $debit, 'credit' => $credit, 'subledger_type' => $subledgerType, 'subledger_id' => $subledgerId]);
        }

        return $entry;
    }

    private function reverse(PurchaseInvoice $purchase): void
    {
        app(PurchaseAccountingIntegrationService::class)->reversePurchase($purchase, '2026-07-29', 1);
    }

    private function assertExactOpposite(AccountingEntry $original, AccountingEntry $reversal): void
    {
        $originalLines = $original->lines()->orderBy('line_number')->get();
        $reversalLines = $reversal->lines()->orderBy('line_number')->get();
        $this->assertSame($originalLines->count(), $reversalLines->count());

        foreach ($originalLines as $index => $line) {
            $opposite = $reversalLines[$index];
            $this->assertSame($line->chart_account_id, $opposite->chart_account_id);
            $this->assertSame($line->operational_account_id, $opposite->operational_account_id);
            $this->assertSame(number_format((float) $line->credit, 4, '.', ''), number_format((float) $opposite->debit, 4, '.', ''));
            $this->assertSame(number_format((float) $line->debit, 4, '.', ''), number_format((float) $opposite->credit, 4, '.', ''));
            $this->assertSame($line->subledger_type, $opposite->subledger_type);
            $this->assertSame($line->subledger_id, $opposite->subledger_id);
        }
    }

    private function assertBalanced(AccountingEntry $entry): void
    {
        $this->assertSame(number_format((float) $entry->lines()->sum('debit'), 4, '.', ''), number_format((float) $entry->lines()->sum('credit'), 4, '.', ''));
    }
}
