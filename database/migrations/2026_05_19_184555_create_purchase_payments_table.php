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
        Schema::create(
            'purchase_payments',
            function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger(
                    'company_id'
                );

                $table->unsignedBigInteger(
                    'purchase_invoice_id'
                );

                $table->unsignedBigInteger(
                    'supplier_id'
                );

                // PAY-5-2026-0001

                $table->string(
                    'payment_no'
                );

                $table->date(
                    'payment_date'
                );

                $table->decimal(
                    'amount',
                    15,
                    2
                );

                $table->string(
                    'payment_method'
                )->nullable();

                $table->string(
                    'reference_no'
                )->nullable();

                // 🔥 RECEIPT FILE

                $table->string(
                    'receipt_file'
                )->nullable();

                $table->text(
                    'note'
                )->nullable();

                $table->unsignedBigInteger(
                    'created_by'
                );

                $table->timestamps();

            }
        );
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::dropIfExists(
            'purchase_payments'
        );
    }
};