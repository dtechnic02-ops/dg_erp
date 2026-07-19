<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE sales_items si
            SET returned_qty = COALESCE((
                SELECT SUM(sri.quantity)
                FROM sales_return_items sri
                INNER JOIN sales_returns sr ON sr.id = sri.sales_return_id
                WHERE sri.sales_item_id = si.id
                  AND sri.status = 1
                  AND sr.status = 1
            ), 0)
        ");

        DB::statement('
            UPDATE sales_payments sp
            INNER JOIN sales_invoices si ON si.id = sp.sales_invoice_id
            SET sp.financial_year_id = si.financial_year_id
            WHERE sp.financial_year_id IS NULL
              AND si.financial_year_id IS NOT NULL
        ');

        DB::statement('
            UPDATE sales_return_refunds srr
            INNER JOIN sales_returns sr ON sr.id = srr.sales_return_id
            SET srr.financial_year_id = sr.financial_year_id
            WHERE srr.financial_year_id IS NULL
              AND sr.financial_year_id IS NOT NULL
        ');

        Schema::table('sales_payments', function (Blueprint $table) {
            $table->dropUnique(['payment_no']);
        });

        Schema::table('sales_payments', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'financial_year_id', 'payment_no'],
                'sales_payments_company_fy_payment_no_unique'
            );
        });

        Schema::table('sales_return_refunds', function (Blueprint $table) {
            $table->dropUnique(['refund_no']);
        });

        Schema::table('sales_return_refunds', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'financial_year_id', 'refund_no'],
                'sales_return_refunds_company_fy_refund_no_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sales_payments', function (Blueprint $table) {
            $table->dropUnique('sales_payments_company_fy_payment_no_unique');
        });

        Schema::table('sales_payments', function (Blueprint $table) {
            $table->unique('payment_no');
        });

        Schema::table('sales_return_refunds', function (Blueprint $table) {
            $table->dropUnique('sales_return_refunds_company_fy_refund_no_unique');
        });

        Schema::table('sales_return_refunds', function (Blueprint $table) {
            $table->unique('refund_no');
        });
    }
};
