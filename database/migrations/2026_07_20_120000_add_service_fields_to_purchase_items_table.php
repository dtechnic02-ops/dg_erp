<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->enum('item_type', [
                'product',
                'service',
            ])
                ->default('product')
                ->after('purchase_invoice_id');

            $table->unsignedBigInteger('service_id')
                ->nullable()
                ->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn([
                'item_type',
                'service_id',
            ]);
        });
    }
};
