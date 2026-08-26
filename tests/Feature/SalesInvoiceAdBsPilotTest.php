<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesInvoiceAdBsPilotTest extends TestCase
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

        $nepalId = DB::table('countries')->insertGetId([
            'name' => 'Nepal',
            'iso_code' => 'NP',
            'is_active' => true,
        ]);
        $otherId = DB::table('countries')->insertGetId([
            'name' => 'India',
            'iso_code' => 'IN',
            'is_active' => true,
        ]);

        DB::table('companies')->insert([
            ['id' => 1, 'company_name' => 'Nepal Company', 'country_id' => $nepalId],
            ['id' => 2, 'company_name' => 'Other Company', 'country_id' => $otherId],
        ]);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Nepal User', 'email' => 'np@example.test', 'password' => Hash::make('secret'), 'company_id' => 1],
            ['id' => 2, 'name' => 'Other User', 'email' => 'in@example.test', 'password' => Hash::make('secret'), 'company_id' => 2],
        ]);

        $this->withoutMiddleware();
    }

    public function test_nepal_company_receives_the_derived_bs_date_from_the_central_endpoint(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->getJson(route('company.calendar.ad-to-bs', ['date' => '2024-04-13']))
            ->assertOk()
            ->assertExactJson(['applicable' => true, 'bs_date' => '2081-01-01']);
    }

    public function test_non_nepal_company_receives_no_nepal_bs_behavior(): void
    {
        $this->actingAs(User::findOrFail(2))
            ->getJson(route('company.calendar.ad-to-bs', ['date' => '2024-04-13']))
            ->assertOk()
            ->assertExactJson(['applicable' => false, 'bs_date' => null]);
    }

    public function test_endpoint_uses_only_the_authenticated_company_country(): void
    {
        $this->actingAs(User::findOrFail(2))
            ->getJson(route('company.calendar.ad-to-bs', ['date' => '2024-04-13', 'company_id' => 1]))
            ->assertOk()
            ->assertExactJson(['applicable' => false, 'bs_date' => null]);

        $this->assertSame('NP', Company::findOrFail(1)->countryMaster->iso_code);
        $this->assertSame('IN', Company::findOrFail(2)->countryMaster->iso_code);
    }

    public function test_invalid_and_unsupported_ad_dates_fail_safely(): void
    {
        $this->actingAs(User::findOrFail(1));

        $this->getJson(route('company.calendar.ad-to-bs', ['date' => '2024-02-30']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');
        $this->getJson(route('company.calendar.ad-to-bs', ['date' => '2033-04-14']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    public function test_sales_bs_field_is_nepal_only_readonly_and_has_no_submittable_name(): void
    {
        $nepal = Blade::render("@include('company.components.nepali-date-field')", [
            'isNepalCompany' => true,
            'saleDateBs' => '2081-01-01',
        ]);
        $other = Blade::render("@include('company.components.nepali-date-field')", [
            'isNepalCompany' => false,
            'saleDateBs' => null,
        ]);

        $this->assertStringContainsString('id="sale_date_bs"', $nepal);
        $this->assertStringContainsString('readonly', $nepal);
        $this->assertStringNotContainsString('name="sale_date_bs"', $nepal);
        $this->assertStringNotContainsString('sale_date_bs', $other);
    }

    public function test_live_sync_calls_the_central_endpoint_without_a_javascript_conversion_algorithm(): void
    {
        $component = file_get_contents(resource_path('views/company/components/nepali-date-field.blade.php'));
        $javascript = file_get_contents(public_path('assets/company/js/dg.js'));

        $this->assertStringContainsString("route('company.calendar.ad-to-bs')", $component);
        $this->assertStringContainsString("addEventListener('change', synchronize)", $javascript);
        $this->assertStringContainsString('fetch(url', $javascript);
        $this->assertStringNotContainsString('convertAdToBs', $component.$javascript);
        $this->assertStringNotContainsString('NepaliDate', $component.$javascript);
    }

    public function test_create_edit_show_and_print_use_derived_bs_without_changing_sales_persistence(): void
    {
        $create = file_get_contents(resource_path('views/company/sales/create.blade.php'));
        $edit = file_get_contents(resource_path('views/company/sales/edit.blade.php'));
        $show = file_get_contents(resource_path('views/company/sales/show.blade.php'));
        $print = file_get_contents(resource_path('views/company/sales/print.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Company/SalesController.php'));

        $this->assertStringContainsString('name="sale_date"', $create);
        $this->assertStringContainsString('name="sale_date"', $edit);
        $this->assertStringContainsString("@include('company.components.nepali-date-field')", $create);
        $this->assertStringContainsString("@include('company.components.nepali-date-field')", $edit);
        $this->assertStringContainsString('@if ($saleDateBs !== null)', $show);
        $this->assertStringContainsString('@if ($saleDateBs !== null)', $print);
        $this->assertStringContainsString("'sale_date' =>", $controller);
        $this->assertStringNotContainsString("'sale_date_bs' =>", $controller);
        $this->assertFalse(Schema::hasColumn('companies', 'sale_date_bs'));
    }
}
