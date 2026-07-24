<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_accounts', function (Blueprint $table) {
            $table->foreignId('financial_year_id')->nullable()->after('company_id');
            $table->foreignId('updated_by')->nullable()->after('created_by');
            $table->foreignId('deleted_by')->nullable()->after('updated_by');
            $table->softDeletes();
        });

        Schema::table('loan_accounts', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('financial_year_id');
            $table->index('status');
            $table->index('party_account_id');
            $table->index('account_id');
            $table->unique(['company_id', 'loan_no']);
        });

        Schema::table('loan_accounts', function (Blueprint $table) {
            $table->foreign('financial_year_id')
                ->references('id')
                ->on('financial_years');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users');

            $table->foreign('deleted_by')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('loan_accounts', function (Blueprint $table) {
            $table->dropForeign(['financial_year_id']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropUnique(['company_id', 'loan_no']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['financial_year_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['party_account_id']);
            $table->dropIndex(['account_id']);
            $table->dropSoftDeletes();
            $table->dropColumn(['financial_year_id', 'updated_by', 'deleted_by']);
        });
    }
};
