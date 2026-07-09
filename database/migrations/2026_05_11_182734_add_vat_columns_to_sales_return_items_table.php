<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_return_items', function (Blueprint $table) {

            $table->decimal(
                'vat_rate',
                5,
                2
            )->default(0);

            $table->decimal(
                'vat_amount',
                12,
                2
            )->default(0);

        });
    }

    public function down(): void
    {
        Schema::table('sales_return_items', function (Blueprint $table) {

            $table->dropColumn([
                'vat_rate',
                'vat_amount'
            ]);

        });
    }
};