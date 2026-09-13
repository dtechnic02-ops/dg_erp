<?php

namespace Tests\Feature;

use App\Models\AccountingEntry;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnRefund;
use App\Models\Role;
use App\Models\User;
use App\Services\DefaultChartAccountBootstrapService;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseFullRevertReverseReturnTest extends TestCase
{
    use RefreshDatabase;

    private const DATE = '2026-06-15';

    private Company $company;
    private User $user;
    private int $financialYearId;
    private int $supplierId;
    private int $accountId;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        DB::table('roles')->insertOrIgnore([
            'id' => Role::COMPANY_ADMIN_ID,
            'name' => 'Company Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach (['module_purchase', 'create_purchase', 'cancel_purchase', 'module_stock', 'view_stock', 'print_stock'] as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'scope' => 'company',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->company = Company::create([
            'company_name' => 'Purchase Reversal Company',
            'mobile' => '9800000001',
            'email' => 'purchase-reversal@example.test',
            'status' => 'active',
        ]);
        $this->user = User::create([
            'name' => 'Purchase Reversal Admin',
            'email' => 'purchase-reversal-admin@example.test',
            'password' => 'password',
            'role_id' => Role::COMPANY_ADMIN_ID,
            'company_id' => $this->company->id,
            'account_status' => 'active',
        ]);
        $this->financialYearId = DB::table('financial_years')->insertGetId([
            'company_id' => $this->company->id,
            'name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => 1,
            'is_closed' => 0,
            'is_locked' => 0,
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        app(DefaultChartAccountBootstrapService::class)->seedForCompany($this->company->id);

        $unitId = DB::table('units')->insertGetId([
            'company_id' => $this->company->id,
            'name' => 'Pieces',
            'short_name' => 'pcs',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->supplierId = DB::table('suppliers')->insertGetId([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'name' => 'Reversal Supplier',
            'opening_balance' => 0,
            'current_balance' => 0,
            'credit_days' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->accountId = DB::table('accounts')->insertGetId([
            'company_id' => $this->company->id,
            'account_group' => 'cash',
            'account_type' => 'Cash',
            'bank_name' => 'Return Cash',
            'account_name' => 'Return Cash',
            'currency' => 'NPR',
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->product = Product::create([
            'company_id' => $this->company->id,
            'unit_id' => $unitId,
            'name' => 'Purchase Reversal Product',
            'cost_price' => 100,
            'retail_price' => 150,
            'wholesale_price' => 125,
            'current_stock' => 0,
            'stock_alert' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);
    }

    public function test_purchase_full_revert_preserves_records_and_reverses_every_effect_once(): void
    {
        $purchase = $this->purchase(10);
        $originalTotal = (float) $purchase->grand_total;

        $this->fullRevert($purchase)
            ->assertSessionHas('success', 'Purchase fully reverted successfully. The original invoice and its history were preserved.');

        $this->assertSame(0, (int) $purchase->fresh()->status);
        $this->assertSame('0.00', $this->money($this->product->fresh()->current_stock));
        $this->assertSame('0.00', $this->money(DB::table('suppliers')->where('id', $this->supplierId)->value('current_balance')));
        $this->assertDatabaseHas('purchase_invoices', ['id' => $purchase->id]);
        $this->assertSame(1, $purchase->items()->count());
        $this->assertSame(1, DB::table('stock_movements')->where('type', 'purchase_cancel')->where('reference_no', $purchase->invoice_no)->count());
        $this->assertSame(1, DB::table('inventory_valuations')->where('source_type', PurchaseInvoice::class)->where('source_id', $purchase->id)->where('source_event', 'cancelled')->count());
        $this->assertSame($this->money($originalTotal), $this->money(DB::table('supplier_transactions')->where('reference_type', 'purchase_invoice_cancel')->where('reference_id', $purchase->id)->sum('debit')));
        $this->assertBalancedEntry('purchase_cancel:'.$purchase->id.':cancelled');

        $movementCount = DB::table('stock_movements')->where('type', 'purchase_cancel')->count();
        $this->fullRevert($purchase)
            ->assertSessionHas('error', 'Purchase has already been fully reverted.');
        $this->assertSame($movementCount, DB::table('stock_movements')->where('type', 'purchase_cancel')->count());
    }

    public function test_partial_and_full_active_returns_block_full_revert(): void
    {
        foreach ([3, 10] as $quantity) {
            $purchase = $this->purchase(10);
            $this->purchaseReturn($purchase, $quantity);

            $before = $this->product->fresh()->current_stock;
            $this->fullRevert($purchase)->assertSessionHas(
                'error',
                'This invoice cannot be fully reverted because one or more active purchase returns exist. Reverse the active return first.'
            );

            $this->assertSame(1, (int) $purchase->fresh()->status);
            $this->assertSame($this->money($before), $this->money($this->product->fresh()->current_stock));
        }
    }

    public function test_reversed_partial_and_full_returns_allow_full_revert_without_double_reversal(): void
    {
        foreach ([3, 10] as $quantity) {
            $startingStock = (float) $this->product->fresh()->current_stock;
            $purchase = $this->purchase(10);
            $return = $this->purchaseReturn($purchase, $quantity);

            $this->reverseReturn($return)->assertSessionHas(
                'success',
                'Purchase return reversed successfully. The original return and its history were preserved.'
            );
            $this->assertSame(0, (int) $return->fresh()->status);
            $this->assertSame('0.00', $this->money($purchase->items()->firstOrFail()->returned_qty));

            $this->fullRevert($purchase)->assertSessionHas('success');
            $this->assertSame($this->money($startingStock), $this->money($this->product->fresh()->current_stock));
            $this->assertSame(1, DB::table('inventory_valuations')->where('source_type', PurchaseInvoice::class)->where('source_id', $purchase->id)->where('source_event', 'cancelled')->count());
            $this->assertBalancedEntry('purchase_return_cancel:'.$return->id.':cancelled');
            $this->assertBalancedEntry('purchase_cancel:'.$purchase->id.':cancelled');
        }
    }

    public function test_multiple_reversed_returns_allow_full_revert_but_any_remaining_active_return_blocks_it(): void
    {
        $purchase = $this->purchase(10);
        foreach ([2, 3] as $quantity) {
            $this->reverseReturn($this->purchaseReturn($purchase, $quantity))->assertSessionHas('success');
        }
        $this->fullRevert($purchase)->assertSessionHas('success');
        $this->assertSame('0.00', $this->money($this->product->fresh()->current_stock));

        $mixed = $this->purchase(10);
        $this->reverseReturn($this->purchaseReturn($mixed, 2))->assertSessionHas('success');
        $active = $this->purchaseReturn($mixed, 3);
        $this->fullRevert($mixed)->assertSessionHas('error');
        $this->assertSame(1, (int) $mixed->fresh()->status);
        $this->assertSame(1, (int) $active->fresh()->status);
    }

    public function test_return_refund_must_be_cancelled_before_reverse_return(): void
    {
        $purchase = $this->purchase(10);
        $return = $this->purchaseReturn($purchase, 3);
        $refund = $this->refundInCash($return);

        $this->reverseReturn($return)->assertSessionHas(
            'error',
            'Cannot reverse a purchase return with refund settlements. Cancel the settlement first.'
        );
        $this->assertSame(1, (int) $return->fresh()->status);

        $this->post(route('company.purchase-return-refunds.cancel', $refund->id), [
            'cancel_date' => self::DATE,
            'cancel_reason' => 'Undo settlement before Return reversal',
        ])->assertSessionHas('success', 'Refund cancelled successfully.');

        $this->reverseReturn($return)->assertSessionHas('success');
        $this->assertSame(0, (int) $refund->fresh()->status);
        $this->assertSame(0, (int) $return->fresh()->status);

        $movementCount = DB::table('stock_movements')->where('type', 'purchase_return_cancel')->count();
        $this->reverseReturn($return)->assertSessionHas('error', 'Purchase return has already been reversed.');
        $this->assertSame($movementCount, DB::table('stock_movements')->where('type', 'purchase_return_cancel')->count());
    }

    public function test_company_and_financial_year_guards_fail_before_reversal_mutation(): void
    {
        $purchase = $this->purchase(10);
        $return = $this->purchaseReturn($purchase, 3);
        $before = $this->product->fresh()->current_stock;

        [$foreignCompany, $foreignUser] = $this->foreignCompanyUser();
        $this->actingAs($foreignUser);
        $this->reverseReturn($return)->assertSessionHas('error');
        $this->assertSame(1, (int) $return->fresh()->status);
        $this->assertSame($this->money($before), $this->money($this->product->fresh()->current_stock));
        $this->assertSame(0, DB::table('inventory_valuations')->where('company_id', $foreignCompany->id)->count());

        $this->actingAs($this->user);
        $otherFy = DB::table('financial_years')->insertGetId([
            'company_id' => $this->company->id,
            'name' => 'FY 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_active' => 0,
            'is_closed' => 1,
            'is_locked' => 1,
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('purchase_returns')->where('id', $return->id)->update(['financial_year_id' => $otherFy]);

        $this->reverseReturn($return->fresh())
            ->assertSessionHas('error', 'Purchase Return belongs to another Financial Year.');
        $this->assertSame(1, (int) $return->fresh()->status);
        $this->assertSame($this->money($before), $this->money($this->product->fresh()->current_stock));
    }

    public function test_full_revert_rejects_a_purchase_from_a_non_active_financial_year(): void
    {
        $purchase = $this->purchase(10);
        $stockBefore = $this->product->fresh()->current_stock;
        $supplierBefore = DB::table('suppliers')->where('id', $this->supplierId)->value('current_balance');
        $otherFy = DB::table('financial_years')->insertGetId([
            'company_id' => $this->company->id,
            'name' => 'Closed FY',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_active' => 0,
            'is_closed' => 1,
            'is_locked' => 1,
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('purchase_invoices')->where('id', $purchase->id)->update(['financial_year_id' => $otherFy]);

        $this->fullRevert($purchase->fresh())->assertSessionHas(
            'error',
            'This Purchase Invoice belongs to another Financial Year. Please activate that Financial Year first.'
        );

        $this->assertSame(1, (int) $purchase->fresh()->status);
        $this->assertSame($this->money($stockBefore), $this->money($this->product->fresh()->current_stock));
        $this->assertSame($this->money($supplierBefore), $this->money(DB::table('suppliers')->where('id', $this->supplierId)->value('current_balance')));
        $this->assertSame(0, DB::table('stock_movements')->where('type', 'purchase_cancel')->where('reference_no', $purchase->invoice_no)->count());
    }

    public function test_reverse_return_failure_rolls_back_inventory_returned_quantity_and_status(): void
    {
        $purchase = $this->purchase(10);
        $return = $this->purchaseReturn($purchase, 3);
        $stockBefore = $this->product->fresh()->current_stock;
        $returnedBefore = $purchase->items()->firstOrFail()->returned_qty;
        AccountingEntry::where('source_key', 'purchase_return:'.$return->id.':created')->delete();

        $this->reverseReturn($return)->assertSessionHas('error', 'Unable to reverse purchase return. Please try again.');

        $this->assertSame(1, (int) $return->fresh()->status);
        $this->assertSame($this->money($stockBefore), $this->money($this->product->fresh()->current_stock));
        $this->assertSame($this->money($returnedBefore), $this->money($purchase->items()->firstOrFail()->returned_qty));
        $this->assertSame(0, DB::table('stock_movements')->where('type', 'purchase_return_cancel')->where('reference_no', $return->return_no)->count());
        $this->assertSame(0, DB::table('inventory_valuations')->where('source_type', \App\Models\PurchaseReturnItem::class)->where('source_event', 'cancelled')->count());
    }

    public function test_full_revert_failure_rolls_back_inventory_supplier_and_status_changes(): void
    {
        $purchase = $this->purchase(10);
        $stockBefore = $this->product->fresh()->current_stock;
        $supplierBefore = DB::table('suppliers')->where('id', $this->supplierId)->value('current_balance');
        AccountingEntry::where('source_key', 'purchase:'.$purchase->id.':created')->delete();

        $this->fullRevert($purchase)->assertSessionHas('error', 'Unable to fully revert invoice. Please try again.');

        $this->assertSame(1, (int) $purchase->fresh()->status);
        $this->assertSame($this->money($stockBefore), $this->money($this->product->fresh()->current_stock));
        $this->assertSame($this->money($supplierBefore), $this->money(DB::table('suppliers')->where('id', $this->supplierId)->value('current_balance')));
        $this->assertSame(0, DB::table('stock_movements')->where('type', 'purchase_cancel')->where('reference_no', $purchase->invoice_no)->count());
        $this->assertSame(0, DB::table('supplier_transactions')->where('reference_type', 'purchase_invoice_cancel')->where('reference_id', $purchase->id)->count());
    }

    private function purchase(float $quantity): PurchaseInvoice
    {
        $invoiceNo = InvoiceNumberService::generate('PU', $this->company->id, $this->financialYearId, PurchaseInvoice::class, 'invoice_no');
        $this->withSession(['pending_purchase_invoice' => [
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYearId,
            'invoice_no' => $invoiceNo,
        ]]);
        $this->post(route('company.purchases.store'), [
            'supplier_id' => $this->supplierId,
            'purchase_date' => self::DATE,
            'item_type' => ['product'],
            'product_id' => [$this->product->id],
            'service_id' => [null],
            'quantity' => [$quantity],
            'unit_price' => [100],
            'vat_rate' => [13],
            'discount_amount' => 0,
            'paid_amount' => 0,
        ])->assertSessionHas('success');

        return PurchaseInvoice::where('company_id', $this->company->id)->latest('id')->firstOrFail();
    }

    private function purchaseReturn(PurchaseInvoice $purchase, float $quantity): PurchaseReturn
    {
        $item = $purchase->items()->firstOrFail();
        $this->post(route('company.purchase-return.store'), [
            'purchase_invoice_id' => $purchase->id,
            'request_key' => (string) Str::uuid(),
            'supplier_id' => $this->supplierId,
            'return_date' => self::DATE,
            'purchase_item_id' => [$item->id],
            'quantity' => [$quantity],
            'note' => 'Supplier return',
        ])->assertSessionHas('success', 'Purchase return saved successfully.');

        return PurchaseReturn::where('purchase_invoice_id', $purchase->id)->latest('id')->firstOrFail();
    }

    private function refundInCash(PurchaseReturn $return): PurchaseReturnRefund
    {
        $this->post(route('company.purchase-return-refunds.store'), [
            'purchase_return_id' => $return->id,
            'idempotency_key' => (string) Str::uuid(),
            'refund_date' => self::DATE,
            'purchase_invoice_id' => [],
            'adjust_amount' => [],
            'account_id' => $this->accountId,
            'cash_amount' => $return->grand_total,
            'note' => 'Cash settlement',
        ])->assertSessionHas('success', 'Purchase return refund created successfully.');

        return PurchaseReturnRefund::where('purchase_return_id', $return->id)->latest('id')->firstOrFail();
    }

    private function fullRevert(PurchaseInvoice $purchase)
    {
        return $this->post(route('company.purchases.cancel', $purchase->id), [
            'cancel_date' => self::DATE,
            'cancel_reason' => 'Verified Full Revert',
        ]);
    }

    private function reverseReturn(PurchaseReturn $return)
    {
        return $this->post(route('company.purchase-return.cancel', $return->id), [
            'cancel_date' => self::DATE,
            'cancel_reason' => 'Verified Reverse Return',
        ]);
    }

    private function foreignCompanyUser(): array
    {
        $company = Company::create([
            'company_name' => 'Foreign Purchase Company',
            'mobile' => '9800000002',
            'email' => 'foreign-purchase@example.test',
            'status' => 'active',
        ]);
        $user = User::create([
            'name' => 'Foreign Admin',
            'email' => 'foreign-purchase-admin@example.test',
            'password' => 'password',
            'role_id' => Role::COMPANY_ADMIN_ID,
            'company_id' => $company->id,
            'account_status' => 'active',
        ]);

        return [$company, $user];
    }

    private function assertBalancedEntry(string $sourceKey): void
    {
        $entry = AccountingEntry::where('source_key', $sourceKey)->firstOrFail();
        $this->assertSame(
            number_format((float) $entry->lines()->sum('debit'), 4, '.', ''),
            number_format((float) $entry->lines()->sum('credit'), 4, '.', '')
        );
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
