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
            'employee_payments',
            function (Blueprint $table) {

                $table->id();

                /*
                Company
                */
                $table->unsignedBigInteger(
                    'company_id'
                );

                /*
                Financial Year
                */
                $table->unsignedBigInteger(
                    'financial_year_id'
                );

                /*
                Employee
                */
                $table->unsignedBigInteger(
                    'employee_account_id'
                );

                /*
                Voucher No
                Example:
                EP-1-2026-0001
                */
                $table->string(
                    'voucher_no'
                )->unique();

                /*
                Payment Date
                */
                $table->date(
                    'payment_date'
                );

                /*
                Salary Period
                */
                $table->integer(
                    'salary_year'
                );

                $table->tinyInteger(
                    'salary_month'
                );

                /*
                Payment Account
                */
                $table->unsignedBigInteger(
                    'account_id'
                );

                /*
                Amount
                */
                $table->decimal(
                    'amount',
                    18,
                    2
                )->default(0);

                /*
                Attachment
                */
                $table->string(
                    'attachment'
                )->nullable();

                /*
                Note
                */
                $table->text(
                    'note'
                )->nullable();

                /*
                Created By
                */
                $table->unsignedBigInteger(
                    'created_by'
                );

                /*
                Status
                */
                $table->tinyInteger(
                    'status'
                )->default(1);

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
            'employee_payments'
        );
    }
};

