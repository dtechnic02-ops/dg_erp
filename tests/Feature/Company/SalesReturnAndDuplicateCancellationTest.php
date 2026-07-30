<?php

namespace Tests\Feature\Company;

use App\Models\AccountingEntry;
use App\Models\AccountingEntryLine;
use App\Models\CustomerTransaction;
use App\Models\InventoryValuation;
use App\Models\SalesCostSnapshot;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\SalesReturnRefund;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesCompanyRouteTestFoundation;
use Tests\TestCase;

class SalesReturnAndDuplicateCancellationTest extends TestCase
{
    use CreatesCompanyRouteTestFoundation;

    public function test_multiple_partial_product_returns_restore_inventory_and_reverse_cogs_using_snapshot_cost(): void
    {
        $context = $this->createReturnContext();
        $countsBefore = $this->counts();

        $this->postProductReturn($context, '0.75', '2026-06-21', 'First partial return');
        $firstReturn = SalesReturn::query()->sole();
        $firstItem = SalesReturnItem::query()->sole();
        $this->assertSame('0.75', number_format((float) $firstItem->quantity, 2, '.', ''));
        $this->assertSame('187.50', $firstReturn->grand_total);
        $this->assertSame('8.750000', number_format((float) $context['fixture']['product']->fresh()->current_stock, 6, '.', ''));

        $this->postProductReturn($context, '0.50', '2026-06-22', 'Second partial return');

        $returns = SalesReturn::query()->orderBy('id')->get();
        $returnItems = SalesReturnItem::query()->orderBy('id')->get();
        $restorationMovements = StockMovement::query()->where('type', 'sales_restore')->orderBy('id')->get();
        $restorationValuations = InventoryValuation::query()->where('movement_type', 'sales_restore')->orderBy('id')->get();
        $cogsEntries = AccountingEntry::query()->with('lines')->where('source_type', 'sales_return_cogs')->orderBy('id')->get();
        $snapshot = SalesCostSnapshot::findOrFail($context['fixture']['salesCostSnapshot']->id);

        $this->assertCount(2, $returns);
        $this->assertCount(2, $returnItems);
        $this->assertSame($context['company']->id, $returns[0]->company_id);
        $this->assertSame($context['fixture']['salesInvoice']->id, $returns[0]->sales_invoice_id);
        $this->assertSame($context['fixture']['salesInvoice']->id, $returns[1]->sales_invoice_id);
        $this->assertSame($context['fixture']['product']->id, $returnItems[0]->product_id);
        $this->assertSame($context['fixture']['product']->id, $returnItems[1]->product_id);
        $this->assertSame('0.75', number_format((float) $returnItems[0]->quantity, 2, '.', ''));
        $this->assertSame('0.50', number_format((float) $returnItems[1]->quantity, 2, '.', ''));
        $this->assertSame('1.25', number_format((float) $returnItems->sum('quantity'), 2, '.', ''));
        $this->assertSame('1.25', $context['fixture']['salesItem']->fresh()->returned_qty);
        $this->assertSame('0.75', number_format(2 - (float) $context['fixture']['salesItem']->fresh()->returned_qty, 2, '.', ''));
        $this->assertSame(1, SalesInvoice::findOrFail($context['fixture']['salesInvoice']->id)->status);

        $this->assertCount(2, $restorationMovements);
        $this->assertSame('0.75', number_format((float) $restorationMovements[0]->quantity, 2, '.', ''));
        $this->assertSame('0.50', number_format((float) $restorationMovements[1]->quantity, 2, '.', ''));
        $this->assertSame('1.25', number_format((float) $restorationMovements->sum('quantity'), 2, '.', ''));
        $this->assertSame('-0.75', number_format((float) $context['fixture']['stockMovement']->quantity + (float) $restorationMovements->sum('quantity'), 2, '.', ''));
        $this->assertSame($returns[0]->return_no, $restorationMovements[0]->reference_no);
        $this->assertSame($returns[1]->return_no, $restorationMovements[1]->reference_no);
        $this->assertSame('9.250000', number_format((float) $context['fixture']['product']->fresh()->current_stock, 6, '.', ''));

        $this->assertCount(2, $restorationValuations);
        $this->assertSame($restorationMovements[0]->id, $restorationValuations[0]->stock_movement_id);
        $this->assertSame($restorationMovements[1]->id, $restorationValuations[1]->stock_movement_id);
        $this->assertSame('75.00000000', $restorationValuations[0]->movement_unit_cost);
        $this->assertSame('75.00000000', $restorationValuations[1]->movement_unit_cost);
        $this->assertSame('56.2500', $restorationValuations[0]->inventory_value_change);
        $this->assertSame('37.5000', $restorationValuations[1]->inventory_value_change);
        $this->assertSame('93.7500', number_format((float) $restorationValuations->sum('inventory_value_change'), 4, '.', ''));
        $this->assertSame('-56.2500', number_format((float) $context['fixture']['inventoryValuation']->inventory_value_change + (float) $restorationValuations->sum('inventory_value_change'), 4, '.', ''));

        $this->assertSame('75.00000000', $snapshot->movement_unit_cost);
        $this->assertSame('150.0000', $snapshot->movement_value);
        $this->assertSame('99.00000000', number_format((float) $context['fixture']['product']->fresh()->cost_price, 8, '.', ''));
        $this->assertSame(1, SalesCostSnapshot::count());

        $this->assertCount(2, $cogsEntries);
        $this->assertEntryAmount($cogsEntries[0], $context['fixture']['accounts']['inventoryAccount']->id, $context['fixture']['accounts']['cogsAccount']->id, '56.2500');
        $this->assertEntryAmount($cogsEntries[1], $context['fixture']['accounts']['inventoryAccount']->id, $context['fixture']['accounts']['cogsAccount']->id, '37.5000');
        $this->assertSame('93.7500', number_format((float) $cogsEntries->flatMap->lines->sum('debit'), 4, '.', ''));
        $this->assertSame('posted', AccountingEntry::findOrFail($context['fixture']['cogsEntry']->id)->status);

        $this->assertSame($countsBefore['sales_returns'] + 2, SalesReturn::count());
        $this->assertSame($countsBefore['sales_return_items'] + 2, SalesReturnItem::count());
        $this->assertSame($countsBefore['stock_movements'] + 2, StockMovement::count());
        $this->assertSame($countsBefore['inventory_valuations'] + 2, InventoryValuation::count());
        $this->assertSame($countsBefore['accounting_entries'] + 2, AccountingEntry::count());
        $this->assertSame($countsBefore['accounting_entry_lines'] + 4, DB::table('accounting_entry_lines')->count());
        $this->assertSame($countsBefore['sales_cost_snapshots'], SalesCostSnapshot::count());
        $this->assertSame($countsBefore['customer_transactions'], CustomerTransaction::count());
        $this->assertSame($countsBefore['sales_return_refunds'], SalesReturnRefund::count());
        $this->assertSame(0, AccountingEntry::query()->where('source_type', 'sales_return_refund')->count());
        $this->assertMiddlewareState($context);
    }

    public function test_product_return_rejects_quantity_exceeding_remaining_returnable_quantity(): void
    {
        $context = $this->createReturnContext();
        $this->postProductReturn($context, '1.25', '2026-06-21', 'Valid partial return');
        $counts = $this->counts();

        $response = $this->post(route('company.sales-return.store'), $this->returnPayload($context, '0.76', '2026-06-22', 'Excess partial return'));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Return qty exceeds available qty.');
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame($counts['sales_returns'], SalesReturn::count());
        $this->assertSame($counts['sales_return_items'], SalesReturnItem::count());
        $this->assertSame($counts['stock_movements'], StockMovement::count());
        $this->assertSame($counts['inventory_valuations'], InventoryValuation::count());
        $this->assertSame($counts['accounting_entries'], AccountingEntry::count());
        $this->assertSame($counts['accounting_entry_lines'], DB::table('accounting_entry_lines')->count());
        $this->assertSame($counts['sales_cost_snapshots'], SalesCostSnapshot::count());
        $this->assertSame($counts['customer_transactions'], CustomerTransaction::count());
        $this->assertSame($counts['sales_return_refunds'], SalesReturnRefund::count());
        $this->assertSame('1.25', $context['fixture']['salesItem']->fresh()->returned_qty);
        $this->assertSame('0.75', number_format(2 - (float) $context['fixture']['salesItem']->fresh()->returned_qty, 2, '.', ''));
        $this->assertSame(1, SalesInvoice::findOrFail($context['fixture']['salesInvoice']->id)->status);
        $this->assertMiddlewareState($context);
    }

    public function test_cancelled_sales_invoice_cannot_be_cancelled_twice(): void
    {
        $context = $this->createReturnContext();
        $sale = $context['fixture']['salesInvoice'];

        $first = $this->post(route('company.sales.cancel', $sale->id), ['cancel_date' => '2026-06-20', 'cancel_reason' => 'Initial cancellation']);
        $first->assertRedirect();
        $first->assertSessionHas('success', 'Sales cancelled successfully.');
        $counts = $this->counts();

        $second = $this->post(route('company.sales.cancel', $sale->id), ['cancel_date' => '2026-06-20', 'cancel_reason' => 'Duplicate cancellation']);

        $second->assertRedirect();
        $second->assertSessionHas('error', 'Sales Already Cancelled.');
        $this->assertSame(302, $second->getStatusCode());
        $this->assertSame(0, SalesInvoice::findOrFail($sale->id)->status);
        foreach ($counts as $table => $count) {
            $this->assertSame($count, $this->counts()[$table]);
        }
        $this->assertSame(1, CustomerTransaction::query()->where('reference_type', 'sales_invoice_cancel')->count());
        $this->assertSame(1, StockMovement::query()->where('type', 'sales_restore')->count());
        $this->assertSame(1, InventoryValuation::query()->where('movement_type', 'sales_restore')->count());
        $this->assertSame(1, AccountingEntry::query()->where('source_key', 'sales:' . $sale->id . ':cancelled')->count());
        $this->assertSame(1, AccountingEntry::query()->where('source_key', 'sales-cogs:' . $sale->id . ':cancelled')->count());
        $this->assertSame(0, SalesReturn::count());
        $this->assertSame(0, SalesReturnRefund::count());
        $this->assertSame('0.0000', number_format((float) CustomerTransaction::query()->whereIn('reference_type', ['sales_invoice', 'sales_invoice_cancel'])->sum('debit') - (float) CustomerTransaction::query()->whereIn('reference_type', ['sales_invoice', 'sales_invoice_cancel'])->sum('credit'), 4, '.', ''));
        $this->assertSame('0.000000', number_format((float) StockMovement::sum('quantity'), 6, '.', ''));
        $this->assertSame('0.0000', number_format((float) InventoryValuation::sum('inventory_value_change'), 4, '.', ''));
        $this->assertSame('150.0000', SalesCostSnapshot::findOrFail($context['fixture']['salesCostSnapshot']->id)->movement_value);
        $this->assertMiddlewareState($context);
    }

    private function createReturnContext(): array
    {
        $this->createCompanyRouteTestSchema();
        $role = $this->createCompanyDashboardRole();
        $company = $this->createActiveCompany();
        $user = $this->createCompanyAdmin($company, $role);
        $this->createOperationalCompanySubscription($company, $this->createActiveSubscriptionPlan());
        $this->authenticateCompanyAdmin($user);

        return ['company' => $company, 'user' => $user, 'old_last_seen' => $user->last_seen->copy(), 'fixture' => $this->createPostedProductSale($company, $user, $this->createActiveFinancialYear($company, $user), $this->createActiveWarehouse($company), $this->createCustomer($company, $user), $this->createProduct($company))];
    }

    private function returnPayload(array $context, string $quantity, string $date, string $note): array
    {
        return ['sales_invoice_id' => $context['fixture']['salesInvoice']->id, 'customer_id' => $context['fixture']['customer']->id, 'return_date' => $date, 'sales_item_id' => [$context['fixture']['salesItem']->id], 'quantity' => [$quantity], 'note' => $note];
    }

    private function postProductReturn(array $context, string $quantity, string $date, string $note): void
    {
        $response = $this->post(route('company.sales-return.store'), $this->returnPayload($context, $quantity, $date, $note));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Sales return saved successfully.');
        $this->assertSame(302, $response->getStatusCode());
    }

    private function counts(): array
    {
        return ['sales_returns' => SalesReturn::count(), 'sales_return_items' => SalesReturnItem::count(), 'stock_movements' => StockMovement::count(), 'inventory_valuations' => InventoryValuation::count(), 'accounting_entries' => AccountingEntry::count(), 'accounting_entry_lines' => DB::table('accounting_entry_lines')->count(), 'sales_cost_snapshots' => SalesCostSnapshot::count(), 'customer_transactions' => CustomerTransaction::count(), 'sales_return_refunds' => SalesReturnRefund::count()];
    }

    private function assertEntryAmount(AccountingEntry $entry, int $inventoryAccountId, int $cogsAccountId, string $amount): void
    {
        $lines = $entry->lines->keyBy('chart_account_id');
        $this->assertSame($amount, $lines[$inventoryAccountId]->debit);
        $this->assertSame($amount, $lines[$cogsAccountId]->credit);
        $this->assertSame($amount, number_format((float) $entry->lines->sum('debit'), 4, '.', ''));
        $this->assertSame($amount, number_format((float) $entry->lines->sum('credit'), 4, '.', ''));
    }

    private function assertMiddlewareState(array $context): void
    {
        $this->assertSame($context['user']->id, auth()->id());
        $this->assertGreaterThan($context['old_last_seen']->getTimestamp(), Carbon::parse($context['user']->fresh()->last_seen)->getTimestamp());
        $this->assertSame($context['company']->id, auth()->user()->company_id);
        $this->assertTrue(app(\App\Services\SubscriptionService::class)->isSubscriptionOperational($context['company']->fresh()));
    }
}
