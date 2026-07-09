<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_items', function (Blueprint $table) {

            $table->decimal(
                'returned_qty',
                15,
                2
            )
            ->default(0)
            ->after('quantity');

        });
    }

    public function down(): void
    {
        Schema::table('sales_items', function (Blueprint $table) {

            $table->dropColumn(
                'returned_qty'
            );

        });
    }
};