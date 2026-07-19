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
        Schema::table('sales_payments', function (Blueprint $table) {

            $table->string('reference_no', 100)
                ->nullable()
                ->after('payment_method');

            $table->string('receipt_file')
                ->nullable()
                ->after('reference_no');

            $table->unsignedBigInteger('updated_by')
                ->nullable()
                ->after('created_by');

            $table->unsignedBigInteger('deleted_by')
                ->nullable()
                ->after('updated_by');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_payments', function (Blueprint $table) {

            $table->dropColumn([
                'reference_no',
                'receipt_file',
                'updated_by',
                'deleted_by',
            ]);

        });
    }
};