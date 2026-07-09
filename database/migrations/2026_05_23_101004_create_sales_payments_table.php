<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RUN MIGRATIONS
     */

    public function up(): void
    {
        Schema::create(
            'sales_payments',
            function (Blueprint $table) {

                $table->id();

                /**
                 * COMPANY
                 */

                $table->unsignedBigInteger(
                    'company_id'
                );

                /**
                 * SALES INVOICE
                 */

                $table->unsignedBigInteger(
                    'sales_invoice_id'
                );

                /**
                 * CUSTOMER
                 */

                $table->unsignedBigInteger(
                    'customer_id'
                );

                /**
                 * ACCOUNT
                 */

                $table->unsignedBigInteger(
                    'account_id'
                );

                /**
                 * PAYMENT INFO
                 */

                $table->string(
                    'payment_no'
                )->unique();

                $table->date(
                    'payment_date'
                );

                $table->decimal(
                    'paid_amount',
                    15,
                    2
                )->default(0);

                /**
                 * PAYMENT METHOD
                 */

                $table->string(
                    'payment_method'
                )
                ->nullable();

                /**
                 * NOTE
                 */

                $table->text(
                    'note'
                )
                ->nullable();

                /**
                 * CREATED BY
                 */

                $table->unsignedBigInteger(
                    'created_by'
                );

                /**
                 * STATUS
                 */

                $table->tinyInteger(
                    'status'
                )->default(1);

                $table->timestamps();

                /**
                 * FOREIGN KEYS
                 */

                $table->foreign(
                    'sales_invoice_id'
                )
                ->references('id')
                ->on('sales_invoices')
                ->onDelete('cascade');

                $table->foreign(
                    'customer_id'
                )
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');

                $table->foreign(
                    'account_id'
                )
                ->references('id')
                ->on('accounts')
                ->onDelete('cascade');

            }
        );
    }

    /**
     * REVERSE MIGRATIONS
     */

    public function down(): void
    {
        Schema::dropIfExists(
            'sales_payments'
        );
    }
};