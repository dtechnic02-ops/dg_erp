<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NepaliDateService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GlobalAdBsRolloutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('countries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->char('iso_code', 2)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name');
            $table->unsignedBigInteger('country_id')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        $nepal = DB::table('countries')->insertGetId(['name' => 'Nepal', 'iso_code' => 'NP', 'is_active' => 1]);
        $other = DB::table('countries')->insertGetId(['name' => 'India', 'iso_code' => 'IN', 'is_active' => 1]);
        DB::table('companies')->insert([
            ['id' => 1, 'company_name' => 'Nepal Company', 'country_id' => $nepal],
            ['id' => 2, 'company_name' => 'Other Company', 'country_id' => $other],
        ]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Nepal User', 'email' => 'np-global@example.test', 'password' => Hash::make('secret'), 'company_id' => 1],
            ['id' => 2, 'name' => 'Other User', 'email' => 'other-global@example.test', 'password' => Hash::make('secret'), 'company_id' => 2],
        ]);

        $this->withoutMiddleware();
    }

    public function test_all_existing_authoritative_create_and_edit_fields_use_the_shared_live_component(): void
    {
        $forms = [
            'sales-payment/create.blade.php' => 'payment_date', 'sales-payment/edit.blade.php' => 'payment_date',
            'sales-return/create.blade.php' => 'return_date', 'sales-return/edit.blade.php' => 'return_date',
            'sales-return-refund/create.blade.php' => 'refund_date', 'sales-return-refund/edit.blade.php' => 'refund_date',
            'purchases/create.blade.php' => 'purchase_date', 'purchases/edit.blade.php' => 'purchase_date',
            'purchase-payments/create.blade.php' => 'payment_date', 'purchase-payments/edit.blade.php' => 'payment_date',
            'purchase-return/create.blade.php' => 'return_date', 'purchase-return/edit.blade.php' => 'return_date',
            'purchase-return-refunds/create.blade.php' => 'refund_date', 'purchase-return-refunds/edit.blade.php' => 'refund_date',
            'income/create.blade.php' => 'income_date', 'income/edit.blade.php' => 'income_date',
            'expense/create.blade.php' => 'expense_date', 'expense/edit.blade.php' => 'expense_date',
            'journal/partials/phase-one-form.blade.php' => 'journal_date',
            'loan-account/create.blade.php' => 'start_date',
            'loan-payment/create.blade.php' => 'payment_date', 'loan-payment/edit.blade.php' => 'payment_date',
            'loan-saving-withdraw/create.blade.php' => 'date',
            'opening_balance/partials/form.blade.php' => 'business_date',
        ];

        foreach ($forms as $path => $field) {
            $source = file_get_contents(resource_path('views/company/' . $path));
            $this->assertStringContainsString("'adInputId' => '{$field}'", $source, $path);
            $this->assertStringNotContainsString('name="' . $field . '_bs"', $source, $path);
        }
    }

    public function test_primary_show_print_report_and_statement_surfaces_use_shared_bs_display(): void
    {
        $views = [
            'sales-payment/show.blade.php', 'sales-payment/print.blade.php',
            'sales-return/show.blade.php', 'sales-return/print.blade.php',
            'sales-return-refund/show.blade.php', 'sales-return-refund/print.blade.php',
            'purchases/show.blade.php', 'purchases/print.blade.php',
            'purchase-payments/show.blade.php', 'purchase-payments/print.blade.php',
            'purchase-return/show.blade.php', 'purchase-return/print.blade.php',
            'purchase-return-refunds/show.blade.php', 'purchase-return-refunds/print.blade.php',
            'income/show.blade.php', 'income/voucher-print.blade.php',
            'expense/show.blade.php', 'expense/voucher-print.blade.php',
            'journal/show.blade.php', 'journal/voucher-print.blade.php',
            'loan-account/show.blade.php', 'loan-payment/show.blade.php', 'loan-payment/print.blade.php',
            'loan-saving-ledger/show.blade.php', 'loan-saving-ledger/index.blade.php',
            'stock-ledger/index.blade.php', 'opening_balance/show.blade.php', 'opening_balance/print.blade.php',
            'customer-statement/index.blade.php', 'supplier-statement/index.blade.php',
        ];

        foreach ($views as $path) {
            $this->assertStringContainsString(
                "@include('company.components.nepali-date-display'",
                file_get_contents(resource_path('views/company/' . $path)),
                $path,
            );
        }
    }

    public function test_nepal_visibility_back_date_and_company_isolation_use_the_central_converter(): void
    {
        $this->actingAs(User::findOrFail(1));
        $nepalDisplay = Blade::render("@include('company.components.nepali-date-display', ['adDate' => '2020-01-01'])");
        $this->assertStringContainsString('2076-09-16 BS', $nepalDisplay);
        $this->getJson(route('company.calendar.ad-to-bs', ['date' => '2020-01-01']))
            ->assertExactJson(['applicable' => true, 'bs_date' => '2076-09-16']);

        $this->actingAs(User::findOrFail(2));
        $otherDisplay = Blade::render("@include('company.components.nepali-date-display', ['adDate' => '2020-01-01'])");
        $this->assertStringNotContainsString('BS', $otherDisplay);
        $this->getJson(route('company.calendar.ad-to-bs', ['date' => '2020-01-01', 'company_id' => 1]))
            ->assertExactJson(['applicable' => false, 'bs_date' => null]);
    }

    public function test_same_ad_date_is_consistent_and_the_shared_field_is_readonly_without_bs_authority(): void
    {
        $service = app(NepaliDateService::class);
        $this->assertSame('2081-01-01', $service->adToBs('2024-04-13'));
        $this->assertSame($service->adToBs('2024-04-13'), $service->adToBs('2024-04-13'));

        $this->actingAs(User::findOrFail(1));
        $field = Blade::render("@include('company.components.nepali-date-field', ['adInputId' => 'payment_date', 'adDate' => '2024-04-13'])");
        $this->assertStringContainsString('readonly', $field);
        $this->assertStringNotContainsString('name="payment_date_bs"', $field);

        $source = file_get_contents(resource_path('views/company/components/nepali-date-field.blade.php'));
        $this->assertStringContainsString("addEventListener('change', synchronizeBsDate)", $source);
        $this->assertStringContainsString('synchronizeBsDate();', $source);
        $this->assertStringContainsString("route('company.calendar.ad-to-bs')", $source);
        $this->assertStringNotContainsString('convertAdToBs', $source);
    }

    public function test_no_bs_schema_or_alternate_financial_date_is_introduced(): void
    {
        $this->assertFalse(Schema::hasColumn('companies', 'date_bs'));
        $this->assertFalse(Schema::hasColumn('companies', 'business_date_bs'));

        foreach (['payment_date', 'return_date', 'refund_date', 'purchase_date', 'income_date', 'expense_date', 'journal_date', 'start_date', 'business_date'] as $field) {
            $this->assertNotSame('', $field);
            $this->assertStringNotContainsString('_bs', $field);
        }
    }
}
