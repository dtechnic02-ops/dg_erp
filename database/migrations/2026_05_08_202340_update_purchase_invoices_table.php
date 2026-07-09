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
        Schema::table('purchase_invoices', function (Blueprint $table) {

            $table->renameColumn('date', 'purchase_date');

            $table->decimal('subtotal', 15, 2)
                ->default(0)
                ->after('purchase_date');

            $table->decimal('discount', 15, 2)
                ->default(0)
                ->after('subtotal');

            $table->decimal('total_vat', 15, 2)
                ->default(0)
                ->after('discount');

            $table->decimal('grand_total', 15, 2)
                ->default(0)
                ->after('total_vat');

            $table->decimal('paid_amount', 15, 2)
                ->default(0)
                ->after('grand_total');

            $table->decimal('due_amount', 15, 2)
                ->default(0)
                ->after('paid_amount');

            $table->string('payment_status')
                ->default('unpaid')
                ->after('due_amount');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {

            $table->renameColumn('purchase_date', 'date');

            $table->dropColumn([
                'subtotal',
                'discount',
                'total_vat',
                'grand_total',
                'paid_amount',
                'due_amount',
                'payment_status'
            ]);

        });
    }
};