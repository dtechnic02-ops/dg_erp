<?php

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('account_group', 20)->nullable()->after('company_id');
        });

        DB::table('accounts')
            ->whereNull('account_group')
            ->whereIn('account_type', ['Cash', 'Bank', 'ATM', 'Wallet'])
            ->update(['account_group' => Account::GROUP_ASSET]);
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('account_group');
        });
    }
};
