<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('sub_ledger_type', 20)->nullable()->after('account_type');
        });

        Schema::table('journal_items', function (Blueprint $table) {
            $table->string('sub_ledger_type', 20)->nullable()->after('account_id');
            $table->unsignedBigInteger('sub_ledger_id')->nullable()->after('sub_ledger_type');
        });
    }

    public function down(): void
    {
        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropColumn(['sub_ledger_type', 'sub_ledger_id']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('sub_ledger_type');
        });
    }
};
