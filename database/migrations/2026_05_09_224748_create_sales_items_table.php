<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('created_by');

            $table->foreignId('company_id');

            $table->foreignId('sales_invoice_id');

            $table->foreignId('product_id');

            $table->decimal('quantity', 15, 2);

            $table->decimal('unit_price', 15, 2);

            $table->decimal('vat_rate', 8, 2)->default(0);

            $table->decimal('vat_amount', 15, 2)->default(0);

            $table->decimal('total_price', 15, 2);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_items');
    }
};