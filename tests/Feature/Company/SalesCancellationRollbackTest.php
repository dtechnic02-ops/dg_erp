<?php

namespace Tests\Feature\Company;

use App\Models\AccountingEntry;
use App\Models\CustomerTransaction;
use App\Models\InventoryValuation;
use App\Models\SalesCostSnapshot;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesCompanyRouteTestFoundation;
use Tests\TestCase;

class SalesCancellationRollbackTest extends TestCase
{
    use CreatesCompanyRouteTestFoundation;

    public function test_sales_cancellation_rolls_back_when_revenue_entry_is_missing(): void
    {
        $context = $this->createCancellationContext();
        $context['fixture']['revenueEntry']->lines()->delete();
        $context['fixture']['revenueEntry']->delete();
        $counts = $this->cancellationCounts();

        $response = $this->cancel($context);

        $this->assertRollbackState($context, $counts);
        $this->assertSame(1, AccountingEntry::query()->where('source_key', 'sales-cogs:' . $context['fixture']['salesInvoice']->id . ':created')->count());
    }

    public function test_sales_cancellation_rolls_back_when_cogs_entry_is_missing(): void
    {
        $context = $this->createCancellationContext();
        $context['fixture']['cogsEntry']->lines()->delete();
        $context['fixture']['cogsEntry']->delete();
        $counts = $this->cancellationCounts();

        $response = $this->cancel($context);

        $this->assertRollbackState($context, $counts);
        $this->assertSame(1, AccountingEntry::query()->where('source_key', 'sales:' . $context['fixture']['salesInvoice']->id . ':created')->where('status', 'posted')->count());
    }

    public function test_sales_cancellation_rolls_back_when_sales_cost_snapshot_is_missing(): void
    {
        $context = $this->createCancellationContext();
        $context['fixture']['salesCostSnapshot']->delete();
        $counts = $this->cancellationCounts();

        $response = $this->cancel($context);

        $this->assertRollbackState($context, $counts);
        $this->assertSame(0, SalesCostSnapshot::count());
        $this->assertSame(1, InventoryValuation::query()->whereKey($context['fixture']['inventoryValuation']->id)->count());
    }

    public function test_sales_cancellation_rolls_back_when_inventory_valuation_is_missing(): void
    {
        $context = $this->createCancellationContext();
        $context['fixture']['inventoryValuation']->delete();
        $counts = $this->cancellationCounts();

        $response = $this->cancel($context);

        $this->assertRollbackState($context, $counts);
        $this->assertSame(1, SalesCostSnapshot::count());
        $this->assertSame(0, InventoryValuation::query()->whereKey($context['fixture']['inventoryValuation']->id)->count());
    }

    private function createCancellationContext(): array
    {
        $this->createCompanyRouteTestSchema();
        $role = $this->createCompanyDashboardRole();
        $company = $this->createActiveCompany();
        $user = $this->createCompanyAdmin($company, $role);
        $this->createOperationalCompanySubscription($company, $this->createActiveSubscriptionPlan());
        $this->authenticateCompanyAdmin($user);

        return [
            'company' => $company,
            'user' => $user,
            'old_last_seen' => $user->last_seen->copy(),
            'fixture' => $this->createPostedProductSale(
                $company,
                $user,
                $this->createActiveFinancialYear($company, $user),
                $this->createActiveWarehouse($company),
                $this->createCustomer($company, $user),
                $this->createProduct($company)
            ),
        ];
    }

    private function cancel(array $context): void
    {
        $response = $this->post(route('company.sales.cancel', $context['fixture']['salesInvoice']->id), [
            'cancel_date' => '2026-06-20',
            'cancel_reason' => 'Controller rollback failure test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Unable to cancel invoice. Please try again.');
        $this->assertSame(302, $response->getStatusCode());
    }

    private function cancellationCounts(): array
    {
        return [
            'customer_transactions' => CustomerTransaction::count(),
            'stock_movements' => StockMovement::count(),
            'inventory_valuations' => InventoryValuation::count(),
            'sales_cost_snapshots' => SalesCostSnapshot::count(),
            'accounting_entries' => AccountingEntry::count(),
            'accounting_entry_lines' => DB::table('accounting_entry_lines')->count(),
        ];
    }

    private function assertRollbackState(array $context, array $counts): void
    {
        $fixture = $context['fixture'];
        $sale = SalesInvoice::findOrFail($fixture['salesInvoice']->id);

        $this->assertSame(1, $sale->status);
        $this->assertSame('8.000000', number_format((float) $fixture['product']->fresh()->current_stock, 6, '.', ''));
        $this->assertSame('-2.000000', number_format((float) StockMovement::findOrFail($fixture['stockMovement']->id)->quantity, 6, '.', ''));
        $this->assertSame($counts['customer_transactions'], CustomerTransaction::count());
        $this->assertSame($counts['stock_movements'], StockMovement::count());
        $this->assertSame($counts['inventory_valuations'], InventoryValuation::count());
        $this->assertSame($counts['sales_cost_snapshots'], SalesCostSnapshot::count());
        $this->assertSame($counts['accounting_entries'], AccountingEntry::count());
        $this->assertSame($counts['accounting_entry_lines'], DB::table('accounting_entry_lines')->count());
        $this->assertSame(0, CustomerTransaction::query()->where('reference_type', 'sales_invoice_cancel')->count());
        $this->assertSame(0, StockMovement::query()->where('type', 'sales_restore')->count());
        $this->assertSame(0, InventoryValuation::query()->where('movement_type', 'sales_restore')->count());
        $this->assertSame(0, AccountingEntry::query()->where('source_event', 'cancelled')->count());
        $this->assertSame($context['user']->id, auth()->id());
        $this->assertGreaterThan($context['old_last_seen']->getTimestamp(), Carbon::parse($context['user']->fresh()->last_seen)->getTimestamp());
        $this->assertSame($context['company']->id, auth()->user()->company_id);
    }
}
