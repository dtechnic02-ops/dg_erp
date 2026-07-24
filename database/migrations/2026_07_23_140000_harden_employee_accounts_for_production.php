<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_accounts', function (Blueprint $table) {
            $table->dropUnique(['employee_code']);
            $table->unique(
                ['company_id', 'employee_code'],
                'employee_accounts_company_code_unique'
            );

            if (!Schema::hasColumn('employee_accounts', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_accounts', function (Blueprint $table) {
            $table->dropUnique('employee_accounts_company_code_unique');
            $table->unique(['employee_code']);

            if (Schema::hasColumn('employee_accounts', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
        });
    }
};
