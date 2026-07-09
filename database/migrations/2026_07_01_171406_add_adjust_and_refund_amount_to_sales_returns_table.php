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
        Schema::table('sales_returns', function (Blueprint $table) {
            $table->decimal(
    'refund_amount',
    18,
    2
)->default(0)->after(
    'grand_total'
);

$table->decimal(
    'adjust_amount',
    18,
    2
)->default(0)->after(
    'refund_amount'
);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_returns', function (Blueprint $table) {
            $table->dropColumn([

    'refund_amount',

    'adjust_amount'

]);
        });
    }
};
