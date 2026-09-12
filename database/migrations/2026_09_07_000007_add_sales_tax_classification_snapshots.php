<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_items', function (Blueprint $table): void {
            $table->string('tax_classification', 40)
                ->default('legacy_unclassified')
                ->after('vat_amount');
            $table->index(['company_id', 'tax_classification'], 'sales_items_company_tax_class_index');
        });

        Schema::table('sales_return_items', function (Blueprint $table): void {
            $table->string('tax_classification', 40)
                ->default('legacy_unclassified')
                ->after('vat_amount');
            $table->index(['company_id', 'tax_classification'], 'sales_return_items_company_tax_class_index');
        });

        DB::table('sales_items')->where('vat_rate', '>', 0)->update([
            'tax_classification' => 'vat_taxable',
        ]);
        DB::table('sales_return_items')->where('vat_rate', '>', 0)->update([
            'tax_classification' => 'vat_taxable',
        ]);
    }

    public function down(): void
    {
        Schema::table('sales_return_items', function (Blueprint $table): void {
            $table->dropIndex('sales_return_items_company_tax_class_index');
            $table->dropColumn('tax_classification');
        });
        Schema::table('sales_items', function (Blueprint $table): void {
            $table->dropIndex('sales_items_company_tax_class_index');
            $table->dropColumn('tax_classification');
        });
    }
};
