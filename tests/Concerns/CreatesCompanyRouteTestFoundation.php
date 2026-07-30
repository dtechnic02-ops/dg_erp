<?php

namespace Tests\Concerns;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ChartAccount;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\FinancialYear;
use App\Models\InventoryValuation;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesCostSnapshot;
use App\Models\SalesInvoice;
use App\Models\SalesItem;
use App\Models\StockMovement;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Accounting\AccountingPostingService;
use App\Services\CustomerTransactionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use LogicException;

trait CreatesCompanyRouteTestFoundation
{
    protected function assertCompanyRouteTestDatabase(): void
    {
        if (! app()->environment('testing') || DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new LogicException('Company route tests require the isolated SQLite :memory: database.');
        }
    }

    protected function createCompanyRouteTestSchema(): void
    {
        $this->assertCompanyRouteTestDatabase();

        foreach (['sales_cost_snapshots','inventory_valuations','accounting_entry_lines','accounting_entries','account_transactions','customer_transactions','sales_return_refund_adjustments','sales_return_refunds','sales_return_items','sales_items','sales_returns','sales_payments','sales_invoices','stock_movements','chart_accounts','products','customers','accounts','warehouses','financial_years','company_subscriptions','subscription_plans','users','roles','companies'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('roles', function (Blueprint $table): void {$table->id();$table->string('name');$table->timestamps();});
        Schema::create('companies', function (Blueprint $table): void {$table->id();$table->string('company_name');$table->string('email')->nullable();$table->string('mobile')->nullable();$table->string('status')->default('active');$table->timestamps();});
        Schema::create('users', function (Blueprint $table): void {$table->id();$table->string('name');$table->string('email')->unique();$table->timestamp('email_verified_at')->nullable();$table->string('password');$table->unsignedBigInteger('role_id')->nullable();$table->unsignedBigInteger('company_id')->nullable();$table->string('job_role')->nullable();$table->string('account_status')->default('active');$table->timestamp('last_seen')->nullable();$table->rememberToken();$table->timestamps();});
        Schema::create('subscription_plans', function (Blueprint $table): void {$table->id();$table->string('code')->unique();$table->string('name');$table->unsignedInteger('staff_limit');$table->json('hidden_modules')->nullable();$table->boolean('is_active')->default(true);$table->unsignedInteger('sort_order')->default(0);$table->timestamps();});
        Schema::create('company_subscriptions', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->string('subscription_type')->default('paid');$table->unsignedBigInteger('subscription_plan_id')->nullable();$table->string('status')->default('active');$table->date('start_date');$table->date('expiry_date')->nullable();$table->unsignedInteger('staff_limit')->default(1);$table->json('hidden_modules')->nullable();$table->boolean('is_all_modules_enabled')->default(false);$table->timestamp('activated_at')->nullable();$table->timestamps();$table->index(['company_id','status']);});
        Schema::create('financial_years', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->string('name');$table->date('start_date');$table->date('end_date');$table->boolean('is_active')->default(true);$table->unsignedBigInteger('created_by')->nullable();$table->timestamps();});
        Schema::create('warehouses', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->string('name');$table->string('status')->default('active');$table->timestamps();});
        Schema::create('customers', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('created_by')->nullable();$table->string('name');$table->string('authority_name')->nullable();$table->string('mobile')->nullable();$table->string('telephone')->nullable();$table->string('fax_no')->nullable();$table->string('email')->nullable();$table->string('website')->nullable();$table->text('address')->nullable();$table->string('tax_no')->nullable();$table->decimal('opening_balance',10,2)->default(0);$table->unsignedInteger('credit_days')->default(0);$table->decimal('current_balance',20,4)->default(0);$table->string('bank_name')->nullable();$table->string('bank_account_no')->nullable();$table->text('note')->nullable();$table->string('image_path')->nullable();$table->string('status')->default('active');$table->timestamps();});
        Schema::create('products', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->string('name');$table->decimal('cost_price',20,8)->default(0);$table->decimal('current_stock',20,6)->default(0);$table->string('status')->default('active');$table->timestamps();});
        Schema::create('accounts', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->string('account_type');$table->string('account_name')->nullable();$table->decimal('current_balance',20,4)->default(0);$table->string('status')->default('active');$table->timestamps();});
        Schema::create('chart_accounts', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('parent_id')->nullable();$table->string('code');$table->string('name');$table->string('account_class');$table->string('account_category')->nullable();$table->string('normal_balance');$table->string('system_code')->nullable();$table->unsignedTinyInteger('level')->default(1);$table->unsignedInteger('sort_order')->default(0);$table->boolean('is_system')->default(false);$table->boolean('is_control')->default(false);$table->boolean('allow_manual_entry')->default(true);$table->string('status')->default('active');$table->unsignedBigInteger('created_by')->nullable();$table->unsignedBigInteger('updated_by')->nullable();$table->timestamps();$table->softDeletes();$table->unique(['company_id','code']);});
        Schema::create('sales_invoices', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('created_by')->nullable();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('financial_year_id');$table->unsignedBigInteger('customer_id');$table->string('invoice_no');$table->date('sale_date');$table->date('due_date')->nullable();foreach(['subtotal','discount','total_vat','grand_total','paid_amount','due_amount'] as $column)$table->decimal($column,20,4)->default(0);$table->string('payment_status')->default('unpaid');$table->text('note')->nullable();$table->integer('status')->default(1);$table->timestamps();});
        Schema::create('sales_payments', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('financial_year_id')->nullable();$table->unsignedBigInteger('sales_invoice_id');$table->unsignedBigInteger('customer_id');$table->unsignedBigInteger('account_id');$table->string('payment_no')->unique();$table->date('payment_date');$table->decimal('paid_amount',20,4)->default(0);$table->string('payment_method')->nullable();$table->string('reference_no')->nullable();$table->string('receipt_file')->nullable();$table->text('note')->nullable();$table->unsignedBigInteger('created_by')->nullable();$table->unsignedBigInteger('updated_by')->nullable();$table->unsignedBigInteger('deleted_by')->nullable();$table->integer('status')->default(1);$table->timestamps();});
        Schema::create('sales_returns', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('financial_year_id')->nullable();$table->unsignedBigInteger('sales_invoice_id');$table->unsignedBigInteger('customer_id')->nullable();$table->string('return_no');$table->date('return_date');foreach(['subtotal','total_vat','grand_total','adjust_amount','refund_amount'] as $column)$table->decimal($column,20,4)->default(0);$table->text('note')->nullable();$table->string('damage_photo')->nullable();$table->unsignedBigInteger('created_by')->nullable();$table->integer('status')->default(1);$table->timestamps();$table->unique(['company_id','financial_year_id','return_no']);});
        Schema::create('sales_return_items', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('financial_year_id');$table->unsignedBigInteger('sales_return_id');$table->unsignedBigInteger('sales_item_id');$table->unsignedBigInteger('product_id')->nullable();$table->unsignedBigInteger('service_id')->nullable();$table->decimal('quantity',20,6);foreach(['unit_price','vat_rate','vat_amount','total_price'] as $column)$table->decimal($column,20,4)->default(0);$table->unsignedBigInteger('created_by')->nullable();$table->boolean('status')->default(true);$table->timestamps();});
        Schema::create('sales_return_refunds', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('financial_year_id')->nullable();$table->unsignedBigInteger('sales_return_id');$table->unsignedBigInteger('customer_id');$table->unsignedBigInteger('account_id')->nullable();$table->uuid('idempotency_key')->nullable();$table->string('refund_no')->unique();$table->date('refund_date');foreach(['refund_amount','adjust_amount','cash_amount'] as $column)$table->decimal($column,20,4)->default(0);$table->string('reference_no')->nullable();$table->string('attachment')->nullable();$table->text('note')->nullable();$table->unsignedBigInteger('created_by')->nullable();$table->unsignedBigInteger('updated_by')->nullable();$table->unsignedBigInteger('deleted_by')->nullable();$table->integer('status')->default(1);$table->timestamps();$table->softDeletes();$table->unique(['company_id','idempotency_key']);});
        Schema::create('sales_return_refund_adjustments', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('sales_return_refund_id');$table->unsignedBigInteger('sales_invoice_id');$table->decimal('adjust_amount',20,4);$table->integer('status')->default(1);$table->unsignedBigInteger('created_by')->nullable();$table->unsignedBigInteger('updated_by')->nullable();$table->unsignedBigInteger('deleted_by')->nullable();$table->timestamps();$table->softDeletes();});
        Schema::create('sales_items', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('created_by')->nullable();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('financial_year_id')->nullable();$table->unsignedBigInteger('sales_invoice_id');$table->string('item_type')->default('product');$table->unsignedBigInteger('product_id')->nullable();$table->unsignedBigInteger('service_id')->nullable();$table->decimal('quantity',20,6);$table->decimal('returned_qty',20,6)->default(0);foreach(['unit_price','vat_rate','vat_amount','total_price'] as $column)$table->decimal($column,20,4)->default(0);$table->timestamps();});
        Schema::create('customer_transactions', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('financial_year_id');$table->unsignedBigInteger('customer_id');$table->date('transaction_date');$table->string('voucher_no');$table->string('reference_type');$table->unsignedBigInteger('reference_id');$table->string('reference_no')->nullable();$table->text('description')->nullable();$table->decimal('debit',20,4)->default(0);$table->decimal('credit',20,4)->default(0);$table->decimal('balance',20,4)->default(0);$table->text('remarks')->nullable();$table->unsignedBigInteger('created_by')->nullable();$table->integer('status')->default(1);$table->timestamps();});
        Schema::create('account_transactions', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('financial_year_id')->nullable();$table->unsignedBigInteger('account_id');$table->date('transaction_date');$table->string('voucher_no');$table->string('reference_type');$table->unsignedBigInteger('reference_id');$table->unsignedBigInteger('journal_item_id')->nullable();$table->unsignedBigInteger('reversed_transaction_id')->nullable();$table->text('description')->nullable();$table->decimal('debit',20,4)->default(0);$table->decimal('credit',20,4)->default(0);$table->decimal('balance',20,4)->default(0);$table->unsignedBigInteger('created_by')->nullable();$table->integer('status')->default(1);$table->timestamps();});
        Schema::create('stock_movements', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('financial_year_id')->nullable();$table->date('transaction_date')->nullable();$table->unsignedBigInteger('product_id');$table->string('type');$table->decimal('quantity',20,6);$table->decimal('before_stock',20,6)->default(0);$table->decimal('after_stock',20,6)->default(0);$table->decimal('unit_price',20,8)->nullable();$table->string('reference_no')->nullable();$table->text('note')->nullable();$table->unsignedBigInteger('created_by')->nullable();$table->timestamps();});
        Schema::create('inventory_valuations', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('product_id');$table->unsignedBigInteger('stock_movement_id')->unique();$table->unsignedBigInteger('valuation_sequence');$table->string('movement_type');$table->string('source_module');$table->string('source_type');$table->unsignedBigInteger('source_id');$table->string('source_event');foreach(['quantity_before','quantity_change','quantity_after'] as $column)$table->decimal($column,20,6);foreach(['inventory_value_before','inventory_value_change','inventory_value_after'] as $column)$table->decimal($column,20,4);foreach(['average_cost_before','movement_unit_cost','average_cost_after'] as $column)$table->decimal($column,20,8);$table->unsignedBigInteger('reversal_of_id')->nullable();$table->timestamp('valued_at');$table->timestamps();$table->unique(['company_id','product_id','valuation_sequence']);});
        Schema::create('sales_cost_snapshots', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->unsignedBigInteger('sales_invoice_id');$table->unsignedBigInteger('sales_item_id')->unique();$table->unsignedBigInteger('product_id');$table->unsignedBigInteger('stock_movement_id')->unique();$table->unsignedBigInteger('inventory_valuation_id');$table->decimal('average_cost_used',20,8);$table->decimal('movement_unit_cost',20,8);$table->decimal('movement_value',20,4);$table->timestamps();});
        Schema::create('accounting_entries', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('company_id');$table->string('entry_number');$table->date('entry_date');$table->string('reference_number')->nullable();$table->string('source_module');$table->string('source_type')->nullable();$table->unsignedBigInteger('source_id')->nullable();$table->string('source_event')->nullable();$table->string('source_key')->nullable();$table->text('description')->nullable();$table->string('status');$table->unsignedBigInteger('reversal_of_id')->nullable();$table->timestamp('posted_at')->nullable();$table->unsignedBigInteger('posted_by')->nullable();$table->timestamps();$table->unique(['company_id','source_key']);});
        Schema::create('accounting_entry_lines', function (Blueprint $table): void {$table->id();$table->unsignedBigInteger('accounting_entry_id');$table->unsignedBigInteger('chart_account_id');$table->unsignedBigInteger('operational_account_id')->nullable();$table->unsignedInteger('line_number');$table->text('description')->nullable();$table->decimal('debit',20,4)->default(0);$table->decimal('credit',20,4)->default(0);$table->string('subledger_type')->nullable();$table->unsignedBigInteger('subledger_id')->nullable();$table->timestamps();});
    }

    protected function createCompanyDashboardRole(): Role
    {
        $role = Role::query()->find(Role::COMPANY_ADMIN_ID);

        if ($role) {
            $role->update(['name' => 'Company Admin']);

            return $role;
        }

        $role = new Role(['name' => 'Company Admin']);
        $role->id = Role::COMPANY_ADMIN_ID;
        $role->save();

        return $role;
    }

    protected function createActiveCompany(): Company
    {
        return Company::create(['company_name' => 'Route Test Company', 'email' => 'route-test@example.test', 'mobile' => '9800000000', 'status' => 'active']);
    }

    protected function createCompanyAdmin(Company $company, Role $role): User
    {
        return User::create(['name' => 'Route Test Company Admin', 'email' => 'company-admin@example.test', 'password' => Hash::make('password'), 'role_id' => $role->id, 'company_id' => $company->id, 'account_status' => 'active', 'last_seen' => now()->subDay()]);
    }

    protected function createActiveSubscriptionPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create(['code' => 'route_test', 'name' => 'Route Test Plan', 'staff_limit' => 10, 'is_active' => true, 'sort_order' => 0]);
    }

    protected function createOperationalCompanySubscription(Company $company, SubscriptionPlan $plan): CompanySubscription
    {
        return CompanySubscription::create(['company_id' => $company->id, 'subscription_type' => 'paid', 'subscription_plan_id' => $plan->id, 'status' => 'active', 'start_date' => now()->subDay()->toDateString(), 'expiry_date' => now()->addYear()->toDateString(), 'staff_limit' => $plan->staff_limit, 'is_all_modules_enabled' => true, 'activated_at' => now()]);
    }

    protected function authenticateCompanyAdmin(User $user): void
    {
        auth()->login($user);
    }

    protected function createActiveFinancialYear(Company $company, ?User $user = null): FinancialYear
    {
        return FinancialYear::create(['company_id' => $company->id, 'name' => 'FY 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true, 'created_by' => $user?->id]);
    }

    protected function createActiveWarehouse(Company $company): object
    {
        $id = DB::table('warehouses')->insertGetId(['company_id' => $company->id, 'name' => 'Main Warehouse', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('warehouses')->find($id);
    }

    protected function createCustomer(Company $company, ?User $user = null): Customer
    {
        return Customer::create(['company_id' => $company->id, 'created_by' => $user?->id, 'name' => 'Route Test Customer', 'current_balance' => '0.0000', 'status' => 'active']);
    }

    protected function createProduct(Company $company): Product
    {
        return Product::create(['company_id' => $company->id, 'name' => 'Route Test Physical Product', 'cost_price' => '99.00000000', 'current_stock' => '0.000000', 'status' => 'active']);
    }

    protected function createPostedProductSale(
        Company $company,
        User $user,
        FinancialYear $financialYear,
        object $warehouse,
        Customer $customer,
        Product $product
    ): array {
        $saleDate = '2026-06-15';

        $accounts = $this->createSalesChartAccounts($company, $user);

        $salesInvoice = SalesInvoice::create([
            'created_by' => $user->id,
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'SI-ROUTE-0001',
            'sale_date' => $saleDate,
            'due_date' => $saleDate,
            'subtotal' => '500.00',
            'discount' => '0.00',
            'total_vat' => '0.00',
            'grand_total' => '500.00',
            'paid_amount' => '0.00',
            'due_amount' => '500.00',
            'payment_status' => 'unpaid',
            'note' => 'Route test posted product sale',
            'status' => 1,
        ]);

        $salesItem = SalesItem::create([
            'created_by' => $user->id,
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'sales_invoice_id' => $salesInvoice->id,
            'item_type' => 'product',
            'product_id' => $product->id,
            'service_id' => null,
            'quantity' => '2.00',
            'returned_qty' => '0.00',
            'unit_price' => '250.00',
            'vat_rate' => '0.00',
            'vat_amount' => '0.00',
            'total_price' => '500.00',
        ]);

        $customerTransaction = CustomerTransactionService::createTransaction([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'customer_id' => $customer->id,
            'transaction_date' => $saleDate,
            'voucher_no' => $salesInvoice->invoice_no,
            'reference_type' => 'sales_invoice',
            'reference_id' => $salesInvoice->id,
            'reference_no' => $salesInvoice->invoice_no,
            'description' => 'Sales Invoice',
            'debit' => '500.00',
            'credit' => '0.00',
            'created_by' => $user->id,
            'status' => 1,
        ]);

        $product->update(['current_stock' => '8.000000']);

        $stockMovement = StockMovement::create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'transaction_date' => $saleDate,
            'product_id' => $product->id,
            'type' => 'sale',
            'quantity' => '-2.000000',
            'before_stock' => '10.000000',
            'after_stock' => '8.000000',
            'unit_price' => '75.00000000',
            'reference_no' => $salesInvoice->invoice_no,
            'note' => 'Posted sale stock issue',
            'created_by' => $user->id,
        ]);

        $inventoryValuation = InventoryValuation::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'stock_movement_id' => $stockMovement->id,
            'valuation_sequence' => 1,
            'movement_type' => 'sale',
            'source_module' => 'sales',
            'source_type' => SalesInvoice::class,
            'source_id' => $salesInvoice->id,
            'source_event' => 'created',
            'quantity_before' => '10.000000',
            'quantity_change' => '-2.000000',
            'quantity_after' => '8.000000',
            'inventory_value_before' => '750.0000',
            'inventory_value_change' => '-150.0000',
            'inventory_value_after' => '600.0000',
            'average_cost_before' => '75.00000000',
            'movement_unit_cost' => '75.00000000',
            'average_cost_after' => '75.00000000',
            'valued_at' => now(),
        ]);

        $salesCostSnapshot = SalesCostSnapshot::create([
            'company_id' => $company->id,
            'sales_invoice_id' => $salesInvoice->id,
            'sales_item_id' => $salesItem->id,
            'product_id' => $product->id,
            'stock_movement_id' => $stockMovement->id,
            'inventory_valuation_id' => $inventoryValuation->id,
            'average_cost_used' => '75.00000000',
            'movement_unit_cost' => '75.00000000',
            'movement_value' => '150.0000',
        ]);

        $postingService = app(AccountingPostingService::class);

        $revenueEntry = $postingService->post([
            'company_id' => $company->id,
            'entry_date' => $saleDate,
            'reference_number' => $salesInvoice->invoice_no,
            'source_module' => 'sales',
            'source_type' => SalesInvoice::class,
            'source_id' => $salesInvoice->id,
            'source_event' => 'created',
            'source_key' => 'sales:' . $salesInvoice->id . ':created',
            'description' => 'Sales invoice - ' . $salesInvoice->invoice_no,
            'posted_by' => $user->id,
            'lines' => [
                ['chart_account_system_code' => 'ACCOUNTS_RECEIVABLE', 'description' => 'Sales receivable - ' . $salesInvoice->invoice_no, 'debit' => '500.0000', 'credit' => '0.0000', 'operational_account_id' => null, 'subledger_type' => 'customer', 'subledger_id' => $customer->id],
                ['chart_account_system_code' => 'SALES_REVENUE', 'description' => 'Product sales revenue - ' . $salesInvoice->invoice_no, 'debit' => '0.0000', 'credit' => '500.0000', 'operational_account_id' => null, 'subledger_type' => null, 'subledger_id' => null],
            ],
        ]);

        $cogsEntry = $postingService->post([
            'company_id' => $company->id,
            'entry_date' => $saleDate,
            'reference_number' => $salesInvoice->invoice_no,
            'source_module' => 'sales_cogs',
            'source_type' => 'sales_cogs',
            'source_id' => $salesInvoice->id,
            'source_event' => 'created',
            'source_key' => 'sales-cogs:' . $salesInvoice->id . ':created',
            'description' => 'Cost of goods sold for sales invoice ' . $salesInvoice->invoice_no,
            'posted_by' => $user->id,
            'lines' => [
                ['chart_account_system_code' => 'COST_OF_GOODS_SOLD', 'description' => 'Cost of goods sold', 'debit' => '150.0000', 'credit' => '0.0000', 'operational_account_id' => null, 'subledger_type' => null, 'subledger_id' => null],
                ['chart_account_system_code' => 'INVENTORY', 'description' => 'Inventory issued on sale', 'debit' => '0.0000', 'credit' => '150.0000', 'operational_account_id' => null, 'subledger_type' => null, 'subledger_id' => null],
            ],
        ]);

        return compact('company', 'user', 'financialYear', 'warehouse', 'customer', 'product', 'salesInvoice', 'salesItem', 'customerTransaction', 'stockMovement', 'inventoryValuation', 'salesCostSnapshot', 'revenueEntry', 'cogsEntry', 'accounts');
    }

    private function createSalesChartAccounts(Company $company, User $user): array
    {
        $definitions = [
            'receivableAccount' => ['code' => '1100', 'name' => 'Accounts Receivable', 'account_class' => 'asset', 'normal_balance' => 'debit', 'system_code' => 'ACCOUNTS_RECEIVABLE', 'is_control' => true],
            'revenueAccount' => ['code' => '4100', 'name' => 'Sales Revenue', 'account_class' => 'income', 'normal_balance' => 'credit', 'system_code' => 'SALES_REVENUE', 'is_control' => false],
            'inventoryAccount' => ['code' => '1300', 'name' => 'Inventory', 'account_class' => 'asset', 'normal_balance' => 'debit', 'system_code' => 'INVENTORY', 'is_control' => false],
            'cogsAccount' => ['code' => '5100', 'name' => 'Cost of Goods Sold', 'account_class' => 'expense', 'normal_balance' => 'debit', 'system_code' => 'COST_OF_GOODS_SOLD', 'is_control' => false],
        ];

        $accounts = [];

        foreach ($definitions as $key => $definition) {
            $accounts[$key] = ChartAccount::query()->firstOrCreate(
                ['company_id' => $company->id, 'system_code' => $definition['system_code']],
                $definition + ['company_id' => $company->id, 'created_by' => $user->id, 'is_system' => true, 'allow_manual_entry' => false, 'status' => 'active']
            );
        }

        return $accounts;
    }
}
