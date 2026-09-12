<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_items', function (Blueprint $table): void {
            $table->decimal('fiscal_discount_amount', 15, 2)->nullable();
            $table->decimal('fiscal_net_base', 15, 2)->nullable();
        });

        Schema::table('sales_return_items', function (Blueprint $table): void {
            $table->decimal('fiscal_discount_amount', 12, 2)->nullable();
            $table->decimal('fiscal_net_base', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_return_items', function (Blueprint $table): void {
            $table->dropColumn(['fiscal_discount_amount', 'fiscal_net_base']);
        });

        Schema::table('sales_items', function (Blueprint $table): void {
            $table->dropColumn(['fiscal_discount_amount', 'fiscal_net_base']);
        });
    }
};
