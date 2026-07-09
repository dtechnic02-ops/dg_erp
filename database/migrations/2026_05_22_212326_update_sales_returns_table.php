<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'sales_returns',
            function (Blueprint $table) {

            /**
             * 🔥 CUSTOMER
             */

            $table->unsignedBigInteger(
                'customer_id'
            )->nullable()
             ->after(
                'sales_invoice_id'
             );

            /**
             * 🔥 TOTALS
             */

            $table->decimal(
                'subtotal',
                18,
                2
            )->default(0)
             ->after('return_date');

            $table->decimal(
                'total_vat',
                18,
                2
            )->default(0)
             ->after('subtotal');

            /**
             * 🔥 RENAME
             */

            $table->renameColumn(
                'total_amount',
                'grand_total'
            );

            /**
             * 🔥 STATUS
             */

            $table->string(
                'status'
            )->default('returned')
             ->after('created_by');

        });
    }

    public function down(): void
    {
        Schema::table(
            'sales_returns',
            function (Blueprint $table) {

            $table->dropColumn([
                'customer_id',
                'subtotal',
                'total_vat',
                'status'
            ]);

            $table->renameColumn(
                'grand_total',
                'total_amount'
            );

        });
    }
};