<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use Carbon\Carbon;

class CheckCompanyExpiry extends Command
{
    protected $signature = 'companies:check-expiry';
    protected $description = 'Check and block expired companies';

    public function handle()
    {
        $today = Carbon::today();

       $companies = Company::whereNotNull('expiry_date')
    ->whereDate('expiry_date', '<', now()) // 🔥 FIX
    ->where('status', '!=', 'blocked')
    ->get();

        foreach ($companies as $company) {
            $company->status = 'blocked';
            $company->save();
        }

        $this->info('Expired companies blocked successfully');
    }
}