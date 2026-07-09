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
        Schema::table('purchase_items', function (Blueprint $table) {

            $table->foreignId('vat_id')
                ->nullable()
                ->after('product_id')
                ->constrained('vats')
                ->nullOnDelete();

            $table->decimal('vat_rate', 5, 2)
                ->default(0)
                ->after('vat_id');

            $table->decimal('vat_amount', 15, 2)
                ->default(0)
                ->after('vat_rate');

            $table->decimal('unit_price', 15, 2)
                ->default(0)
                ->after('price');

            $table->decimal('total_price', 15, 2)
                ->default(0)
                ->after('unit_price');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {

            $table->dropForeign(['vat_id']);

            $table->dropColumn([
                'vat_id',
                'vat_rate',
                'vat_amount',
                'unit_price',
                'total_price'
            ]);

        });
    }
};