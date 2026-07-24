<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('crm_lead_id')->nullable()->after('company_id');
            $table->index(['company_id', 'crm_lead_id'], 'customers_company_crm_lead_index');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_company_crm_lead_index');
            $table->dropColumn('crm_lead_id');
        });
    }
};
