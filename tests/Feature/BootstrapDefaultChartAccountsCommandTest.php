<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Services\DefaultChartAccountBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BootstrapDefaultChartAccountsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_backfills_missing_accounts_and_is_safe_to_rerun(): void
    {
        $company = Company::query()->create([
            'company_name' => 'Backfill Company',
            'email' => 'backfill@example.test',
            'mobile' => '9800000099',
            'status' => 'active',
        ]);

        $this->artisan('companies:bootstrap-default-chart-accounts')
            ->expectsOutputToContain('Processed 1 companies')
            ->assertSuccessful();

        $requiredCodes = app(DefaultChartAccountBootstrapService::class)->requiredSystemCodes();
        $accounts = ChartAccount::query()->where('company_id', $company->id)->orderBy('id')->get();
        $this->assertEqualsCanonicalizing($requiredCodes, $accounts->pluck('system_code')->all());
        $this->assertContains('INVENTORY', $accounts->pluck('system_code')->all());

        ChartAccount::query()
            ->where('company_id', $company->id)
            ->where('system_code', 'INVENTORY')
            ->update(['name' => 'Existing Inventory Configuration', 'status' => 'inactive']);
        $snapshot = ChartAccount::query()->where('company_id', $company->id)->orderBy('id')->get()->map->getAttributes()->all();
        $this->artisan('companies:bootstrap-default-chart-accounts')
            ->expectsOutputToContain('created 0 missing default chart accounts')
            ->assertSuccessful();

        $this->assertSame($snapshot, ChartAccount::query()->where('company_id', $company->id)->orderBy('id')->get()->map->getAttributes()->all());
        $this->assertDatabaseHas('chart_accounts', [
            'company_id' => $company->id,
            'system_code' => 'INVENTORY',
            'name' => 'Existing Inventory Configuration',
            'status' => 'inactive',
        ]);
    }
}
