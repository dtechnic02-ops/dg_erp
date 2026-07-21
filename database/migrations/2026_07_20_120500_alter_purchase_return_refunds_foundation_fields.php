<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_return_refunds', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')
                ->nullable()
                ->after('purchase_return_id');

            $table->decimal('adjust_amount', 18, 2)
                ->default(0)
                ->after('amount');

            $table->decimal('cash_amount', 18, 2)
                ->default(0)
                ->after('adjust_amount');

            if (!Schema::hasColumn('purchase_return_refunds', 'reference_no')) {
                $table->string('reference_no')
                    ->nullable()
                    ->after('payment_method');
            }

            $table->unsignedBigInteger('updated_by')
                ->nullable()
                ->after('created_by');

            $table->unsignedBigInteger('deleted_by')
                ->nullable()
                ->after('updated_by');

            $table->softDeletes();
        });

        Schema::table('purchase_return_refunds', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_return_refunds', function (Blueprint $table) {
            $table->dropSoftDeletes();

            $table->dropColumn([
                'supplier_id',
                'adjust_amount',
                'cash_amount',
                'updated_by',
                'deleted_by',
            ]);

            if (Schema::hasColumn('purchase_return_refunds', 'reference_no')) {
                $table->dropColumn('reference_no');
            }
        });

        Schema::table('purchase_return_refunds', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')
                ->nullable(false)
                ->change();
        });
    }
};
