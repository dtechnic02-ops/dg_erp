<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'invoice_payments',
            function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger(
                    'company_id'
                );

                $table->unsignedBigInteger(
                    'sales_invoice_id'
                );

                $table->unsignedBigInteger(
                    'customer_id'
                );

                $table->unsignedBigInteger(
                    'account_id'
                );

                $table->date(
                    'payment_date'
                );

                $table->decimal(
                    'amount',
                    15,
                    2
                );

                $table->text(
                    'note'
                )
                ->nullable();

                $table->unsignedBigInteger(
                    'created_by'
                )
                ->nullable();

                $table->timestamps();

            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'invoice_payments'
        );
    }
};