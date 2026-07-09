<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('sales_payments', function (Blueprint $table) {

        $table->foreignId('financial_year_id')
            ->nullable()
            ->after('company_id')
            ->constrained('financial_years')
            ->cascadeOnDelete();

    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('sales_payments', function (Blueprint $table) {

        $table->dropForeign(
            ['financial_year_id']
        );

        $table->dropColumn(
            'financial_year_id'
        );

    });
}
};
