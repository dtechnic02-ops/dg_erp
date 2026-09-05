<?php

namespace App\Console\Commands;

use App\Models\ChartAccount;
use App\Models\Company;
use App\Services\DefaultChartAccountBootstrapService;
use Illuminate\Console\Command;

class BootstrapDefaultChartAccounts extends Command
{
    protected $signature = 'companies:bootstrap-default-chart-accounts
                            {--company= : Bootstrap only the specified company ID}';

    protected $description = 'Create missing default system chart accounts without changing existing accounts or transactions';

    public function __construct(private DefaultChartAccountBootstrapService $bootstrap)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $companyId = $this->option('company');
        if ($companyId !== null && (! ctype_digit((string) $companyId) || (int) $companyId < 1)) {
            $this->error('The --company option must be a positive company ID.');

            return self::FAILURE;
        }

        $query = Company::query()->select('id', 'company_name')->orderBy('id');
        if ($companyId !== null) {
            $query->whereKey((int) $companyId);
        }

        $processed = 0;
        $created = 0;
        $unresolved = [];
        $requiredCodes = $this->bootstrap->requiredSystemCodes();

        $query->chunkById(100, function ($companies) use (&$processed, &$created, &$unresolved, $requiredCodes): void {
            foreach ($companies as $company) {
                $before = ChartAccount::query()
                    ->forCompany($company->id)
                    ->where('is_system', true)
                    ->whereIn('system_code', $requiredCodes)
                    ->count();

                $this->bootstrap->seedForCompany($company->id);

                $presentCodes = ChartAccount::query()
                    ->forCompany($company->id)
                    ->where('is_system', true)
                    ->whereIn('system_code', $requiredCodes)
                    ->pluck('system_code')
                    ->all();
                $missing = array_values(array_diff($requiredCodes, $presentCodes));

                $processed++;
                $created += count($presentCodes) - $before;
                if ($missing !== []) {
                    $unresolved[] = $company->id.': '.implode(', ', $missing);
                }
            }
        });

        if ($companyId !== null && $processed === 0) {
            $this->error("Company [{$companyId}] was not found.");

            return self::FAILURE;
        }

        $this->info("Processed {$processed} companies; created {$created} missing default chart accounts.");
        foreach ($unresolved as $message) {
            $this->warn('Unresolved company '.$message);
        }

        return $unresolved === [] ? self::SUCCESS : self::FAILURE;
    }
}
