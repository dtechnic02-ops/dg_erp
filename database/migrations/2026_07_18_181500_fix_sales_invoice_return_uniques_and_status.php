<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            UPDATE sales_invoices si
            INNER JOIN (
                SELECT sales_invoice_id, MIN(financial_year_id) AS fy_id
                FROM sales_payments
                WHERE financial_year_id IS NOT NULL
                GROUP BY sales_invoice_id
            ) sp ON sp.sales_invoice_id = si.id
            SET si.financial_year_id = sp.fy_id
            WHERE si.financial_year_id IS NULL
        ');

        DB::statement('
            UPDATE sales_returns sr
            INNER JOIN sales_invoices si ON si.id = sr.sales_invoice_id
            SET sr.financial_year_id = si.financial_year_id
            WHERE sr.financial_year_id IS NULL
              AND si.financial_year_id IS NOT NULL
        ');

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'financial_year_id', 'invoice_no'],
                'sales_invoices_company_fy_invoice_no_unique'
            );
        });

        Schema::table('sales_returns', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'financial_year_id', 'return_no'],
                'sales_returns_company_fy_return_no_unique'
            );
        });

        DB::statement("
            UPDATE sales_returns
            SET status = CASE
                WHEN LOWER(TRIM(status)) IN ('0', 'cancelled', 'canceled', 'cancel') THEN '0'
                ELSE '1'
            END
        ");

        Schema::table('sales_returns', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_returns', function (Blueprint $table) {
            $table->string('status')->default('returned')->change();
        });

        DB::statement("
            UPDATE sales_returns
            SET status = CASE
                WHEN status = '0' THEN '0'
                ELSE 'returned'
            END
        ");

        Schema::table('sales_returns', function (Blueprint $table) {
            $table->dropUnique('sales_returns_company_fy_return_no_unique');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropUnique('sales_invoices_company_fy_invoice_no_unique');
        });
    }
};
