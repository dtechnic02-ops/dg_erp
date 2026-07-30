<?php

namespace App\Providers;

use App\Models\CompanySubscription;
use App\Models\FinancialYear;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('company.partials.header', function ($view): void {
            $companyId = auth()->user()?->company_id;

            if (! $companyId) {
                $view->with([
                    'headerSubscription' => null,
                    'headerFinancialYear' => null,
                ]);

                return;
            }

            $view->with([
                'headerSubscription' => CompanySubscription::query()
                    ->forCompany($companyId)
                    ->active()
                    ->with('plan:id,code')
                    ->latest('id')
                    ->first(),
                'headerFinancialYear' => FinancialYear::query()
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->latest('id')
                    ->first(),
            ]);
        });
    }
}
