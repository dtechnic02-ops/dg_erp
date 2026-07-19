<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->date('due_date')
                ->nullable()
                ->after('sale_date');
        });

        DB::statement(
            'UPDATE sales_invoices si
             INNER JOIN customers c ON si.customer_id = c.id
             SET si.due_date = DATE_ADD(si.sale_date, INTERVAL COALESCE(c.credit_days, 0) DAY)'
        );
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
