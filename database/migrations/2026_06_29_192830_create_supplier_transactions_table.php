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
        Schema::create('supplier_transactions', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('company_id');

            $table->unsignedBigInteger('financial_year_id');

            $table->unsignedBigInteger('supplier_id');

            $table->date('transaction_date');

            $table->string('voucher_no')->nullable();

            $table->string('reference_type',50);

            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('description')->nullable();

            $table->decimal(
                'debit',
                18,
                2
            )->default(0);

            $table->decimal(
                'credit',
                18,
                2
            )->default(0);

            $table->decimal(
                'balance',
                18,
                2
            )->default(0);

            $table->string(
'remarks'
)->nullable();

$table->string(
    'reference_no'
)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->boolean('status')->default(1);

            $table->timestamps();

            /*
            INDEX
            */

            $table->index([
                'company_id',
                'supplier_id'
            ]);

            $table->index([
                'financial_year_id'
            ]);

            $table->index([
                'transaction_date'
            ]);

            $table->index([
                'reference_type',
                'reference_id'
            ]);

            $table->index([
                'voucher_no'
            ]);
            

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'supplier_transactions'
        );
    }
};