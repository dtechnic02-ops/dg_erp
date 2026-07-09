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
      Schema::create('account_transactions', function (Blueprint $table) {

    $table->id();

    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('financial_year_id');

    $table->unsignedBigInteger('account_id');

    $table->date('transaction_date');

    $table->string('voucher_no')->nullable();

    /*
    EmployeePayment
    PurchasePayment
    Expense
    Income
    SalesInvoice
    LoanPayment
    etc.
    */
    $table->string('reference_type')->nullable();

    /*
    Module ID
    */
    $table->unsignedBigInteger('reference_id')->nullable();

    $table->text('description')->nullable();

    /*
    Debit Increase
    */
    $table->decimal(
        'debit',
        18,
        2
    )->default(0);

    /*
    Credit Increase
    */
    $table->decimal(
        'credit',
        18,
        2
    )->default(0);

    /*
    Running Balance
    */
    $table->decimal(
        'balance',
        18,
        2
    )->default(0);

    $table->unsignedBigInteger('created_by')->nullable();

    $table->tinyInteger('status')->default(1);

    $table->timestamps();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
