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
            'sales_return_refunds',
            function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger(
                    'company_id'
                );

                $table->unsignedBigInteger(
                    'sales_return_id'
                );

                $table->unsignedBigInteger(
                    'customer_id'
                );

                $table->unsignedBigInteger(
                    'account_id'
                );

                $table->string(
                    'refund_no'
                )->unique();

                $table->date(
                    'refund_date'
                );

                $table->decimal(
                    'refund_amount',
                    15,
                    2
                )->default(0);

                $table->text(
                    'note'
                )->nullable();

                $table->unsignedBigInteger(
                    'created_by'
                );

                $table->tinyInteger(
                    'status'
                )->default(1);

                $table->timestamps();

                /**
                 * FOREIGN KEYS
                 */

                $table->foreign(
                    'sales_return_id'
                )
                ->references('id')
                ->on('sales_returns')
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
            'sales_return_refunds'
        );
    }
};