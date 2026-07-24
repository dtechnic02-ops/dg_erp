<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Subscription Expiry Scheduler
|--------------------------------------------------------------------------
|
| Runs daily to expire subscriptions whose expiry_date has passed.
| Updates company_subscriptions.status, companies.status, and history.
|
| Production deployment — add ONE cron entry on the server:
|   * * * * * cd /path/to/dg_erp && php artisan schedule:run >> /dev/null 2>&1
|
| Verify locally:
|   php artisan schedule:list
|   php artisan companies:check-expiry
|
*/
Schedule::command('companies:check-expiry')->dailyAt('00:05');
