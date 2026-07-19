<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_return_refunds', function (Blueprint $table) {

            // Amounts
            $table->decimal('adjust_amount', 18, 2)
                ->default(0)
                ->after('refund_amount');

            $table->decimal('cash_amount', 18, 2)
                ->default(0)
                ->after('adjust_amount');

            // Payment Information
            $table->string('payment_method')
                ->nullable()
                ->after('cash_amount');

            $table->string('reference_no')
                ->nullable()
                ->after('payment_method');

            // Attachment
            $table->string('attachment')
                ->nullable()
                ->after('reference_no');

            // Audit
            $table->unsignedBigInteger('updated_by')
                ->nullable()
                ->after('created_by');

            $table->unsignedBigInteger('deleted_by')
                ->nullable()
                ->after('updated_by');

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('sales_return_refunds', function (Blueprint $table) {

            $table->dropSoftDeletes();

            $table->dropColumn([
                'adjust_amount',
                'cash_amount',
                'payment_method',
                'reference_no',
                'attachment',
                'updated_by',
                'deleted_by',
            ]);
        });
    }
};