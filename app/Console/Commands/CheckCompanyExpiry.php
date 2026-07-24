<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class CheckCompanyExpiry extends Command
{
    protected $signature = 'companies:check-expiry';
    protected $description = 'Expire companies whose subscriptions have ended';

    public function __construct(private SubscriptionService $subscriptionService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        Company::query()
            ->where('status', '!=', 'blocked')
            ->whereHas('subscriptions', function ($q) {
                $q->where('status', 'active')
                    ->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<', now()->toDateString());
            })
            ->each(function (Company $company) {
                $this->subscriptionService->expireSubscription($company);
            });

        $this->info('Expired subscriptions processed successfully.');

        return self::SUCCESS;
    }
}
