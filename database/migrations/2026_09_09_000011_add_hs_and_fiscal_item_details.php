<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('origin_type', 20)->nullable();
            $table->string('hs_code', 20)->nullable();
            $table->string('product_type', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('size', 100)->nullable();
        });

        Schema::table('sales_items', function (Blueprint $table): void {
            $table->string('fiscal_origin_type', 20)->nullable();
            $table->string('fiscal_hs_code', 20)->nullable();
            $table->string('fiscal_brand_name', 255)->nullable();
            $table->string('fiscal_product_type', 100)->nullable();
            $table->string('fiscal_model', 100)->nullable();
            $table->string('fiscal_size', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_items', function (Blueprint $table): void {
            $table->dropColumn([
                'fiscal_origin_type', 'fiscal_hs_code', 'fiscal_brand_name',
                'fiscal_product_type', 'fiscal_model', 'fiscal_size',
            ]);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['origin_type', 'hs_code', 'product_type', 'model', 'size']);
        });
    }
};
