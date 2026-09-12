<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->timestamp('fiscal_snapshot_captured_at')->nullable();
            $table->string('seller_name_snapshot')->nullable();
            $table->text('seller_address_snapshot')->nullable();
            $table->string('seller_pan_snapshot')->nullable();
            $table->string('seller_vat_snapshot')->nullable();
            $table->string('buyer_name_snapshot')->nullable();
            $table->text('buyer_address_snapshot')->nullable();
            $table->string('buyer_tax_no_snapshot')->nullable();
            $table->index(['company_id', 'buyer_tax_no_snapshot'], 'sales_invoices_company_buyer_tax_snapshot_index');
        });

        Schema::table('sales_items', function (Blueprint $table): void {
            $table->string('item_name_snapshot')->nullable();
            $table->string('unit_name_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_items', function (Blueprint $table): void {
            $table->dropColumn(['item_name_snapshot', 'unit_name_snapshot']);
        });
        Schema::table('sales_invoices', function (Blueprint $table): void {
            $table->dropIndex('sales_invoices_company_buyer_tax_snapshot_index');
            $table->dropColumn([
                'fiscal_snapshot_captured_at', 'seller_name_snapshot', 'seller_address_snapshot',
                'seller_pan_snapshot', 'seller_vat_snapshot', 'buyer_name_snapshot',
                'buyer_address_snapshot', 'buyer_tax_no_snapshot',
            ]);
        });
    }
};
